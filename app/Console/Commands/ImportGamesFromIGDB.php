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
                            {--queue : Run in background via queue}
                            {--catchup : Import all missing games since trophy era (ignores igdb_id cursor)}';

    protected $description = 'Import PlayStation games from IGDB (automatically continues from where you left off)';

    public function handle(IGDBService $igdbService): int
    {
        $limit = (int) $this->option('limit');
        $useQueue = $this->option('queue');
        $catchup = $this->option('catchup');

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

        // Determine cursor: --catchup skips cursor to scan from the beginning
        $cursorIgdbId = null;
        $existingCount = Game::whereNotNull('igdb_id')->count();

        if ($catchup) {
            $this->info("Catchup mode: scanning all games since trophy era (no igdb_id cursor).");
            $this->info("Existing games in DB: {$existingCount}");
        } elseif ($existingCount > 0) {
            $cursorIgdbId = Game::whereNotNull('igdb_id')->max('igdb_id');
            $this->info("Found {$existingCount} existing games — fetching IGDB entries with id > {$cursorIgdbId}");
        } else {
            $this->info("No existing games found, starting fresh import.");
        }

        if ($useQueue) {
            ImportIGDBGames::dispatch($limit, sinceIgdbId: $cursorIgdbId);
            $this->info("Job dispatched to queue. Run 'php artisan queue:work' to process.");
            return Command::SUCCESS;
        }

        // Run synchronously with auto-pagination loop
        \DB::disableQueryLog();

        $totalImported = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $genresCreated = 0;
        $platformsCreated = 0;
        $batchNumber = 0;

        do {
            $batchNumber++;
            $this->info("Batch #{$batchNumber}: fetching up to {$limit} games from IGDB" . ($cursorIgdbId ? " (id > {$cursorIgdbId})" : "") . "...");

            $igdbGames = $igdbService->fetchPlayStationGames($limit, $cursorIgdbId);
            $this->info("Fetched " . count($igdbGames) . " games from IGDB");

            if (empty($igdbGames)) {
                break;
            }

            $progressBar = $this->output->createProgressBar(count($igdbGames));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($igdbGames as $index => $igdbGame) {
                try {
                    // Track the highest IGDB ID for next batch cursor
                    if (isset($igdbGame['id']) && ($cursorIgdbId === null || $igdbGame['id'] > $cursorIgdbId)) {
                        $cursorIgdbId = $igdbGame['id'];
                    }

                    // Skip if already imported (by IGDB ID)
                    if (isset($igdbGame['id']) && Game::where('igdb_id', $igdbGame['id'])->exists()) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
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
                        'description' => $gameData['description'],
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
            $this->newLine();

            $totalImported += $imported;
            $totalSkipped += $skipped;
            $totalErrors += $errors;

            $this->info("Batch #{$batchNumber}: imported={$imported}, skipped={$skipped}, errors={$errors}");

        } while (count($igdbGames) >= $limit);

        $this->newLine();
        $this->info("Import complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Batches Processed', $batchNumber],
                ['Games Imported', $totalImported],
                ['Games Skipped', $totalSkipped],
                ['Genres Created', $genresCreated],
                ['Platforms Created', $platformsCreated],
                ['Errors', $totalErrors],
                ['Total Games in DB', Game::count()],
                ['Total Genres in DB', Genre::count()],
                ['Total Platforms in DB', Platform::count()],
            ]
        );

        return Command::SUCCESS;
    }
}
