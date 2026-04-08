<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameReviewController extends Controller
{
    /**
     * Get all reviews for a game (public).
     */
    public function index($idOrSlug): JsonResponse
    {
        $game = ctype_digit((string) $idOrSlug)
            ? Game::findOrFail($idOrSlug)
            : Game::where('slug', $idOrSlug)->firstOrFail();

        $version = Cache::get('games:cache_version', 1);
        $cacheKey = "game_reviews:v{$version}:{$game->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($game) {
            $reviews = GameReview::where('game_id', $game->id)
                ->with('user:id,name,avatar,profile_slug,profile_name,profile_public')
                ->orderByDesc('created_at')
                ->get();

            $avgRating = $reviews->avg('rating');

            return [
                'reviews' => $reviews->map(fn ($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->profile_name ?? $review->user->name,
                        'avatar' => $review->user->avatar,
                        'profile_slug' => $review->user->profile_public ? $review->user->profile_slug : null,
                    ],
                ]),
                'average_rating' => $avgRating ? round($avgRating, 1) : null,
                'total_count' => $reviews->count(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Create a review (requires platinumed status).
     */
    public function store(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // Verify platinumed status
        $hasPlatinumed = $user->games()
            ->where('game_id', $gameId)
            ->wherePivot('status', 'platinumed')
            ->exists();

        if (!$hasPlatinumed) {
            return response()->json(['message' => 'You must have platinumed this game to leave a review'], 403);
        }

        // Check for existing review
        if (GameReview::where('user_id', $user->id)->where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'You already have a review for this game'], 409);
        }

        GameReview::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'rating' => $validated['rating'],
            'body' => $validated['body'] ?? null,
        ]);

        $this->bustReviewCache($gameId);

        return response()->json(['message' => 'Review created'], 201);
    }

    /**
     * Update a review (allowed regardless of current status).
     */
    public function update(Request $request, int $gameId): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = GameReview::where('user_id', $request->user()->id)
            ->where('game_id', $gameId)
            ->firstOrFail();

        $review->update([
            'rating' => $validated['rating'],
            'body' => $validated['body'] ?? null,
        ]);

        $this->bustReviewCache($gameId);

        return response()->json(['message' => 'Review updated']);
    }

    /**
     * Delete a review.
     */
    public function destroy(Request $request, int $gameId): JsonResponse
    {
        $review = GameReview::where('user_id', $request->user()->id)
            ->where('game_id', $gameId)
            ->firstOrFail();

        $review->delete();

        $this->bustReviewCache($gameId);

        return response()->json(['message' => 'Review deleted']);
    }

    private function bustReviewCache(int $gameId): void
    {
        $version = Cache::get('games:cache_version', 1);
        Cache::forget("game_reviews:v{$version}:{$gameId}");
    }
}
