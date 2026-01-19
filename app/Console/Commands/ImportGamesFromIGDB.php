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
                            {--fresh : Delete all games before importing}';

    protected $description = 'Import PlayStation games from IGDB (auto-syncs from latest release date)';

    public function handle(IGDBService $igdbService): int
    {
        $limit = (int) $this->option('limit');
        $useQueue = $this->option('queue');
        $fresh = $this->option('fresh');

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

        // Clear existing games if fresh flag is set
        if ($fresh) {
            if ($this->confirm('This will delete all existing games. Are you sure?')) {
                Game::query()->delete();
                $this->info("Cleared existing games.");
            } else {
                $this->info("Cancelled.");
                return Command::SUCCESS;
            }
        }

        // Determine the sync timestamp based on latest game in DB
        $sinceTimestamp = null;
        $latestGame = Game::whereNotNull('release_date')
            ->orderBy('release_date', 'desc')
            ->first();

        if ($latestGame && $latestGame->release_date) {
            $sinceTimestamp = $latestGame->release_date->timestamp;
            $this->info("Syncing from: " . $latestGame->release_date->format('Y-m-d') . " ({$latestGame->title})");
        } else {
            $this->info("No existing games found, starting full import.");
        }

        if ($useQueue) {
            ImportIGDBGames::dispatch($limit, 0, $sinceTimestamp);
            $this->info("Job dispatched to queue. Run 'php artisan queue:work' to process.");
            return Command::SUCCESS;
        }

        // Run synchronously
        $this->info("Fetching up to {$limit} games...");

        // Get existing IGDB IDs to skip
        $existingIgdbIds = Game::whereNotNull('igdb_id')->pluck('igdb_id')->toArray();
        $this->info("Found " . count($existingIgdbIds) . " existing games with IGDB IDs");

        $igdbGames = $igdbService->fetchPlayStationGames($limit, 0, $sinceTimestamp);
        $this->info("Fetched " . count($igdbGames) . " games from IGDB");

        $progressBar = $this->output->createProgressBar(count($igdbGames));
        $progressBar->start();

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $genresCreated = 0;
        $platformsCreated = 0;

        foreach ($igdbGames as $igdbGame) {
            try {
                // Skip if already imported (by IGDB ID)
                if (isset($igdbGame['id']) && in_array($igdbGame['id'], $existingIgdbIds)) {
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

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed: " . ($igdbGame['name'] ?? 'Unknown') . " - " . $e->getMessage());
            }

            $progressBar->advance();
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
