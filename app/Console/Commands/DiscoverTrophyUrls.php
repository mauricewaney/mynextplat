<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscoverTrophyUrls extends Command
{
    protected $signature = 'trophy:discover-urls
                            {--source=playstationtrophies : Source to discover (playstationtrophies)}
                            {--limit=100 : Number of games to check per batch}
                            {--all : Check all games without URLs for this source}';

    protected $description = 'Auto-discover trophy guide URLs for games';

    public function handle(): int
    {
        $source = $this->option('source');
        $limit = (int) $this->option('limit');
        $all = $this->option('all');

        $this->info("Trophy URL Discovery");
        $this->info("====================");
        $this->newLine();

        return match ($source) {
            'playstationtrophies' => $this->discoverPlayStationTrophies($limit, $all),
            default => $this->error("Unknown source: {$source}") ?? Command::FAILURE,
        };
    }

    /**
     * Discover PlayStationTrophies.org URLs
     * Pattern: https://www.playstationtrophies.org/game/{slug}/guide/
     */
    protected function discoverPlayStationTrophies(int $limit, bool $all): int
    {
        $query = Game::whereNull('playstationtrophies_url');

        if (!$all) {
            $query->limit($limit);
        }

        // Prioritize newer games (more likely to have guides)
        $games = $query->orderBy('release_date', 'desc')->get();

        if ($games->isEmpty()) {
            $this->info("All games already have PlayStationTrophies URLs or no games found.");
            return Command::SUCCESS;
        }

        $this->info("Checking {$games->count()} games for PlayStationTrophies.org guides...");
        $this->warn("Note: Many games won't have guides - that's expected.");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        $stats = ['found' => 0, 'not_found' => 0, 'errors' => 0];

        foreach ($games as $game) {
            try {
                $url = $this->tryPlayStationTrophiesUrl($game);

                if ($url) {
                    $game->playstationtrophies_url = $url;
                    $game->save();
                    $stats['found']++;
                } else {
                    $stats['not_found']++;
                }

                // Rate limiting - 300ms between requests
                usleep(300000);

            } catch (\Exception $e) {
                $stats['errors']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Discovery complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['URLs Found', $stats['found']],
                ['Not Found', $stats['not_found']],
                ['Errors', $stats['errors']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Try to find a PlayStationTrophies.org guide URL for a game
     */
    protected function tryPlayStationTrophiesUrl(Game $game): ?string
    {
        // Try different slug variations
        $slugVariations = $this->generateSlugVariations($game->title, $game->slug);

        foreach ($slugVariations as $slug) {
            $url = "https://www.playstationtrophies.org/game/{$slug}/guide/";

            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; TrophyGuideBot/1.0)'])
                    ->head($url);

                if ($response->successful()) {
                    return $url;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Generate slug variations to try
     */
    protected function generateSlugVariations(string $title, ?string $existingSlug): array
    {
        $variations = [];

        // Use existing slug if available
        if ($existingSlug) {
            $variations[] = $existingSlug;
        }

        // Generate from title
        $baseSlug = Str::slug($title);
        $variations[] = $baseSlug;

        // Common variations
        // Remove common suffixes
        $cleanTitle = preg_replace('/\s*\(?(PS[345]|PlayStation\s*[345]|Remastered|Remake|Director\'s Cut|GOTY|Game of the Year)\)?$/i', '', $title);
        $variations[] = Str::slug($cleanTitle);

        // Replace & with and
        $variations[] = Str::slug(str_replace('&', 'and', $title));

        // Remove "The" prefix
        $variations[] = Str::slug(preg_replace('/^The\s+/i', '', $title));

        // Remove colons and what follows for sequels (e.g., "God of War: Ragnarok" -> "god-of-war-ragnarok")
        $variations[] = Str::slug(str_replace(':', '', $title));

        return array_unique(array_filter($variations));
    }
}
