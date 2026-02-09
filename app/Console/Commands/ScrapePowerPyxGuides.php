<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapePowerPyxGuides extends Command
{
    protected $signature = 'ppx:scrape-guides
                            {--dry-run : Show what would be imported without saving}
                            {--match-only : Only try to match existing unmatched URLs}';

    protected $description = 'Scrape the PowerPyx guides index page to get all guide URLs';

    protected const GUIDES_URL = 'https://www.powerpyx.com/guides/';

    public function handle(): int
    {
        $this->info('PowerPyx Guide Scraper');
        $this->info('======================');
        $this->newLine();

        if ($this->option('match-only')) {
            return $this->matchUnmatched();
        }

        $this->info('Fetching guides from: ' . self::GUIDES_URL);

        try {
            $response = Http::timeout(30)->get(self::GUIDES_URL);

            if (!$response->successful()) {
                $this->error("Failed to fetch guides page: HTTP {$response->status()}");
                return Command::FAILURE;
            }

            $html = $response->body();
            $guides = $this->extractGuides($html);

            $this->info("Found {$guides->count()} guides on PowerPyx");
            $this->newLine();

            if ($this->option('dry-run')) {
                $this->info('DRY RUN - showing first 20 guides:');
                foreach ($guides->take(20) as $guide) {
                    $this->line("  {$guide['title']} => {$guide['url']}");
                }
                return Command::SUCCESS;
            }

            $stats = $this->importGuides($guides);

            $this->newLine();
            $this->info('Import complete!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total guides found', $guides->count()],
                    ['New URLs added', $stats['new']],
                    ['Already existed', $stats['existing']],
                    ['Auto-matched to games', $stats['matched']],
                    ['Unmatched (need manual review)', $stats['unmatched']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Extract guides from the HTML page
     */
    protected function extractGuides(string $html): \Illuminate\Support\Collection
    {
        $guides = collect();

        // PowerPyx guides page has links in format:
        // <a href="https://www.powerpyx.com/game-name-trophy-guide-roadmap/">Game Name</a>
        // or relative: <a href="/game-name-trophy-guide-roadmap/">Game Name</a>

        // Match all trophy guide links
        if (preg_match_all(
            '#<a[^>]+href=["\']([^"\']*(?:trophy-guide|trophy-guide-roadmap)[^"\']*)["\'][^>]*>([^<]+)</a>#i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $url = $match[1];
                $title = html_entity_decode(trim($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // Skip empty titles or obvious non-games
                if (empty($title) || strlen($title) < 2) {
                    continue;
                }

                // Normalize URL
                if (!str_starts_with($url, 'http')) {
                    $url = 'https://www.powerpyx.com' . $url;
                }

                // Extract slug from URL
                $slug = $this->extractSlugFromUrl($url);

                if ($slug) {
                    $guides->push([
                        'title' => $title,
                        'url' => $url,
                        'slug' => $slug,
                    ]);
                }
            }
        }

        // Remove duplicates by URL
        return $guides->unique('url')->values();
    }

    /**
     * Extract slug from PowerPyx URL
     */
    protected function extractSlugFromUrl(string $url): ?string
    {
        // URL format: https://www.powerpyx.com/game-name-trophy-guide-roadmap/
        if (preg_match('#powerpyx\.com/([^/]+)-trophy-guide(?:-roadmap)?/?$#i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Import guides to database and try to match to games
     */
    protected function importGuides(\Illuminate\Support\Collection $guides): array
    {
        $stats = ['new' => 0, 'existing' => 0, 'matched' => 0, 'unmatched' => 0];

        $progressBar = $this->output->createProgressBar($guides->count());
        $progressBar->start();

        foreach ($guides as $guide) {
            // Check if URL already exists
            $existing = TrophyGuideUrl::where('url', $guide['url'])->first();

            if ($existing) {
                $stats['existing']++;
                $progressBar->advance();
                continue;
            }

            // Try to find matching game
            $game = $this->findMatchingGame($guide['slug'], $guide['title']);

            // Create trophy URL record
            TrophyGuideUrl::create([
                'source' => 'powerpyx',
                'url' => $guide['url'],
                'extracted_slug' => $guide['slug'],
                'extracted_title' => $guide['title'],
                'game_id' => $game?->id,
                'matched_at' => $game ? now() : null,
            ]);

            // Update game if matched
            if ($game && empty($game->powerpyx_url)) {
                $game->powerpyx_url = $guide['url'];
                $game->save();
                $stats['matched']++;
            } else {
                $stats['unmatched']++;
            }

            $stats['new']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $stats;
    }

    /**
     * Try to match a guide to a game in the database
     */
    protected function findMatchingGame(string $slug, string $title): ?Game
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        // Try exact slug match
        $game = Game::where('slug', $slug)->first();
        if ($game) return $game;

        // Try normalized slug match
        $game = Game::whereRaw('LOWER(REPLACE(slug, "-", "")) = ?', [str_replace('-', '', $normalizedSlug)])->first();
        if ($game) return $game;

        // Try title match
        $game = Game::whereRaw('LOWER(title) = ?', [strtolower($title)])->first();
        if ($game) return $game;

        // Try fuzzy title match (remove common suffixes)
        $cleanTitle = preg_replace('/\s*\(PS[345]|Vita|PSVR\)\s*$/i', '', $title);
        $game = Game::whereRaw('LOWER(title) = ?', [strtolower($cleanTitle)])->first();
        if ($game) return $game;

        // Try slug-derived title match
        $titleFromSlug = Str::title(str_replace('-', ' ', $normalizedSlug));
        $game = Game::whereRaw('LOWER(title) = ?', [strtolower($titleFromSlug)])->first();
        if ($game) return $game;

        return null;
    }

    /**
     * Normalize a slug for matching
     */
    protected function normalizeSlug(string $slug): string
    {
        $suffixes = ['-ps5', '-ps4', '-ps3', '-vita', '-psvr', '-remastered', '-remake'];

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

    /**
     * Try to match existing unmatched PowerPyx URLs
     */
    protected function matchUnmatched(): int
    {
        $unmatched = TrophyGuideUrl::where('source', 'powerpyx')
            ->whereNull('game_id')
            ->get();

        if ($unmatched->isEmpty()) {
            $this->info('No unmatched PowerPyx URLs to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$unmatched->count()} unmatched PowerPyx URLs. Trying to match...");

        $matched = 0;
        $progressBar = $this->output->createProgressBar($unmatched->count());
        $progressBar->start();

        foreach ($unmatched as $trophyUrl) {
            $game = $this->findMatchingGame($trophyUrl->extracted_slug, $trophyUrl->extracted_title);

            if ($game) {
                $trophyUrl->game_id = $game->id;
                $trophyUrl->matched_at = now();
                $trophyUrl->save();

                if (empty($game->powerpyx_url)) {
                    $game->powerpyx_url = $trophyUrl->url;
                    $game->save();
                }

                $matched++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Matched {$matched} URLs to games.");
        $this->info(($unmatched->count() - $matched) . " URLs still unmatched.");

        return Command::SUCCESS;
    }
}
