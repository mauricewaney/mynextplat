<?php

namespace App\Services;

use App\Models\Game;
use App\Services\TrophyScrapers\PowerPyxScraper;
use App\Services\TrophyScrapers\TrophyScraperInterface;
use Illuminate\Support\Facades\Log;

class TrophyScraperService
{
    /** @var TrophyScraperInterface[] */
    protected array $scrapers = [];

    public function __construct()
    {
        // Register available scrapers in priority order
        $this->scrapers = [
            new PowerPyxScraper(),
            // Add more scrapers here as they become available
            // new PSNProfilesScraper(),
            // new PlayStationTrophiesScraper(),
        ];
    }

    /**
     * Get all registered scrapers
     *
     * @return TrophyScraperInterface[]
     */
    public function getScrapers(): array
    {
        return $this->scrapers;
    }

    /**
     * Scrape trophy data for a game from all available sources
     */
    public function scrapeForGame(Game $game): array
    {
        $results = [];

        foreach ($this->scrapers as $scraper) {
            $sourceName = $scraper->getSourceName();

            try {
                $data = $scraper->scrapeByTitle($game->title);

                if ($data) {
                    $results[$sourceName] = [
                        'success' => true,
                        'data' => $data,
                    ];
                } else {
                    $results[$sourceName] = [
                        'success' => false,
                        'error' => 'Guide not found',
                    ];
                }
            } catch (\Exception $e) {
                $results[$sourceName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                Log::warning("Trophy scrape failed for {$game->title} from {$sourceName}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Scrape and update a game with trophy data
     * Uses the first successful scraper's data
     */
    public function scrapeAndUpdateGame(Game $game): array
    {
        $results = $this->scrapeForGame($game);
        $updated = false;
        $source = null;

        foreach ($results as $sourceName => $result) {
            if ($result['success'] && !empty($result['data'])) {
                $data = $result['data'];

                // Update game fields
                if ($data['difficulty'] !== null) {
                    $game->difficulty = $data['difficulty'];
                }
                if ($data['time_min'] !== null) {
                    $game->time_min = $data['time_min'];
                }
                if ($data['time_max'] !== null) {
                    $game->time_max = $data['time_max'];
                }
                if ($data['playthroughs_required'] !== null) {
                    $game->playthroughs_required = $data['playthroughs_required'];
                }
                if ($data['has_online_trophies'] !== null) {
                    $game->has_online_trophies = $data['has_online_trophies'];
                }
                if ($data['missable_trophies'] !== null) {
                    $game->missable_trophies = $data['missable_trophies'];
                }

                // Store the guide URL based on source
                if (!empty($data['guide_url'])) {
                    $urlField = $this->getUrlFieldForSource($sourceName);
                    if ($urlField) {
                        $game->{$urlField} = $data['guide_url'];
                    }
                }

                $updated = true;
                $source = $sourceName;
                break; // Use first successful source
            }
        }

        // Always mark as scraped, even if no guide found (prevents infinite loops)
        $game->last_scraped_at = now();
        $game->save();

        return [
            'updated' => $updated,
            'source' => $source,
            'results' => $results,
        ];
    }

    /**
     * Get the database field for storing a guide URL based on source
     */
    protected function getUrlFieldForSource(string $sourceName): ?string
    {
        return match ($sourceName) {
            'PowerPyx' => 'powerpyx_url',
            'PSNProfiles' => 'psnprofiles_url',
            'PlayStationTrophies' => 'playstationtrophies_url',
            default => null,
        };
    }

    /**
     * Scrape a specific source for a game
     */
    public function scrapeFromSource(Game $game, string $sourceName): ?array
    {
        foreach ($this->scrapers as $scraper) {
            if ($scraper->getSourceName() === $sourceName) {
                return $scraper->scrapeByTitle($game->title);
            }
        }

        return null;
    }
}
