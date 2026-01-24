<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\PSNController;
use App\Http\Controllers\Admin\TrophyUrlImportController;

/*
|--------------------------------------------------------------------------
| API Routes - Public
|--------------------------------------------------------------------------
*/

Route::prefix('games')->group(function () {
    Route::get('/', [GameController::class, 'index']);
    Route::get('/filters', [GameController::class, 'filterOptions']);
    Route::get('/{idOrSlug}', [GameController::class, 'show']);
});

// PSN Library Lookup (public)
Route::get('/psn/my-library', [GameController::class, 'psnMyLibrary']);
Route::get('/psn/my-owned-games', [GameController::class, 'psnMyOwnedGames']);
Route::get('/psn/lookup/{username}', [GameController::class, 'psnLookup']);

/*
|--------------------------------------------------------------------------
| API Routes - Admin
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
    Route::get('/search-igdb', [TrophyUrlImportController::class, 'searchIgdb']);
    Route::post('/{id}/match', [TrophyUrlImportController::class, 'match']);
    Route::post('/{id}/import-igdb', [TrophyUrlImportController::class, 'importFromIgdb']);
    Route::post('/{id}/toggle-dlc', [TrophyUrlImportController::class, 'toggleDlc']);
    Route::delete('/{id}', [TrophyUrlImportController::class, 'destroy']);
});

Route::prefix('admin/games')->group(function () {
    // List & Form Data
    Route::get('/', [AdminGameController::class, 'index']);
    Route::get('/form-data', [AdminGameController::class, 'getFormData']);
    Route::get('/stats', [AdminGameController::class, 'getStats']);
    Route::get('/test-igdb', [AdminGameController::class, 'testIgdb']);

    // NP ID Management
    Route::get('/unmatched-psn', [AdminGameController::class, 'getUnmatchedPsnTitles']);
    Route::get('/search-for-linking', [AdminGameController::class, 'searchGamesForLinking']);
    Route::post('/link-np-id', [AdminGameController::class, 'linkNpId']);
    Route::post('/unlink-np-id', [AdminGameController::class, 'unlinkNpId']);

    // Bulk Operations (static routes must come before {id} routes)
    Route::post('/bulk-scrape-images', [AdminGameController::class, 'bulkScrapeImages']);
    Route::post('/bulk-delete', [AdminGameController::class, 'bulkDelete']);
    Route::post('/bulk-add-genres', [AdminGameController::class, 'bulkAddGenres']);
    Route::post('/bulk-remove-genres', [AdminGameController::class, 'bulkRemoveGenres']);
    Route::post('/bulk-add-tags', [AdminGameController::class, 'bulkAddTags']);
    Route::post('/bulk-remove-tags', [AdminGameController::class, 'bulkRemoveTags']);
    Route::post('/bulk-add-platforms', [AdminGameController::class, 'bulkAddPlatforms']);
    Route::post('/bulk-remove-platforms', [AdminGameController::class, 'bulkRemovePlatforms']);

    // CRUD Operations (dynamic {id} routes last)
    Route::post('/', [AdminGameController::class, 'store']);
    Route::get('/{id}', [AdminGameController::class, 'show']);
    Route::put('/{id}', [AdminGameController::class, 'update']);
    Route::delete('/{id}', [AdminGameController::class, 'destroy']);
    Route::post('/{id}/scrape-image', [AdminGameController::class, 'scrapeImage']);
});
