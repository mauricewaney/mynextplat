<?php

namespace App\Console\Commands;

use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchTrophySitemaps extends Command
{
    protected $signature = 'trophy:fetch-sitemap
                            {--source=all : Source to fetch (powerpyx, playstationtrophies, psnprofiles, all)}
                            {--file= : Path to local sitemap XML file (for Cloudflare-blocked sites)}
                            {--fresh : Clear existing URLs for this source before importing}
                            {--use-scraper : Use ScraperAPI to bypass Cloudflare (requires SCRAPER_API_KEY in .env)}';

    protected $description = 'Fetch trophy guide URLs from sitemaps and store them for later matching';

    public function handle(): int
    {
        $source = $this->option('source');
        $file = $this->option('file');
        $fresh = $this->option('fresh');
        $useScraper = $this->option('use-scraper');

        $this->info("Trophy Sitemap Fetcher");
        $this->info("======================");
        $this->newLine();

        if ($useScraper && !config('services.scraper_api.key')) {
            $this->error("ScraperAPI key not found. Add SCRAPER_API_KEY to your .env file.");
            $this->line("Get a free API key at: https://www.scraperapi.com/");
            return Command::FAILURE;
        }

        if ($source === 'all') {
            $this->fetchPowerPyx($fresh);
            $this->newLine();
            $this->fetchPlayStationTrophies($file, $fresh, $useScraper);
            $this->newLine();
            $this->fetchPSNProfiles($file, $fresh, $useScraper);
        } elseif ($source === 'powerpyx') {
            $this->fetchPowerPyx($fresh);
        } elseif ($source === 'playstationtrophies') {
            $this->fetchPlayStationTrophies($file, $fresh, $useScraper);
        } elseif ($source === 'psnprofiles') {
            $this->fetchPSNProfiles($file, $fresh, $useScraper);
        } else {
            $this->error("Unknown source: {$source}");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->showStats();

        return Command::SUCCESS;
    }

    protected function fetchPowerPyx(bool $fresh): void
    {
        $this->info("Fetching PowerPyx sitemaps...");

        if ($fresh) {
            $deleted = TrophyGuideUrl::where('source', 'powerpyx')->delete();
            $this->warn("Cleared {$deleted} existing PowerPyx URLs");
        }

        $totalNew = 0;
        $totalSkipped = 0;

        for ($i = 1; $i <= 20; $i++) {
            $url = "https://www.powerpyx.com/post-sitemap{$i}.xml";

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Googlebot/2.1'])
                    ->get($url);

                if (!$response->successful()) {
                    break;
                }

                $urls = $this->extractUrlsFromXml($response->body(), 'trophy-guide');
                $stats = $this->storeUrls($urls, 'powerpyx');

                $totalNew += $stats['new'];
                $totalSkipped += $stats['skipped'];

                $this->line("  Sitemap {$i}: {$stats['new']} new, {$stats['skipped']} existing");

            } catch (\Exception $e) {
                break;
            }
        }

        $this->info("PowerPyx complete: {$totalNew} new URLs stored, {$totalSkipped} already existed");
    }

    protected function fetchPlayStationTrophies(?string $file, bool $fresh, bool $useScraper = false): void
    {
        $this->info("Fetching PlayStationTrophies sitemap...");

        if ($fresh) {
            $deleted = TrophyGuideUrl::where('source', 'playstationtrophies')->delete();
            $this->warn("Cleared {$deleted} existing PlayStationTrophies URLs");
        }

        $content = null;

        // Option 1: Use local file
        if ($file) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                return;
            }
            $this->line("  Loading from local file: {$file}");
            $content = str_ends_with($file, '.gz')
                ? gzdecode(file_get_contents($file))
                : file_get_contents($file);
        }
        // Option 2: Use ScraperAPI
        elseif ($useScraper) {
            $this->line("  Using ScraperAPI to bypass Cloudflare...");
            $content = $this->fetchWithScraperApi('https://www.playstationtrophies.org/sitemaps/sitemap-guides.xml');
        }
        // Option 3: Manual instructions
        else {
            $this->warn("PlayStationTrophies.org is behind Cloudflare.");
            $this->newLine();
            $this->line("Options:");
            $this->line("  1. Use ScraperAPI (free tier): --use-scraper");
            $this->line("  2. Download manually and use: --file=/path/to/sitemap.xml");
            $this->newLine();
            $this->line("Sitemap URL: https://www.playstationtrophies.org/sitemaps/sitemap-guides.xml.gz");
            return;
        }

        if (!$content) {
            $this->error("Failed to fetch sitemap content");
            return;
        }

        $urls = $this->extractUrlsFromXml($content, '/guide/');
        $stats = $this->storeUrls($urls, 'playstationtrophies');

        $this->info("PlayStationTrophies complete: {$stats['new']} new URLs stored, {$stats['skipped']} already existed");
    }

    protected function fetchPSNProfiles(?string $file, bool $fresh, bool $useScraper = false): void
    {
        $this->info("Fetching PSNProfiles guide sitemap...");

        if ($fresh) {
            $deleted = TrophyGuideUrl::where('source', 'psnprofiles')->delete();
            $this->warn("Cleared {$deleted} existing PSNProfiles URLs");
        }

        $content = null;

        // Option 1: Use local file
        if ($file) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                return;
            }
            $this->line("  Loading from local file: {$file}");
            $content = str_ends_with($file, '.gz')
                ? gzdecode(file_get_contents($file))
                : file_get_contents($file);
        }
        // Option 2: Use ScraperAPI
        elseif ($useScraper) {
            $this->line("  Using ScraperAPI to bypass Cloudflare...");
            // PSNProfiles has multiple sitemaps, try the guides one
            $content = $this->fetchWithScraperApi('https://psnprofiles.com/sitemap/guides.xml');

            if (!$content) {
                // Try alternative sitemap locations
                $this->line("  Trying alternative sitemap location...");
                $content = $this->fetchWithScraperApi('https://psnprofiles.com/sitemap.xml');
            }
        }
        // Option 3: Manual instructions
        else {
            $this->warn("PSNProfiles.com is behind Cloudflare.");
            $this->newLine();
            $this->line("Options:");
            $this->line("  1. Use ScraperAPI (free tier): --use-scraper");
            $this->line("  2. Download manually and use: --file=/path/to/sitemap.xml");
            $this->newLine();
            $this->line("Try: https://psnprofiles.com/sitemap/guides.xml");
            return;
        }

        if (!$content) {
            $this->error("Failed to fetch sitemap content");
            return;
        }

        // PSNProfiles guide URLs look like: https://psnprofiles.com/guide/12345-game-name
        $urls = $this->extractUrlsFromXml($content, '/guide/');
        $stats = $this->storeUrls($urls, 'psnprofiles');

        $this->info("PSNProfiles complete: {$stats['new']} new URLs stored, {$stats['skipped']} already existed");
    }

    /**
     * Fetch URL using ScraperAPI to bypass Cloudflare
     */
    protected function fetchWithScraperApi(string $url): ?string
    {
        $apiKey = config('services.scraper_api.key');

        if (!$apiKey) {
            $this->error("ScraperAPI key not configured");
            return null;
        }

        try {
            $response = Http::timeout(60)->get('https://api.scraperapi.com', [
                'api_key' => $apiKey,
                'url' => $url,
                'render' => 'false', // Don't need JS rendering for XML
            ]);

            if ($response->successful()) {
                $this->line("  ScraperAPI request successful");
                return $response->body();
            }

            $this->error("  ScraperAPI returned status: " . $response->status());
            $this->line("  Response: " . Str::limit($response->body(), 200));
            return null;

        } catch (\Exception $e) {
            $this->error("  ScraperAPI error: " . $e->getMessage());
            return null;
        }
    }

    protected function extractUrlsFromXml(string $xml, string $filter): array
    {
        $urls = [];

        preg_match_all('/<loc>([^<]+)<\/loc>/', $xml, $matches);

        foreach ($matches[1] as $url) {
            if (stripos($url, $filter) !== false) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    protected function storeUrls(array $urls, string $source): array
    {
        $new = 0;
        $skipped = 0;

        foreach ($urls as $url) {
            $extracted = $this->extractGameInfo($url, $source);

            if (!$extracted) {
                continue;
            }

            $existing = TrophyGuideUrl::where('url', $url)->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            TrophyGuideUrl::create([
                'source' => $source,
                'url' => $url,
                'extracted_slug' => $extracted['slug'],
                'extracted_title' => $extracted['title'],
            ]);

            $new++;
        }

        return ['new' => $new, 'skipped' => $skipped];
    }

    protected function extractGameInfo(string $url, string $source): ?array
    {
        $slug = match ($source) {
            'powerpyx' => $this->extractPowerPyxSlug($url),
            'playstationtrophies' => $this->extractPlayStationTrophiesSlug($url),
            'psnprofiles' => $this->extractPSNProfilesSlug($url),
            default => null,
        };

        if (!$slug) {
            return null;
        }

        // Convert slug to human-readable title
        $title = Str::title(str_replace('-', ' ', $slug));

        return [
            'slug' => $slug,
            'title' => $title,
        ];
    }

    protected function extractPowerPyxSlug(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');

        // Handle old format with /guides/ prefix
        $path = preg_replace('#^guides/#', '', $path);

        // Remove .html extension if present
        $path = preg_replace('#\.html$#', '', $path);

        // Remove trophy-guide-roadmap or trophy-guide suffix
        $slug = preg_replace('#-trophy-guide(-roadmap)?$#i', '', $path);

        // Skip individual trophy guides (contain specific trophy names)
        // We want main game guides, not "game-name-specific-trophy-trophy-guide"
        // Main guides typically end with just "trophy-guide-roadmap"
        if (!preg_match('#trophy-guide-roadmap#i', $url) && !preg_match('#trophy-guide\.html$#i', $url)) {
            // This might be an individual trophy guide, skip it
            // Unless it's the only guide format for that game
            if (preg_match('#-trophy-guide$#i', $url)) {
                return $slug;
            }
            return null;
        }

        return $slug ?: null;
    }

    protected function extractPlayStationTrophiesSlug(string $url): ?string
    {
        if (preg_match('#/game/([^/]+)/guide/#', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractPSNProfilesSlug(string $url): ?string
    {
        // PSNProfiles guide URLs: https://psnprofiles.com/guide/12345-game-name-trophy-guide
        if (preg_match('#/guide/\d+-([^/]+?)(?:-trophy-guide)?$#i', $url, $matches)) {
            return $matches[1];
        }
        // Fallback: just get everything after /guide/
        if (preg_match('#/guide/(.+)$#', $url, $matches)) {
            // Remove numeric prefix
            $slug = preg_replace('#^\d+-#', '', $matches[1]);
            // Remove common suffixes
            $slug = preg_replace('#-trophy-guide$#i', '', $slug);
            return $slug ?: null;
        }
        return null;
    }

    protected function showStats(): void
    {
        $this->info("Current trophy URL database:");
        $this->table(
            ['Source', 'Total URLs', 'Matched', 'Unmatched'],
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
    }
}
