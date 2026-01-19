<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GameController;

/*
|--------------------------------------------------------------------------
| API Routes - Admin Games Management
|--------------------------------------------------------------------------
*/

Route::prefix('admin/games')->group(function () {
    // List & Form Data
    Route::get('/', [GameController::class, 'index']);
    Route::get('/form-data', [GameController::class, 'getFormData']);
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
