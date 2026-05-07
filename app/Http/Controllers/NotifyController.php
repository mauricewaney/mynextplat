<?php

namespace App\Http\Controllers;

use App\Mail\NotifyConfirmation;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NotifyController extends Controller
{
    /**
     * Email-only opt-in for guide notifications. No account required upfront —
     * we create a placeholder user, attach the game to their list, and send a
     * magic link to verify the email. Once verified, the existing
     * `notifications:new-guides` command picks it up automatically.
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

        // Attach the game only if it's not already in the list. Don't overwrite
        // existing pivots — a user who marked this 'completed' shouldn't have it
        // reset to 'backlog' just because they hit subscribe again.
        if (!$user->games()->where('game_id', $gameId)->exists()) {
            $user->games()->attach($gameId, ['status' => 'backlog']);
        }

        // Already verified → no need for magic link, just confirm
        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'subscribed',
                'message' => "We'll email you when a guide is added.",
            ]);
        }

        $signedUrl = URL::temporarySignedRoute(
            'notify.confirm',
            now()->addDays(7),
            ['user' => $user->id, 'game' => $gameId]
        );

        Mail::to($user->email)->send(new NotifyConfirmation($user, Game::find($gameId), $signedUrl));

        return response()->json([
            'status' => 'check_email',
            'message' => 'Check your email to confirm.',
        ]);
    }

    /**
     * Magic link target. Verifies the signed URL, marks email_verified_at, and
     * logs the user in via session. Any games already in their list become
     * eligible for notifications immediately.
     */
    public function confirm(Request $request, User $user): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return redirect('/?notify=expired');
        }

        // email_verified_at is not in $fillable on the User model — set directly and save.
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user, true);

        $game = Game::find($request->query('game'));
        $redirectTo = $game ? "/game/{$game->slug}?notify=confirmed" : '/?notify=confirmed';

        return redirect($redirectTo);
    }
}
