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
     * Get unmatched PSN titles from the log file
     */
    public function getUnmatchedPsnTitles()
    {
        $logPath = storage_path('logs/psn_unmatched.txt');

        if (!file_exists($logPath)) {
            return response()->json([
                'unmatched' => [],
                'message' => 'No unmatched titles found. Load a PSN library first.'
            ]);
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $unmatched = [];

        foreach ($lines as $line) {
            if (preg_match('/^(.+?)\s*\[([A-Z]{4}\d+_\d+)\]$/', $line, $matches)) {
                $title = trim($matches[1]);
                $npId = $matches[2];

                // Check if already linked
                $existingGame = Game::whereJsonContains('np_communication_ids', $npId)->first();

                $unmatched[] = [
                    'psn_title' => $title,
                    'np_id' => $npId,
                    'linked_to' => $existingGame ? [
                        'id' => $existingGame->id,
                        'title' => $existingGame->title,
                    ] : null,
                    'suggestions' => $existingGame ? [] : $this->findSuggestions($title),
                ];
            }
        }

        return response()->json([
            'unmatched' => $unmatched,
            'total' => count($unmatched),
            'linked' => count(array_filter($unmatched, fn($u) => $u['linked_to'] !== null)),
        ]);
    }

    /**
     * Link an NP ID to a game
     */
    public function linkNpId(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'np_id' => 'required|string|regex:/^[A-Z]{4}\d+_\d+$/',
        ]);

        $game = Game::findOrFail($request->game_id);
        $npId = $request->np_id;

        // Check if NP ID is already linked to another game
        $existingGame = Game::whereJsonContains('np_communication_ids', $npId)->first();
        if ($existingGame && $existingGame->id !== $game->id) {
            return response()->json([
                'success' => false,
                'message' => "NP ID is already linked to \"{$existingGame->title}\" (ID {$existingGame->id})",
            ], 409);
        }

        // Add NP ID to game
        $ids = $game->np_communication_ids ?? [];
        if (!in_array($npId, $ids)) {
            $ids[] = $npId;
            $game->np_communication_ids = $ids;
            $game->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Linked {$npId} to \"{$game->title}\"",
            'game' => $game,
        ]);
    }

    /**
     * Unlink an NP ID from a game
     */
    public function unlinkNpId(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'np_id' => 'required|string',
        ]);

        $game = Game::findOrFail($request->game_id);
        $npId = $request->np_id;

        $ids = $game->np_communication_ids ?? [];
        $ids = array_values(array_filter($ids, fn($id) => $id !== $npId));

        $game->np_communication_ids = empty($ids) ? null : $ids;
        $game->save();

        return response()->json([
            'success' => true,
            'message' => "Unlinked {$npId} from \"{$game->title}\"",
            'game' => $game,
        ]);
    }

    /**
     * Search games for NP ID linking
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
     * Find game suggestions for a PSN title
     */
    private function findSuggestions(string $title): array
    {
        $normalized = $this->normalizeForSearch($title);
        $suggestions = [];

        // Get all games (memory intensive but needed for fuzzy search)
        $games = Game::select('id', 'title')->get();

        foreach ($games as $game) {
            $normalizedDb = $this->normalizeForSearch($game->title);

            similar_text($normalized, $normalizedDb, $percent);

            // Bonus for containment
            if (str_contains($normalizedDb, $normalized) || str_contains($normalized, $normalizedDb)) {
                $percent = min(100, $percent + 15);
            }

            if ($percent >= 60) {
                $suggestions[] = [
                    'id' => $game->id,
                    'title' => $game->title,
                    'similarity' => round($percent),
                ];
            }
        }

        // Sort by similarity
        usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($suggestions, 0, 5);
    }

    /**
     * Normalize title for search matching
     */
    private function normalizeForSearch(string $str): string
    {
        $str = preg_replace('/[\x{2122}\x{00AE}\x{00A9}]/u', '', $str);
        $str = preg_replace('/[\x{2018}\x{2019}\x{0060}]/u', "'", $str);
        $str = preg_replace('/[\x{201C}\x{201D}]/u', '"', $str);
        $str = preg_replace('/\s*[:\x{2013}\x{2014}-]\s*/u', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return strtolower(trim($str));
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
