<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * List all public profiles.
     */
    public function index(Request $request)
    {
        $profiles = User::where('profile_public', true)
            ->select(['id', 'name', 'avatar', 'profile_slug', 'profile_name'])
            ->withCount([
                'games',
                'games as platinum_count' => function ($query) {
                    $query->where('user_game.status', 'platinumed');
                },
            ])
            ->orderByDesc('platinum_count')
            ->orderByDesc('games_count')
            ->paginate(18);

        // Add display_name to each profile
        $profiles->getCollection()->transform(function ($profile) {
            $profile->display_name = $profile->profile_name ?: $profile->name;
            return $profile;
        });

        return response()->json($profiles);
    }

    /**
     * Get a public profile by slug or ID.
     */
    public function show(string $identifier)
    {
        // Try to find by slug first, then by ID
        $user = User::where('profile_slug', $identifier)->first();

        if (!$user && is_numeric($identifier)) {
            $user = User::find($identifier);
        }

        if (!$user) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $currentUser = auth('sanctum')->user();
        $isOwner = $currentUser && $currentUser->id === $user->id;

        $displayName = $user->profile_name ?: $user->name;

        // Check if profile is accessible
        if (!$user->profile_public && !$isOwner) {
            return response()->json([
                'private' => true,
                'user' => [
                    'display_name' => $displayName,
                    'avatar' => $user->avatar,
                ],
            ]);
        }

        // Get the user's games with relevant info
        $games = $user->games()
            ->select([
                'games.id',
                'games.title',
                'games.slug',
                'games.cover_url',
                'games.difficulty',
                'games.time_min',
                'games.time_max',
                'games.psnprofiles_url',
                'games.playstationtrophies_url',
                'games.powerpyx_url',
            ])
            ->orderByPivot('updated_at', 'desc')
            ->get()
            ->map(function ($game) {
                return [
                    'id' => $game->id,
                    'title' => $game->title,
                    'slug' => $game->slug,
                    'cover_url' => $game->cover_url,
                    'difficulty' => $game->difficulty,
                    'time_min' => $game->time_min,
                    'time_max' => $game->time_max,
                    'has_guide' => $game->psnprofiles_url || $game->playstationtrophies_url || $game->powerpyx_url,
                    'status' => $game->pivot->status,
                ];
            });

        // Calculate stats
        $stats = [
            'total' => $games->count(),
            'platinum' => $games->where('status', 'platinum')->count(),
            'completed' => $games->where('status', 'completed')->count(),
            'playing' => $games->where('status', 'playing')->count(),
            'want_to_play' => $games->where('status', 'want_to_play')->count(),
        ];

        return response()->json([
            'private' => false,
            'is_owner' => $isOwner,
            'user' => [
                'id' => $user->id,
                'display_name' => $displayName,
                'avatar' => $user->avatar,
                'profile_slug' => $user->profile_slug,
                'member_since' => $user->created_at->format('F Y'),
            ],
            'games' => $games,
            'stats' => $stats,
        ]);
    }

    /**
     * Get current user's settings.
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();

        // Generate a profile name and slug if not set
        if (!$user->profile_name) {
            $user->profile_name = $user->name;
        }

        if (!$user->profile_slug) {
            $user->profile_slug = $user->generateProfileSlug();
        }

        if ($user->isDirty()) {
            $user->save();
        }

        return response()->json([
            'profile_public' => $user->profile_public,
            'profile_slug' => $user->profile_slug,
            'profile_name' => $user->profile_name,
            'profile_url' => url('/u/' . $user->getProfileIdentifier()),
            'notify_new_guides' => $user->notify_new_guides,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    /**
     * Update current user's settings.
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'profile_public' => 'sometimes|boolean',
            'profile_name' => 'sometimes|string|min:2|max:50',
            'notify_new_guides' => 'sometimes|boolean',
        ], [
            'profile_name.min' => 'Collection name must be at least 2 characters.',
            'profile_name.max' => 'Collection name must be at most 50 characters.',
        ]);

        // Auto-generate slug from profile_name if it changed
        if (isset($validated['profile_name'])) {
            $slug = Str::slug($validated['profile_name']);
            if (empty($slug)) {
                $slug = 'user';
            }

            // Ensure uniqueness
            $baseSlug = $slug;
            $counter = 1;
            while (User::where('profile_slug', $slug)->where('id', '!=', $user->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validated['profile_slug'] = $slug;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Settings updated',
            'profile_public' => $user->profile_public,
            'profile_slug' => $user->profile_slug,
            'profile_name' => $user->profile_name,
            'profile_url' => url('/u/' . $user->getProfileIdentifier()),
            'notify_new_guides' => $user->notify_new_guides,
        ]);
    }

    /**
     * Check if a profile name (and its generated slug) is available.
     */
    public function checkSlug(Request $request)
    {
        $profileName = $request->input('name', '');
        $user = $request->user();

        if (strlen($profileName) < 2) {
            return response()->json(['available' => false, 'message' => 'Too short (min 2 characters)']);
        }

        if (strlen($profileName) > 50) {
            return response()->json(['available' => false, 'message' => 'Too long (max 50 characters)']);
        }

        $slug = Str::slug($profileName);

        if (empty($slug)) {
            return response()->json(['available' => false, 'message' => 'Invalid name — must contain letters or numbers']);
        }

        $exists = User::where('profile_slug', $slug)
            ->where('id', '!=', $user->id)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'slug' => $slug,
            'message' => $exists ? 'This name generates a URL that\'s already taken' : 'Available',
        ]);
    }
}
