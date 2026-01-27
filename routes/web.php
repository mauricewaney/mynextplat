<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Google OAuth routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// SEO routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// SPA catch-all (must be last)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
