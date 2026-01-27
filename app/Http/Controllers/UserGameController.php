<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserGameController extends Controller
{
    /**
     * Get the current user's game list.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->games();

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->wherePivot('status', $request->status);
        }

        // Include game relationships
        $query->with(['platforms', 'genres']);

        // Order by when added to list
        $query->orderByPivot('created_at', 'desc');

        $games = $query->get()->map(function ($game) {
            return [
                'id' => $game->id,
                'title' => $game->title,
                'slug' => $game->slug,
                'cover_url' => $game->cover_url,
                'difficulty' => $game->difficulty,
                'time_min' => $game->time_min,
                'time_max' => $game->time_max,
                'critic_score' => $game->critic_score,
                'platforms' => $game->platforms,
                'genres' => $game->genres,
                'psnprofiles_url' => $game->psnprofiles_url,
                'playstationtrophies_url' => $game->playstationtrophies_url,
                'powerpyx_url' => $game->powerpyx_url,
                'status' => $game->pivot->status,
                'notes' => $game->pivot->notes,
                'added_at' => $game->pivot->created_at,
            ];
        });

        return response()->json($games);
    }

    /**
     * Add a game to the user's list.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => ['required', 'exists:games,id'],
            'status' => ['sometimes', Rule::in(['want_to_play', 'playing', 'completed', 'platinum', 'abandoned'])],
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
            'status' => $validated['status'] ?? 'want_to_play',
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
            'status' => ['sometimes', Rule::in(['want_to_play', 'playing', 'completed', 'platinum', 'abandoned'])],
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
            'status' => ['sometimes', 'in:want_to_play,playing,completed,platinum,abandoned'],
        ]);

        $user = $request->user();
        $gameIds = $validated['game_ids'];
        $status = $validated['status'] ?? 'want_to_play';

        // Get existing game IDs in user's list
        $existingIds = $user->games()->whereIn('game_id', $gameIds)->pluck('game_id')->toArray();

        // Filter to only new games
        $newGameIds = array_diff($gameIds, $existingIds);

        // Get games that already have guides (to mark as notified)
        $gamesWithGuides = Game::whereIn('id', $newGameIds)
            ->where(function ($q) {
                $q->whereNotNull('psnprofiles_url')
                  ->orWhereNotNull('playstationtrophies_url')
                  ->orWhereNotNull('powerpyx_url');
            })
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
