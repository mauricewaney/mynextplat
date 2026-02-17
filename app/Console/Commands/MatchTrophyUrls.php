<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MatchTrophyUrls extends Command
{
    protected $signature = 'trophy:match-urls
                            {--source=all : Source to match (powerpyx, playstationtrophies, psnprofiles, all)}
                            {--game-id= : Only match for a specific game ID}
                            {--dry-run : Show matches without saving}
                            {--show-unmatched : Show unmatched URLs}';

    protected $description = 'Match stored trophy guide URLs to games in the database';

    public function handle(): int
    {
        $source = $this->option('source');
        $gameId = $this->option('game-id');
        $dryRun = $this->option('dry-run');
        $showUnmatched = $this->option('show-unmatched');

        // Disable query logging to prevent memory buildup
        \DB::disableQueryLog();

        $this->info("Trophy URL Matcher");
        $this->info("==================");
        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN MODE - no changes will be saved");
            $this->newLine();
        }

        // Count unmatched URLs first (without loading them all)
        $urlQuery = TrophyGuideUrl::unmatched();
        if ($source !== 'all') {
            $urlQuery->where('source', $source);
        }
        $unmatchedCount = $urlQuery->count();

        if ($unmatchedCount === 0) {
            $this->info("No unmatched URLs to process.");
            return Command::SUCCESS;
        }

        // Build lookup maps from games without keeping Eloquent models in memory
        $this->info("Building game lookup maps...");

        $lookups = ['bySlug' => [], 'byNormalizedSlug' => [], 'byNormalizedTitle' => []];
        $gameCount = 0;

        $gameQuery = Game::select(['id', 'title', 'slug']);
        if ($gameId) {
            $gameQuery->where('id', $gameId);
        }

        $gameQuery->chunk(2000, function ($games) use (&$lookups, &$gameCount) {
            foreach ($games as $game) {
                $id = $game->id;

                if ($game->slug) {
                    $lookups['bySlug'][$game->slug] = $id;
                }

                $normalizedSlug = $this->normalizeSlug($game->slug ?? Str::slug($game->title));
                $lookups['byNormalizedSlug'][$normalizedSlug] = $id;

                $normalizedTitle = $this->normalizeSlug(Str::slug($game->title));
                $lookups['byNormalizedTitle'][$normalizedTitle] = $id;

                $gameCount++;
            }
        });

        if ($gameCount === 0) {
            $this->info("No games in database to match against.");
            return Command::SUCCESS;
        }

        $this->info("Matching {$unmatchedCount} unmatched URLs against {$gameCount} games...");
        $this->newLine();

        $stats = ['matched' => 0, 'unmatched' => 0];
        $matchSamples = [];

        $progressBar = $this->output->createProgressBar($unmatchedCount);
        $progressBar->start();

        // Process URLs in chunks
        $urlQuery = TrophyGuideUrl::unmatched();
        if ($source !== 'all') {
            $urlQuery->where('source', $source);
        }

        $urlQuery->chunk(500, function ($urlRecords) use ($lookups, $dryRun, $showUnmatched, &$stats, &$matchSamples, $progressBar) {
            foreach ($urlRecords as $urlRecord) {
                $gameId = $this->findMatchingGameId($urlRecord->extracted_slug, $lookups);

                if ($gameId) {
                    if (count($matchSamples) < 20) {
                        $matchSamples[] = [
                            'source' => $urlRecord->source,
                            'slug' => $urlRecord->extracted_slug,
                            'game_id' => $gameId,
                        ];
                    }
                    $stats['matched']++;

                    if (!$dryRun) {
                        $urlRecord->game_id = $gameId;
                        $urlRecord->matched_at = now();
                        $urlRecord->save();

                        // Update the game's URL field for quick access
                        $this->updateGameUrlById($gameId, $urlRecord);
                    }
                } else {
                    $stats['unmatched']++;

                    if ($showUnmatched) {
                        $this->newLine();
                        $this->warn("  No match: {$urlRecord->extracted_slug}");
                    }
                }

                $progressBar->advance();
            }

            gc_collect_cycles();
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Matching complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Newly Matched', $stats['matched']],
                ['Still Unmatched', $stats['unmatched']],
            ]
        );

        if ($dryRun && count($matchSamples) > 0) {
            $this->newLine();
            $this->info("Sample matches (first 20):");
            $this->table(
                ['Game ID', 'Source', 'URL Slug'],
                collect($matchSamples)->map(fn($m) => [
                    $m['game_id'],
                    $m['source'],
                    Str::limit($m['slug'], 40),
                ])->toArray()
            );
        }

        $this->newLine();
        $this->showStats();

        return Command::SUCCESS;
    }

    /**
     * Find a matching game ID using lookup maps (no Eloquent models in memory)
     */
    protected function findMatchingGameId(string $extractedSlug, array $lookups): ?int
    {
        // Strategy 1: Exact slug match
        if (isset($lookups['bySlug'][$extractedSlug])) {
            return $lookups['bySlug'][$extractedSlug];
        }

        // Strategy 2: Normalized slug match
        $normalizedExtracted = $this->normalizeSlug($extractedSlug);

        if (isset($lookups['byNormalizedSlug'][$normalizedExtracted])) {
            return $lookups['byNormalizedSlug'][$normalizedExtracted];
        }

        if (isset($lookups['byNormalizedTitle'][$normalizedExtracted])) {
            return $lookups['byNormalizedTitle'][$normalizedExtracted];
        }

        return null;
    }

    /**
     * Normalize a slug for comparison
     * Note: We intentionally keep remake/remaster suffixes as they have different trophy lists
     */
    protected function normalizeSlug(string $slug): string
    {
        // Only remove platform suffixes - these don't affect trophy lists
        $suffixes = [
            '-ps5', '-ps4', '-ps3',
            '-playstation-5', '-playstation-4', '-playstation-3',
        ];

        $slug = strtolower($slug);

        foreach ($suffixes as $suffix) {
            if (str_ends_with($slug, $suffix)) {
                $slug = substr($slug, 0, -strlen($suffix));
            }
        }

        // Remove "the-" prefix
        $slug = preg_replace('#^the-#', '', $slug);

        // Remove special characters and normalize dashes
        $slug = preg_replace('#[^a-z0-9-]#', '', $slug);
        $slug = preg_replace('#-+#', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Update the game's URL field by ID (avoids keeping model in memory)
     */
    protected function updateGameUrlById(int $gameId, TrophyGuideUrl $urlRecord): void
    {
        $field = match ($urlRecord->source) {
            'powerpyx' => 'powerpyx_url',
            'playstationtrophies' => 'playstationtrophies_url',
            'psnprofiles' => 'psnprofiles_url',
            default => null,
        };

        if ($field) {
            Game::where('id', $gameId)
                ->whereNull($field)
                ->update([$field => $urlRecord->url]);
        }
    }

    /**
     * Show current stats
     */
    protected function showStats(): void
    {
        $this->info("Trophy URL database status:");
        $this->table(
            ['Source', 'Total', 'Matched', 'Unmatched'],
            [
                [
                    'PowerPyx',
                    TrophyGuideUrl::source('powerpyx')->count(),
                    TrophyGuideUrl::source('powerpyx')->matched()->count(),
                    TrophyGuideUrl::source('powerpyx')->unmatched()->count(),
                ],
                [
                    'PlayStationTrophies',
                    TrophyGuideUrl::source('playstationtrophies')->count(),
                    TrophyGuideUrl::source('playstationtrophies')->matched()->count(),
                    TrophyGuideUrl::source('playstationtrophies')->unmatched()->count(),
                ],
                [
                    'PSNProfiles',
                    TrophyGuideUrl::source('psnprofiles')->count(),
                    TrophyGuideUrl::source('psnprofiles')->matched()->count(),
                    TrophyGuideUrl::source('psnprofiles')->unmatched()->count(),
                ],
            ]
        );

        $this->newLine();
        $gamesWithUrls = Game::where(function ($q) {
            $q->whereNotNull('powerpyx_url')
              ->orWhereNotNull('playstationtrophies_url')
              ->orWhereNotNull('psnprofiles_url');
        })->count();

        $this->info("Games with trophy URLs: {$gamesWithUrls} / " . Game::count());
    }
}
