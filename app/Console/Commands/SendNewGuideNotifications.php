<?php

namespace App\Console\Commands;

use App\Mail\NewGuideNotification;
use App\Models\Game;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendNewGuideNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:new-guides
                            {--dry-run : Show what would be sent without actually sending}
                            {--user= : Send only to a specific user ID (for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications to users about new guides for games in their list';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificUserId = $this->option('user');

        if ($dryRun) {
            $this->info('DRY RUN - No emails will be sent');
        }

        // Get users who have notifications enabled
        $usersQuery = User::where('notify_new_guides', true)
            ->whereNotNull('email');

        if ($specificUserId) {
            $usersQuery->where('id', $specificUserId);
        }

        $users = $usersQuery->get();

        $this->info("Found {$users->count()} users with notifications enabled");

        $totalEmailsSent = 0;
        $totalGamesNotified = 0;

        foreach ($users as $user) {
            // Find games in user's list that:
            // 1. Have at least one guide URL
            // 2. Haven't been notified yet (guide_notified_at is null)
            $gamesWithNewGuides = Game::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereNull('guide_notified_at');
            })
            ->where(function ($query) {
                $query->whereNotNull('psnprofiles_url')
                    ->orWhereNotNull('playstationtrophies_url')
                    ->orWhereNotNull('powerpyx_url');
            })
            ->get();

            if ($gamesWithNewGuides->isEmpty()) {
                continue;
            }

            $this->line("User {$user->name} ({$user->email}): {$gamesWithNewGuides->count()} games with new guides");

            foreach ($gamesWithNewGuides as $game) {
                $this->line("  - {$game->title}");
            }

            if (!$dryRun) {
                // Send the email
                Mail::to($user)->send(new NewGuideNotification($user, $gamesWithNewGuides));

                // Mark these games as notified
                DB::table('user_game')
                    ->where('user_id', $user->id)
                    ->whereIn('game_id', $gamesWithNewGuides->pluck('id'))
                    ->update(['guide_notified_at' => now()]);

                // Update user's last notified timestamp
                $user->update(['last_notified_at' => now()]);

                $totalEmailsSent++;
                $totalGamesNotified += $gamesWithNewGuides->count();
            }
        }

        if ($dryRun) {
            $this->info('DRY RUN complete. No emails were sent.');
        } else {
            $this->info("Sent {$totalEmailsSent} emails covering {$totalGamesNotified} games");
        }

        return Command::SUCCESS;
    }
}
