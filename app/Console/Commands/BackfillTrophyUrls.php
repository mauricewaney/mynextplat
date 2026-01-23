<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BackfillTrophyUrls extends Command
{
    protected $signature = 'trophy:backfill
                            {--source=all : Source to backfill (psnprofiles, playstationtrophies, all)}
                            {--batch=20 : Games per batch (smaller = safer)}
                            {--delay=3 : Delay between searches in seconds}
                            {--batch-pause=60 : Pause between batches in seconds}
                            {--start-id=0 : Start from this game ID (for resuming)}
                            {--dry-run : Show what would be done without saving}';

    protected $description = 'Backfill trophy guide URLs for all games (runs until complete, can be resumed)';

    protected int $found = 0;
    protected int $notFound = 0;
    protected int $errors = 0;
    protected int $lastProcessedId = 0;

    public function handle(): int
    {
        $source = $this->option('source');
        $batchSize = (int) $this->option('batch');
        $delay = (float) $this->option('delay');
        $batchPause = (int) $this->option('batch-pause');
        $startId = (int) $this->option('start-id');
        $dryRun = $this->option('dry-run');

        $this->info("Trophy URL Backfill");
        $this->info("===================");
        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN MODE - no changes will be saved");
            $this->newLine();
        }

        // Disable query log for memory management
        \DB::disableQueryLog();

        $sources = $source === 'all'
            ? ['psnprofiles', 'playstationtrophies']
            : [$source];

        foreach ($sources as $currentSource) {
            $this->found = 0;
            $this->notFound = 0;
            $this->errors = 0;
            $this->lastProcessedId = $startId;

            $this->backfillSource($currentSource, $batchSize, $delay, $batchPause, $dryRun);
            $this->newLine();
        }

        $this->info("Backfill complete!");
        $this->showStats();

        return Command::SUCCESS;
    }

    protected function backfillSource(string $source, int $batchSize, float $delay, int $batchPause, bool $dryRun): void
    {
        $urlField = $this->getUrlField($source);
        $siteDomain = $this->getSiteDomain($source);

        if (!$urlField || !$siteDomain) {
            $this->error("Unknown source: {$source}");
            return;
        }

        // Count total games to process
        $totalGames = Game::where('id', '>', $this->lastProcessedId)->count();
        $gamesWithUrl = Game::where('id', '>', $this->lastProcessedId)->whereNotNull($urlField)->count();
        $gamesWithoutUrl = $totalGames - $gamesWithUrl;

        $this->info("[{$source}] Total games to check: {$totalGames}");
        $this->info("[{$source}] Already have URLs: {$gamesWithUrl}");
        $this->info("[{$source}] Need to search: {$gamesWithoutUrl}");
        $this->newLine();

        if ($this->lastProcessedId > 0) {
            $this->info("[{$source}] Resuming from game ID: {$this->lastProcessedId}");
        }

        $batchNumber = 1;
        $processedCount = 0;

        while (true) {
            // Get next batch of games (all games, not just missing URLs)
            // We process ALL games in ID order to ensure we don't re-search
            $games = Game::where('id', '>', $this->lastProcessedId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($games->isEmpty()) {
                $this->newLine();
                $this->info("[{$source}] All games processed!");
                break;
            }

            $this->line("Batch {$batchNumber}: Processing games {$games->first()->id} - {$games->last()->id}...");

            $batchFound = 0;
            $batchSkipped = 0;
            $batchSearched = 0;

            foreach ($games as $game) {
                // Skip if already has URL for this source
                if (!empty($game->$urlField)) {
                    $batchSkipped++;
                    $this->lastProcessedId = $game->id;
                    continue;
                }

                // Search for this game
                $url = $this->searchForGame($game, $siteDomain, $source);

                if ($url) {
                    $this->found++;
                    $batchFound++;

                    if (!$dryRun) {
                        $game->$urlField = $url;
                        $game->save();
                        $this->storeTrophyUrl($url, $source, $game);
                    }

                    $this->output->write("<info>.</info>");
                } else {
                    $this->notFound++;
                    $this->output->write("<comment>x</comment>");
                }

                $batchSearched++;
                $this->lastProcessedId = $game->id;
                $processedCount++;

                // Rate limiting
                usleep((int) ($delay * 1000000));
            }

            $this->newLine();
            $this->line("  Searched: {$batchSearched} | Found: {$batchFound} | Skipped (has URL): {$batchSkipped} | Errors: {$this->errors}");

            // Detect potential rate limiting (too many errors or zero found in a full batch)
            if ($this->errors > 10 || ($batchSearched > 10 && $batchFound === 0 && $batchSkipped === 0)) {
                $this->newLine();
                $this->warn("Possible rate limiting detected. Taking a longer break (2 minutes)...");
                $this->info("Resume command: php artisan trophy:backfill --source={$source} --start-id={$this->lastProcessedId}");
                sleep(120);
                $this->errors = 0; // Reset error count after break
            }

            $batchNumber++;

            // Garbage collection
            gc_collect_cycles();

            // Show progress every 5 batches
            if ($batchNumber % 5 === 0) {
                $this->newLine();
                $this->info("Progress: Processed {$processedCount} games | Found {$this->found} URLs | Last ID: {$this->lastProcessedId}");
                $this->info("To resume later: php artisan trophy:backfill --source={$source} --start-id={$this->lastProcessedId}");
                $this->newLine();
            }

            // Pause between batches to avoid rate limiting
            $this->line("  Pausing {$batchPause}s before next batch...");
            sleep($batchPause);
        }

        $this->newLine();
        $this->info("[{$source}] Summary: Searched {$processedCount} games | Found {$this->found} URLs | Errors: {$this->errors}");
    }

    protected function searchForGame(Game $game, string $siteDomain, string $source): ?string
    {
        $searchQuery = "site:{$siteDomain} \"{$game->title}\" trophy guide";

        try {
            $response = Http::timeout(30)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            ])->get('https://html.duckduckgo.com/html/', [
                'q' => $searchQuery,
            ]);

            if (!$response->successful()) {
                $this->errors++;
                return null;
            }

            $html = $response->body();
            $urls = $this->extractGuideUrlsFromHtml($html, $source);

            // Return first matching URL
            return $urls[0] ?? null;

        } catch (\Exception $e) {
            $this->errors++;
            return null;
        }
    }

    protected function extractGuideUrlsFromHtml(string $html, string $source): array
    {
        $pattern = match ($source) {
            'psnprofiles' => '#https?://psnprofiles\.com/guide/\d+-[a-z0-9-]+#i',
            'playstationtrophies' => '#https?://(?:www\.)?playstationtrophies\.org/game/[a-z0-9-]+/guide/?#i',
            default => null,
        };

        if (!$pattern) {
            return [];
        }

        $urls = [];
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[0] as $url) {
                $url = html_entity_decode($url);
                $url = strtok($url, '?');
                $urls[] = rtrim($url, '/');
            }
        }

        return array_unique($urls);
    }

    protected function getUrlField(string $source): ?string
    {
        return match ($source) {
            'psnprofiles' => 'psnprofiles_url',
            'playstationtrophies' => 'playstationtrophies_url',
            default => null,
        };
    }

    protected function getSiteDomain(string $source): ?string
    {
        return match ($source) {
            'psnprofiles' => 'psnprofiles.com',
            'playstationtrophies' => 'playstationtrophies.org',
            default => null,
        };
    }

    protected function storeTrophyUrl(string $url, string $source, Game $game): void
    {
        if (TrophyGuideUrl::where('url', $url)->exists()) {
            return;
        }

        TrophyGuideUrl::create([
            'source' => $source,
            'url' => $url,
            'extracted_slug' => $game->slug,
            'extracted_title' => $game->title,
            'game_id' => $game->id,
            'matched_at' => now(),
        ]);
    }

    protected function showStats(): void
    {
        $this->newLine();
        $this->table(
            ['Source', 'Games with URL', 'Games without URL'],
            [
                [
                    'PSNProfiles',
                    Game::whereNotNull('psnprofiles_url')->count(),
                    Game::whereNull('psnprofiles_url')->count(),
                ],
                [
                    'PlayStationTrophies',
                    Game::whereNotNull('playstationtrophies_url')->count(),
                    Game::whereNull('playstationtrophies_url')->count(),
                ],
                [
                    'PowerPyx',
                    Game::whereNotNull('powerpyx_url')->count(),
                    Game::whereNull('powerpyx_url')->count(),
                ],
            ]
        );
    }
}
