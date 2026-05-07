<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotifyController extends Controller
{
    /**
     * Email-only opt-in for guide notifications. No login, no magic link,
     * no unsubscribe — each subscription auto-completes when the notification
     * fires (guide_notified_at is set on the user_game pivot, blocking any
     * further mail for that game).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'game_id' => 'required|integer|exists:games,id',
        ]);

        $email = strtolower(trim($validated['email']));
        $gameId = (int) $validated['game_id'];

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => Str::before($email, '@'),
                'notify_new_guides' => true,
            ]
        );

        if (!$user->games()->where('game_id', $gameId)->exists()) {
            $user->games()->attach($gameId, ['status' => 'backlog']);
        }

        return response()->json([
            'status' => 'subscribed',
            'message' => "We'll email you when a guide is added.",
        ]);
    }
}
