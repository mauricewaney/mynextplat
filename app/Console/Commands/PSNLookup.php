<?php

namespace App\Console\Commands;

use App\Services\PSNService;
use Illuminate\Console\Command;

class PSNLookup extends Command
{
    protected $signature = 'psn:lookup
                            {username : PSN username to lookup}
                            {--unplatinumed : Only show games without platinum earned}
                            {--limit=50 : Number of games to show}
                            {--match : Try to match games against local database}';

    protected $description = 'Lookup a PSN user\'s games and trophy progress';

    public function handle(PSNService $psnService): int
    {
        $username = $this->argument('username');
        $unplatinumedOnly = $this->option('unplatinumed');
        $limit = (int) $this->option('limit');
        $matchLocal = $this->option('match');

        $this->info("Looking up PSN user: {$username}");
        $this->newLine();

        // Authenticate
        $this->line('Authenticating with PSN...');

        $npsso = config('services.psn.npsso');
        $this->line('NPSSO token length: ' . strlen($npsso ?? ''));

        if (!$psnService->authenticateFromConfig()) {
            $this->error('Failed to authenticate with PSN. Check your PSN_NPSSO token in .env');
            $this->newLine();

            // Show recent log entries
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                $lines = explode("\n", $logs);
                $psnLogs = array_filter($lines, fn($l) => str_contains($l, 'PSN'));
                $this->line('Recent PSN log entries:');
                foreach (array_slice($psnLogs, -5) as $log) {
                    $this->line('  ' . substr($log, 0, 200));
                }
            }

            $this->newLine();
            $this->info('To get your NPSSO token:');
            $this->line('  1. Go to https://www.playstation.com and sign in');
            $this->line('  2. Visit https://ca.account.sony.com/api/v1/ssocookie');
            $this->line('  3. Copy the "npsso" value');
            $this->line('  4. Add to .env: PSN_NPSSO=your_token_here');
            return self::FAILURE;
        }
        $this->info('Authenticated successfully!');
        $this->newLine();

        // Get games
        $this->line('Fetching games for ' . $username . '...');

        if ($unplatinumedOnly) {
            $data = $psnService->getUnplatinumedGames($username);
        } else {
            $data = $psnService->getGamesForUser($username);
        }

        if (!$data) {
            $this->error('Failed to fetch user data. User may not exist or profile is private.');
            return self::FAILURE;
        }

        $user = $data['user'];
        $titles = array_slice($data['titles'], 0, $limit);

        $this->info("Found user: {$user['onlineId']} (Account ID: {$user['accountId']})");
        $this->info("Total games" . ($unplatinumedOnly ? ' without platinum' : '') . ": " . count($data['titles']));
        $this->newLine();

        // Display games
        $tableData = [];
        foreach ($titles as $title) {
            $defined = $title['definedTrophies'] ?? [];
            $earned = $title['earnedTrophies'] ?? [];
            $progress = $title['progress'] ?? 0;

            $platStatus = ($defined['platinum'] ?? 0) > 0
                ? (($earned['platinum'] ?? 0) > 0 ? 'Yes' : 'No')
                : 'N/A';

            $tableData[] = [
                'name' => mb_substr($title['trophyTitleName'] ?? 'Unknown', 0, 40),
                'platform' => $title['trophyTitlePlatform'] ?? '?',
                'progress' => $progress . '%',
                'platinum' => $platStatus,
                'earned' => ($earned['bronze'] ?? 0) . '/' . ($earned['silver'] ?? 0) . '/' . ($earned['gold'] ?? 0),
            ];
        }

        $this->table(
            ['Game', 'Platform', 'Progress', 'Platinum', 'B/S/G Earned'],
            $tableData
        );

        // Match against local database
        if ($matchLocal) {
            $this->newLine();
            $this->line('Matching against local database...');

            $matched = 0;
            $withGuide = 0;

            foreach ($data['titles'] as $title) {
                $gameName = $title['trophyTitleName'] ?? '';
                $game = \App\Models\Game::where('title', 'LIKE', '%' . $gameName . '%')->first();

                if ($game) {
                    $matched++;
                    if ($game->playstationtrophies_url || $game->powerpyx_url) {
                        $withGuide++;
                    }
                }
            }

            $this->info("Matched {$matched} games in local database");
            $this->info("{$withGuide} of those have trophy guides");
        }

        return self::SUCCESS;
    }
}
