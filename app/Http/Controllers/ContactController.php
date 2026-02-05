<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * Submit a contact message.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip();

        // Rate limiting
        $rateLimitKey = $user ? "contact:user:{$user->id}" : "contact:ip:{$ip}";
        $maxAttempts = $user ? 10 : 5;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'category' => ['required', Rule::in(array_keys(ContactMessage::CATEGORIES))],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create([
            'user_id' => $user?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'pending',
            'ip_address' => $ip,
        ]);

        RateLimiter::hit($rateLimitKey, 3600); // 1 hour window

        return response()->json([
            'message' => 'Thank you! Your message has been sent and we\'ll get back to you soon.',
            'id' => $contactMessage->id,
        ], 201);
    }

    /**
     * Verify reCAPTCHA v3 token.
     */
    private function verifyRecaptcha(string $token, string $ip): bool
    {
        $secret = config('services.recaptcha.secret');

        if (!$secret) {
            return true;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            $result = $response->json();

            return $result['success'] ?? false
                && ($result['score'] ?? 0) >= 0.5
                && ($result['action'] ?? '') === 'submit_contact';
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
