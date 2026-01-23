<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\TrophyGuideUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrophyUrlImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'html' => 'required|string|min:100',
            'source' => 'required|in:psnprofiles,playstationtrophies',
        ]);

        $html = $request->input('html');
        $source = $request->input('source');

        // Extract URLs based on source
        $urls = $this->extractUrls($html, $source);

        if (empty($urls)) {
            return response()->json([
                'success' => false,
                'message' => 'No guide URLs found in the HTML',
                'stats' => null,
            ]);
        }

        // Process URLs
        $stats = [
            'found' => count($urls),
            'new' => 0,
            'existing' => 0,
            'matched' => 0,
        ];

        foreach ($urls as $url) {
            // Check if already exists
            if (TrophyGuideUrl::where('url', $url)->exists()) {
                $stats['existing']++;
                continue;
            }

            // Extract slug from URL
            $slug = $this->extractSlug($url, $source);

            if (!$slug) {
                continue;
            }

            // Try to find matching game
            $game = $this->findMatchingGame($slug);

            // Create trophy URL record
            TrophyGuideUrl::create([
                'source' => $source,
                'url' => $url,
                'extracted_slug' => $slug,
                'extracted_title' => Str::title(str_replace('-', ' ', $slug)),
                'game_id' => $game?->id,
                'matched_at' => $game ? now() : null,
            ]);

            // Update game if matched
            $urlField = $source === 'psnprofiles' ? 'psnprofiles_url' : 'playstationtrophies_url';
            if ($game && empty($game->$urlField)) {
                $game->$urlField = $url;
                $game->save();
                $stats['matched']++;
            }

            $stats['new']++;
        }

        // Get total counts
        $stats['total_psnprofiles'] = TrophyGuideUrl::where('source', 'psnprofiles')->count();
        $stats['total_playstationtrophies'] = TrophyGuideUrl::where('source', 'playstationtrophies')->count();
        $stats['games_with_psnprofiles'] = Game::whereNotNull('psnprofiles_url')->count();
        $stats['games_with_playstationtrophies'] = Game::whereNotNull('playstationtrophies_url')->count();

        return response()->json([
            'success' => true,
            'message' => "Processed {$stats['found']} URLs: {$stats['new']} new, {$stats['existing']} existing, {$stats['matched']} matched to games",
            'stats' => $stats,
        ]);
    }

    protected function extractUrls(string $html, string $source): array
    {
        $urls = [];

        if ($source === 'psnprofiles') {
            // Match: href="/guide/12345-game-name"
            if (preg_match_all('#href="(/guide/\d+-[^"]+)"#i', $html, $matches)) {
                foreach ($matches[1] as $path) {
                    $urls[] = "https://psnprofiles.com{$path}";
                }
            }
        } elseif ($source === 'playstationtrophies') {
            // Match: href="/game/game-name/guide"
            if (preg_match_all('#href="(/game/[^"]+/guide/?)"#i', $html, $matches)) {
                foreach ($matches[1] as $path) {
                    $urls[] = "https://www.playstationtrophies.org{$path}";
                }
            }
        }

        return array_unique($urls);
    }

    protected function extractSlug(string $url, string $source): ?string
    {
        if ($source === 'psnprofiles') {
            // URL: https://psnprofiles.com/guide/12345-game-name-trophy-guide
            if (preg_match('#/guide/\d+-(.+)$#i', $url, $matches)) {
                $slug = $matches[1];
                $slug = preg_replace('#-(trophy-guide|walkthrough|platinum-walkthrough)$#i', '', $slug);
                return $slug;
            }
        } elseif ($source === 'playstationtrophies') {
            // URL: https://www.playstationtrophies.org/game/game-name/guide
            if (preg_match('#/game/([^/]+)/guide#i', $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    protected function findMatchingGame(string $slug): ?Game
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        // Try exact slug match
        $game = Game::where('slug', $slug)->first();
        if ($game) return $game;

        // Try normalized match
        $game = Game::whereRaw('LOWER(REPLACE(slug, "-", "")) = ?', [str_replace('-', '', $normalizedSlug)])->first();
        if ($game) return $game;

        // Try title match
        $titleFromSlug = Str::title(str_replace('-', ' ', $normalizedSlug));
        $game = Game::whereRaw('LOWER(title) = ?', [strtolower($titleFromSlug)])->first();
        if ($game) return $game;

        return null;
    }

    protected function normalizeSlug(string $slug): string
    {
        $suffixes = ['-ps5', '-ps4', '-ps3', '-vita', '-psvr'];

        $slug = strtolower($slug);
        foreach ($suffixes as $suffix) {
            if (str_ends_with($slug, $suffix)) {
                $slug = substr($slug, 0, -strlen($suffix));
            }
        }

        $slug = preg_replace('#^the-#', '', $slug);

        return $slug;
    }

    public function stats()
    {
        return response()->json([
            'psnprofiles' => [
                'total_urls' => TrophyGuideUrl::where('source', 'psnprofiles')->count(),
                'matched' => TrophyGuideUrl::where('source', 'psnprofiles')->whereNotNull('game_id')->count(),
                'unmatched' => TrophyGuideUrl::where('source', 'psnprofiles')->whereNull('game_id')->count(),
                'games_with_url' => Game::whereNotNull('psnprofiles_url')->count(),
            ],
            'playstationtrophies' => [
                'total_urls' => TrophyGuideUrl::where('source', 'playstationtrophies')->count(),
                'matched' => TrophyGuideUrl::where('source', 'playstationtrophies')->whereNotNull('game_id')->count(),
                'unmatched' => TrophyGuideUrl::where('source', 'playstationtrophies')->whereNull('game_id')->count(),
                'games_with_url' => Game::whereNotNull('playstationtrophies_url')->count(),
            ],
            'total_games' => Game::count(),
        ]);
    }

    public function unmatched()
    {
        $urls = TrophyGuideUrl::whereNull('game_id')
            ->orderBy('extracted_title')
            ->get(['id', 'source', 'url', 'extracted_slug', 'extracted_title']);

        return response()->json([
            'urls' => $urls,
            'stats' => [
                'psnprofiles_unmatched' => TrophyGuideUrl::where('source', 'psnprofiles')->whereNull('game_id')->count(),
                'psnprofiles_matched' => TrophyGuideUrl::where('source', 'psnprofiles')->whereNotNull('game_id')->count(),
                'pst_unmatched' => TrophyGuideUrl::where('source', 'playstationtrophies')->whereNull('game_id')->count(),
                'pst_matched' => TrophyGuideUrl::where('source', 'playstationtrophies')->whereNotNull('game_id')->count(),
            ],
        ]);
    }

    public function match(Request $request, $id)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
        ]);

        $trophyUrl = TrophyGuideUrl::findOrFail($id);
        $game = Game::findOrFail($request->game_id);

        // Update trophy URL record
        $trophyUrl->game_id = $game->id;
        $trophyUrl->matched_at = now();
        $trophyUrl->save();

        // Update game with URL
        $urlField = $trophyUrl->source === 'psnprofiles' ? 'psnprofiles_url' : 'playstationtrophies_url';
        if (empty($game->$urlField)) {
            $game->$urlField = $trophyUrl->url;
            $game->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Matched to {$game->title}",
        ]);
    }

    public function destroy($id)
    {
        $trophyUrl = TrophyGuideUrl::findOrFail($id);
        $trophyUrl->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
