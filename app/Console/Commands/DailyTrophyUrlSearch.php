<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DailyTrophyUrlSearch extends Command
{
    protected $signature = 'trophy:daily-search
                            {--dry-run : Show results without saving}';

    protected $description = 'Search for new trophy guides published in the last 24 hours (runs daily)';

    protected array $sources = [
        'psnprofiles' => [
            'domain' => 'psnprofiles.com',
            'pattern' => '#https?://psnprofiles\.com/guide/(\d+)-([a-z0-9-]+)#i',
            'field' => 'psnprofiles_url',
        ],
        'playstationtrophies' => [
            'domain' => 'playstationtrophies.org',
            'pattern' => '#https?://(?:www\.)?playstationtrophies\.org/game/([a-z0-9-]+)/guide/?#i',
            'field' => 'playstationtrophies_url',
        ],
        'powerpyx' => [
            'domain' => 'powerpyx.com',
            'pattern' => '#https?://(?:www\.)?powerpyx\.com/([a-z0-9-]+)-trophy-guide#i',
            'field' => 'powerpyx_url',
        ],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $totalFound = 0;
        $totalMatched = 0;

        $this->info('Daily Trophy URL Search');
        $this->info('=======================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved');
            $this->newLine();
        }

        foreach ($this->sources as $source => $config) {
            $this->line("Searching {$source}...");

            $urls = $this->searchSource($config['domain']);

            if (empty($urls)) {
                $this->line("  No new guides found");
                continue;
            }

            $this->line("  Found " . count($urls) . " URLs");

            foreach ($urls as $url) {
                // Extract guide URL from search result
                if (!preg_match($config['pattern'], $url, $matches)) {
                    continue;
                }

                $guideUrl = $matches[0];
                $slug = $matches[count($matches) - 1]; // Last capture group is the slug

                // Skip if already exists
                if (TrophyGuideUrl::where('url', $guideUrl)->exists()) {
                    continue;
                }

                $totalFound++;

                // Try to match to a game
                $game = $this->findGame($slug);

                if (!$dryRun) {
                    // Store the URL
                    TrophyGuideUrl::create([
                        'source' => $source,
                        'url' => $guideUrl,
                        'extracted_slug' => $slug,
                        'extracted_title' => str_replace('-', ' ', $slug),
                        'game_id' => $game?->id,
                        'matched_at' => $game ? now() : null,
                    ]);

                    // Update game if matched
                    if ($game && empty($game->{$config['field']})) {
                        $game->{$config['field']} = $guideUrl;
                        $game->save();
                        $totalMatched++;
                    }
                }

                $status = $game ? "<info>MATCHED: {$game->title}</info>" : '<comment>unmatched</comment>';
                $this->line("  + {$slug} - {$status}");
            }

            // Small delay between sources
            sleep(2);
        }

        $this->newLine();
        $this->info("Done! Found {$totalFound} new URLs, matched {$totalMatched} to games.");

        return Command::SUCCESS;
    }

    protected function searchSource(string $domain): array
    {
        $query = "site:{$domain} trophy guide";

        try {
            // DuckDuckGo with last 24 hours filter
            $response = Http::timeout(30)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            ])->get('https://html.duckduckgo.com/html/', [
                'q' => $query,
                'df' => 'd', // Last day
            ]);

            if (!$response->successful()) {
                $this->error("  Search failed: HTTP {$response->status()}");
                return [];
            }

            $html = $response->body();

            // Extract all URLs from search results
            $urls = [];
            if (preg_match_all('#https?://[^\s"<>]+' . preg_quote($domain, '#') . '[^\s"<>]*#i', $html, $matches)) {
                $urls = array_unique($matches[0]);
            }

            return $urls;

        } catch (\Exception $e) {
            $this->error("  Search error: " . $e->getMessage());
            return [];
        }
    }

    protected function findGame(string $slug): ?Game
    {
        // Clean up slug
        $slug = strtolower($slug);
        $slug = preg_replace('#-(trophy-guide|walkthrough|platinum-walkthrough|guide)$#i', '', $slug);
        $slug = preg_replace('#-(ps5|ps4|ps3|vita|psvr|psvr2)$#i', '', $slug);

        // Try exact slug match
        $game = Game::where('slug', $slug)->first();
        if ($game) return $game;

        // Try without hyphens
        $normalized = str_replace('-', '', $slug);
        $game = Game::whereRaw('LOWER(REPLACE(slug, "-", "")) = ?', [$normalized])->first();
        if ($game) return $game;

        // Try title match
        $title = str_replace('-', ' ', $slug);
        $game = Game::whereRaw('LOWER(title) = ?', [$title])->first();
        if ($game) return $game;

        return null;
    }
}
