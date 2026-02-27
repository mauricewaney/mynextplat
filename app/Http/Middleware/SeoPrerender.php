<?php

namespace App\Http\Middleware;

use App\Models\Game;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SeoPrerender
{
    private const BOT_PATTERNS = [
        'Googlebot',
        'Bingbot',
        'Slurp',           // Yahoo
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'facebookexternalhit',
        'Facebot',
        'Twitterbot',
        'Discordbot',
        'WhatsApp',
        'LinkedInBot',
        'Slackbot',
        'TelegramBot',
        'Applebot',
        'PinterestBot',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isBot($request) || !$request->isMethod('GET')) {
            return $next($request);
        }

        $slug = $this->extractGameSlug($request->path());

        if (!$slug) {
            return $next($request);
        }

        $version = Cache::get('games:cache_version', 1);
        $cacheKey = "seo:game:v{$version}:{$slug}";

        $html = Cache::remember($cacheKey, 3600, function () use ($slug) {
            $game = Game::with(['genres', 'platforms'])
                ->where('slug', $slug)
                ->first();

            if (!$game) {
                return null;
            }

            return view('seo.game', ['game' => $game])->render();
        });

        if ($html === null) {
            Cache::forget($cacheKey);
            return $next($request);
        }

        return response($html, 200)
            ->header('Content-Type', 'text/html')
            ->header('X-Prerendered', 'true');
    }

    private function isBot(Request $request): bool
    {
        $userAgent = $request->userAgent() ?? '';

        foreach (self::BOT_PATTERNS as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractGameSlug(string $path): ?string
    {
        if (preg_match('#^game/([a-z0-9\-]+)$#', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
