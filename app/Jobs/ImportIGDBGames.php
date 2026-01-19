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
    protected int $offset;
    protected ?int $sinceTimestamp;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param int $limit Number of games to fetch
     * @param int $offset Offset for pagination
     * @param int|null $sinceTimestamp Only fetch games released on or after this Unix timestamp
     */
    public function __construct(int $limit = 100, int $offset = 0, ?int $sinceTimestamp = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sinceTimestamp = $sinceTimestamp;
    }

    /**
     * Execute the job.
     */
    public function handle(IGDBService $igdbService): void
    {
        $mode = $this->sinceTimestamp ? 'incremental sync' : 'bulk import';
        Log::info("Starting IGDB import ({$mode}): limit={$this->limit}, offset={$this->offset}");

        // Get existing IGDB IDs to skip
        $existingIgdbIds = Game::whereNotNull('igdb_id')->pluck('igdb_id')->toArray();

        // Fetch games from IGDB
        $igdbGames = $igdbService->fetchPlayStationGames($this->limit, $this->offset, $this->sinceTimestamp);

        Log::info("Fetched " . count($igdbGames) . " games from IGDB");

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($igdbGames as $igdbGame) {
            try {
                // Skip if already imported (by IGDB ID)
                if (isset($igdbGame['id']) && in_array($igdbGame['id'], $existingIgdbIds)) {
                    $skipped++;
                    continue;
                }

                $gameData = $igdbService->parseGameData($igdbGame);

                // Double-check by slug as well
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
                    'metacritic_score' => $gameData['metacritic_score'],
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
                Log::error("Failed to import game: " . ($igdbGame['name'] ?? 'Unknown') . " - " . $e->getMessage());
            }
        }

        Log::info("IGDB import complete: imported={$imported}, skipped={$skipped}, errors={$errors}");
    }
}
