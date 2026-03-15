<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// API auth fallback — prevents "Route [login] not defined" when Sanctum rejects unauthenticated API requests
Route::get('/login', fn () => response()->json(['message' => 'Unauthenticated'], 401))->name('login');

// Google OAuth routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// SEO routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// SEO directory pages (served to everyone — no cloaking)
Route::get('/games/genre/{slug}', [DirectoryController::class, 'genre']);
Route::get('/games/platform/{slug}', [DirectoryController::class, 'platform']);
Route::get('/guides/{slug}', [DirectoryController::class, 'preset']);

// Browse hub
Route::get('/browse', [DirectoryController::class, 'browse']);

// Public pages
Route::get('/', fn () => view('pages.home'));
Route::get('/game/{slug}', fn ($slug) => view('pages.game', ['slug' => $slug]));
Route::get('/contact', fn () => view('pages.contact'));
Route::get('/privacy', fn () => view('pages.privacy'));
Route::get('/profiles', fn () => view('pages.profiles'));
Route::get('/u/{identifier}', fn ($identifier) => view('pages.profile', ['identifier' => $identifier]));
Route::get('/report-issue', fn () => view('pages.report-issue'));

// Auth-protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-games', fn () => view('pages.my-games'));
    Route::get('/settings', fn () => view('pages.settings'));
});

// Admin (keeps internal vue-router)
Route::middleware(['auth:sanctum', 'admin'])
    ->get('/admin/{any?}', fn () => view('pages.admin'))
    ->where('any', '.*');

// 404 catch-all
Route::fallback(fn () => response()->view('pages.not-found', [], 404));
