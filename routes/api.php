<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\PSNController;
use App\Http\Controllers\Admin\TrophyUrlImportController;

/*
|--------------------------------------------------------------------------
| API Routes - Admin Games Management
|--------------------------------------------------------------------------
*/

// PSN Lookup
Route::prefix('admin/psn')->group(function () {
    Route::get('/lookup/{username}', [PSNController::class, 'lookup']);
    Route::post('/match-games', [PSNController::class, 'matchGames']);
});

// Trophy URL Import
Route::prefix('admin/trophy-urls')->group(function () {
    Route::post('/import', [TrophyUrlImportController::class, 'import']);
    Route::get('/stats', [TrophyUrlImportController::class, 'stats']);
    Route::get('/unmatched', [TrophyUrlImportController::class, 'unmatched']);
    Route::post('/{id}/match', [TrophyUrlImportController::class, 'match']);
    Route::delete('/{id}', [TrophyUrlImportController::class, 'destroy']);
});

Route::prefix('admin/games')->group(function () {
    // List & Form Data
    Route::get('/', [GameController::class, 'index']);
    Route::get('/form-data', [GameController::class, 'getFormData']);
    Route::get('/stats', [GameController::class, 'getStats']);
    Route::get('/test-igdb', [GameController::class, 'testIgdb']);

    // Bulk Operations (static routes must come before {id} routes)
    Route::post('/bulk-scrape-images', [GameController::class, 'bulkScrapeImages']);
    Route::post('/bulk-delete', [GameController::class, 'bulkDelete']);
    Route::post('/bulk-add-genres', [GameController::class, 'bulkAddGenres']);
    Route::post('/bulk-remove-genres', [GameController::class, 'bulkRemoveGenres']);
    Route::post('/bulk-add-tags', [GameController::class, 'bulkAddTags']);
    Route::post('/bulk-remove-tags', [GameController::class, 'bulkRemoveTags']);
    Route::post('/bulk-add-platforms', [GameController::class, 'bulkAddPlatforms']);
    Route::post('/bulk-remove-platforms', [GameController::class, 'bulkRemovePlatforms']);

    // CRUD Operations (dynamic {id} routes last)
    Route::post('/', [GameController::class, 'store']);
    Route::get('/{id}', [GameController::class, 'show']);
    Route::put('/{id}', [GameController::class, 'update']);
    Route::delete('/{id}', [GameController::class, 'destroy']);
    Route::post('/{id}/scrape-image', [GameController::class, 'scrapeImage']);
});
