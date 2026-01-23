<?php

namespace App\Services\TrophyScrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PowerPyxScraper implements TrophyScraperInterface
{
    protected const BASE_URL = 'https://www.powerpyx.com';

    public function getSourceName(): string
    {
        return 'PowerPyx';
    }

    /**
     * Find a guide URL by searching Google for the game on PowerPyx
     */
    public function findGuideUrl(string $gameTitle): ?string
    {
        // Generate the expected URL slug
        $slug = $this->generateSlug($gameTitle);
        $expectedUrl = self::BASE_URL . '/' . $slug . '-trophy-guide-roadmap/';

        // Try the direct URL first
        $response = Http::timeout(10)->get($expectedUrl);
        if ($response->successful()) {
            return $expectedUrl;
        }

        // Try without "roadmap" suffix
        $altUrl = self::BASE_URL . '/' . $slug . '-trophy-guide/';
        $response = Http::timeout(10)->get($altUrl);
        if ($response->successful()) {
            return $altUrl;
        }

        return null;
    }

    /**
     * Generate a URL slug from game title
     */
    protected function generateSlug(string $gameTitle): string
    {
        // Remove special characters and convert to lowercase
        $slug = Str::slug($gameTitle);

        // Common replacements for game titles
        $slug = str_replace(['--'], ['-'], $slug);

        return $slug;
    }

    /**
     * Scrape trophy data from a PowerPyx guide URL
     */
    public function scrapeGuide(string $url): array
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch PowerPyx guide: HTTP {$response->status()}");
        }

        $html = $response->body();

        return [
            'difficulty' => $this->extractDifficulty($html),
            'time_min' => $this->extractTimeMin($html),
            'time_max' => $this->extractTimeMax($html),
            'playthroughs_required' => $this->extractPlaythroughs($html),
            'has_online_trophies' => $this->hasOnlineTrophies($html),
            'online_trophies_count' => $this->extractOnlineTrophiesCount($html),
            'missable_trophies' => $this->hasMissableTrophies($html),
            'missable_trophies_count' => $this->extractMissableTrophiesCount($html),
            'guide_url' => $url,
        ];
    }

    /**
     * Scrape by searching for game title
     */
    public function scrapeByTitle(string $gameTitle): ?array
    {
        $url = $this->findGuideUrl($gameTitle);

        if (!$url) {
            return null;
        }

        try {
            return $this->scrapeGuide($url);
        } catch (\Exception $e) {
            Log::warning("PowerPyx scrape failed for '{$gameTitle}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Extract difficulty rating (1-10)
     * For ranges like "6-7/10", returns the higher value
     */
    protected function extractDifficulty(string $html): ?int
    {
        // Pattern handles ranges like "6-7/10" and non-breaking spaces
        if (preg_match('/Estimated\s+trophy\s+difficulty[^0-9]*(\d+)(?:\s*[-–]\s*(\d+))?\s*\/\s*10/i', $html, $matches)) {
            // If it's a range, take the higher value
            if (!empty($matches[2])) {
                return max((int) $matches[1], (int) $matches[2]);
            }
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract minimum time to platinum
     */
    protected function extractTimeMin(string $html): ?int
    {
        // Pattern: "40-60 hours" with various prefixes
        if (preg_match('/(?:time|platinum)[^0-9]*(\d+)\s*[-–~]\s*(\d+)\s*(?:hours?|hrs?)/i', $html, $matches)) {
            return (int) $matches[1];
        }

        // Single value: "~50 hours"
        if (preg_match('/(?:time|platinum)[^0-9]*[~≈]?\s*(\d+)\s*(?:hours?|hrs?)/i', $html, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract maximum time to platinum
     */
    protected function extractTimeMax(string $html): ?int
    {
        // Pattern: "40-60 hours"
        if (preg_match('/(?:time|platinum)[^0-9]*(\d+)\s*[-–~]\s*(\d+)\s*(?:hours?|hrs?)/i', $html, $matches)) {
            return (int) $matches[2];
        }

        // Single value - use same as min
        if (preg_match('/(?:time|platinum)[^0-9]*[~≈]?\s*(\d+)\s*(?:hours?|hrs?)/i', $html, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract playthroughs required
     */
    protected function extractPlaythroughs(string $html): ?int
    {
        // Use [^0-9]* to handle non-breaking spaces and other chars between label and number
        if (preg_match('/Minimum\s+Playthroughs?[^0-9]*(\d+)/i', $html, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Check if game has online trophies
     */
    protected function hasOnlineTrophies(string $html): ?bool
    {
        // Use [^0-9]* to handle non-breaking spaces
        if (preg_match('/Online\s+Trophies?[^0-9]*(\d+)/i', $html, $matches)) {
            return ((int) $matches[1]) > 0;
        }

        return null;
    }

    /**
     * Extract online trophies count
     */
    protected function extractOnlineTrophiesCount(string $html): ?int
    {
        if (preg_match('/Online\s+Trophies?[^0-9]*(\d+)/i', $html, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Check if game has missable trophies
     */
    protected function hasMissableTrophies(string $html): ?bool
    {
        // Use [^0-9]* to handle non-breaking spaces
        if (preg_match('/(?:Number\s+of\s+)?[Mm]issable\s+[Tt]rophies?[^0-9]*(\d+)/i', $html, $matches)) {
            return ((int) $matches[1]) > 0;
        }

        return null;
    }

    /**
     * Extract missable trophies count
     */
    protected function extractMissableTrophiesCount(string $html): ?int
    {
        if (preg_match('/(?:Number\s+of\s+)?[Mm]issable\s+[Tt]rophies?[^0-9]*(\d+)/i', $html, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
