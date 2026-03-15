<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;

class CleanNpCommunicationIds extends Command
{
    protected $signature = 'games:clean-np-ids {--dry-run : Preview changes without saving}';
    protected $description = 'Remove non-NPWR IDs (CUSA/PPSA) from games np_communication_ids';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no changes will be saved.');
        }

        $games = Game::whereNotNull('np_communication_ids')->get(['id', 'title', 'np_communication_ids']);

        $cleaned = 0;
        $nulled = 0;

        foreach ($games as $game) {
            $ids = $game->np_communication_ids;
            $npwrOnly = array_values(array_filter($ids, fn($id) => str_starts_with($id, 'NPWR')));
            $removed = array_diff($ids, $npwrOnly);

            if (empty($removed)) continue;

            $this->line("{$game->title} (ID {$game->id})");
            $this->line("  Removing: " . implode(', ', $removed));

            if (empty($npwrOnly)) {
                $this->line("  → Setting np_communication_ids to NULL");
                if (!$dryRun) {
                    $game->np_communication_ids = null;
                    $game->save();
                }
                $nulled++;
            } else {
                $this->line("  → Keeping: " . implode(', ', $npwrOnly));
                if (!$dryRun) {
                    $game->np_communication_ids = $npwrOnly;
                    $game->save();
                }
            }

            $cleaned++;
        }

        $this->newLine();
        $this->info("Done. {$cleaned} games cleaned ({$nulled} set to NULL).");

        if ($dryRun) {
            $this->warn('Run without --dry-run to apply changes.');
        }
    }
}
