<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Genre;
use App\Models\Platform;
use App\Services\IGDBService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportIGDBGames implements ShouldQueue
{
    use Queueable;

    protected int $limit;
    protected ?int $sinceIgdbId;
    protected ?int $releasedSinceTimestamp;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param int $limit Number of games to fetch
     * @param int|null $sinceIgdbId Only fetch games with IGDB ID greater than this value
     * @param int|null $releasedSinceTimestamp Only fetch games released on or after this Unix timestamp
     */
    public function __construct(int $limit = 100, ?int $sinceIgdbId = null, ?int $releasedSinceTimestamp = null)
    {
        $this->limit = $limit;
        $this->sinceIgdbId = $sinceIgdbId;
        $this->releasedSinceTimestamp = $releasedSinceTimestamp;
    }

    /**
     * Execute the job.
     */
    public function handle(IGDBService $igdbService): void
    {
        Log::channel('jobs')->info("Starting IGDB import: limit={$this->limit}, sinceIgdbId={$this->sinceIgdbId}");

        // Fetch games from IGDB using ID-based cursor for incremental sync
        $igdbGames = $igdbService->fetchPlayStationGames($this->limit, $this->sinceIgdbId, $this->releasedSinceTimestamp);

        Log::channel('jobs')->info("Fetched " . count($igdbGames) . " games from IGDB");

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $maxIgdbId = $this->sinceIgdbId;

        foreach ($igdbGames as $igdbGame) {
            try {
                // Track the highest IGDB ID seen for auto-pagination
                if (isset($igdbGame['id']) && ($maxIgdbId === null || $igdbGame['id'] > $maxIgdbId)) {
                    $maxIgdbId = $igdbGame['id'];
                }

                // Skip if already imported (by IGDB ID or slug)
                if (isset($igdbGame['id']) && Game::where('igdb_id', $igdbGame['id'])->exists()) {
                    $skipped++;
                    continue;
                }

                $gameData = $igdbService->parseGameData($igdbGame);

                if (Game::where('slug', $gameData['slug'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Create the game with all fields
                $game = Game::create([
                    'igdb_id' => $gameData['igdb_id'],
                    'title' => $gameData['title'],
                    'slug' => $gameData['slug'],
                    'developer' => $gameData['developer'],
                    'publisher' => $gameData['publisher'],
                    'release_date' => $gameData['release_date'],
                    'cover_url' => $gameData['cover_url'],
                    'banner_url' => $gameData['banner_url'],
                    'critic_score' => $gameData['critic_score'],
                    'critic_score_count' => $gameData['critic_score_count'],
                    'user_score' => $gameData['user_score'],
                    'user_score_count' => $gameData['user_score_count'],
                ]);

                // Create/attach platforms dynamically
                $platformIds = [];
                foreach ($gameData['platforms_data'] as $platformData) {
                    $platform = Platform::firstOrCreate(
                        ['slug' => $platformData['slug']],
                        $platformData
                    );
                    $platformIds[] = $platform->id;
                }
                if (!empty($platformIds)) {
                    $game->platforms()->attach($platformIds);
                }

                // Create/attach genres dynamically
                $genreIds = [];
                foreach ($gameData['genre_names'] as $genreName) {
                    $genre = Genre::firstOrCreate(
                        ['name' => $genreName],
                        ['name' => $genreName, 'slug' => \Illuminate\Support\Str::slug($genreName)]
                    );
                    $genreIds[] = $genre->id;
                }
                if (!empty($genreIds)) {
                    $game->genres()->attach($genreIds);
                }

                $imported++;

                // Rate limiting: small delay between processing
                usleep(50000); // 50ms

            } catch (\Exception $e) {
                $errors++;
                Log::channel('jobs')->error("Failed to import game: " . ($igdbGame['name'] ?? 'Unknown') . " - " . $e->getMessage());
            }
        }

        Log::channel('jobs')->info("IGDB import complete: imported={$imported}, skipped={$skipped}, errors={$errors}");

        if ($imported > 0) {
            \App\Http\Controllers\GameController::bustGameCache();
        }

        // Auto-pagination: if batch was full, there may be more games to fetch
        if (count($igdbGames) >= $this->limit && $maxIgdbId !== null) {
            Log::channel('jobs')->info("Batch was full ({$this->limit}), dispatching next batch from igdb_id > {$maxIgdbId}");
            self::dispatch($this->limit, sinceIgdbId: $maxIgdbId, releasedSinceTimestamp: $this->releasedSinceTimestamp);
        }
    }
}
