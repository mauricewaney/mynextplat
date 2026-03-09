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
use Illuminate\Support\Facades\DB;
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
        \App\Http\Controllers\GameController::bustGameCache();

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
        \App\Http\Controllers\GameController::bustGameCache();

        return response()->json($game->load('genres', 'tags', 'platforms'));
    }

    public function destroy($id)
    {
        $game = Game::findOrFail($id);
        $game->delete();

        // Clear games cache
        \App\Http\Controllers\GameController::bustGameCache();

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
        $gamesWithGuide = Game::where('has_guide', true)->count();
        $gamesWithDifficulty = Game::whereNotNull('difficulty')->count();

        // needs_data: has a guide but missing ALL of: difficulty, time_min, playthroughs_required
        $gamesNeedingData = Game::where('has_guide', true)
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
            ->where('has_guide', true)
            ->where('title', 'LIKE', '%' . $search . '%')
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
        \App\Http\Controllers\GameController::bustGameCache();

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

        \App\Http\Controllers\GameController::bustGameCache();
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

        \App\Http\Controllers\GameController::bustGameCache();
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

        \App\Http\Controllers\GameController::bustGameCache();
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

        \App\Http\Controllers\GameController::bustGameCache();
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

        \App\Http\Controllers\GameController::bustGameCache();
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

        \App\Http\Controllers\GameController::bustGameCache();
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

        // If the duplicate has more user ratings, it's the more popular version —
        // take all its scores as the canonical ones
        if (($duplicate->user_score_count ?? 0) > ($primary->user_score_count ?? 0)) {
            $primary->user_score = $duplicate->user_score;
            $primary->user_score_count = $duplicate->user_score_count;
            $primary->critic_score = $duplicate->critic_score;
            $primary->critic_score_count = $duplicate->critic_score_count;
            $primary->opencritic_score = $duplicate->opencritic_score;
        }

        // Trophy breakdown — take from whichever actually has counts
        $primaryTrophyTotal = ($primary->bronze_count ?? 0) + ($primary->silver_count ?? 0)
            + ($primary->gold_count ?? 0) + ($primary->platinum_count ?? 0);
        $duplicateTrophyTotal = ($duplicate->bronze_count ?? 0) + ($duplicate->silver_count ?? 0)
            + ($duplicate->gold_count ?? 0) + ($duplicate->platinum_count ?? 0);
        if ($duplicateTrophyTotal > 0 && $primaryTrophyTotal === 0) {
            $primary->bronze_count = $duplicate->bronze_count;
            $primary->silver_count = $duplicate->silver_count;
            $primary->gold_count = $duplicate->gold_count;
            $primary->platinum_count = $duplicate->platinum_count;
        }

        // Merge other fields if primary is missing them
        $fieldsToMerge = [
            'cover_url', 'banner_url', 'description', 'developer', 'publisher',
            'release_date', 'difficulty', 'time_min', 'time_max', 'playthroughs_required',
            'user_score', 'user_score_count', 'critic_score', 'critic_score_count',
            'opencritic_score', 'trophy_icon_url',
        ];
        foreach ($fieldsToMerge as $field) {
            if ($primary->$field === null && $duplicate->$field !== null) {
                $primary->$field = $duplicate->$field;
            }
        }

        // Merge igdb_id if primary doesn't have one
        if (!$primary->igdb_id && $duplicate->igdb_id) {
            $primary->igdb_id = $duplicate->igdb_id;
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

        // Transfer user_game entries (skip conflicts where user already has primary)
        DB::table('user_game')
            ->where('game_id', $duplicate->id)
            ->whereNotIn('user_id', DB::table('user_game')->where('game_id', $primary->id)->pluck('user_id'))
            ->update(['game_id' => $primary->id]);
        DB::table('user_game')->where('game_id', $duplicate->id)->delete();

        // Transfer guide clicks
        DB::table('guide_clicks')->where('game_id', $duplicate->id)->update(['game_id' => $primary->id]);

        // Transfer featured clicks
        DB::table('featured_clicks')->where('game_id', $duplicate->id)->update(['game_id' => $primary->id]);

        // Transfer featured placements
        DB::table('featured_placements')->where('game_id', $duplicate->id)->update(['game_id' => $primary->id]);

        // Transfer game corrections
        DB::table('game_corrections')->where('game_id', $duplicate->id)->update(['game_id' => $primary->id]);

        // Transfer trophy guide URLs
        DB::table('trophy_guide_urls')->where('game_id', $duplicate->id)->update(['game_id' => $primary->id]);

        // Delete the duplicate
        $duplicateTitle = $duplicate->title;
        $duplicate->delete();

        // Clear games cache
        \App\Http\Controllers\GameController::bustGameCache();

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
     * Scan all games for potential duplicates by normalized title
     */
    public function scanDuplicates()
    {
        $games = Game::select(
            'id', 'title', 'igdb_id', 'cover_url', 'has_platinum',
            'bronze_count', 'silver_count', 'gold_count', 'platinum_count',
            'psnprofiles_url', 'playstationtrophies_url', 'powerpyx_url',
            'difficulty', 'time_min', 'time_max', 'description', 'developer',
            'release_date', 'critic_score'
        )->get();

        // Build game data with counts (once, reused for both passes)
        $gameDataMap = [];
        foreach ($games as $game) {
            $gameDataMap[$game->id] = $this->buildGameDataForScan($game);
        }

        // Pass 1: Exact duplicates (same normalized title)
        $exactGroups = [];
        foreach ($games as $game) {
            $normalized = $this->normalizeTitle($game->title);
            $exactGroups[$normalized][] = $game->id;
        }

        $exactDuplicates = [];
        $exactGameIds = []; // Track which games are already in exact duplicates
        foreach ($exactGroups as $normalized => $ids) {
            if (count($ids) < 2) continue;
            $groupData = array_map(fn($id) => $gameDataMap[$id], $ids);
            usort($groupData, fn($a, $b) => $b['completeness'] <=> $a['completeness']);
            $exactDuplicates[] = [
                'normalized_title' => $normalized,
                'games' => $groupData,
            ];
            foreach ($ids as $id) {
                $exactGameIds[$id] = true;
            }
        }
        usort($exactDuplicates, fn($a, $b) => count($b['games']) <=> count($a['games']));

        // Pass 2: Possible duplicates (same base title after stripping edition suffixes)
        // Only consider games NOT already in exact duplicate groups
        $fuzzyGroups = [];
        foreach ($games as $game) {
            if (isset($exactGameIds[$game->id])) continue;
            $base = $this->stripEditionSuffix($this->normalizeTitle($game->title));
            $fuzzyGroups[$base][] = $game->id;
        }

        $possibleDuplicates = [];
        foreach ($fuzzyGroups as $base => $ids) {
            if (count($ids) < 2) continue;
            // Verify titles are actually different (otherwise they'd be exact dupes)
            $titles = array_map(fn($id) => $gameDataMap[$id]['title'], $ids);
            if (count(array_unique($titles)) < 2) continue;

            $groupData = array_map(fn($id) => $gameDataMap[$id], $ids);
            usort($groupData, fn($a, $b) => $b['completeness'] <=> $a['completeness']);
            $possibleDuplicates[] = [
                'normalized_title' => $base,
                'games' => $groupData,
            ];
        }
        usort($possibleDuplicates, fn($a, $b) => count($b['games']) <=> count($a['games']));

        return response()->json([
            'exact_groups' => $exactDuplicates,
            'possible_groups' => $possibleDuplicates,
        ]);
    }

    /**
     * Build scan data for a single game
     */
    private function buildGameDataForScan(Game $game): array
    {
        $psnTitlesCount = DB::table('psn_titles')->where('game_id', $game->id)->count();
        $usersCount = DB::table('user_game')->where('game_id', $game->id)->count();
        $guideUrlsCount = DB::table('trophy_guide_urls')->where('game_id', $game->id)->count();

        $completeness = 0;
        if ($game->cover_url) $completeness++;
        if ($game->description) $completeness++;
        if ($game->developer) $completeness++;
        if ($game->release_date) $completeness++;
        if ($game->difficulty) $completeness++;
        if ($game->time_min) $completeness++;
        if ($game->critic_score) $completeness++;
        if ($game->igdb_id) $completeness++;
        if ($game->psnprofiles_url) $completeness++;
        if ($game->playstationtrophies_url) $completeness++;
        if ($game->powerpyx_url) $completeness++;
        if ($game->bronze_count) $completeness++;

        return [
            'id' => $game->id,
            'title' => $game->title,
            'igdb_id' => $game->igdb_id,
            'cover_url' => $game->cover_url,
            'has_platinum' => $game->has_platinum,
            'bronze_count' => $game->bronze_count,
            'silver_count' => $game->silver_count,
            'gold_count' => $game->gold_count,
            'platinum_count' => $game->platinum_count,
            'psnprofiles_url' => $game->psnprofiles_url,
            'playstationtrophies_url' => $game->playstationtrophies_url,
            'powerpyx_url' => $game->powerpyx_url,
            'psn_titles_count' => $psnTitlesCount,
            'users_count' => $usersCount,
            'guide_urls_count' => $guideUrlsCount,
            'completeness' => $completeness,
        ];
    }

    /**
     * Normalize a game title for duplicate comparison
     */
    private function normalizeTitle(string $title): string
    {
        $title = mb_strtolower($title);
        // Strip trademark symbols
        $title = preg_replace('/[\x{2122}\x{00AE}\x{00A9}]/u', '', $title);
        // Normalize dashes to hyphens
        $title = preg_replace('/[\x{2013}\x{2014}]/u', '-', $title);
        // Normalize quotes
        $title = preg_replace('/[\x{2018}\x{2019}\x{201C}\x{201D}]/u', '', $title);
        // Collapse whitespace
        $title = preg_replace('/\s+/', ' ', $title);
        return trim($title);
    }

    /**
     * Strip common edition/remaster suffixes for fuzzy duplicate matching
     */
    private function stripEditionSuffix(string $normalized): string
    {
        $suffixes = [
            // Editions
            'game of the year edition', 'goty edition', 'goty',
            'definitive edition', 'complete edition', 'ultimate edition',
            'deluxe edition', 'gold edition', 'premium edition',
            'legendary edition', 'enhanced edition', 'special edition',
            'digital deluxe edition', 'digital edition',
            // Remasters / remakes
            'remastered', 'remaster', 'hd remaster',
            'remake', 'hd', 'hd collection',
            // Director/final versions
            "director's cut", 'directors cut', 'final cut',
            'extended edition', 'expanded edition',
            // Platform/version markers
            'ps5 edition', 'ps4 edition',
            // Common trailing markers
            'complete', 'ultimate', 'deluxe', 'gold',
        ];

        // Sort by length desc so longer suffixes match first
        usort($suffixes, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($suffixes as $suffix) {
            // Strip suffix with optional leading separator (: - –)
            $title = preg_replace('/\s*[-:\x{2013}\x{2014}]?\s*' . preg_quote($suffix, '/') . '\s*$/u', '', $normalized);
            if ($title !== $normalized) {
                return trim($title);
            }
        }

        return $normalized;
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
            'no_guide_needed' => 'nullable|boolean',
            'has_platinum' => 'nullable|boolean',
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
