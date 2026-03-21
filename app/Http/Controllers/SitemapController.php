<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Genre;
use App\Models\Platform;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const GAMES_PER_SITEMAP = 5000;

    /**
     * Sitemap index — points to individual sitemap files
     */
    public function index(): Response
    {
        $version = Cache::get('games:cache_version', 1);

        $content = Cache::remember("sitemap:index:v{$version}", 3600, function () {
            $totalGames = Game::whereNotNull('slug')->count();
            $gamePages = max(1, ceil($totalGames / self::GAMES_PER_SITEMAP));

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // Static pages sitemap
            $xml .= '  <sitemap><loc>' . url('/sitemap-pages.xml') . '</loc></sitemap>' . "\n";

            // Games with guides get their own sitemap (highest priority for crawling)
            $xml .= '  <sitemap><loc>' . url('/sitemap-games-with-guides.xml') . '</loc></sitemap>' . "\n";

            // Remaining games split into chunks
            for ($i = 1; $i <= $gamePages; $i++) {
                $xml .= '  <sitemap><loc>' . url("/sitemap-games-{$i}.xml") . '</loc></sitemap>' . "\n";
            }

            $xml .= '</sitemapindex>';

            return $xml;
        });

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Static pages, genres, platforms, presets
     */
    public function pages(): Response
    {
        $version = Cache::get('games:cache_version', 1);

        $content = Cache::remember("sitemap:pages:v{$version}", 3600, function () {
            $genres = Genre::select('slug')->orderBy('name')->get();
            $platforms = Platform::select('slug')->orderBy('name')->get();

            $presets = [
                'fast-and-easy', 'must-play', 'no-stress', 'easy-platinums',
                'quick-platinums', 'offline-only', 'no-missables', 'hidden-gems',
                'quality-epics',
            ];

            return view('sitemap-pages', compact('genres', 'platforms', 'presets'))->render();
        });

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Games that have at least one guide — these are the most valuable pages
     */
    public function gamesWithGuides(): Response
    {
        $version = Cache::get('games:cache_version', 1);

        $content = Cache::remember("sitemap:guided:v{$version}", 3600, function () {
            $games = Game::select('slug', 'updated_at')
                ->whereNotNull('slug')
                ->where(function ($q) {
                    $q->whereNotNull('psnprofiles_url')
                      ->orWhereNotNull('playstationtrophies_url')
                      ->orWhereNotNull('powerpyx_url');
                })
                ->orderBy('updated_at', 'desc')
                ->get();

            return view('sitemap-games', ['games' => $games, 'priority' => '0.8'])->render();
        });

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * All games paginated (includes games without guides)
     */
    public function games(int $page): Response
    {
        $version = Cache::get('games:cache_version', 1);

        $content = Cache::remember("sitemap:games:{$page}:v{$version}", 3600, function () use ($page) {
            $games = Game::select('slug', 'updated_at')
                ->whereNotNull('slug')
                ->orderBy('slug')
                ->offset(($page - 1) * self::GAMES_PER_SITEMAP)
                ->limit(self::GAMES_PER_SITEMAP)
                ->get();

            if ($games->isEmpty()) {
                return null;
            }

            return view('sitemap-games', ['games' => $games, 'priority' => '0.5'])->render();
        });

        if ($content === null) {
            abort(404);
        }

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Robots.txt
     */
    public function robots(): Response
    {
        $sitemapUrl = url('/sitemap.xml');

        $content = <<<ROBOTS
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /my-games
Disallow: /api/

Sitemap: {$sitemapUrl}
ROBOTS;

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
