<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\TrophyScraperService;
use Illuminate\Console\Command;

class ScrapeTrophyData extends Command
{
    protected $signature = 'trophy:scrape
                            {--game= : Scrape a specific game by ID or slug}
                            {--limit=100 : Number of games to scrape per batch}
                            {--all : Scrape ALL games that have never been scraped (initial bulk run)}
                            {--new : Only scrape games imported since last scrape (for daily runs)}';

    protected $description = 'Scrape trophy data from PowerPyx for games in the database';

    public function handle(TrophyScraperService $scraperService): int
    {
        $gameOption = $this->option('game');
        $limit = (int) $this->option('limit');
        $all = $this->option('all');
        $new = $this->option('new');

        $this->info("Trophy Data Scraper (PowerPyx)");
        $this->info("==============================");
        $this->newLine();

        // Single game mode
        if ($gameOption) {
            return $this->scrapeSingleGame($scraperService, $gameOption);
        }

        // Bulk mode - scrape all never-scraped games
        if ($all) {
            return $this->scrapeAllUnscraped($scraperService, $limit);
        }

        // New games mode - only games imported after last trophy scrape
        if ($new) {
            return $this->scrapeNewGames($scraperService, $limit);
        }

        // Default: show help
        $this->info("Usage:");
        $this->line("  php artisan trophy:scrape --game=<id|slug>  Scrape a specific game");
        $this->line("  php artisan trophy:scrape --all             Bulk scrape all unscraped games");
        $this->line("  php artisan trophy:scrape --new             Scrape newly imported games");
        $this->newLine();
        $this->info("Options:");
        $this->line("  --limit=100  Number of games per batch (default: 100)");

        return Command::SUCCESS;
    }

    /**
     * Scrape a single game
     */
    protected function scrapeSingleGame(TrophyScraperService $scraperService, string $identifier): int
    {
        $game = is_numeric($identifier)
            ? Game::find($identifier)
            : Game::where('slug', $identifier)->first();

        if (!$game) {
            $this->error("Game not found: {$identifier}");
            return Command::FAILURE;
        }

        $this->info("Scraping: {$game->title}");

        $result = $scraperService->scrapeAndUpdateGame($game);

        if ($result['updated']) {
            $this->info("  Updated from: {$result['source']}");
            $this->displayScrapedData($game);
        } else {
            $this->warn("  No data found from any source");
            foreach ($result['results'] as $source => $sourceResult) {
                $status = $sourceResult['success'] ? 'Found' : $sourceResult['error'];
                $this->line("    {$source}: {$status}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Scrape ALL games that have never been scraped (bulk initial run)
     * Runs in batches until all games are processed
     */
    protected function scrapeAllUnscraped(TrophyScraperService $scraperService, int $batchSize): int
    {
        // Count total unscraped games
        $totalUnscraped = Game::whereNull('last_scraped_at')->count();

        if ($totalUnscraped === 0) {
            $this->info("All games have already been scraped.");
            return Command::SUCCESS;
        }

        $this->info("Found {$totalUnscraped} games that have never been scraped.");
        $this->info("Processing in batches of {$batchSize}...");
        $this->warn("Note: Not all games will have PowerPyx guides - that's expected.");
        $this->newLine();

        if (!$this->confirm('This may take a while. Continue?')) {
            return Command::SUCCESS;
        }

        $stats = ['found' => 0, 'not_found' => 0, 'errors' => 0];
        $processed = 0;

        while (true) {
            // Get next batch of unscraped games
            $games = Game::whereNull('last_scraped_at')
                ->orderBy('release_date', 'desc') // Newer games first (more likely to have guides)
                ->limit($batchSize)
                ->get();

            if ($games->isEmpty()) {
                break;
            }

            $this->info("Processing batch of {$games->count()} games...");
            $progressBar = $this->output->createProgressBar($games->count());
            $progressBar->start();

            foreach ($games as $game) {
                $result = $this->scrapeGameSilent($scraperService, $game);
                $stats[$result]++;
                $processed++;
                $progressBar->advance();

                // Rate limiting - 500ms between requests to be respectful
                usleep(500000);
            }

            $progressBar->finish();
            $this->newLine();
            $this->line("Progress: {$processed}/{$totalUnscraped} | Found: {$stats['found']} | Not found: {$stats['not_found']}");
            $this->newLine();
        }

        $this->newLine();
        $this->info("Bulk scrape complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                ['Guides Found', $stats['found']],
                ['Not Found (expected)', $stats['not_found']],
                ['Errors', $stats['errors']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Scrape only newly imported games (for daily runs after IGDB import)
     */
    protected function scrapeNewGames(TrophyScraperService $scraperService, int $limit): int
    {
        // Find games that were imported (created) after the most recent scrape
        $lastScrapedGame = Game::whereNotNull('last_scraped_at')
            ->orderBy('last_scraped_at', 'desc')
            ->first();

        $query = Game::whereNull('last_scraped_at');

        if ($lastScrapedGame) {
            // Only get games created after last scrape session
            $query->where('created_at', '>', $lastScrapedGame->last_scraped_at);
            $this->info("Looking for games imported after: " . $lastScrapedGame->last_scraped_at->format('Y-m-d H:i'));
        } else {
            $this->info("No previous scrape found - will scrape all unscraped games.");
        }

        $games = $query->orderBy('release_date', 'desc')->limit($limit)->get();

        if ($games->isEmpty()) {
            $this->info("No new games to scrape.");
            return Command::SUCCESS;
        }

        $this->info("Found {$games->count()} new games to scrape.");
        $this->newLine();

        $stats = ['found' => 0, 'not_found' => 0, 'errors' => 0];

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        foreach ($games as $game) {
            $result = $this->scrapeGameSilent($scraperService, $game);
            $stats[$result]++;
            $progressBar->advance();

            usleep(500000); // 500ms rate limit
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("New games scrape complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Guides Found', $stats['found']],
                ['Not Found', $stats['not_found']],
                ['Errors', $stats['errors']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Scrape a game silently (for batch operations)
     * Returns: 'found', 'not_found', or 'errors'
     */
    protected function scrapeGameSilent(TrophyScraperService $scraperService, Game $game): string
    {
        try {
            $result = $scraperService->scrapeAndUpdateGame($game);
            return $result['updated'] ? 'found' : 'not_found';
        } catch (\Exception $e) {
            return 'errors';
        }
    }

    /**
     * Display scraped data for a game
     */
    protected function displayScrapedData(Game $game): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Difficulty', $game->difficulty ? "{$game->difficulty}/10" : 'N/A'],
                ['Time', $game->time_min && $game->time_max
                    ? "{$game->time_min}-{$game->time_max} hours"
                    : ($game->time_min ? "{$game->time_min} hours" : 'N/A')],
                ['Playthroughs', $game->playthroughs_required ?? 'N/A'],
                ['Online Trophies', $game->has_online_trophies ? 'Yes' : 'No'],
                ['Missable Trophies', $game->missable_trophies ? 'Yes' : 'No'],
                ['PowerPyx URL', $game->powerpyx_url ?? 'N/A'],
            ]
        );
    }
}
