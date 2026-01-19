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
    // Get the latest release date from the database for incremental sync
    $sinceTimestamp = null;
    $latestGame = Game::whereNotNull('release_date')
        ->orderBy('release_date', 'desc')
        ->first();

    if ($latestGame && $latestGame->release_date) {
        $sinceTimestamp = $latestGame->release_date->timestamp;
    }

    ImportIGDBGames::dispatch(100, 0, $sinceTimestamp);
})
    ->daily()
    ->at('03:00')
    ->name('igdb-import-new-games')
    ->withoutOverlapping()
    ->onOneServer();
