<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Platform;
use App\Models\TrophyGuideUrl;
use App\Services\IGDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrophyUrlImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'html' => 'required|string|min:100',
            'source' => 'required|in:psnprofiles,playstationtrophies,powerpyx',
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
            $urlField = match($source) {
                'psnprofiles' => 'psnprofiles_url',
                'playstationtrophies' => 'playstationtrophies_url',
                'powerpyx' => 'powerpyx_url',
            };
            if ($game && empty($game->$urlField)) {
                $game->$urlField = $url;
                $game->save();
                $stats['matched']++;
            }

            $stats['new']++;
        }

        // Run URL matcher for any unmatched new URLs
        if ($stats['new'] > 0) {
            Artisan::call('trophy:match-urls', ['--source' => $source]);
        }

        // Get total counts
        $stats['total_psnprofiles'] = TrophyGuideUrl::where('source', 'psnprofiles')->count();
        $stats['total_playstationtrophies'] = TrophyGuideUrl::where('source', 'playstationtrophies')->count();
        $stats['total_powerpyx'] = TrophyGuideUrl::where('source', 'powerpyx')->count();
        $stats['games_with_psnprofiles'] = Game::whereNotNull('psnprofiles_url')->count();
        $stats['games_with_playstationtrophies'] = Game::whereNotNull('playstationtrophies_url')->count();
        $stats['games_with_powerpyx'] = Game::whereNotNull('powerpyx_url')->count();

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
        } elseif ($source === 'powerpyx') {
            // Match: href="...trophy-guide..." or href="...trophy-guide-roadmap..."
            if (preg_match_all('#href=["\']([^"\']*(?:trophy-guide|trophy-guide-roadmap)[^"\']*)["\']#i', $html, $matches)) {
                foreach ($matches[1] as $path) {
                    $url = $path;
                    if (!str_starts_with($url, 'http')) {
                        $url = 'https://www.powerpyx.com' . $url;
                    }
                    $urls[] = $url;
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
        } elseif ($source === 'powerpyx') {
            // URL: https://www.powerpyx.com/game-name-trophy-guide-roadmap/
            if (preg_match('#powerpyx\.com/([^/]+)-trophy-guide(?:-roadmap)?/?$#i', $url, $matches)) {
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
            'powerpyx' => [
                'total_urls' => TrophyGuideUrl::where('source', 'powerpyx')->count(),
                'matched' => TrophyGuideUrl::where('source', 'powerpyx')->whereNotNull('game_id')->count(),
                'unmatched' => TrophyGuideUrl::where('source', 'powerpyx')->whereNull('game_id')->count(),
                'games_with_url' => Game::whereNotNull('powerpyx_url')->count(),
            ],
            'total_games' => Game::count(),
        ]);
    }

    public function unmatched(Request $request)
    {
        $showDlc = $request->boolean('include_dlc');

        $urls = TrophyGuideUrl::whereNull('game_id')
            ->where('is_dlc', $showDlc)
            ->orderByDesc('created_at')
            ->get(['id', 'source', 'url', 'extracted_slug', 'extracted_title', 'created_at']);

        return response()->json([
            'urls' => $urls,
            'stats' => [
                'psnprofiles_unmatched' => TrophyGuideUrl::where('source', 'psnprofiles')->whereNull('game_id')->where('is_dlc', false)->count(),
                'psnprofiles_matched' => TrophyGuideUrl::where('source', 'psnprofiles')->whereNotNull('game_id')->count(),
                'pst_unmatched' => TrophyGuideUrl::where('source', 'playstationtrophies')->whereNull('game_id')->where('is_dlc', false)->count(),
                'pst_matched' => TrophyGuideUrl::where('source', 'playstationtrophies')->whereNotNull('game_id')->count(),
                'ppx_unmatched' => TrophyGuideUrl::where('source', 'powerpyx')->whereNull('game_id')->where('is_dlc', false)->count(),
                'ppx_matched' => TrophyGuideUrl::where('source', 'powerpyx')->whereNotNull('game_id')->count(),
                'dlc_count' => TrophyGuideUrl::whereNull('game_id')->where('is_dlc', true)->count(),
            ],
        ]);
    }

    public function toggleDlc($id)
    {
        $trophyUrl = TrophyGuideUrl::findOrFail($id);
        $trophyUrl->is_dlc = !$trophyUrl->is_dlc;
        $trophyUrl->save();

        return response()->json([
            'success' => true,
            'is_dlc' => $trophyUrl->is_dlc,
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

        // Update game with URL (only if empty, frontend handles confirmation)
        $urlField = match($trophyUrl->source) {
            'psnprofiles' => 'psnprofiles_url',
            'playstationtrophies' => 'playstationtrophies_url',
            'powerpyx' => 'powerpyx_url',
        };
        if (empty($game->$urlField) || $request->boolean('force')) {
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

    /**
     * Search IGDB for games
     */
    public function searchIgdb(Request $request, IGDBService $igdbService)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        try {
            $games = $igdbService->searchGames($request->query('query'), 10);

            return response()->json([
                'success' => true,
                'games' => $games,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IGDB search failed: ' . $e->getMessage(),
                'games' => [],
            ], 500);
        }
    }

    /**
     * Import a game from IGDB and match it to a trophy URL
     */
    public function scrapePowerPyx()
    {
        try {
            $exitCode = Artisan::call('ppx:scrape-guides');
            $output = Artisan::output();

            // Parse key stats from command output
            $stats = [
                'new' => 0,
                'existing' => 0,
                'matched' => 0,
            ];

            if (preg_match('/New URLs added\s*\|\s*(\d+)/i', $output, $m)) {
                $stats['new'] = (int) $m[1];
            }
            if (preg_match('/Already existed\s*\|\s*(\d+)/i', $output, $m)) {
                $stats['existing'] = (int) $m[1];
            }
            if (preg_match('/Auto-matched to games\s*\|\s*(\d+)/i', $output, $m)) {
                $stats['matched'] = (int) $m[1];
            }

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? "PowerPyx import complete: {$stats['new']} new, {$stats['existing']} existing, {$stats['matched']} matched"
                    : 'PowerPyx import failed',
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('PowerPyx scrape failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'PowerPyx import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importFromIgdb(Request $request, $id, IGDBService $igdbService)
    {
        $request->validate([
            'igdb_id' => 'required|integer',
            'title' => 'required|string',
            'slug' => 'required|string',
            'cover_url' => 'nullable|string',
            'banner_url' => 'nullable|string',
            'developer' => 'nullable|string',
            'publisher' => 'nullable|string',
            'release_date' => 'nullable|date',
            'critic_score' => 'nullable|integer',
            'platforms_data' => 'nullable|array',
        ]);

        $trophyUrl = TrophyGuideUrl::findOrFail($id);

        // Check if game with this IGDB ID already exists
        $existingGame = Game::where('igdb_id', $request->igdb_id)->first();

        if ($existingGame) {
            $urlField = match($trophyUrl->source) {
                'psnprofiles' => 'psnprofiles_url',
                'playstationtrophies' => 'playstationtrophies_url',
                'powerpyx' => 'powerpyx_url',
            };

            // Check if game already has a guide URL for this source
            if (!empty($existingGame->$urlField) && !$request->boolean('force')) {
                return response()->json([
                    'success' => false,
                    'needs_confirmation' => true,
                    'existing_url' => $existingGame->$urlField,
                    'game_title' => $existingGame->title,
                    'message' => 'Game already has a guide URL for this source',
                ]);
            }

            // Match to the existing game
            $trophyUrl->game_id = $existingGame->id;
            $trophyUrl->matched_at = now();
            $trophyUrl->save();

            // Update game with URL (overwrite if force=true)
            $existingGame->$urlField = $trophyUrl->url;
            $existingGame->save();

            return response()->json([
                'success' => true,
                'message' => "Matched to existing game: {$existingGame->title}",
                'game' => $existingGame,
            ]);
        }

        // Create new game
        $game = Game::create([
            'igdb_id' => $request->igdb_id,
            'title' => $request->title,
            'slug' => $request->slug,
            'cover_url' => $request->cover_url,
            'banner_url' => $request->banner_url,
            'developer' => $request->developer,
            'publisher' => $request->publisher,
            'release_date' => $request->release_date,
            'critic_score' => $request->critic_score,
        ]);

        // Attach platforms
        if ($request->has('platforms_data') && is_array($request->platforms_data)) {
            $platformIds = [];
            foreach ($request->platforms_data as $platformData) {
                $platform = Platform::firstOrCreate(
                    ['slug' => $platformData['slug']],
                    [
                        'name' => $platformData['name'],
                        'short_name' => $platformData['short_name'] ?? null,
                    ]
                );
                $platformIds[] = $platform->id;
            }
            $game->platforms()->sync($platformIds);
        }

        // Match trophy URL to game
        $trophyUrl->game_id = $game->id;
        $trophyUrl->matched_at = now();
        $trophyUrl->save();

        // Set guide URL on game
        $urlField = match($trophyUrl->source) {
            'psnprofiles' => 'psnprofiles_url',
            'playstationtrophies' => 'playstationtrophies_url',
            'powerpyx' => 'powerpyx_url',
        };
        $game->$urlField = $trophyUrl->url;
        $game->save();

        // Load platforms for response
        $game->load('platforms');

        return response()->json([
            'success' => true,
            'message' => "Created and matched: {$game->title}",
            'game' => $game,
        ]);
    }
}
