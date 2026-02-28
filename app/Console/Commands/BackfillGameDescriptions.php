<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\IGDBService;
use Illuminate\Console\Command;

class BackfillGameDescriptions extends Command
{
    protected $signature = 'igdb:backfill-descriptions
                            {--batch=50 : Games to fetch per IGDB request}
                            {--sleep=250 : Milliseconds to wait between IGDB requests}';

    protected $description = 'One-time backfill of descriptions (IGDB summary) for existing games';

    public function handle(IGDBService $igdbService): int
    {
        $batchSize = (int) $this->option('batch');
        $sleepMs = (int) $this->option('sleep');

        $total = Game::whereNotNull('igdb_id')->whereNull('description')->count();

        if ($total === 0) {
            $this->info('All games already have descriptions. Nothing to do.');
            return Command::SUCCESS;
        }

        $this->info("Backfilling descriptions for {$total} games...");

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        Game::whereNotNull('igdb_id')
            ->whereNull('description')
            ->orderBy('igdb_id')
            ->chunkById($batchSize, function ($games) use ($igdbService, &$updated, &$skipped, &$errors, $progressBar, $sleepMs) {
                $igdbIds = $games->pluck('igdb_id')->toArray();

                try {
                    $summaries = $igdbService->fetchSummaries($igdbIds);

                    foreach ($games as $game) {
                        if (isset($summaries[$game->igdb_id])) {
                            $game->update(['description' => $summaries[$game->igdb_id]]);
                            $updated++;
                        } else {
                            $skipped++;
                        }

                        $progressBar->advance();
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Batch error: {$e->getMessage()}");
                    $errors += count($igdbIds);
                    $progressBar->advance(count($igdbIds));
                }

                usleep($sleepMs * 1000);
            });

        $progressBar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Updated', $updated],
                ['No summary on IGDB', $skipped],
                ['Errors', $errors],
            ]
        );

        return Command::SUCCESS;
    }
}
