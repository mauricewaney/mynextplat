<?php

use App\Jobs\ImportIGDBGames;
use App\Jobs\RefreshIGDBScores;
use App\Models\Game;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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
| Uses IGDB ID cursor - fetches games with ID greater than our max.
|
*/

Schedule::call(function () {
    // Use max IGDB ID as cursor — IDs are incremental, so no buffer needed
    $maxIgdbId = Game::whereNotNull('igdb_id')->max('igdb_id');

    ImportIGDBGames::dispatch(500, sinceIgdbId: $maxIgdbId);
})
    ->daily()
    ->at('03:00')
    ->name('igdb-import-new-games')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Import Recently Released Games
|--------------------------------------------------------------------------
|
| Catch games that were added to IGDB long ago but released recently.
| The main import uses an IGDB ID cursor, which misses old entries
| that only recently became relevant due to a new release date.
|
*/

Schedule::call(function () {
    // Look for games released in the last 30 days
    $releasedSince = now()->subDays(30)->timestamp;

    ImportIGDBGames::dispatch(100, sinceIgdbId: null, releasedSinceTimestamp: $releasedSince);
})
    ->daily()
    ->at('03:15')
    ->name('igdb-import-recently-released')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Weekly Catchup Import
|--------------------------------------------------------------------------
|
| Scan all IGDB entries since trophy era to catch any games that slipped
| through the cracks (e.g., old IGDB entries with new release dates).
| Runs Sunday nights; auto-paginates through all batches via the queue.
|
*/

Schedule::call(function () {
    ImportIGDBGames::dispatch(500, sinceIgdbId: null);
})
    ->weeklyOn(0, '02:00')
    ->name('igdb-weekly-catchup')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Refresh IGDB Scores
|--------------------------------------------------------------------------
|
| Fetch games updated on IGDB since last refresh and update scores.
| Uses updated_at cursor to efficiently fetch only changed entries.
| Runs daily at 3:30 AM (after imports, before trophy scraping).
|
*/

Schedule::call(function () {
    $lastRefresh = Cache::get('igdb_scores_last_refresh');

    RefreshIGDBScores::dispatch(500, $lastRefresh);
})
    ->daily()
    ->at('03:30')
    ->name('igdb-refresh-scores')
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
