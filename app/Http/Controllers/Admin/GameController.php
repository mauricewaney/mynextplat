<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\Platform;
use App\Services\IGDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    protected $igdbService;

    public function __construct(IGDBService $igdbService)
    {
        $this->igdbService = $igdbService;
    }

    public function index(Request $request)
    {
        $query = Game::with(['genres', 'tags', 'platforms']);

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        // Genre filter
        if ($request->filled('genre_ids')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->whereIn('genres.id', $request->genre_ids);
            });
        }

        // Tag filter
        if ($request->filled('tag_ids')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->whereIn('tags.id', $request->tag_ids);
            });
        }

        // Platform filter
        if ($request->filled('platform_ids')) {
            $query->whereHas('platforms', function ($q) use ($request) {
                $q->whereIn('platforms.id', $request->platform_ids);
            });
        }

        // Difficulty filters (only apply if not at default range, include NULL values)
        $diffMin = $request->input('difficulty_min');
        $diffMax = $request->input('difficulty_max');
        if ($request->filled('difficulty_min') && $diffMin > 1) {
            $query->where(function ($q) use ($diffMin) {
                $q->where('difficulty', '>=', $diffMin)->orWhereNull('difficulty');
            });
        }
        if ($request->filled('difficulty_max') && $diffMax < 10) {
            $query->where(function ($q) use ($diffMax) {
                $q->where('difficulty', '<=', $diffMax)->orWhereNull('difficulty');
            });
        }

        // Time filters (only apply if not at default range, include NULL values)
        $timeMin = $request->input('time_min');
        $timeMax = $request->input('time_max');
        if ($request->filled('time_min') && $timeMin > 0) {
            $query->where(function ($q) use ($timeMin) {
                $q->where('time_max', '>=', $timeMin)->orWhereNull('time_max');
            });
        }
        if ($request->filled('time_max') && $timeMax < 200) {
            $query->where(function ($q) use ($timeMax) {
                $q->where('time_min', '<=', $timeMax)->orWhereNull('time_min');
            });
        }

        // Max playthroughs
        if ($request->filled('max_playthroughs')) {
            $query->where('playthroughs_required', '<=', $request->max_playthroughs);
        }

        // Min score
        if ($request->filled('min_score')) {
            $query->where(function ($q) use ($request) {
                $q->where('critic_score', '>=', $request->min_score)
                    ->orWhere('opencritic_score', '>=', $request->min_score);
            });
        }

        // Boolean filters
        if ($request->filled('has_online_trophies')) {
            $query->where('has_online_trophies', $request->has_online_trophies === 'true' || $request->has_online_trophies === true);
        }
        if ($request->filled('missable_trophies')) {
            $query->where('missable_trophies', $request->missable_trophies === 'true' || $request->missable_trophies === true);
        }

        // No genres/tags/platforms filters
        if ($request->filled('no_genres') && $request->no_genres) {
            $query->doesntHave('genres');
        }
        if ($request->filled('no_tags') && $request->no_tags) {
            $query->doesntHave('tags');
        }
        if ($request->filled('no_platforms') && $request->no_platforms) {
            $query->doesntHave('platforms');
        }

        // Has guide filter
        if ($request->filled('has_guide') && $request->has_guide) {
            $query->where(function ($q) {
                $q->whereNotNull('psnprofiles_url')
                  ->orWhereNotNull('playstationtrophies_url')
                  ->orWhereNotNull('powerpyx_url');
            });
        }

        // Needs data filter (has guide but no difficulty)
        if ($request->filled('needs_data') && $request->needs_data) {
            $query->where(function ($q) {
                $q->whereNotNull('psnprofiles_url')
                  ->orWhereNotNull('playstationtrophies_url')
                  ->orWhereNotNull('powerpyx_url');
            })->whereNull('difficulty');
        }

        // Guide source filters (PSNP, PST, PPX)
        if ($request->filled('guide_psnp') && $request->guide_psnp) {
            $query->whereNotNull('psnprofiles_url');
        }
        if ($request->filled('guide_pst') && $request->guide_pst) {
            $query->whereNotNull('playstationtrophies_url');
        }
        if ($request->filled('guide_ppx') && $request->guide_ppx) {
            $query->whereNotNull('powerpyx_url');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Handle NULL values for numeric columns - put NULLs at the end
        $nullableColumns = ['difficulty', 'time_min', 'time_max', 'critic_score', 'opencritic_score', 'release_date'];
        if (in_array($sortBy, $nullableColumns)) {
            // Sort NULLs last regardless of sort direction
            $query->orderByRaw("CASE WHEN {$sortBy} IS NULL THEN 1 ELSE 0 END")
                  ->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $games = $query->paginate(50);

        return response()->json([
            'data' => $games->items(),
            'total' => $games->total(),
            'current_page' => $games->currentPage(),
            'last_page' => $games->lastPage(),
        ]);
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

        return response()->json($game->load('genres', 'tags', 'platforms'));
    }

    public function destroy($id)
    {
        $game = Game::findOrFail($id);
        $game->delete();

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
            $q->whereNotNull('playstationtrophies_url')
              ->orWhereNotNull('powerpyx_url');
        })->count();
        $gamesWithDifficulty = Game::whereNotNull('difficulty')->count();
        $gamesNeedingData = Game::where(function ($q) {
            $q->whereNotNull('playstationtrophies_url')
              ->orWhereNotNull('powerpyx_url');
        })->whereNull('difficulty')->count();

        return response()->json([
            'total_games' => $totalGames,
            'with_guide' => $gamesWithGuide,
            'with_difficulty' => $gamesWithDifficulty,
            'needs_data' => $gamesNeedingData,
            'completion_percent' => $gamesWithGuide > 0
                ? round(($gamesWithGuide - $gamesNeedingData) / $gamesWithGuide * 100, 1)
                : 0,
        ]);
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

        return response()->json(['message' => 'Platforms removed successfully']);
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
