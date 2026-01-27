<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate XML sitemap
     */
    public function index(): Response
    {
        $games = Game::select('slug', 'updated_at')
            ->whereNotNull('slug')
            ->orderBy('updated_at', 'desc')
            ->get();

        $content = view('sitemap', ['games' => $games])->render();

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
