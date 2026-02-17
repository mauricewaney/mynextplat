<?php

use App\Jobs\ImportIGDBGames;
use App\Models\Game;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Import new PlayStation games from IGDB daily at 3 AM.
| Uses incremental sync - fetches games from the latest release date in DB.
|
*/

Schedule::call(function () {
    // Use created_at as cursor to catch any recently added IGDB entries
    $sinceTimestamp = null;
    $latestGame = Game::whereNotNull('igdb_id')
        ->orderBy('created_at', 'desc')
        ->first();

    if ($latestGame) {
        // Subtract 1 day buffer to avoid missing edge cases
        $sinceTimestamp = $latestGame->created_at->subDay()->timestamp;
    }

    ImportIGDBGames::dispatch(100, 0, $sinceTimestamp);
})
    ->daily()
    ->at('03:00')
    ->name('igdb-import-new-games')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Trophy Data Scraping
|--------------------------------------------------------------------------
|
| Scrape trophy data from PowerPyx for newly imported games.
| Runs at 4 AM (1 hour after IGDB import) to catch new games.
|
*/

Schedule::command('trophy:scrape --new --limit=200')
    ->daily()
    ->at('04:00')
    ->name('trophy-scrape-new-games')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Trophy URL Discovery (PSNProfiles, PlayStationTrophies, PowerPyx)
|--------------------------------------------------------------------------
|
| Search for trophy guides published in the last 24 hours.
| Runs at 5 AM daily - just 3 search queries, one per site.
| Matches found URLs to games in the database.
|
*/

Schedule::command('trophy:daily-search')
    ->daily()
    ->at('05:00')
    ->name('trophy-daily-search')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Server Shutdown → Unobtainable
|--------------------------------------------------------------------------
|
| Games whose server_shutdown_date has passed are marked unobtainable.
| Runs daily at 00:05 AM.
|
*/

Schedule::call(function () {
    Game::where('server_shutdown_date', '<=', now())
        ->where('is_unobtainable', false)
        ->update(['is_unobtainable' => true]);
})
    ->daily()
    ->at('00:05')
    ->name('server-shutdown-unobtainable')
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| New Guide Email Notifications
|--------------------------------------------------------------------------
|
| Send email notifications to users about new guides for games in their list.
| Runs at 6 AM daily - after trophy URL discovery completes.
|
*/

Schedule::command('notifications:new-guides')
    ->daily()
    ->at('06:00')
    ->name('new-guide-notifications')
    ->withoutOverlapping()
    ->onOneServer();
