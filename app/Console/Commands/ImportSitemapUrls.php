<?php

namespace App\Console\Commands;

use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportSitemapUrls extends Command
{
    protected $signature = 'sitemap:import
                            {file : Path to the sitemap XML file}
                            {--source=playstationtrophies : Source identifier (playstationtrophies, powerpyx, etc.)}
                            {--pattern= : URL pattern to filter (e.g., "/game/")}
                            {--dry-run : Show what would be imported without saving}';

    protected $description = 'Import URLs from a sitemap XML file into trophy_guide_urls';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $source = $this->option('source');
        $pattern = $this->option('pattern');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Reading sitemap from: {$filePath}");
        $this->info("Source: {$source}");

        $content = file_get_contents($filePath);
        $urls = $this->extractUrls($content);

        $this->info("Found " . count($urls) . " total URLs in sitemap");

        // Filter URLs if pattern specified
        if ($pattern) {
            $urls = array_filter($urls, fn($url) => str_contains($url, $pattern));
            $this->info("Filtered to " . count($urls) . " URLs matching pattern: {$pattern}");
        }

        if (empty($urls)) {
            $this->warn("No URLs found to import.");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("\n[DRY RUN] Would import the following URLs:\n");
            foreach (array_slice($urls, 0, 20) as $url) {
                $slug = $this->extractSlug($url, $source);
                $title = $this->slugToTitle($slug);
                $this->line("  - {$url}");
                $this->line("    Slug: {$slug}, Title: {$title}");
            }
            if (count($urls) > 20) {
                $this->info("\n... and " . (count($urls) - 20) . " more URLs");
            }
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($urls as $url) {
            try {
                $slug = $this->extractSlug($url, $source);
                $title = $this->slugToTitle($slug);

                $result = TrophyGuideUrl::updateOrCreate(
                    ['url' => $url],
                    [
                        'source' => $source,
                        'extracted_slug' => $slug,
                        'extracted_title' => $title,
                    ]
                );

                if ($result->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing {$url}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import complete!");
        $this->info("  New URLs imported: {$imported}");
        $this->info("  Existing URLs updated: {$skipped}");

        if (!empty($errors)) {
            $this->warn("\nErrors encountered:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->warn("  ... and " . (count($errors) - 10) . " more errors");
            }
        }

        return self::SUCCESS;
    }

    protected function extractUrls(string $xml): array
    {
        $urls = [];

        // Use regex to extract URLs from <loc> tags
        // This handles both standard sitemaps and sitemap indexes
        preg_match_all('/<loc>\s*(https?:\/\/[^<\s]+)\s*<\/loc>/i', $xml, $matches);

        if (!empty($matches[1])) {
            $urls = array_map('trim', $matches[1]);
        }

        return array_unique($urls);
    }

    protected function extractSlug(string $url, string $source): string
    {
        $parsed = parse_url($url, PHP_URL_PATH);
        $path = trim($parsed, '/');

        return match ($source) {
            'playstationtrophies' => $this->extractPlaystationTrophiesSlug($path),
            'powerpyx' => $this->extractPowerPyxSlug($path),
            default => $this->extractGenericSlug($path),
        };
    }

    protected function extractPlaystationTrophiesSlug(string $path): string
    {
        // URLs like: game/atelier-meruru-the-apprentice-of-arland/guide/
        // Extract: atelier-meruru-the-apprentice-of-arland
        $parts = explode('/', $path);

        // Find "game" segment and get the next part
        $gameIndex = array_search('game', $parts);
        if ($gameIndex !== false && isset($parts[$gameIndex + 1])) {
            return $parts[$gameIndex + 1];
        }

        // Fallback to last non-empty segment
        return $this->extractGenericSlug($path);
    }

    protected function extractPowerPyxSlug(string $path): string
    {
        // PowerPyx URLs vary, extract meaningful slug
        $parts = array_filter(explode('/', $path));

        // Remove common suffixes like "trophy-guide", "walkthrough"
        $parts = array_filter($parts, fn($p) => !in_array($p, ['trophy-guide', 'walkthrough', 'guide']));

        return end($parts) ?: $path;
    }

    protected function extractGenericSlug(string $path): string
    {
        $parts = array_filter(explode('/', $path));
        return end($parts) ?: $path;
    }

    protected function slugToTitle(string $slug): string
    {
        // Convert slug to human-readable title
        // atelier-meruru-the-apprentice-of-arland -> Atelier Meruru The Apprentice Of Arland
        return Str::title(str_replace('-', ' ', $slug));
    }
}
