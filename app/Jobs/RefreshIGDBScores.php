<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\IGDBService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshIGDBScores implements ShouldQueue
{
    use Queueable;

    protected int $limit;
    protected ?int $sinceUpdatedAt;

    public int $timeout = 600;

    public function __construct(int $limit = 500, ?int $sinceUpdatedAt = null)
    {
        $this->limit = $limit;
        $this->sinceUpdatedAt = $sinceUpdatedAt;
    }

    public function handle(IGDBService $igdbService): void
    {
        $log = Log::channel('jobs');

        $log->info("Score refresh starting: limit={$this->limit}, sinceUpdatedAt={$this->sinceUpdatedAt}");

        $igdbGames = $igdbService->fetchUpdatedGameScores($this->limit, $this->sinceUpdatedAt);

        $log->info("Fetched " . count($igdbGames) . " updated games from IGDB");

        $checked = 0;
        $changed = 0;
        $notInDb = 0;
        $errors = 0;
        $maxUpdatedAt = $this->sinceUpdatedAt;

        foreach ($igdbGames as $igdbGame) {
            try {
                $checked++;

                if (isset($igdbGame['updated_at']) && ($maxUpdatedAt === null || $igdbGame['updated_at'] > $maxUpdatedAt)) {
                    $maxUpdatedAt = $igdbGame['updated_at'];
                }

                $game = Game::where('igdb_id', $igdbGame['id'])->first();

                if (!$game) {
                    $notInDb++;
                    continue;
                }

                $newCriticScore = isset($igdbGame['aggregated_rating'])
                    ? (int) round($igdbGame['aggregated_rating'])
                    : null;
                $newCriticCount = $igdbGame['aggregated_rating_count'] ?? null;
                $newUserScore = isset($igdbGame['rating'])
                    ? (int) round($igdbGame['rating'])
                    : null;
                $newUserCount = $igdbGame['rating_count'] ?? null;

                $hasChanged = $game->critic_score !== $newCriticScore
                    || $game->critic_score_count !== $newCriticCount
                    || $game->user_score !== $newUserScore
                    || $game->user_score_count !== $newUserCount;

                if ($hasChanged) {
                    $game->update([
                        'critic_score' => $newCriticScore,
                        'critic_score_count' => $newCriticCount,
                        'user_score' => $newUserScore,
                        'user_score_count' => $newUserCount,
                    ]);
                    $changed++;
                }
            } catch (\Exception $e) {
                $errors++;
                $log->error("Score refresh error for IGDB ID {$igdbGame['id']}: {$e->getMessage()}");
            }
        }

        $log->info("Score refresh complete: checked={$checked}, changed={$changed}, not_in_db={$notInDb}, errors={$errors}");

        if ($changed > 0) {
            \App\Http\Controllers\GameController::bustGameCache();
        }

        // Store cursor for next run
        if ($maxUpdatedAt !== null) {
            Cache::put('igdb_scores_last_refresh', $maxUpdatedAt);
        }

        // Auto-pagination: if batch was full, dispatch next job with max updated_at as cursor
        if (count($igdbGames) >= $this->limit && $maxUpdatedAt !== null) {
            $log->info("Batch was full ({$this->limit}), dispatching next batch from updated_at > {$maxUpdatedAt}");
            self::dispatch($this->limit, $maxUpdatedAt);
        }
    }
}
