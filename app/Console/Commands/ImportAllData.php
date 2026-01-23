<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportAllData extends Command
{
    protected $signature = 'import:all
                            {--limit=500 : Number of games to import (for testing)}
                            {--full : Import ALL games (no limit, takes a long time)}
                            {--fresh : Run migrate:fresh before importing (DESTRUCTIVE)}
                            {--skip-trophies : Skip trophy scraping (IGDB only)}';

    protected $description = 'Import all game data: IGDB games, PowerPyx trophy data, and trophy guide URLs';

    public function handle(): int
    {
        $limit = $this->option('full') ? null : (int) $this->option('limit');
        $fresh = $this->option('fresh');
        $skipTrophies = $this->option('skip-trophies');

        $this->newLine();
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       COMPLETE DATA IMPORT WIZARD        ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        // Show what will happen
        $this->info('This will run the following steps:');
        $step = 1;
        if ($fresh) {
            $this->warn("  {$step}. [DESTRUCTIVE] Wipe database (migrate:fresh)");
            $step++;
        }
        if (!$skipTrophies) {
            $this->line("  {$step}. Fetch trophy guide URLs from sitemaps (PowerPyx)");
            $step++;
        }
        $this->line("  {$step}. Import games from IGDB" . ($limit ? " (limit: {$limit})" : ' (ALL games)'));
        $step++;
        if (!$skipTrophies) {
            $this->line("  {$step}. Match trophy URLs to imported games");
        }
        $this->newLine();

        if (!$limit) {
            $this->warn('⚠️  FULL IMPORT MODE - This will take a LONG time (hours)!');
            $this->warn('   IGDB has 10,000+ PlayStation games.');
            $this->newLine();
        }

        if (!$this->confirm('Continue with import?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $startTime = now();

        $step = 1;

        // Step: Fresh migration (optional)
        if ($fresh) {
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Step {$step}: Running migrate:fresh...");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $this->call('migrate:fresh', ['--force' => true]);
            $step++;
        }

        // Step: Fetch trophy sitemaps (before IGDB import so matching can happen)
        if (!$skipTrophies) {
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Step {$step}: Fetching trophy guide URLs from sitemaps...");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $this->call('trophy:fetch-sitemap', ['--source' => 'powerpyx']);
            $step++;
        }

        // Step: IGDB Import (auto-matches trophy URLs after import)
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Step {$step}: Importing games from IGDB...");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($limit) {
            $this->call('igdb:import', ['--limit' => $limit]);
        } else {
            // Full import - run in batches
            $this->fullIgdbImport();
        }
        $step++;

        // Step: Run final trophy URL matching (catches any stragglers)
        if (!$skipTrophies) {
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Step {$step}: Final trophy URL matching...");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $this->call('trophy:match-urls');
        }

        $this->showSummary($startTime);

        return Command::SUCCESS;
    }

    /**
     * Run full IGDB import in batches
     */
    protected function fullIgdbImport(): void
    {
        $batchSize = 250; // Reduced from 500 for better memory management
        $batchNumber = 1;

        // Disable query logging to prevent memory buildup
        \DB::disableQueryLog();

        $this->info("Running full import in batches of {$batchSize}...");
        $this->newLine();

        while (true) {
            $beforeCount = \App\Models\Game::count();
            $this->line("Batch {$batchNumber}: Fetching up to {$batchSize} new games...");

            // The igdb:import command now automatically excludes existing games
            $this->callSilent('igdb:import', ['--limit' => $batchSize]);

            $afterCount = \App\Models\Game::count();
            $newGames = $afterCount - $beforeCount;

            if ($newGames === 0) {
                // No new games imported, we're done
                $this->info("No more new games found. Import complete.");
                break;
            }

            $this->line("  Imported {$newGames} games (Total: {$afterCount})");
            $batchNumber++;

            // Clear Eloquent model cache and force garbage collection
            \Illuminate\Database\Eloquent\Model::clearBootedModels();
            gc_collect_cycles();

            // Small delay between batches to be nice to the API
            sleep(2);
        }

        $this->newLine();
        $this->info("Total games in database: " . \App\Models\Game::count());
    }

    /**
     * Show final summary
     */
    protected function showSummary(\Carbon\Carbon $startTime): void
    {
        $duration = $startTime->diffForHumans(now(), ['parts' => 2, 'short' => true]);

        $this->newLine();
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║            IMPORT COMPLETE!              ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        $stats = [
            ['Total Games', \App\Models\Game::count()],
            ['With PowerPyx URL', \App\Models\Game::whereNotNull('powerpyx_url')->count()],
            ['With PS Trophies URL', \App\Models\Game::whereNotNull('playstationtrophies_url')->count()],
            ['Trophy URLs in DB', \App\Models\TrophyGuideUrl::count()],
            ['Trophy URLs Matched', \App\Models\TrophyGuideUrl::matched()->count()],
            ['Genres', \App\Models\Genre::count()],
            ['Platforms', \App\Models\Platform::count()],
        ];

        $this->table(['Metric', 'Count'], $stats);

        $this->newLine();
        $this->info("Duration: {$duration}");
    }
}
