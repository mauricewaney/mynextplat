<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameCorrection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class GameCorrectionController extends Controller
{
    /**
     * Get categories for the form.
     */
    public function categories(): JsonResponse
    {
        return response()->json(GameCorrection::CATEGORIES);
    }

    /**
     * Search games for the autocomplete.
     */
    public function searchGames(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $games = Game::where('title', 'like', "%{$query}%")
            ->select('id', 'title', 'slug', 'cover_url')
            ->orderBy('title')
            ->limit(10)
            ->get();

        return response()->json($games);
    }

    /**
     * Submit a correction.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip();

        // Rate limiting
        $rateLimitKey = $user ? "corrections:user:{$user->id}" : "corrections:ip:{$ip}";
        $maxAttempts = $user ? 10 : 5; // Logged in users get more

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => "Too many submissions. Please try again in {$seconds} seconds.",
            ], 429);
        }

        // Verify reCAPTCHA for guests
        if (!$user) {
            $recaptchaToken = $request->input('recaptcha_token');

            if (!$recaptchaToken) {
                return response()->json(['message' => 'reCAPTCHA verification required.'], 422);
            }

            $recaptchaValid = $this->verifyRecaptcha($recaptchaToken, $ip);

            if (!$recaptchaValid) {
                return response()->json(['message' => 'reCAPTCHA verification failed. Please try again.'], 422);
            }
        }

        $validated = $request->validate([
            'game_id' => ['required', 'exists:games,id'],
            'category' => ['required', Rule::in(array_keys(GameCorrection::CATEGORIES))],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $correction = GameCorrection::create([
            'game_id' => $validated['game_id'],
            'user_id' => $user?->id,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'source_url' => $validated['source_url'] ?? null,
            'email' => $validated['email'] ?? null,
            'ip_address' => $ip,
            'status' => 'pending',
        ]);

        RateLimiter::hit($rateLimitKey, 3600); // 1 hour window

        return response()->json([
            'message' => 'Thank you! Your correction has been submitted and will be reviewed.',
            'id' => $correction->id,
        ], 201);
    }

    /**
     * Verify reCAPTCHA v3 token.
     */
    private function verifyRecaptcha(string $token, string $ip): bool
    {
        $secret = config('services.recaptcha.secret');

        if (!$secret) {
            // If not configured, allow submission (dev mode)
            return true;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            $result = $response->json();

            // v3 returns a score from 0.0 to 1.0
            // 0.5 is Google's recommended threshold
            return $result['success'] ?? false
                && ($result['score'] ?? 0) >= 0.5
                && ($result['action'] ?? '') === 'submit_correction';
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
