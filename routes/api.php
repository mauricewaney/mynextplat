<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UserGameController;
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
    Route::get('/{idOrSlug}/recommendations', [GameController::class, 'recommendations']);
    Route::get('/{idOrSlug}/guide-votes', [GameController::class, 'guideVotes']);
});

// PSN Library Lookup (public)
Route::get('/psn/my-library', [GameController::class, 'psnMyLibrary']);
Route::get('/psn/my-owned-games', [GameController::class, 'psnMyOwnedGames']);
Route::get('/psn/lookup/{username}', [GameController::class, 'psnLookup']);
Route::get('/psn/library/{username}', [GameController::class, 'psnUserLibrary']);

// Auth - Get current user (public, returns null if not authenticated)
Route::get('/user', [AuthController::class, 'user']);

/*
|--------------------------------------------------------------------------
| API Routes - Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // User preferences
    Route::put('/user/preferences', [AuthController::class, 'updatePreferences']);

    // User's game list
    Route::prefix('my-games')->group(function () {
        Route::get('/', [UserGameController::class, 'index']);
        Route::post('/', [UserGameController::class, 'store']);
        Route::post('/bulk', [UserGameController::class, 'bulkStore']);
        Route::get('/{gameId}/check', [UserGameController::class, 'check']);
        Route::put('/{gameId}', [UserGameController::class, 'update']);
        Route::delete('/{gameId}', [UserGameController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| API Routes - Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // PSN Management
    Route::prefix('admin/psn')->group(function () {
        // User lookup (also collects NP IDs)
        Route::get('/lookup/{username}', [PSNController::class, 'lookup']);
        Route::get('/collect/{username}', [PSNController::class, 'collectFromUser']);

        // PSN Titles database
        Route::get('/stats', [PSNController::class, 'getStats']);
        Route::get('/unmatched', [PSNController::class, 'getUnmatched']);
        Route::get('/suggestions/{psnTitleId}', [PSNController::class, 'getSuggestions']);

        // Linking
        Route::post('/link', [PSNController::class, 'linkToGame']);
        Route::post('/unlink', [PSNController::class, 'unlinkFromGame']);
        Route::post('/bulk-link', [PSNController::class, 'bulkLink']);
        Route::post('/auto-match-all', [PSNController::class, 'autoMatchAll']);

        // Skipping
        Route::post('/skip', [PSNController::class, 'skip']);
        Route::post('/unskip', [PSNController::class, 'unskip']);
        Route::post('/clear-skips', [PSNController::class, 'clearSkips']);

        // Game creation
        Route::post('/create-game', [PSNController::class, 'createGameAndLink']);

        // IGDB integration
        Route::get('/search-igdb', [PSNController::class, 'searchIgdb']);
        Route::post('/import-igdb-and-link', [PSNController::class, 'importFromIgdbAndLink']);
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
        Route::get('/search-guides', [AdminGameController::class, 'searchGuides']);
        Route::get('/test-igdb', [AdminGameController::class, 'testIgdb']);

        // Game search for PSN linking
        Route::get('/search-for-linking', [AdminGameController::class, 'searchGamesForLinking']);
        Route::get('/{id}/psn-titles', [AdminGameController::class, 'getLinkedPsnTitles']);

        // Merge duplicates
        Route::post('/merge', [AdminGameController::class, 'mergeGames']);
        Route::get('/find-duplicates', [AdminGameController::class, 'findDuplicates']);

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
});
