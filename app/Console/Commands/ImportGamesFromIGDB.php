<?php

namespace App\Console\Commands;

use App\Jobs\ImportIGDBGames;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Platform;
use App\Services\IGDBService;
use Illuminate\Console\Command;

class ImportGamesFromIGDB extends Command
{
    protected $signature = 'igdb:import
                            {--limit=500 : Number of games to fetch per batch}
                            {--queue : Run in background via queue}';

    protected $description = 'Import PlayStation games from IGDB (automatically continues from where you left off)';

    public function handle(IGDBService $igdbService): int
    {
        $limit = (int) $this->option('limit');
        $useQueue = $this->option('queue');

        $this->info("IGDB Game Importer");
        $this->info("==================");

        // Test connection first
        $this->info("Testing IGDB connection...");
        $connectionTest = $igdbService->testConnection();

        if (!$connectionTest['success']) {
            $this->error($connectionTest['message']);
            return Command::FAILURE;
        }

        $this->info("Connection successful!");

        // Use created_at cursor to find recently added IGDB entries
        $existingCount = Game::whereNotNull('igdb_id')->count();
        $cursorTimestamp = null;
        $boundaryExcludeIds = [];

        if ($existingCount > 0) {
            $latestGame = Game::whereNotNull('igdb_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestGame) {
                // Subtract 1 day buffer to avoid missing edge cases
                $cursorTimestamp = $latestGame->created_at->subDay()->timestamp;

                $this->info("Found {$existingCount} existing games - fetching IGDB entries added since {$latestGame->created_at->subDay()->format('Y-m-d')}");
            }
        } else {
            $this->info("No existing games found, starting fresh import.");
        }

        if ($useQueue) {
            ImportIGDBGames::dispatch($limit, 0, $cursorTimestamp, $boundaryExcludeIds);
            $this->info("Job dispatched to queue. Run 'php artisan queue:work' to process.");
            return Command::SUCCESS;
        }

        // Run synchronously - use cursor-based pagination
        $this->info("Fetching up to {$limit} NEW games from IGDB...");

        // Disable query logging to prevent memory buildup
        \DB::disableQueryLog();

        $igdbGames = $igdbService->fetchPlayStationGames($limit, 0, $cursorTimestamp, $boundaryExcludeIds);
        $this->info("Fetched " . count($igdbGames) . " games from IGDB");

        $progressBar = $this->output->createProgressBar(count($igdbGames));
        $progressBar->start();

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $genresCreated = 0;
        $platformsCreated = 0;

        foreach ($igdbGames as $index => $igdbGame) {
            try {
                // Skip if already imported (by IGDB ID) - check boundary exclusions and DB
                if (isset($igdbGame['id'])) {
                    if (in_array($igdbGame['id'], $boundaryExcludeIds) || Game::where('igdb_id', $igdbGame['id'])->exists()) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }
                }

                $gameData = $igdbService->parseGameData($igdbGame);

                // Double-check by slug
                if (Game::where('slug', $gameData['slug'])->exists()) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Create the game
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
                    if ($platform->wasRecentlyCreated) {
                        $platformsCreated++;
                    }
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
                    if ($genre->wasRecentlyCreated) {
                        $genresCreated++;
                    }
                    $genreIds[] = $genre->id;
                }
                if (!empty($genreIds)) {
                    $game->genres()->attach($genreIds);
                }

                $imported++;

                // Clear the game reference to help garbage collection
                unset($game);

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed: " . ($igdbGame['name'] ?? 'Unknown') . " - " . $e->getMessage());
            }

            $progressBar->advance();

            // Periodic garbage collection every 50 games to prevent memory buildup
            if (($index + 1) % 50 === 0) {
                gc_collect_cycles();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Import complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Games Imported', $imported],
                ['Games Skipped', $skipped],
                ['Genres Created', $genresCreated],
                ['Platforms Created', $platformsCreated],
                ['Errors', $errors],
                ['Total Games in DB', Game::count()],
                ['Total Genres in DB', Genre::count()],
                ['Total Platforms in DB', Platform::count()],
            ]
        );

        return Command::SUCCESS;
    }
}
