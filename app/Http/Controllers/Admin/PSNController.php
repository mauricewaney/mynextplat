<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController;
use App\Services\PSNService;
use App\Services\IGDBService;
use App\Models\Game;
use App\Models\PsnTitle;
use App\Models\Platform;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PSNController extends Controller
{
    /**
     * Lookup a PSN user's library and collect NP IDs
     */
    public function lookup(string $username, PSNService $psnService)
    {
        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with PSN. Check PSN_NPSSO token.',
            ], 401);
        }

        $data = $psnService->getGamesForUser($username);

        if (!$data || isset($data['error'])) {
            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'User not found or profile is private.',
            ], 404);
        }

        // Process games, collect NP IDs, and match with local database
        $processedGames = [];
        $newTitlesCount = 0;

        foreach ($data['titles'] as $title) {
            $npId = $title['npCommunicationId'] ?? null;
            $gameName = $title['trophyTitleName'] ?? 'Unknown';
            $platform = $title['trophyTitlePlatform'] ?? '';

            // Collect NP ID into database
            $psnTitle = null;
            if ($npId) {
                $existing = PsnTitle::where('np_communication_id', $npId)->first();
                if ($existing) {
                    $existing->incrementSeen();
                    $psnTitle = $existing;
                } else {
                    $psnTitle = PsnTitle::upsertFromTrophy($title, $username);
                    $newTitlesCount++;
                }
            }

            // Try to find matching game - first check if PsnTitle is already linked
            $localMatch = null;
            if ($psnTitle && $psnTitle->game_id) {
                $game = $psnTitle->game;
                if ($game) {
                    $localMatch = $this->formatGameMatch($game);
                }
            }

            // If not linked via PsnTitle, try fuzzy matching
            if (!$localMatch) {
                $localMatch = $this->findLocalMatch($gameName, $npId);
            }

            $defined = $title['definedTrophies'] ?? [];
            $earned = $title['earnedTrophies'] ?? [];
            $progress = $title['progress'] ?? 0;

            $hasPlatinum = ($defined['platinum'] ?? 0) > 0;
            $earnedPlatinum = ($earned['platinum'] ?? 0) > 0;

            $processedGames[] = [
                'np_communication_id' => $npId,
                'psn_title_id' => $psnTitle?->id,
                'psn_name' => $gameName,
                'psn_platform' => $platform,
                'psn_image' => $title['trophyTitleIconUrl'] ?? null,
                'progress' => $progress,
                'has_platinum' => $hasPlatinum,
                'earned_platinum' => $earnedPlatinum,
                'trophies' => [
                    'bronze' => ['earned' => $earned['bronze'] ?? 0, 'total' => $defined['bronze'] ?? 0],
                    'silver' => ['earned' => $earned['silver'] ?? 0, 'total' => $defined['silver'] ?? 0],
                    'gold' => ['earned' => $earned['gold'] ?? 0, 'total' => $defined['gold'] ?? 0],
                    'platinum' => ['earned' => $earned['platinum'] ?? 0, 'total' => $defined['platinum'] ?? 0],
                ],
                'local_match' => $localMatch,
            ];
        }

        return response()->json([
            'success' => true,
            'user' => [
                'username' => $data['user']['onlineId'],
                'account_id' => $data['user']['accountId'],
                'avatar' => $data['user']['avatarUrl'] ?? null,
            ],
            'total_games' => count($data['titles']),
            'new_titles_discovered' => $newTitlesCount,
            'games' => $processedGames,
            'stats' => $this->calculateStats($processedGames),
        ]);
    }

    /**
     * Collect NP IDs from a user without returning full game data
     * Useful for bulk importing from multiple accounts
     * Also auto-matches 100% similarity titles
     */
    public function collectFromUser(string $username, PSNService $psnService)
    {
        set_time_limit(300);
        ignore_user_abort(true); // Continue even if Nginx drops the connection

        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with PSN.',
            ], 401);
        }

        $data = $psnService->getGamesForUser($username);

        if (!$data || isset($data['error'])) {
            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'User not found or profile is private.',
            ], 404);
        }

        // Index fetched titles by NP ID
        $titlesByNpId = [];
        foreach ($data['titles'] as $title) {
            $npId = $title['npCommunicationId'] ?? null;
            if ($npId) {
                $titlesByNpId[$npId] = $title;
            }
        }

        $allNpIds = array_keys($titlesByNpId);

        // Bulk load ALL existing PSN titles in one query (instead of 1 query per title)
        $existingNpIds = collect();
        foreach (array_chunk($allNpIds, 1000) as $chunk) {
            $existingNpIds = $existingNpIds->merge(
                PsnTitle::whereIn('np_communication_id', $chunk)->pluck('np_communication_id')
            );
        }
        $existingSet = $existingNpIds->flip()->all();

        // Bulk increment times_seen for existing titles
        $existingCount = 0;
        foreach (array_chunk($existingNpIds->all(), 1000) as $chunk) {
            $existingCount += PsnTitle::whereIn('np_communication_id', $chunk)->increment('times_seen');
        }

        // Determine new titles (not in DB yet)
        $newNpIds = array_diff($allNpIds, array_keys($existingSet));

        // Batch insert new titles
        $newCount = 0;
        $insertBatch = [];
        $now = now();
        foreach ($newNpIds as $npId) {
            $title = $titlesByNpId[$npId];
            $defined = $title['definedTrophies'] ?? [];
            $insertBatch[] = [
                'np_communication_id' => $npId,
                'psn_title' => $title['trophyTitleName'] ?? 'Unknown',
                'platform' => $title['trophyTitlePlatform'] ?? null,
                'icon_url' => $title['trophyTitleIconUrl'] ?? null,
                'discovered_from' => $username,
                'times_seen' => 1,
                'bronze_count' => $defined['bronze'] ?? null,
                'silver_count' => $defined['silver'] ?? null,
                'gold_count' => $defined['gold'] ?? null,
                'has_platinum' => ($defined['platinum'] ?? 0) > 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($insertBatch) >= 500) {
                PsnTitle::insert($insertBatch);
                $newCount += count($insertBatch);
                $insertBatch = [];
            }
        }
        if (!empty($insertBatch)) {
            PsnTitle::insert($insertBatch);
            $newCount += count($insertBatch);
        }

        // Auto-match new titles using pre-loaded game titles hashmap
        $gameTitles = Game::select('id', 'title')->get()
            ->mapWithKeys(fn($g) => [strtolower(trim($g->title)) => $g->id])
            ->all();

        $autoMatchedCount = 0;
        if ($newCount > 0) {
            // Load newly inserted titles for auto-matching
            $newPsnTitles = collect();
            foreach (array_chunk(array_values($newNpIds), 1000) as $chunk) {
                $newPsnTitles = $newPsnTitles->merge(
                    PsnTitle::whereIn('np_communication_id', $chunk)->whereNull('game_id')->get()
                );
            }

            foreach ($newPsnTitles as $psnTitle) {
                $normalizedPsn = strtolower(trim($psnTitle->psn_title));
                $gameId = $gameTitles[$normalizedPsn] ?? null;
                if ($gameId) {
                    $game = Game::find($gameId);
                    if ($game) {
                        $psnTitle->linkToGame($game);
                        $autoMatchedCount++;
                    }
                }
            }
        }

        // Bust game cache if any games were updated via auto-matching
        if ($autoMatchedCount > 0) {
            GameController::bustGameCache();
        }

        return response()->json([
            'success' => true,
            'message' => "Collected {$newCount} new titles, updated {$existingCount} existing, auto-matched {$autoMatchedCount}.",
            'username' => $username,
            'total_titles' => count($data['titles']),
            'new_titles' => $newCount,
            'existing_titles' => $existingCount,
            'auto_matched' => $autoMatchedCount,
        ]);
    }

    /**
     * Try to auto-match a PSN title to a game (100% match only)
     */
    private function tryAutoMatch(PsnTitle $psnTitle): bool
    {
        $normalized = $this->normalizeForSearch($psnTitle->psn_title);

        // Find all games whose normalized title exactly matches
        $matches = Game::select('id', 'title')->get()
            ->filter(fn($g) => $this->normalizeForSearch($g->title) === $normalized);

        // Only auto-match if there's exactly one match
        if ($matches->count() === 1) {
            $psnTitle->linkToGame($matches->first());
            return true;
        }

        return false;
    }

    /**
     * Run auto-match on all unmatched PSN titles
     */
    public function autoMatchAll()
    {
        set_time_limit(300);

        $unmatched = PsnTitle::unmatched()->get();

        // Pre-load game titles once for fast lookup, using normalizeForSearch for consistency
        $gameTitleMap = [];
        foreach (Game::select('id', 'title')->get() as $g) {
            $key = $this->normalizeForSearch($g->title);
            // Track duplicates — skip auto-match if multiple games share the same normalized title
            $gameTitleMap[$key] = isset($gameTitleMap[$key]) ? null : $g->id;
        }

        $matchedCount = 0;

        foreach ($unmatched as $psnTitle) {
            $normalizedPsn = $this->normalizeForSearch($psnTitle->psn_title);
            $gameId = $gameTitleMap[$normalizedPsn] ?? null;
            if ($gameId) {
                $game = Game::find($gameId);
                if ($game) {
                    $psnTitle->linkToGame($game);
                    $matchedCount++;
                }
            }
        }

        if ($matchedCount > 0) {
            GameController::bustGameCache();
        }

        return response()->json([
            'success' => true,
            'message' => "Auto-matched {$matchedCount} titles.",
            'matched_count' => $matchedCount,
            'remaining_unmatched' => PsnTitle::unmatched()->count(),
        ]);
    }

    /**
     * Get PSN titles statistics
     */
    public function getStats()
    {
        return response()->json([
            'total_titles' => PsnTitle::count(),
            'matched_titles' => PsnTitle::matched()->count(),
            'unmatched_titles' => PsnTitle::unmatched()->count(),
            'platforms' => PsnTitle::selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform'),
            'top_discovered_from' => PsnTitle::selectRaw('discovered_from, COUNT(*) as count')
                ->whereNotNull('discovered_from')
                ->groupBy('discovered_from')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'discovered_from'),
        ]);
    }

    /**
     * Get unmatched PSN titles
     */
    public function getUnmatched(Request $request)
    {
        $query = PsnTitle::unmatched();

        // Filter by platform
        if ($request->has('platform')) {
            $query->forPlatform($request->platform);
        }

        // Filter by search term
        if ($request->has('search')) {
            $query->where('psn_title', 'like', '%' . $request->search . '%');
        }

        // Determine sort field and direction
        $sortBy = $request->get('sort', 'times_seen');
        $sortDir = strtoupper($request->get('dir', 'desc')) === 'ASC' ? 'ASC' : 'DESC';

        $sortField = match($sortBy) {
            'title' => 'psn_title',
            'created_at' => 'created_at',
            default => 'times_seen',
        };

        // Sort with skipped items ALWAYS at the end, then by chosen field within each group
        $query->orderByRaw('CASE WHEN skipped_at IS NULL THEN 0 ELSE 1 END ASC')
              ->orderBy($sortField, $sortDir);

        $perPage = min($request->get('per_page', 50), 100);

        $paginated = $query->paginate($perPage);

        // Pre-compute suggestions for each unmatched title (skip suggestions for skipped items)
        $items = $paginated->getCollection()->map(function ($psnTitle) {
            $suggestions = $psnTitle->skipped_at ? [] : $this->findSuggestions($psnTitle->psn_title);
            return array_merge($psnTitle->toArray(), [
                'suggestions' => $suggestions,
                'is_skipped' => $psnTitle->skipped_at !== null,
                'best_similarity' => !empty($suggestions) ? $suggestions[0]['similarity'] : 0,
            ]);
        });

        // Sort by best match similarity if requested
        if ($sortBy === 'similarity') {
            $sorted = $items->sortByDesc('best_similarity')->values();
            $paginated->setCollection($sorted);
        } else {
            $paginated->setCollection($items);
        }

        // Add skip count to response
        $skipCount = PsnTitle::unmatched()->skipped()->count();

        return response()->json(array_merge($paginated->toArray(), [
            'skip_count' => $skipCount,
        ]));
    }

    /**
     * Link a PSN title to a game
     */
    public function linkToGame(Request $request)
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
            'game_id' => 'required|exists:games,id',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $game = Game::findOrFail($request->game_id);

        $psnTitle->linkToGame($game);
        GameController::bustGameCache();

        return response()->json([
            'success' => true,
            'message' => "Linked \"{$psnTitle->psn_title}\" to \"{$game->title}\"",
            'psn_title' => $psnTitle->load('game'),
        ]);
    }

    /**
     * Unlink a PSN title from its game
     */
    public function unlinkFromGame(Request $request)
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $gameName = $psnTitle->game?->title ?? 'Unknown';

        $psnTitle->unlinkFromGame();

        return response()->json([
            'success' => true,
            'message' => "Unlinked \"{$psnTitle->psn_title}\" from \"{$gameName}\"",
            'psn_title' => $psnTitle,
        ]);
    }

    /**
     * Skip a PSN title
     */
    public function skip(Request $request)
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $psnTitle->skip();

        return response()->json([
            'success' => true,
            'message' => "Skipped \"{$psnTitle->psn_title}\"",
        ]);
    }

    /**
     * Unskip a PSN title
     */
    public function unskip(Request $request)
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $psnTitle->unskip();

        return response()->json([
            'success' => true,
            'message' => "Unskipped \"{$psnTitle->psn_title}\"",
        ]);
    }

    /**
     * Clear all skips
     */
    public function clearSkips()
    {
        $count = PsnTitle::skipped()->count();
        PsnTitle::skipped()->update(['skipped_at' => null]);

        return response()->json([
            'success' => true,
            'message' => "Cleared {$count} skipped titles",
            'count' => $count,
        ]);
    }

    /**
     * Bulk link PSN titles to games
     */
    public function bulkLink(Request $request)
    {
        $request->validate([
            'links' => 'required|array',
            'links.*.psn_title_id' => 'required|exists:psn_titles,id',
            'links.*.game_id' => 'required|exists:games,id',
        ]);

        $linked = 0;
        foreach ($request->links as $link) {
            $psnTitle = PsnTitle::find($link['psn_title_id']);
            $game = Game::find($link['game_id']);

            if ($psnTitle && $game) {
                $psnTitle->linkToGame($game);
                $linked++;
            }
        }

        if ($linked > 0) {
            GameController::bustGameCache();
        }

        return response()->json([
            'success' => true,
            'message' => "Linked {$linked} titles.",
            'linked_count' => $linked,
        ]);
    }

    /**
     * Get suggestions for matching a PSN title
     */
    public function getSuggestions(int $psnTitleId)
    {
        $psnTitle = PsnTitle::findOrFail($psnTitleId);

        $suggestions = $this->findSuggestions($psnTitle->psn_title);

        return response()->json([
            'psn_title' => $psnTitle,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Search unmatched PSN titles for linking to a specific game
     * Returns titles matching the search query + auto-suggestions based on game title
     */
    public function searchUnmatchedForGame(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'search' => 'nullable|string|min:1',
        ]);

        $game = Game::findOrFail($request->game_id);
        $search = $request->get('search', '');

        // If no search term, auto-suggest based on game title
        if (!$search) {
            $search = $game->title;
        }

        $normalizedSearch = $this->normalizeForSearch($search);
        $onlyUnmatched = $request->boolean('unmatched_only', false);

        // Get PSN titles (optionally filter to unmatched only)
        $query = PsnTitle::with('game:id,title')->whereNull('skipped_at');
        if ($onlyUnmatched) {
            $query->whereNull('game_id');
        }
        // Exclude titles already linked to THIS game
        $query->where(function ($q) use ($game) {
            $q->whereNull('game_id')->orWhere('game_id', '!=', $game->id);
        });
        $unmatchedTitles = $query->get();

        $results = [];
        foreach ($unmatchedTitles as $psnTitle) {
            $normalizedPsn = $this->normalizeForSearch($psnTitle->psn_title);

            similar_text($normalizedSearch, $normalizedPsn, $percent);

            if (str_contains($normalizedPsn, $normalizedSearch) || str_contains($normalizedSearch, $normalizedPsn)) {
                $percent = min(100, $percent + 15);
            }

            if ($percent >= 40) {
                $result = [
                    'id' => $psnTitle->id,
                    'psn_title' => $psnTitle->psn_title,
                    'np_communication_id' => $psnTitle->np_communication_id,
                    'platform' => $psnTitle->platform,
                    'icon_url' => $psnTitle->icon_url,
                    'times_seen' => $psnTitle->times_seen,
                    'similarity' => round($percent),
                    'linked_game_id' => $psnTitle->game_id,
                    'linked_game_title' => null,
                ];
                if ($psnTitle->game_id) {
                    $result['linked_game_title'] = $psnTitle->game?->title;
                }
                $results[] = $result;
            }
        }

        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return response()->json(array_slice($results, 0, 20));
    }

    /**
     * Try to find a matching game in the local database
     */
    private function findLocalMatch(string $psnName, ?string $npId = null): ?array
    {
        // First check if this NP ID is already linked via PsnTitle
        if ($npId) {
            $psnTitle = PsnTitle::where('np_communication_id', $npId)
                ->whereNotNull('game_id')
                ->with('game')
                ->first();

            if ($psnTitle && $psnTitle->game) {
                return $this->formatGameMatch($psnTitle->game);
            }
        }

        // Check by NP ID in games table
        if ($npId) {
            $game = Game::whereJsonContains('np_communication_ids', $npId)->first();
            if ($game) {
                return $this->formatGameMatch($game);
            }
        }

        // Try exact match
        $game = Game::where('title', $psnName)->first();

        // Try case-insensitive match
        if (!$game) {
            $game = Game::whereRaw('LOWER(title) = ?', [strtolower($psnName)])->first();
        }

        // Try LIKE match
        if (!$game) {
            $game = Game::where('title', 'LIKE', '%' . $psnName . '%')->first();
        }

        // Try with common suffixes removed
        if (!$game) {
            $cleanName = preg_replace('/\s*(Trophies|Trophy|PS4|PS5|\(PS4\)|\(PS5\))$/i', '', $psnName);
            $cleanName = trim($cleanName);
            if ($cleanName !== $psnName) {
                $game = Game::where('title', 'LIKE', '%' . $cleanName . '%')->first();
            }
        }

        return $game ? $this->formatGameMatch($game) : null;
    }

    /**
     * Format game for match response
     */
    private function formatGameMatch(Game $game): array
    {
        return [
            'id' => $game->id,
            'title' => $game->title,
            'cover_url' => $game->cover_url,
            'difficulty' => $game->difficulty,
            'time_range' => $game->time_range,
            'has_guide' => !empty($game->playstationtrophies_url) || !empty($game->powerpyx_url),
            'playstationtrophies_url' => $game->playstationtrophies_url,
            'powerpyx_url' => $game->powerpyx_url,
        ];
    }

    /**
     * Find game suggestions for a PSN title
     */
    private function findSuggestions(string $title): array
    {
        $normalized = $this->normalizeForSearch($title);
        $suggestions = [];

        $games = Game::select('id', 'title', 'cover_url')->get();

        foreach ($games as $game) {
            $normalizedDb = $this->normalizeForSearch($game->title);

            similar_text($normalized, $normalizedDb, $percent);

            // Bonus for containment, but cap at 99 so only true exact matches reach 100
            if (str_contains($normalizedDb, $normalized) || str_contains($normalized, $normalizedDb)) {
                $percent = min($normalized === $normalizedDb ? 100 : 99, $percent + 15);
            }

            if ($percent >= 50) {
                $suggestions[] = [
                    'id' => $game->id,
                    'title' => $game->title,
                    'cover_url' => $game->cover_url,
                    'similarity' => round($percent),
                ];
            }
        }

        usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($suggestions, 0, 10);
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
     * Create a new game from PSN title data and link it
     * Auto-merges with existing game if 90%+ title match found
     */
    public function createGameAndLink(Request $request)
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
            'title' => 'required|string|max:255',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $wasMerged = false;

        // Check for existing game to merge with
        $game = $this->findExistingGameToMerge($request->title);

        if ($game) {
            // Merge data into existing game
            $this->mergeIntoExistingGame($game, [
                'trophy_icon_url' => $psnTitle->icon_url,
                'bronze_count' => $psnTitle->bronze_count,
                'silver_count' => $psnTitle->silver_count,
                'gold_count' => $psnTitle->gold_count,
                'platinum_count' => $psnTitle->has_platinum ? 1 : 0,
                'has_platinum' => $psnTitle->has_platinum,
            ]);
            $wasMerged = true;
        } else {
            // Create new game
            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $counter = 1;
            while (Game::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $game = Game::create([
                'title' => $request->title,
                'slug' => $slug,
                'trophy_icon_url' => $psnTitle->icon_url,
                'bronze_count' => $psnTitle->bronze_count,
                'silver_count' => $psnTitle->silver_count,
                'gold_count' => $psnTitle->gold_count,
                'platinum_count' => $psnTitle->has_platinum ? 1 : 0,
                'has_platinum' => $psnTitle->has_platinum,
            ]);
        }

        // Determine platform from PSN title
        $platformIds = [];
        if ($psnTitle->platform) {
            $platformMap = [
                'PS5' => ['slug' => 'ps5', 'name' => 'PlayStation 5', 'short_name' => 'PS5'],
                'PS4' => ['slug' => 'ps4', 'name' => 'PlayStation 4', 'short_name' => 'PS4'],
                'PS3' => ['slug' => 'ps3', 'name' => 'PlayStation 3', 'short_name' => 'PS3'],
                'PSVITA' => ['slug' => 'ps-vita', 'name' => 'PlayStation Vita', 'short_name' => 'Vita'],
                'VITA' => ['slug' => 'ps-vita', 'name' => 'PlayStation Vita', 'short_name' => 'Vita'],
            ];

            $platformData = $platformMap[strtoupper($psnTitle->platform)] ?? null;
            if ($platformData) {
                $platform = Platform::firstOrCreate(
                    ['slug' => $platformData['slug']],
                    ['name' => $platformData['name'], 'short_name' => $platformData['short_name']]
                );
                $platformIds[] = $platform->id;
            }
        }

        // Attach platforms (syncWithoutDetaching to add without removing existing)
        if (!empty($platformIds)) {
            $game->platforms()->syncWithoutDetaching($platformIds);
        }

        // Link the PSN title
        $psnTitle->linkToGame($game);
        GameController::bustGameCache();

        $action = $wasMerged ? 'Merged into' : 'Created';
        return response()->json([
            'success' => true,
            'message' => "{$action} \"{$game->title}\" and linked to \"{$psnTitle->psn_title}\"",
            'game' => $game->load('platforms'),
            'psn_title' => $psnTitle,
            'was_merged' => $wasMerged,
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
            $results = $igdbService->searchGames($request->query('query'), 10);

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IGDB search failed: ' . $e->getMessage(),
                'results' => [],
            ], 500);
        }
    }

    /**
     * Import a game from IGDB and link a PSN title to it
     * Auto-merges with existing game if 90%+ title match found
     */
    public function importFromIgdbAndLink(Request $request, IGDBService $igdbService)
    {
        $request->validate([
            'psn_title_id' => 'required|exists:psn_titles,id',
            'igdb_game' => 'required|array',
            'igdb_game.igdb_id' => 'required|integer',
            'igdb_game.title' => 'required|string',
        ]);

        $psnTitle = PsnTitle::findOrFail($request->psn_title_id);
        $igdbData = $request->igdb_game;
        $wasMerged = false;
        $wasExisting = false;

        // Check if game already exists by IGDB ID
        $game = Game::where('igdb_id', $igdbData['igdb_id'])->first();

        if ($game) {
            $wasExisting = true;
        } else {
            // Check for existing game to merge with by title
            $game = $this->findExistingGameToMerge($igdbData['title']);

            if ($game) {
                // Merge IGDB data into existing game
                $this->mergeIntoExistingGame($game, [
                    'igdb_id' => $igdbData['igdb_id'],
                    'developer' => $igdbData['developer'] ?? null,
                    'publisher' => $igdbData['publisher'] ?? null,
                    'release_date' => $igdbData['release_date'] ?? null,
                    'cover_url' => $igdbData['cover_url'] ?? null,
                    'banner_url' => $igdbData['banner_url'] ?? null,
                    'critic_score' => $igdbData['critic_score'] ?? null,
                    'critic_score_count' => $igdbData['critic_score_count'] ?? null,
                    'user_score' => $igdbData['user_score'] ?? null,
                    'user_score_count' => $igdbData['user_score_count'] ?? null,
                ]);
                $wasMerged = true;
            } else {
                // Create new game
                $slug = Str::slug($igdbData['title']);
                $originalSlug = $slug;
                $counter = 1;
                while (Game::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $game = Game::create([
                    'igdb_id' => $igdbData['igdb_id'],
                    'title' => $igdbData['title'],
                    'slug' => $slug,
                    'developer' => $igdbData['developer'] ?? null,
                    'publisher' => $igdbData['publisher'] ?? null,
                    'release_date' => $igdbData['release_date'] ?? null,
                    'cover_url' => $igdbData['cover_url'] ?? null,
                    'banner_url' => $igdbData['banner_url'] ?? null,
                    'critic_score' => $igdbData['critic_score'] ?? null,
                    'critic_score_count' => $igdbData['critic_score_count'] ?? null,
                    'user_score' => $igdbData['user_score'] ?? null,
                    'user_score_count' => $igdbData['user_score_count'] ?? null,
                ]);
            }
        }

        // Handle platforms (add without removing existing)
        if (!empty($igdbData['platforms_data'])) {
            $platformIds = [];
            foreach ($igdbData['platforms_data'] as $platformData) {
                $platform = Platform::firstOrCreate(
                    ['slug' => $platformData['slug']],
                    ['name' => $platformData['name'], 'short_name' => $platformData['short_name'] ?? null]
                );
                $platformIds[] = $platform->id;
            }
            $game->platforms()->syncWithoutDetaching($platformIds);
        }

        // Handle genres (add without removing existing)
        if (!empty($igdbData['genre_names'])) {
            $genreIds = [];
            foreach ($igdbData['genre_names'] as $genreName) {
                $genre = Genre::firstOrCreate(
                    ['slug' => Str::slug($genreName)],
                    ['name' => $genreName]
                );
                $genreIds[] = $genre->id;
            }
            $game->genres()->syncWithoutDetaching($genreIds);
        }

        // Link the PSN title to the game
        $psnTitle->linkToGame($game);
        GameController::bustGameCache();

        $action = $wasExisting ? 'Linked to existing' : ($wasMerged ? 'Merged into' : 'Imported');
        return response()->json([
            'success' => true,
            'message' => "{$action} \"{$game->title}\" and linked to \"{$psnTitle->psn_title}\"",
            'game' => $game->load('platforms', 'genres'),
            'psn_title' => $psnTitle,
            'was_merged' => $wasMerged,
            'was_existing' => $wasExisting,
        ]);
    }

    /**
     * Calculate stats for the user's library
     */
    private function calculateStats(array $games): array
    {
        $total = count($games);
        $withPlatinum = 0;
        $earnedPlatinum = 0;
        $matchedGames = 0;
        $withGuide = 0;

        foreach ($games as $game) {
            if ($game['has_platinum']) {
                $withPlatinum++;
                if ($game['earned_platinum']) {
                    $earnedPlatinum++;
                }
            }
            if ($game['local_match']) {
                $matchedGames++;
                if ($game['local_match']['has_guide']) {
                    $withGuide++;
                }
            }
        }

        return [
            'total_games' => $total,
            'games_with_platinum' => $withPlatinum,
            'platinums_earned' => $earnedPlatinum,
            'platinums_remaining' => $withPlatinum - $earnedPlatinum,
            'matched_in_database' => $matchedGames,
            'with_guide' => $withGuide,
            'match_rate' => $total > 0 ? round(($matchedGames / $total) * 100) : 0,
        ];
    }

    /**
     * Find an existing game that closely matches the title (for auto-merge)
     * Returns the game if similarity >= 90%, otherwise null
     */
    private function findExistingGameToMerge(string $title): ?Game
    {
        $normalized = $this->normalizeTitle($title);

        // Search for games with similar titles
        $candidates = Game::whereRaw('LOWER(title) LIKE ?', ['%' . strtolower(substr($title, 0, 10)) . '%'])
            ->orWhereRaw('LOWER(title) LIKE ?', ['%' . strtolower($title) . '%'])
            ->limit(20)
            ->get();

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($candidates as $game) {
            $gameNormalized = $this->normalizeTitle($game->title);

            // Calculate similarity
            similar_text($normalized, $gameNormalized, $percent);

            // Also check Levenshtein for short titles
            $lenDiff = abs(strlen($normalized) - strlen($gameNormalized));
            if (strlen($normalized) > 0 && strlen($gameNormalized) > 0 && $lenDiff < 5) {
                $lev = levenshtein($normalized, $gameNormalized);
                $maxLen = max(strlen($normalized), strlen($gameNormalized));
                $levPercent = (1 - ($lev / $maxLen)) * 100;
                $percent = max($percent, $levPercent);
            }

            if ($percent > $bestSimilarity) {
                $bestSimilarity = $percent;
                $bestMatch = $game;
            }

            // If exact match, use it immediately
            if ($normalized === $gameNormalized) {
                return $game;
            }
        }

        // Return if 90% or higher similarity
        if ($bestSimilarity >= 90 && $bestMatch) {
            \Log::info('Auto-merge: Found existing game', [
                'new_title' => $title,
                'existing_title' => $bestMatch->title,
                'similarity' => $bestSimilarity
            ]);
            return $bestMatch;
        }

        return null;
    }

    /**
     * Merge data from a new import into an existing game
     */
    private function mergeIntoExistingGame(Game $existing, array $newData): Game
    {
        // Only fill in missing data, don't overwrite
        $fieldsToMerge = [
            'igdb_id', 'developer', 'publisher', 'release_date',
            'cover_url', 'banner_url', 'critic_score', 'critic_score_count',
            'user_score', 'user_score_count', 'description',
            'trophy_icon_url', 'bronze_count', 'silver_count', 'gold_count',
        ];

        foreach ($fieldsToMerge as $field) {
            if ($existing->$field === null && isset($newData[$field]) && $newData[$field] !== null) {
                $existing->$field = $newData[$field];
            }
        }

        // Handle platinum fields
        if (!$existing->has_platinum && !empty($newData['has_platinum'])) {
            $existing->has_platinum = true;
            $existing->platinum_count = $newData['platinum_count'] ?? 1;
        }

        $existing->save();

        \Log::info('Auto-merge: Merged data into existing game', [
            'game_id' => $existing->id,
            'game_title' => $existing->title,
        ]);

        return $existing;
    }
}
