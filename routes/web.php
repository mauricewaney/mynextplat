<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Auth fallback — API requests get JSON 401, web requests redirect to homepage with login prompt
Route::get('/login', function (\Illuminate\Http\Request $request) {
    if ($request->expectsJson()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    return redirect('/?login=required');
})->name('login');

// Google OAuth routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// SEO routes
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-games-with-guides.xml', [SitemapController::class, 'gamesWithGuides']);
Route::get('/sitemap-games-{page}.xml', [SitemapController::class, 'games'])->where('page', '[0-9]+');
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
Route::get('/libraries', fn () => view('pages.profiles'));
Route::get('/profiles', fn () => redirect('/libraries', 301));
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

// Local-only mail previews. Visit these in the browser to render any Mailable
// as inline HTML — no SMTP needed. Returning a Mailable from a route triggers
// Laravel's built-in render-to-response behavior.
if (app()->environment('local')) {
    Route::prefix('dev/mail')->group(function () {
        Route::get('/', function () {
            return response('<ul style="font: 14px sans-serif; padding: 24px;">'
                . '<li><a href="/dev/mail/new-guide">new-guide</a> — sent when a trophy guide drops for a subscribed game</li>'
                . '<li><a href="/dev/mail/admin-contact">admin-contact</a> — admin alert for a new contact message</li>'
                . '<li><a href="/dev/mail/admin-correction">admin-correction</a> — admin alert for a new game correction</li>'
                . '</ul>');
        });

        Route::get('/new-guide', function () {
            $user = \App\Models\User::first() ?? new \App\Models\User([
                'name' => 'maurice',
                'email' => 'maurice@example.com',
            ]);
            $games = \App\Models\Game::where(fn ($q) => $q->whereNotNull('psnprofiles_url')
                ->orWhereNotNull('powerpyx_url')
                ->orWhereNotNull('playstationtrophies_url'))
                ->limit(3)
                ->get();
            if ($games->isEmpty()) {
                $games = \App\Models\Game::limit(3)->get();
            }
            return new \App\Mail\NewGuideNotification($user, $games);
        });

        Route::get('/admin-contact', function () {
            $msg = \App\Models\ContactMessage::latest()->first() ?? new \App\Models\ContactMessage([
                'name' => 'Jane Tester',
                'email' => 'jane@example.com',
                'category' => 'bug_report',
                'subject' => 'Trophy count off on Returnal',
                'message' => "The Veteran trophy isn't showing up in the list. Could you check?",
                'ip_address' => '127.0.0.1',
            ]);
            $msg->created_at ??= now();
            $cap = (int) config('mail.admin_inbox_daily_cap', 5);
            $capReached = request()->boolean('cap');
            return new \App\Mail\AdminInboxNotification('contact', $msg, $capReached, $cap);
        });

        Route::get('/admin-correction', function () {
            $correction = \App\Models\GameCorrection::with('game')->latest()->first();
            if (!$correction) {
                $correction = new \App\Models\GameCorrection([
                    'category' => 'guide_links',
                    'description' => 'The PowerPyx link 404s for this game.',
                    'source_url' => 'https://www.powerpyx.com/',
                    'email' => 'reporter@example.com',
                    'ip_address' => '127.0.0.1',
                ]);
                $correction->setRelation('game', \App\Models\Game::first());
                $correction->created_at = now();
            }
            $cap = (int) config('mail.admin_inbox_daily_cap', 5);
            $capReached = request()->boolean('cap');
            return new \App\Mail\AdminInboxNotification('correction', $correction, $capReached, $cap);
        });
    });
}

// 404 catch-all
Route::fallback(fn () => response()->view('pages.not-found', [], 404));
