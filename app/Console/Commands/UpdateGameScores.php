<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\IGDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateGameScores extends Command
{
    protected $signature = 'igdb:update-scores
                            {--chunk=50 : Number of games to fetch per IGDB request (max 500)}
                            {--only-missing : Only update games that have no scores at all}';

    protected $description = 'Update user/critic scores and counts from IGDB for existing games (does NOT touch guide URLs or other fields)';

    public function handle(IGDBService $igdbService): int
    {
        $chunkSize = min((int) $this->option('chunk'), 500);
        $onlyMissing = $this->option('only-missing');

        $this->info('IGDB Score Updater');
        $this->info('==================');
        $this->info('This will ONLY update: critic_score, critic_score_count, user_score, user_score_count');
        $this->info('Guide URLs and all other fields will NOT be touched.');
        $this->newLine();

        // Get games that have an igdb_id
        $query = Game::whereNotNull('igdb_id');

        if ($onlyMissing) {
            $query->where(function ($q) {
                $q->whereNull('user_score')
                  ->whereNull('user_score_count')
                  ->whereNull('critic_score_count');
            });
            $this->info('Mode: Only updating games with missing score data');
        } else {
            $this->info('Mode: Updating all games with IGDB IDs');
        }

        $totalGames = $query->count();

        if ($totalGames === 0) {
            $this->info('No games to update.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalGames} games to update.");
        $this->newLine();

        // Disable query logging to prevent memory buildup
        \DB::disableQueryLog();

        $progressBar = $this->output->createProgressBar($totalGames);
        $progressBar->start();

        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $errors = 0;

        // Process in chunks
        $query->select(['id', 'igdb_id', 'title'])
            ->chunkById($chunkSize, function ($games) use (
                $igdbService, &$updated, &$skipped, &$notFound, &$errors, $progressBar, $chunkSize
            ) {
                $igdbIds = $games->pluck('igdb_id')->toArray();

                try {
                    $igdbData = $this->fetchScoresFromIGDB($igdbService, $igdbIds, $chunkSize);
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error('IGDB API error: ' . $e->getMessage());
                    $errors += count($igdbIds);
                    $progressBar->advance(count($igdbIds));
                    // Sleep and retry once
                    sleep(2);
                    try {
                        $igdbData = $this->fetchScoresFromIGDB($igdbService, $igdbIds, $chunkSize);
                    } catch (\Exception $e2) {
                        $this->error('Retry failed: ' . $e2->getMessage());
                        return false; // stop chunking
                    }
                }

                // Index IGDB results by ID
                $igdbById = [];
                foreach ($igdbData as $item) {
                    $igdbById[$item['id']] = $item;
                }

                foreach ($games as $game) {
                    $igdbGame = $igdbById[$game->igdb_id] ?? null;

                    if (!$igdbGame) {
                        $notFound++;
                        $progressBar->advance();
                        continue;
                    }

                    $criticScore = isset($igdbGame['aggregated_rating'])
                        ? (int) round($igdbGame['aggregated_rating'])
                        : null;
                    $criticScoreCount = $igdbGame['aggregated_rating_count'] ?? null;
                    $userScore = isset($igdbGame['rating'])
                        ? (int) round($igdbGame['rating'])
                        : null;
                    $userScoreCount = $igdbGame['rating_count'] ?? null;

                    Game::where('id', $game->id)->update([
                        'critic_score' => $criticScore,
                        'critic_score_count' => $criticScoreCount,
                        'user_score' => $userScore,
                        'user_score_count' => $userScoreCount,
                    ]);

                    $updated++;
                    $progressBar->advance();
                }

                // Respect IGDB rate limits (4 requests/second)
                usleep(300000); // 300ms between chunks
            });

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Score update complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Games Updated', $updated],
                ['Not Found on IGDB', $notFound],
                ['Errors', $errors],
                ['Total Processed', $updated + $notFound + $errors],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Fetch score data from IGDB for a batch of game IDs.
     */
    private function fetchScoresFromIGDB(IGDBService $igdbService, array $igdbIds, int $limit): array
    {
        $accessToken = $this->getAccessToken($igdbService);

        $idList = implode(',', $igdbIds);

        $query = 'fields aggregated_rating,aggregated_rating_count,rating,rating_count; '
            . 'where id = (' . $idList . '); '
            . 'limit ' . $limit . ';';

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Client-ID' => config('services.igdb.client_id'),
                'Authorization' => 'Bearer ' . $accessToken,
            ])->withBody($query, 'text/plain')
              ->post('https://api.igdb.com/v4/games');

        if (!$response->successful()) {
            throw new \Exception('IGDB API error: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Get IGDB access token via the service.
     */
    private function getAccessToken(IGDBService $igdbService): string
    {
        // Use reflection to access the protected method
        $reflection = new \ReflectionMethod($igdbService, 'getAccessToken');
        $reflection->setAccessible(true);
        $token = $reflection->invoke($igdbService);

        if (!$token) {
            throw new \Exception('Failed to get IGDB access token. Check your credentials.');
        }

        return $token;
    }
}
