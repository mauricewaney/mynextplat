<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\Platform;
use App\Services\IGDBService;
use App\Services\GameFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GameController extends Controller
{
    protected $igdbService;
    protected $filterService;

    public function __construct(IGDBService $igdbService, GameFilterService $filterService)
    {
        $this->igdbService = $igdbService;
        $this->filterService = $filterService;
    }

    public function index(Request $request)
    {
        $query = Game::with(['genres', 'tags', 'platforms']);

        // Apply filters (admin mode = true for all filters)
        $this->filterService->applyFilters($query, $request, true);

        // Paginate (admin default: 50 per page)
        return response()->json(
            $this->filterService->paginate($query, $request, 50, 100)
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateGameData($request);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Game::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $game = Game::create($validated);

        // Sync relationships
        if (isset($validated['genre_ids'])) {
            $game->genres()->sync($validated['genre_ids']);
        }
        if (isset($validated['tag_ids'])) {
            $game->tags()->sync($validated['tag_ids']);
        }
        if (isset($validated['platform_ids'])) {
            $game->platforms()->sync($validated['platform_ids']);
        }

        // Clear games cache
        Cache::forget('games:default:page1');

        return response()->json($game->load('genres', 'tags', 'platforms'), 201);
    }

    public function show($id)
    {
        $game = Game::with(['genres', 'tags', 'platforms'])->findOrFail($id);
        return response()->json($game);
    }

    public function update(Request $request, $id)
    {
        $game = Game::findOrFail($id);

        $validated = $this->validateGameData($request, $id);

        // Handle slug
        if (isset($validated['slug']) && $validated['slug'] !== $game->slug) {
            // Ensure new slug is unique
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Game::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $game->update($validated);

        // Sync relationships
        if (isset($validated['genre_ids'])) {
            $game->genres()->sync($validated['genre_ids']);
        }
        if (isset($validated['tag_ids'])) {
            $game->tags()->sync($validated['tag_ids']);
        }
        if (isset($validated['platform_ids'])) {
            $game->platforms()->sync($validated['platform_ids']);
        }

        // Clear games cache
        Cache::forget('games:default:page1');

        return response()->json($game->load('genres', 'tags', 'platforms'));
    }

    public function destroy($id)
    {
        $game = Game::findOrFail($id);
        $game->delete();

        // Clear games cache
        Cache::forget('games:default:page1');

        return response()->json(['message' => 'Game deleted successfully']);
    }

    public function getFormData()
    {
        return response()->json([
            'genres' => Genre::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'platforms' => Platform::orderBy('name')->get(),
        ]);
    }

    public function getStats()
    {
        $totalGames = Game::count();
        $gamesWithGuide = Game::where(function ($q) {
            $q->whereNotNull('psnprofiles_url')
              ->orWhereNotNull('playstationtrophies_url')
              ->orWhereNotNull('powerpyx_url');
        })->count();
        $gamesWithDifficulty = Game::whereNotNull('difficulty')->count();

        // needs_data: has a guide URL but missing ALL of: difficulty, time_min, playthroughs_required
        $gamesNeedingData = Game::where(function ($q) {
            $q->whereNotNull('psnprofiles_url')
              ->orWhereNotNull('playstationtrophies_url')
              ->orWhereNotNull('powerpyx_url');
        })
        ->whereNull('difficulty')
        ->whereNull('time_min')
        ->whereNull('playthroughs_required')
        ->count();

        $verified = Game::where('is_verified', true)->count();

        return response()->json([
            'total_games' => $totalGames,
            'with_guide' => $gamesWithGuide,
            'with_difficulty' => $gamesWithDifficulty,
            'needs_data' => $gamesNeedingData,
            'verified' => $verified,
            'verified_percent' => $gamesWithGuide > 0
                ? round($verified / $gamesWithGuide * 100, 1)
                : 0,
            'completion_percent' => $gamesWithGuide > 0
                ? round(($gamesWithGuide - $gamesNeedingData) / $gamesWithGuide * 100, 1)
                : 0,
        ]);
    }

    /**
     * Search games that have guides (for copying guide URLs)
     */
    public function searchGuides(Request $request)
    {
        $search = $request->get('search');

        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $games = Game::with('platforms')
            ->where(function ($q) {
                $q->whereNotNull('psnprofiles_url')
                  ->orWhereNotNull('playstationtrophies_url')
                  ->orWhereNotNull('powerpyx_url');
            })
            ->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%']);
            })
            ->select('id', 'title', 'psnprofiles_url', 'playstationtrophies_url', 'powerpyx_url')
            ->orderByRaw('LENGTH(title) ASC') // Shorter titles first (likely base games)
            ->limit(10)
            ->get();

        return response()->json($games);
    }

    /**
     * Scrape image from IGDB for a single game
     */
    public function scrapeImage($id)
    {
        $game = Game::findOrFail($id);

        try {
            $result = $this->igdbService->searchAndGetCover($game->title);

            if ($result['cover_url']) {
                $game->cover_url = $result['cover_url'];

                // Also save banner if available
                if ($result['banner_url']) {
                    $game->banner_url = $result['banner_url'];
                }

                $game->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Images scraped successfully',
                    'game' => $game->load('genres', 'tags', 'platforms'),
                    'igdb_match' => $result['game_name']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No images found on IGDB for "' . $game->title . '"'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error scraping image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk scrape images for multiple games
     */
    public function bulkScrapeImages(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id'
        ]);

        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($request->game_ids)
        ];

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);

            if (!$game) {
                $results['failed'][] = [
                    'id' => $gameId,
                    'title' => 'Unknown',
                    'reason' => 'Game not found'
                ];
                continue;
            }

            try {
                $result = $this->igdbService->searchAndGetCover($game->title);

                if ($result['cover_url']) {
                    $game->cover_url = $result['cover_url'];

                    if ($result['banner_url']) {
                        $game->banner_url = $result['banner_url'];
                    }

                    $game->save();

                    $results['success'][] = [
                        'id' => $game->id,
                        'title' => $game->title,
                        'igdb_match' => $result['game_name']
                    ];
                } else {
                    $results['failed'][] = [
                        'id' => $game->id,
                        'title' => $game->title,
                        'reason' => 'No images found on IGDB'
                    ];
                }

                // Rate limiting: wait 250ms between requests (4 requests per second)
                usleep(250000);
            } catch (\Exception $e) {
                dd($e->getMessage());
                $results['failed'][] = [
                    'id' => $game->id,
                    'title' => $game->title,
                    'reason' => $e->getMessage()
                ];
            }
        }

        $message = sprintf(
            'Scraped %d of %d games. %d succeeded, %d failed.',
            count($results['success']),
            $results['total'],
            count($results['success']),
            count($results['failed'])
        );

        return response()->json([
            'message' => $message,
            'results' => $results
        ]);
    }

    /**
     * Test IGDB connection
     */
    public function testIgdb()
    {
        $result = $this->igdbService->testConnection();
        return response()->json($result);
    }

    // Bulk operations
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id'
        ]);

        Game::whereIn('id', $request->game_ids)->delete();
        Cache::forget('games:default:page1');

        return response()->json(['message' => 'Games deleted successfully']);
    }

    public function bulkAddGenres(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id',
            'genre_ids' => 'required|array',
            'genre_ids.*' => 'exists:genres,id'
        ]);

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);
            $game->genres()->syncWithoutDetaching($request->genre_ids);
        }

        Cache::forget('games:default:page1');
        return response()->json(['message' => 'Genres added successfully']);
    }

    public function bulkRemoveGenres(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id',
            'genre_ids' => 'required|array',
            'genre_ids.*' => 'exists:genres,id'
        ]);

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);
            $game->genres()->detach($request->genre_ids);
        }

        Cache::forget('games:default:page1');
        return response()->json(['message' => 'Genres removed successfully']);
    }

    public function bulkAddTags(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);
            $game->tags()->syncWithoutDetaching($request->tag_ids);
        }

        Cache::forget('games:default:page1');
        return response()->json(['message' => 'Tags added successfully']);
    }

    public function bulkRemoveTags(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);
            $game->tags()->detach($request->tag_ids);
        }

        Cache::forget('games:default:page1');
        return response()->json(['message' => 'Tags removed successfully']);
    }

    public function bulkAddPlatforms(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id',
            'platform_ids' => 'required|array',
            'platform_ids.*' => 'exists:platforms,id'
        ]);

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);
            $game->platforms()->syncWithoutDetaching($request->platform_ids);
        }

        Cache::forget('games:default:page1');
        return response()->json(['message' => 'Platforms added successfully']);
    }

    public function bulkRemovePlatforms(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'exists:games,id',
            'platform_ids' => 'required|array',
            'platform_ids.*' => 'exists:platforms,id'
        ]);

        foreach ($request->game_ids as $gameId) {
            $game = Game::find($gameId);
            $game->platforms()->detach($request->platform_ids);
        }

        Cache::forget('games:default:page1');
        return response()->json(['message' => 'Platforms removed successfully']);
    }

    /**
     * Search games for linking (used by PSN title matching UI)
     */
    public function searchGamesForLinking(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->query('query');

        $games = Game::where('title', 'like', "%{$query}%")
            ->select('id', 'title', 'np_communication_ids', 'cover_url')
            ->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END", ["{$query}%"])
            ->orderBy('title')
            ->limit(20)
            ->get();

        return response()->json($games);
    }

    /**
     * Get PSN titles linked to a specific game
     */
    public function getLinkedPsnTitles(int $gameId)
    {
        $game = Game::findOrFail($gameId);

        $psnTitles = \App\Models\PsnTitle::where('game_id', $gameId)
            ->orderBy('psn_title')
            ->get();

        return response()->json([
            'game' => [
                'id' => $game->id,
                'title' => $game->title,
                'np_communication_ids' => $game->np_communication_ids,
            ],
            'psn_titles' => $psnTitles,
        ]);
    }

    /**
     * Merge duplicate games into one
     */
    public function mergeGames(Request $request)
    {
        $request->validate([
            'primary_id' => 'required|exists:games,id',
            'duplicate_id' => 'required|exists:games,id|different:primary_id',
        ]);

        $primary = Game::with(['genres', 'tags', 'platforms'])->findOrFail($request->primary_id);
        $duplicate = Game::with(['genres', 'tags', 'platforms'])->findOrFail($request->duplicate_id);

        // Merge NP Communication IDs
        $npIds = array_unique(array_merge(
            $primary->np_communication_ids ?? [],
            $duplicate->np_communication_ids ?? []
        ));
        $primary->np_communication_ids = !empty($npIds) ? array_values($npIds) : null;

        // Merge guide URLs (only if primary doesn't have them)
        if (!$primary->psnprofiles_url && $duplicate->psnprofiles_url) {
            $primary->psnprofiles_url = $duplicate->psnprofiles_url;
        }
        if (!$primary->playstationtrophies_url && $duplicate->playstationtrophies_url) {
            $primary->playstationtrophies_url = $duplicate->playstationtrophies_url;
        }
        if (!$primary->powerpyx_url && $duplicate->powerpyx_url) {
            $primary->powerpyx_url = $duplicate->powerpyx_url;
        }

        // Merge other fields if primary is missing them
        $fieldsToMerge = [
            'cover_url', 'banner_url', 'description', 'developer', 'publisher',
            'release_date', 'difficulty', 'time_min', 'time_max', 'playthroughs_required',
            'critic_score', 'opencritic_score', 'trophy_icon_url',
            'bronze_count', 'silver_count', 'gold_count', 'platinum_count',
        ];
        foreach ($fieldsToMerge as $field) {
            if ($primary->$field === null && $duplicate->$field !== null) {
                $primary->$field = $duplicate->$field;
            }
        }

        // Merge boolean fields (prefer true)
        if ($duplicate->has_platinum && !$primary->has_platinum) {
            $primary->has_platinum = true;
        }
        if ($duplicate->has_online_trophies && !$primary->has_online_trophies) {
            $primary->has_online_trophies = true;
        }
        if ($duplicate->missable_trophies && !$primary->missable_trophies) {
            $primary->missable_trophies = true;
        }

        $primary->save();

        // Merge relationships (add duplicate's to primary)
        $primary->genres()->syncWithoutDetaching($duplicate->genres->pluck('id')->toArray());
        $primary->tags()->syncWithoutDetaching($duplicate->tags->pluck('id')->toArray());
        $primary->platforms()->syncWithoutDetaching($duplicate->platforms->pluck('id')->toArray());

        // Update all PSN titles that were linked to duplicate
        \App\Models\PsnTitle::where('game_id', $duplicate->id)
            ->update(['game_id' => $primary->id]);

        // Delete the duplicate
        $duplicateTitle = $duplicate->title;
        $duplicate->delete();

        // Clear games cache
        Cache::forget('games:default:page1');

        return response()->json([
            'success' => true,
            'message' => "Merged \"{$duplicateTitle}\" into \"{$primary->title}\"",
            'game' => $primary->fresh()->load(['genres', 'tags', 'platforms']),
        ]);
    }

    /**
     * Find potential duplicate games
     */
    public function findDuplicates(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:2',
            'exclude_id' => 'nullable|integer',
        ]);

        $search = $request->title;
        $excludeId = $request->exclude_id;

        // Normalize search term for better matching
        $normalized = preg_replace('/[^\w\s]/u', '', strtolower($search));
        $words = array_filter(explode(' ', $normalized));

        $query = Game::with('platforms')
            ->select('id', 'title', 'cover_url', 'np_communication_ids', 'psnprofiles_url', 'playstationtrophies_url', 'powerpyx_url');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Search for similar titles
        $query->where(function ($q) use ($search, $words) {
            // Exact match first
            $q->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%']);

            // Also match by individual words (for reordered titles)
            foreach ($words as $word) {
                if (strlen($word) >= 3) {
                    $q->orWhereRaw('LOWER(title) LIKE ?', ['%' . $word . '%']);
                }
            }
        });

        $games = $query->orderByRaw('LENGTH(title) ASC')
            ->limit(15)
            ->get();

        return response()->json($games);
    }

    /**
     * Validate game data for create/update
     */
    private function validateGameData(Request $request, $gameId = null)
    {
        $slugRule = 'nullable|string|max:255|unique:games,slug';
        if ($gameId) {
            $slugRule .= ',' . $gameId;
        }

        return $request->validate([
            // Basic info
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'description' => 'nullable|string',
            'developer' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'release_date' => 'nullable|date',

            // Trophy info
            'difficulty' => 'nullable|integer|min:1|max:10',
            'time_min' => 'nullable|numeric|min:0',
            'time_max' => 'nullable|numeric|min:0',
            'playthroughs_required' => 'nullable|integer|min:1',
            'has_online_trophies' => 'nullable|boolean',
            'missable_trophies' => 'nullable|boolean',
            'is_unobtainable' => 'nullable|boolean',
            'server_shutdown_date' => 'nullable|date',
            'is_verified' => 'nullable|boolean',
            'data_source' => 'nullable|string|max:30',

            // Scores
            'critic_score' => 'nullable|integer|min:0|max:100',
            'opencritic_score' => 'nullable|integer|min:0|max:100',

            // Pricing
            'price_current' => 'nullable|numeric|min:0',
            'price_original' => 'nullable|numeric|min:0',
            'amazon_link' => 'nullable|url|max:500',
            'bol_link' => 'nullable|url|max:500',

            // Images & links
            'cover_url' => 'nullable|url|max:500',
            'banner_url' => 'nullable|url|max:500',
            'psnprofiles_url' => 'nullable|url|max:500',
            'playstationtrophies_url' => 'nullable|url|max:500',
            'powerpyx_url' => 'nullable|url|max:500',
            'psn_store_url' => 'nullable|url|max:500',

            // Relationships
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'exists:genres,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'platform_ids' => 'required|array|min:1',
            'platform_ids.*' => 'exists:platforms,id',
        ], [
            'title.required' => 'Game title is required',
            'platform_ids.required' => 'At least one platform is required',
            'platform_ids.min' => 'At least one platform is required',
            'difficulty.min' => 'Difficulty must be between 1 and 10',
            'difficulty.max' => 'Difficulty must be between 1 and 10',
        ]);
    }
}
