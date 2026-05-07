<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotifyController extends Controller
{
    /**
     * Email-only opt-in for guide notifications. No account, no login, no magic
     * link — abuse is handled via the unsubscribe link in every notification mail
     * (and rate-limiting on the route). If a typo happens, the worst outcome is
     * an unrelated address gets one mail, which they can one-click out of.
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

    /**
     * One-click unsubscribe via signed URL. Flips notify_new_guides off for the
     * user, killing all future guide notifications. Per-game unsubscribe is
     * intentionally out of scope — most people who hit this just want to stop
     * mail, not curate.
     */
    public function unsubscribe(Request $request, User $user): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return redirect('/?notify=expired');
        }

        $user->notify_new_guides = false;
        $user->save();

        return redirect('/?notify=unsubscribed');
    }
}
