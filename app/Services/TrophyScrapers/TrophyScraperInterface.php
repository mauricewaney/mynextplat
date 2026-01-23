<?php

namespace App\Services\TrophyScrapers;

interface TrophyScraperInterface
{
    /**
     * Get the name of this scraper source
     */
    public function getSourceName(): string;

    /**
     * Search for a game's trophy guide URL
     */
    public function findGuideUrl(string $gameTitle): ?string;

    /**
     * Scrape trophy data from a guide URL
     *
     * @return array{
     *   difficulty: ?int,
     *   time_min: ?int,
     *   time_max: ?int,
     *   playthroughs_required: ?int,
     *   has_online_trophies: ?bool,
     *   online_trophies_count: ?int,
     *   missable_trophies: ?bool,
     *   missable_trophies_count: ?int,
     *   guide_url: string
     * }
     */
    public function scrapeGuide(string $url): array;

    /**
     * Scrape trophy data by searching for the game title
     */
    public function scrapeByTitle(string $gameTitle): ?array;
}
