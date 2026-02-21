<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserGameController extends Controller
{
    /**
     * Get the current user's game list with full filtering support.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userGameIds = $user->games()->pluck('games.id')->toArray();

        if (empty($userGameIds)) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $request->input('per_page', 24),
                'status_counts' => $this->getStatusCounts($user),
            ]);
        }

        $query = Game::with(['platforms', 'genres', 'tags'])
            ->whereIn('id', $userGameIds);

        // Filter by user's game status
        if ($request->filled('status') && $request->status !== 'all') {
            $statusGameIds = $user->games()
                ->wherePivot('status', $request->status)
                ->pluck('games.id')
                ->toArray();
            $query->whereIn('id', $statusGameIds);
        }

        // Apply game filters (search, difficulty, time, platforms, etc.)
        $this->applyGameFilters($query, $request);

        // Sorting
        $sortBy = $request->input('sort_by', 'added_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $minRatings = 3;

        if ($sortBy === 'added_at') {
            // Sort by when user added the game
            $query->orderByRaw("FIELD(id, " . implode(',', $userGameIds) . ")");
        } elseif ($sortBy === 'user_score') {
            $query->orderByRaw("CASE WHEN user_score IS NULL OR (user_score_count IS NOT NULL AND user_score_count < ?) THEN 1 ELSE 0 END", [$minRatings])
                  ->orderBy($sortBy, $sortOrder);
        } elseif ($sortBy === 'critic_score') {
            $query->orderByRaw("CASE WHEN critic_score IS NULL OR (critic_score_count IS NOT NULL AND critic_score_count < ?) THEN 1 ELSE 0 END", [$minRatings])
                  ->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 24), 100);
        $paginated = $query->paginate($perPage);

        // Add user-specific data (status, notes) to each game
        $userGames = $user->games()->whereIn('games.id', $paginated->pluck('id'))
            ->get()
            ->keyBy('id');

        $games = $paginated->getCollection()->map(function ($game) use ($userGames) {
            $userGame = $userGames->get($game->id);
            return array_merge($game->toArray(), [
                'user_status' => $userGame?->pivot->status,
                'user_notes' => $userGame?->pivot->notes,
                'added_at' => $userGame?->pivot->created_at,
            ]);
        });

        return response()->json([
            'data' => $games,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'status_counts' => $this->getStatusCounts($user),
        ]);
    }

    /**
     * Get status counts for the user's games.
     */
    private function getStatusCounts($user): array
    {
        $counts = ['all' => 0];
        $statuses = ['backlog', 'in_progress', 'completed', 'platinumed', 'abandoned'];

        foreach ($statuses as $status) {
            $counts[$status] = 0;
        }

        $games = $user->games()->get();
        $counts['all'] = $games->count();

        foreach ($games as $game) {
            $status = $game->pivot->status;
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * Apply game filters (similar to GameFilterService but simplified).
     */
    private function applyGameFilters($query, Request $request): void
    {
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Has guide filter — uses indexed boolean column
        if ($request->filled('has_guide')) {
            $query->where('has_guide', $this->isTruthy($request->has_guide));
        }

        // Difficulty range
        if ($request->filled('difficulty_min')) {
            $query->where('difficulty', '>=', (int) $request->difficulty_min);
        }
        if ($request->filled('difficulty_max')) {
            $query->where('difficulty', '<=', (int) $request->difficulty_max);
        }

        // Time range
        if ($request->filled('time_min')) {
            $query->where('time_max', '>=', (int) $request->time_min);
        }
        if ($request->filled('time_max')) {
            $query->where('time_min', '<=', (int) $request->time_max);
        }

        // Platforms
        if ($request->filled('platform_ids')) {
            $platformIds = is_array($request->platform_ids)
                ? $request->platform_ids
                : explode(',', $request->platform_ids);
            $query->whereHas('platforms', fn($q) => $q->whereIn('platforms.id', $platformIds));
        }

        // Genres
        if ($request->filled('genre_ids')) {
            $genreIds = is_array($request->genre_ids)
                ? $request->genre_ids
                : explode(',', $request->genre_ids);
            $query->whereHas('genres', fn($q) => $q->whereIn('genres.id', $genreIds));
        }

        // Tags
        if ($request->filled('tag_ids')) {
            $tagIds = is_array($request->tag_ids)
                ? $request->tag_ids
                : explode(',', $request->tag_ids);
            $query->whereHas('tags', fn($q) => $q->whereIn('tags.id', $tagIds));
        }

        // Max playthroughs
        if ($request->filled('max_playthroughs')) {
            $query->where('playthroughs_required', '<=', (int) $request->max_playthroughs);
        }

        // IGDB User Score range filter
        if ($request->filled('user_score_min') && (int) $request->user_score_min > 0) {
            $query->where('user_score', '>=', (int) $request->user_score_min);
        }
        if ($request->filled('user_score_max') && (int) $request->user_score_max < 100) {
            $query->where('user_score', '<=', (int) $request->user_score_max);
        }

        // IGDB Critic Score range filter
        if ($request->filled('critic_score_min') && (int) $request->critic_score_min > 0) {
            $query->where('critic_score', '>=', (int) $request->critic_score_min);
        }
        if ($request->filled('critic_score_max') && (int) $request->critic_score_max < 100) {
            $query->where('critic_score', '<=', (int) $request->critic_score_max);
        }

        // Legacy min_score filter (checks any score)
        if ($request->filled('min_score')) {
            $minScore = (int) $request->min_score;
            $query->where(function ($q) use ($minScore) {
                $q->where('critic_score', '>=', $minScore)
                  ->orWhere('user_score', '>=', $minScore)
                  ->orWhere('opencritic_score', '>=', $minScore);
            });
        }

        // Has platinum
        if ($request->filled('has_platinum')) {
            $query->where('has_platinum', $this->isTruthy($request->has_platinum));
        }

        // No online trophies
        if ($request->filled('has_online_trophies') && !$this->isTruthy($request->has_online_trophies)) {
            $query->where(function ($q) {
                $q->where('has_online_trophies', false)->orWhereNull('has_online_trophies');
            });
        }

        // No missable trophies
        if ($request->filled('missable_trophies') && !$this->isTruthy($request->missable_trophies)) {
            $query->where(function ($q) {
                $q->where('missable_trophies', false)->orWhereNull('missable_trophies');
            });
        }

        // Guide source filters
        if ($request->filled('guide_psnp') && $this->isTruthy($request->guide_psnp)) {
            $query->whereNotNull('psnprofiles_url');
        }
        if ($request->filled('guide_pst') && $this->isTruthy($request->guide_pst)) {
            $query->whereNotNull('playstationtrophies_url');
        }
        if ($request->filled('guide_ppx') && $this->isTruthy($request->guide_ppx)) {
            $query->whereNotNull('powerpyx_url');
        }
    }

    private function isTruthy($value): bool
    {
        return in_array($value, [true, 'true', '1', 1], true);
    }

    /**
     * Add a game to the user's list.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => ['required', 'exists:games,id'],
            'status' => ['sometimes', Rule::in(['backlog', 'in_progress', 'completed', 'platinumed', 'abandoned'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $gameId = $validated['game_id'];

        // Check if already in list
        if ($user->games()->where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'Game already in list'], 409);
        }

        // Check if game already has a guide - if so, mark as notified
        // (only notify about NEW guides, not games added that already have guides)
        $game = Game::find($gameId);
        $hasGuide = $game && ($game->psnprofiles_url || $game->playstationtrophies_url || $game->powerpyx_url);

        $user->games()->attach($gameId, [
            'status' => $validated['status'] ?? 'backlog',
            'notes' => $validated['notes'] ?? null,
            'guide_notified_at' => $hasGuide ? now() : null,
        ]);

        return response()->json(['message' => 'Game added to list'], 201);
    }

    /**
     * Check if a game is in the user's list.
     */
    public function check(Request $request, int $gameId): JsonResponse
    {
        $userGame = $request->user()->games()
            ->where('game_id', $gameId)
            ->first();

        if (!$userGame) {
            return response()->json(['in_list' => false]);
        }

        return response()->json([
            'in_list' => true,
            'status' => $userGame->pivot->status,
            'notes' => $userGame->pivot->notes,
            'preferred_guide' => $userGame->pivot->preferred_guide,
        ]);
    }

    /**
     * Update a game's status/notes in the user's list.
     */
    public function update(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['backlog', 'in_progress', 'completed', 'platinumed', 'abandoned'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'preferred_guide' => ['sometimes', 'nullable', Rule::in(['psnprofiles', 'playstationtrophies', 'powerpyx'])],
        ]);

        $user = $request->user();

        if (!$user->games()->where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'Game not in list'], 404);
        }

        $updateData = [];
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }
        if (array_key_exists('preferred_guide', $validated)) {
            $updateData['preferred_guide'] = $validated['preferred_guide'];
        }

        $user->games()->updateExistingPivot($gameId, $updateData);

        return response()->json(['message' => 'Game updated']);
    }

    /**
     * Remove a game from the user's list.
     */
    public function destroy(Request $request, int $gameId): JsonResponse
    {
        $user = $request->user();

        if (!$user->games()->where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'Game not in list'], 404);
        }

        $user->games()->detach($gameId);

        return response()->json(['message' => 'Game removed from list']);
    }

    /**
     * Bulk add games to the user's list (merge - skip existing).
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_ids' => ['required', 'array', 'min:1'],
            'game_ids.*' => ['integer', 'exists:games,id'],
            'status' => ['sometimes', 'in:backlog,in_progress,completed,platinumed,abandoned'],
        ]);

        $user = $request->user();
        $gameIds = $validated['game_ids'];
        $status = $validated['status'] ?? 'backlog';

        // Get existing game IDs in user's list
        $existingIds = $user->games()->whereIn('game_id', $gameIds)->pluck('game_id')->toArray();

        // Filter to only new games
        $newGameIds = array_diff($gameIds, $existingIds);

        // Get games that already have guides (to mark as notified)
        $gamesWithGuides = Game::whereIn('id', $newGameIds)
            ->where('has_guide', true)
            ->pluck('id')
            ->toArray();

        // Prepare pivot data
        $attachData = [];
        foreach ($newGameIds as $gameId) {
            $attachData[$gameId] = [
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
                // Mark as notified if game already has a guide
                'guide_notified_at' => in_array($gameId, $gamesWithGuides) ? now() : null,
            ];
        }

        // Attach new games
        if (!empty($attachData)) {
            $user->games()->attach($attachData);
        }

        return response()->json([
            'message' => 'Games added to list',
            'added' => count($newGameIds),
            'skipped' => count($existingIds),
        ]);
    }
}
