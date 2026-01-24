<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LinkNpIdToGame extends Command
{
    protected $signature = 'psn:link-np-id
                            {--game= : Game ID or slug to link}
                            {--np= : NP Communication ID to add}
                            {--list : List unmatched titles from log}
                            {--process : Auto-process unmatched titles with fuzzy matching}
                            {--threshold=70 : Similarity threshold for fuzzy matching (0-100)}';

    protected $description = 'Link NP Communication IDs to games or process unmatched titles';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listUnmatched();
        }

        if ($this->option('process')) {
            return $this->processUnmatched();
        }

        // Manual linking
        $gameInput = $this->option('game');
        $npId = $this->option('np');

        if (!$gameInput || !$npId) {
            $this->error('Both --game and --np are required for manual linking.');
            $this->info('Usage: php artisan psn:link-np-id --game=1234 --np=NPWR12345_00');
            $this->info('       php artisan psn:link-np-id --list');
            $this->info('       php artisan psn:link-np-id --process');
            return 1;
        }

        return $this->linkManually($gameInput, $npId);
    }

    protected function listUnmatched(): int
    {
        $logPath = storage_path('logs/psn_unmatched.txt');

        if (!file_exists($logPath)) {
            $this->warn('No unmatched titles log found. Load a PSN library first.');
            return 0;
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->info("Found " . count($lines) . " unmatched titles:");
        $this->newLine();

        foreach ($lines as $i => $line) {
            // Parse: "Game Title [NPWR12345_00]"
            if (preg_match('/^(.+?)\s*\[([A-Z]{4}\d+_\d+)\]$/', $line, $matches)) {
                $title = trim($matches[1]);
                $npId = $matches[2];

                // Try to find similar games
                $similar = $this->findSimilarGames($title, 60);

                $this->line(sprintf("[%d] %s", $i + 1, $line));
                if (!empty($similar)) {
                    $this->info("    Possible matches:");
                    foreach (array_slice($similar, 0, 3) as $match) {
                        $this->line(sprintf("      - ID %d: %s (%.0f%% match)",
                            $match['game']->id,
                            $match['game']->title,
                            $match['similarity']
                        ));
                    }
                }
                $this->newLine();
            } else {
                $this->line(sprintf("[%d] %s (no NP ID found)", $i + 1, $line));
            }
        }

        return 0;
    }

    protected function processUnmatched(): int
    {
        $logPath = storage_path('logs/psn_unmatched.txt');

        if (!file_exists($logPath)) {
            $this->warn('No unmatched titles log found. Load a PSN library first.');
            return 0;
        }

        $threshold = (int) $this->option('threshold');
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->info("Processing " . count($lines) . " unmatched titles with {$threshold}% threshold...");
        $this->newLine();

        $linked = 0;
        $skipped = 0;
        $noMatch = 0;

        foreach ($lines as $line) {
            if (!preg_match('/^(.+?)\s*\[([A-Z]{4}\d+_\d+)\]$/', $line, $matches)) {
                $skipped++;
                continue;
            }

            $title = trim($matches[1]);
            $npId = $matches[2];

            // Check if NP ID is already linked
            $existingGame = Game::whereJsonContains('np_communication_ids', $npId)->first();
            if ($existingGame) {
                $this->line("  Skip: {$npId} already linked to \"{$existingGame->title}\"");
                $skipped++;
                continue;
            }

            // Find best match
            $similar = $this->findSimilarGames($title, $threshold);

            if (empty($similar)) {
                $this->warn("  No match: \"{$title}\" [{$npId}]");
                $noMatch++;
                continue;
            }

            $bestMatch = $similar[0];
            $game = $bestMatch['game'];
            $similarity = $bestMatch['similarity'];

            // Auto-link if similarity is high enough
            if ($similarity >= $threshold) {
                $this->addNpIdToGame($game, $npId);
                $this->info(sprintf("  Linked: \"%s\" → \"%s\" (ID %d, %.0f%%)",
                    $title, $game->title, $game->id, $similarity));
                $linked++;
            } else {
                $this->warn(sprintf("  Low match: \"%s\" best match is \"%s\" (%.0f%%)",
                    $title, $game->title, $similarity));
                $noMatch++;
            }
        }

        $this->newLine();
        $this->info("Results: {$linked} linked, {$skipped} skipped, {$noMatch} no match");

        return 0;
    }

    protected function linkManually(string $gameInput, string $npId): int
    {
        // Find game by ID or slug
        $game = is_numeric($gameInput)
            ? Game::find($gameInput)
            : Game::where('slug', $gameInput)->first();

        if (!$game) {
            $this->error("Game not found: {$gameInput}");
            return 1;
        }

        // Check if already linked
        $existingGame = Game::whereJsonContains('np_communication_ids', $npId)->first();
        if ($existingGame) {
            if ($existingGame->id === $game->id) {
                $this->info("NP ID {$npId} is already linked to \"{$game->title}\"");
            } else {
                $this->error("NP ID {$npId} is already linked to \"{$existingGame->title}\" (ID {$existingGame->id})");
            }
            return 0;
        }

        $this->addNpIdToGame($game, $npId);
        $this->info("Linked {$npId} to \"{$game->title}\" (ID {$game->id})");

        return 0;
    }

    protected function addNpIdToGame(Game $game, string $npId): void
    {
        $ids = $game->np_communication_ids ?? [];
        if (!in_array($npId, $ids)) {
            $ids[] = $npId;
            $game->np_communication_ids = $ids;
            $game->save();
        }
    }

    protected function findSimilarGames(string $title, int $threshold): array
    {
        $normalizedSearch = $this->normalizeTitle($title);
        $results = [];

        // Get all games (cached in memory for performance)
        static $games = null;
        static $normalizedTitles = null;

        if ($games === null) {
            $games = Game::all(['id', 'title', 'slug']);
            $normalizedTitles = [];
            foreach ($games as $game) {
                $normalizedTitles[$game->id] = $this->normalizeTitle($game->title);
            }
        }

        foreach ($games as $game) {
            $normalizedDb = $normalizedTitles[$game->id];

            // Calculate similarity
            similar_text($normalizedSearch, $normalizedDb, $percent);

            // Also check if one contains the other (helps with subtitles)
            $containsBonus = 0;
            if (str_contains($normalizedDb, $normalizedSearch) || str_contains($normalizedSearch, $normalizedDb)) {
                $containsBonus = 20;
            }

            $finalScore = min(100, $percent + $containsBonus);

            if ($finalScore >= $threshold) {
                $results[] = [
                    'game' => $game,
                    'similarity' => $finalScore,
                ];
            }
        }

        // Sort by similarity descending
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $results;
    }

    protected function normalizeTitle(string $str): string
    {
        // Remove trademark/special characters
        $str = preg_replace('/[\x{2122}\x{00AE}\x{00A9}\x{221A}\x{00B7}]/u', '', $str);
        // Normalize quotes
        $str = preg_replace('/[\x{2018}\x{2019}\x{0060}]/u', "'", $str);
        $str = preg_replace('/[\x{201C}\x{201D}]/u', '"', $str);
        // Normalize dashes/colons
        $str = preg_replace('/\s*[:\x{2013}\x{2014}-]\s*/u', ' ', $str);
        // Remove extra whitespace
        $str = preg_replace('/\s+/', ' ', $str);
        // Lowercase
        return strtolower(trim($str));
    }
}
