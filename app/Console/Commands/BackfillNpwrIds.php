<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\PsnTitle;
use Illuminate\Console\Command;

class BackfillNpwrIds extends Command
{
    protected $signature = 'games:backfill-npwr {--dry-run : Preview changes without saving}';
    protected $description = 'Backfill np_communication_ids on games from linked psn_titles';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no changes will be saved.');
        }

        $linkedTitles = PsnTitle::whereNotNull('game_id')
            ->where('np_communication_id', 'like', 'NPWR%')
            ->get(['np_communication_id', 'game_id', 'psn_title']);

        $backfilled = 0;

        foreach ($linkedTitles as $psnTitle) {
            $game = Game::find($psnTitle->game_id, ['id', 'title', 'np_communication_ids']);
            if (!$game) continue;

            $ids = $game->np_communication_ids ?? [];
            if (in_array($psnTitle->np_communication_id, $ids)) continue;

            $ids[] = $psnTitle->np_communication_id;
            $this->line("{$game->title} (ID {$game->id})");
            $this->line("  Adding: {$psnTitle->np_communication_id} (from \"{$psnTitle->psn_title}\")");

            if (!$dryRun) {
                $game->np_communication_ids = $ids;
                $game->save();
            }

            $backfilled++;
        }

        $this->newLine();
        $this->info("{$backfilled} games backfilled with NPWRs from linked psn_titles.");

        if ($dryRun) {
            $this->warn('Run without --dry-run to apply changes.');
        }
    }
}
