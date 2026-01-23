<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportPSNProfilesFromHtml extends Command
{
    protected $signature = 'trophy:import-html
                            {file? : Path to HTML file (or use --paste for interactive)}
                            {--paste : Paste HTML directly (end with EOF on a new line)}
                            {--dry-run : Show extracted URLs without saving}';

    protected $description = 'Extract PSNProfiles guide URLs from pasted HTML source';

    public function handle(): int
    {
        $file = $this->argument('file');
        $paste = $this->option('paste');
        $dryRun = $this->option('dry-run');

        $this->info("PSNProfiles HTML Importer");
        $this->info("=========================");
        $this->newLine();

        // Get HTML content
        $html = '';

        if ($file) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                return Command::FAILURE;
            }
            $html = file_get_contents($file);
            $this->info("Loaded HTML from: {$file}");
        } elseif ($paste) {
            $this->info("Paste the HTML source below, then type 'EOF' on a new line and press Enter:");
            $this->newLine();

            $lines = [];
            while (true) {
                $line = fgets(STDIN);
                if (trim($line) === 'EOF') {
                    break;
                }
                $lines[] = $line;
            }
            $html = implode('', $lines);
            $this->info("Received " . strlen($html) . " characters of HTML");
        } else {
            // Check if there's piped input
            if (!posix_isatty(STDIN)) {
                $html = file_get_contents('php://stdin');
                $this->info("Received " . strlen($html) . " characters from stdin");
            } else {
                $this->error("Please provide a file path, use --paste, or pipe HTML content");
                $this->line("Examples:");
                $this->line("  php artisan trophy:import-html page1.html");
                $this->line("  php artisan trophy:import-html --paste");
                $this->line("  cat page1.html | php artisan trophy:import-html");
                return Command::FAILURE;
            }
        }

        if (empty($html)) {
            $this->error("No HTML content provided");
            return Command::FAILURE;
        }

        // Extract guide URLs
        $urls = $this->extractGuideUrls($html);

        if (empty($urls)) {
            $this->warn("No guide URLs found in the HTML");
            return Command::SUCCESS;
        }

        $this->info("Found " . count($urls) . " guide URLs");
        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN - showing first 20 URLs:");
            foreach (array_slice($urls, 0, 20) as $url) {
                $this->line("  {$url}");
            }
            if (count($urls) > 20) {
                $this->line("  ... and " . (count($urls) - 20) . " more");
            }
            return Command::SUCCESS;
        }

        // Process URLs
        $new = 0;
        $existing = 0;
        $matched = 0;

        $progressBar = $this->output->createProgressBar(count($urls));
        $progressBar->start();

        foreach ($urls as $url) {
            $fullUrl = "https://psnprofiles.com{$url}";

            // Check if already exists
            if (TrophyGuideUrl::where('url', $fullUrl)->exists()) {
                $existing++;
                $progressBar->advance();
                continue;
            }

            // Extract slug from URL
            $slug = $this->extractSlug($url);

            if (!$slug) {
                $progressBar->advance();
                continue;
            }

            // Try to find matching game
            $game = $this->findMatchingGame($slug);

            // Create trophy URL record
            $trophyUrl = TrophyGuideUrl::create([
                'source' => 'psnprofiles',
                'url' => $fullUrl,
                'extracted_slug' => $slug,
                'extracted_title' => Str::title(str_replace('-', ' ', $slug)),
                'game_id' => $game?->id,
                'matched_at' => $game ? now() : null,
            ]);

            // Update game if matched
            if ($game && empty($game->psnprofiles_url)) {
                $game->psnprofiles_url = $fullUrl;
                $game->save();
                $matched++;
            }

            $new++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Import complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['New URLs Added', $new],
                ['Already Existed', $existing],
                ['Matched to Games', $matched],
                ['Total PSNProfiles URLs', TrophyGuideUrl::where('source', 'psnprofiles')->count()],
            ]
        );

        return Command::SUCCESS;
    }

    protected function extractGuideUrls(string $html): array
    {
        $urls = [];

        // Match guide URLs: /guide/12345-game-name-trophy-guide
        // Also match walkthrough URLs if present
        if (preg_match_all('#href="(/guide/\d+-[^"]+)"#i', $html, $matches)) {
            $urls = array_unique($matches[1]);
        }

        return $urls;
    }

    protected function extractSlug(string $url): ?string
    {
        // URL format: /guide/12345-game-name-trophy-guide
        if (preg_match('#/guide/\d+-(.+)$#i', $url, $matches)) {
            $slug = $matches[1];

            // Remove common suffixes
            $slug = preg_replace('#-(trophy-guide|walkthrough|platinum-walkthrough)$#i', '', $slug);

            return $slug;
        }

        return null;
    }

    protected function findMatchingGame(string $slug): ?Game
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        // Try exact slug match
        $game = Game::where('slug', $slug)->first();
        if ($game) return $game;

        // Try normalized match
        $game = Game::whereRaw('LOWER(REPLACE(slug, "-", "")) = ?', [str_replace('-', '', $normalizedSlug)])->first();
        if ($game) return $game;

        // Try title match
        $titleFromSlug = Str::title(str_replace('-', ' ', $normalizedSlug));
        $game = Game::whereRaw('LOWER(title) = ?', [strtolower($titleFromSlug)])->first();
        if ($game) return $game;

        return null;
    }

    protected function normalizeSlug(string $slug): string
    {
        $suffixes = ['-ps5', '-ps4', '-ps3', '-vita', '-psvr'];

        $slug = strtolower($slug);
        foreach ($suffixes as $suffix) {
            if (str_ends_with($slug, $suffix)) {
                $slug = substr($slug, 0, -strlen($suffix));
            }
        }

        $slug = preg_replace('#^the-#', '', $slug);

        return $slug;
    }
}
