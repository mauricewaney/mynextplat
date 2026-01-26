<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Google OAuth routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// SPA catch-all (must be last)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
