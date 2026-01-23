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

        // Get games to match against - we need these for lookup maps
        // Only select fields we need to reduce memory
        $gameQuery = Game::select(['id', 'title', 'slug', 'powerpyx_url', 'playstationtrophies_url', 'psnprofiles_url']);

        if ($gameId) {
            $gameQuery->where('id', $gameId);
        }

        $games = $gameQuery->get();

        if ($games->isEmpty()) {
            $this->info("No games in database to match against.");
            return Command::SUCCESS;
        }

        $this->info("Matching {$unmatchedCount} unmatched URLs against {$games->count()} games...");
        $this->newLine();

        // Build lookup maps for faster matching
        $lookups = $this->buildGameLookups($games);

        $stats = ['matched' => 0, 'unmatched' => 0];
        $matches = [];

        $progressBar = $this->output->createProgressBar($unmatchedCount);
        $progressBar->start();

        // Process URLs in chunks to manage memory
        $urlQuery = TrophyGuideUrl::unmatched();
        if ($source !== 'all') {
            $urlQuery->where('source', $source);
        }

        $urlQuery->chunk(500, function ($urlRecords) use ($lookups, $games, $dryRun, $showUnmatched, &$stats, &$matches, $progressBar) {
            foreach ($urlRecords as $urlRecord) {
                $game = $this->findMatchingGame($urlRecord->extracted_slug, $lookups, $games);

                if ($game) {
                    // Only keep first 20 matches for dry-run display to save memory
                    if (count($matches) < 20) {
                        $matches[] = [
                            'url' => $urlRecord,
                            'game' => $game,
                        ];
                    }
                    $stats['matched']++;

                    if (!$dryRun) {
                        $urlRecord->game_id = $game->id;
                        $urlRecord->matched_at = now();
                        $urlRecord->save();

                        // Also update the game's URL field for quick access
                        $this->updateGameUrl($game, $urlRecord);
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

            // Garbage collection after each chunk
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

        if ($dryRun && count($matches) > 0) {
            $this->newLine();
            $this->info("Sample matches (first 20):");
            $this->table(
                ['Game Title', 'Source', 'URL Slug'],
                collect($matches)->take(20)->map(fn($m) => [
                    Str::limit($m['game']->title, 35),
                    $m['url']->source,
                    Str::limit($m['url']->extracted_slug, 35),
                ])->toArray()
            );
        }

        $this->newLine();
        $this->showStats();

        return Command::SUCCESS;
    }

    /**
     * Build lookup maps for faster game matching
     */
    protected function buildGameLookups($games): array
    {
        $bySlug = [];
        $byNormalizedSlug = [];
        $byNormalizedTitle = [];

        foreach ($games as $game) {
            // By exact slug
            if ($game->slug) {
                $bySlug[$game->slug] = $game;
            }

            // By normalized slug
            $normalizedSlug = $this->normalizeSlug($game->slug ?? Str::slug($game->title));
            $byNormalizedSlug[$normalizedSlug] = $game;

            // By normalized title
            $normalizedTitle = $this->normalizeSlug(Str::slug($game->title));
            $byNormalizedTitle[$normalizedTitle] = $game;
        }

        return [
            'bySlug' => $bySlug,
            'byNormalizedSlug' => $byNormalizedSlug,
            'byNormalizedTitle' => $byNormalizedTitle,
        ];
    }

    /**
     * Find a matching game using various strategies
     */
    protected function findMatchingGame(string $extractedSlug, array $lookups, $allGames): ?Game
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

        // Strategy 3: Partial matching - check if slugs overlap significantly
        foreach ($allGames as $game) {
            $gameSlug = $this->normalizeSlug($game->slug ?? Str::slug($game->title));

            // Check if one contains the other (for subtitles, editions, etc.)
            if ($this->slugsMatch($normalizedExtracted, $gameSlug)) {
                return $game;
            }
        }

        return null;
    }

    /**
     * Check if two slugs match (accounting for variations)
     * Conservative matching - only match on exact or very close matches
     */
    protected function slugsMatch(string $slug1, string $slug2): bool
    {
        // Exact match
        if ($slug1 === $slug2) {
            return true;
        }

        // Don't do partial matching - too many false positives
        // e.g., "mass-effect" should NOT match "mass-effect-andromeda"
        // Only exact matches after normalization are allowed

        return false;
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
     * Update the game's URL field for quick access
     */
    protected function updateGameUrl(Game $game, TrophyGuideUrl $urlRecord): void
    {
        $field = match ($urlRecord->source) {
            'powerpyx' => 'powerpyx_url',
            'playstationtrophies' => 'playstationtrophies_url',
            'psnprofiles' => 'psnprofiles_url',
            default => null,
        };

        if ($field && empty($game->$field)) {
            $game->$field = $urlRecord->url;
            $game->save();
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
