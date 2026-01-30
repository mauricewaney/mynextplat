<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
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

        // Check if profile is accessible
        if (!$user->profile_public && !$isOwner) {
            return response()->json([
                'private' => true,
                'user' => [
                    'name' => $user->name,
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
                'name' => $user->name,
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

        // Generate a profile slug if not set
        if (!$user->profile_slug) {
            $user->profile_slug = $user->generateProfileSlug();
            $user->save();
        }

        return response()->json([
            'profile_public' => $user->profile_public,
            'profile_slug' => $user->profile_slug,
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
            'profile_slug' => [
                'sometimes',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]{1,2}$/',
                Rule::unique('users', 'profile_slug')->ignore($user->id),
            ],
            'notify_new_guides' => 'sometimes|boolean',
        ], [
            'profile_slug.regex' => 'Username can only contain lowercase letters, numbers, and hyphens (not at start/end).',
            'profile_slug.unique' => 'This username is already taken.',
        ]);

        // Normalize slug to lowercase
        if (isset($validated['profile_slug'])) {
            $validated['profile_slug'] = Str::lower($validated['profile_slug']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Settings updated',
            'profile_public' => $user->profile_public,
            'profile_slug' => $user->profile_slug,
            'profile_url' => url('/u/' . $user->getProfileIdentifier()),
            'notify_new_guides' => $user->notify_new_guides,
        ]);
    }

    /**
     * Check if a profile slug is available.
     */
    public function checkSlug(Request $request)
    {
        $slug = Str::lower($request->input('slug', ''));
        $user = $request->user();

        if (strlen($slug) < 3) {
            return response()->json(['available' => false, 'message' => 'Too short (min 3 characters)']);
        }

        if (strlen($slug) > 30) {
            return response()->json(['available' => false, 'message' => 'Too long (max 30 characters)']);
        }

        if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]{1,2}$/', $slug)) {
            return response()->json(['available' => false, 'message' => 'Invalid format']);
        }

        $exists = User::where('profile_slug', $slug)
            ->where('id', '!=', $user->id)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Already taken' : 'Available',
        ]);
    }
}
