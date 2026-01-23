<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SearchTrophyUrls extends Command
{
    protected $signature = 'trophy:search-urls
                            {--source=all : Source to search (psnprofiles, playstationtrophies, all)}
                            {--mode=recent : Search mode: "recent" (find new guides) or "missing" (search for games without URLs)}
                            {--period=week : For recent mode: day, week, or month}
                            {--limit=50 : For missing mode: max games to process}
                            {--delay=1 : Delay between searches in seconds}
                            {--dry-run : Show results without saving}';

    protected $description = 'Search for trophy guide URLs - either recent guides or for specific games missing URLs';

    protected int $found = 0;
    protected int $matched = 0;
    protected int $notMatched = 0;
    protected int $errors = 0;

    public function handle(): int
    {
        $source = $this->option('source');
        $mode = $this->option('mode');
        $dryRun = $this->option('dry-run');

        $this->info("Trophy URL Search");
        $this->info("=================");
        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN MODE - no changes will be saved");
            $this->newLine();
        }

        $sources = $source === 'all'
            ? ['psnprofiles', 'playstationtrophies']
            : [$source];

        foreach ($sources as $currentSource) {
            if ($mode === 'recent') {
                $this->searchRecentGuides($currentSource, $dryRun);
            } else {
                $this->searchMissingGames($currentSource, $dryRun);
            }
            $this->newLine();

            // Delay between sources
            if ($source === 'all') {
                sleep(2);
            }
        }

        $this->info("Search complete!");

        if ($mode === 'recent') {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Guide URLs Found', $this->found],
                    ['Matched to Games', $this->matched],
                    ['No Match in DB', $this->notMatched],
                    ['Errors', $this->errors],
                ]
            );
        } else {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['URLs Found', $this->found],
                    ['Not Found', $this->notMatched],
                    ['Errors', $this->errors],
                ]
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Search for recently published trophy guides and match to games
     */
    protected function searchRecentGuides(string $source, bool $dryRun): void
    {
        $period = $this->option('period');
        $delay = (float) $this->option('delay');

        $siteDomain = match ($source) {
            'psnprofiles' => 'psnprofiles.com',
            'playstationtrophies' => 'playstationtrophies.org',
            default => null,
        };

        if (!$siteDomain) {
            $this->error("Unknown source: {$source}");
            return;
        }

        $this->info("[{$source}] Searching for guides published in the past {$period}...");

        // Search for recent trophy guides
        $guideUrls = $this->searchRecentGuidesOnSite($siteDomain, $source, $period);

        if (empty($guideUrls)) {
            $this->info("[{$source}] No new guides found.");
            return;
        }

        $this->found += count($guideUrls);
        $this->info("[{$source}] Found " . count($guideUrls) . " guide URLs");
        $this->newLine();

        // Match each URL to a game
        $progressBar = $this->output->createProgressBar(count($guideUrls));
        $progressBar->start();

        foreach ($guideUrls as $url) {
            $slug = $this->extractSlugFromUrl($url, $source);

            if (!$slug) {
                $progressBar->advance();
                continue;
            }

            // Try to find a matching game
            $game = $this->findGameBySlug($slug, $source);

            if ($game) {
                $this->matched++;
                $urlField = $this->getUrlField($source);

                if (!$dryRun && $urlField && empty($game->$urlField)) {
                    $game->$urlField = $url;
                    $game->save();

                    // Store in TrophyGuideUrl table
                    $this->storeTrophyUrl($url, $source, $game);
                }

                $this->newLine();
                $this->info("  ✓ Matched: {$game->title}");
            } else {
                $this->notMatched++;

                // Store URL anyway for potential future matching
                if (!$dryRun) {
                    $this->storeUnmatchedUrl($url, $source, $slug);
                }
            }

            $progressBar->advance();
            usleep((int) ($delay * 500000)); // Half the delay since we already fetched
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Search for recent guides on a site using DuckDuckGo with date filter
     */
    protected function searchRecentGuidesOnSite(string $siteDomain, string $source, string $period): array
    {
        $dateFilter = match ($period) {
            'day' => 'd',
            'week' => 'w',
            'month' => 'm',
            default => 'w',
        };

        $guideUrls = [];

        // Do multiple searches to get more results
        $searchQueries = [
            "site:{$siteDomain} trophy guide",
            "site:{$siteDomain} trophy guide roadmap",
        ];

        foreach ($searchQueries as $query) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                ])->get('https://html.duckduckgo.com/html/', [
                    'q' => $query,
                    'df' => $dateFilter, // Date filter: d=day, w=week, m=month
                ]);

                if (!$response->successful()) {
                    $this->errors++;
                    continue;
                }

                $html = $response->body();
                $urls = $this->extractGuideUrlsFromHtml($html, $source);
                $guideUrls = array_merge($guideUrls, $urls);

                // Small delay between searches
                sleep(1);

            } catch (\Exception $e) {
                $this->errors++;
                $this->error("  Search error: " . $e->getMessage());
            }
        }

        // Remove duplicates
        return array_unique($guideUrls);
    }

    /**
     * Extract guide URLs from DuckDuckGo HTML results
     */
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
                $url = strtok($url, '?'); // Remove query strings
                $urls[] = rtrim($url, '/');
            }
        }

        return array_unique($urls);
    }

    /**
     * Extract game slug from a trophy guide URL
     */
    protected function extractSlugFromUrl(string $url, string $source): ?string
    {
        return match ($source) {
            'psnprofiles' => $this->extractPsnProfilesSlug($url),
            'playstationtrophies' => $this->extractPlayStationTrophiesSlug($url),
            default => null,
        };
    }

    protected function extractPsnProfilesSlug(string $url): ?string
    {
        // URL format: https://psnprofiles.com/guide/12345-game-name-trophy-guide
        if (preg_match('#/guide/\d+-([a-z0-9-]+?)(?:-trophy-guide)?$#i', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractPlayStationTrophiesSlug(string $url): ?string
    {
        // URL format: https://www.playstationtrophies.org/game/game-name/guide/
        if (preg_match('#/game/([a-z0-9-]+)/guide#i', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Find a game by slug with fuzzy matching
     */
    protected function findGameBySlug(string $slug, string $source): ?Game
    {
        // Normalize the slug
        $normalizedSlug = $this->normalizeSlug($slug);

        // Try exact slug match first
        $game = Game::where('slug', $slug)->first();
        if ($game) {
            return $game;
        }

        // Try normalized slug match
        $game = Game::whereRaw('LOWER(REPLACE(slug, "-", "")) = ?', [str_replace('-', '', $normalizedSlug)])->first();
        if ($game) {
            return $game;
        }

        // Try matching by converting slug to title
        $titleFromSlug = Str::title(str_replace('-', ' ', $normalizedSlug));
        $game = Game::whereRaw('LOWER(title) = ?', [strtolower($titleFromSlug)])->first();
        if ($game) {
            return $game;
        }

        // Try partial match on title
        $game = Game::whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($titleFromSlug) . '%'])->first();
        if ($game) {
            return $game;
        }

        return null;
    }

    /**
     * Normalize a slug for comparison
     */
    protected function normalizeSlug(string $slug): string
    {
        // Remove common suffixes
        $suffixes = ['-ps5', '-ps4', '-ps3', '-trophy-guide', '-guide', '-roadmap'];

        $slug = strtolower($slug);
        foreach ($suffixes as $suffix) {
            if (str_ends_with($slug, $suffix)) {
                $slug = substr($slug, 0, -strlen($suffix));
            }
        }

        // Remove "the-" prefix
        $slug = preg_replace('#^the-#', '', $slug);

        return $slug;
    }

    protected function getUrlField(string $source): ?string
    {
        return match ($source) {
            'psnprofiles' => 'psnprofiles_url',
            'playstationtrophies' => 'playstationtrophies_url',
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

    protected function storeUnmatchedUrl(string $url, string $source, string $slug): void
    {
        if (TrophyGuideUrl::where('url', $url)->exists()) {
            return;
        }

        TrophyGuideUrl::create([
            'source' => $source,
            'url' => $url,
            'extracted_slug' => $slug,
            'extracted_title' => Str::title(str_replace('-', ' ', $slug)),
            'game_id' => null,
            'matched_at' => null,
        ]);
    }

    /**
     * Original mode: Search for specific games missing URLs
     */
    protected function searchMissingGames(string $source, bool $dryRun): void
    {
        $limit = (int) $this->option('limit');
        $delay = (float) $this->option('delay');

        $urlField = $this->getUrlField($source);
        if (!$urlField) {
            $this->error("Unknown source: {$source}");
            return;
        }

        $siteDomain = match ($source) {
            'psnprofiles' => 'psnprofiles.com',
            'playstationtrophies' => 'playstationtrophies.org',
        };

        // Find games missing this URL
        $games = Game::whereNull($urlField)
            ->orderBy('title')
            ->limit($limit)
            ->get();

        if ($games->isEmpty()) {
            $this->info("[{$source}] All games already have URLs!");
            return;
        }

        $this->info("[{$source}] Searching for {$games->count()} games...");
        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        foreach ($games as $game) {
            $url = $this->searchForGame($game, $siteDomain, $source);

            if ($url) {
                $this->found++;

                if (!$dryRun) {
                    $game->$urlField = $url;
                    $game->save();
                    $this->storeTrophyUrl($url, $source, $game);
                }
            } else {
                $this->notMatched++;
            }

            $progressBar->advance();

            if ($delay > 0) {
                usleep((int) ($delay * 1000000));
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function searchForGame(Game $game, string $siteDomain, string $source): ?string
    {
        $searchQuery = "site:{$siteDomain} \"{$game->title}\" trophy guide";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            ])->get('https://html.duckduckgo.com/html/', [
                'q' => $searchQuery,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $urls = $this->extractGuideUrlsFromHtml($html, $source);

            return $urls[0] ?? null;

        } catch (\Exception $e) {
            $this->errors++;
            return null;
        }
    }
}
