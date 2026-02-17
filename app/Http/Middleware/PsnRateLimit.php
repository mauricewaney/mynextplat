<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PsnRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $freeLookups = config('services.psn.free_lookups', 3);
        $cooldownSeconds = config('services.psn.cooldown_seconds', 60);

        // Use IP as identifier (works for both authenticated and anonymous users)
        $ip = $request->ip();
        $countKey = "psn_lookups:{$ip}";
        $cooldownKey = "psn_cooldown:{$ip}";

        $lookupCount = Cache::get($countKey, 0);

        // After free lookups, enforce cooldown
        if ($lookupCount >= $freeLookups) {
            $cooldownRemaining = Cache::get($cooldownKey);
            if ($cooldownRemaining) {
                $secondsLeft = now()->diffInSeconds($cooldownRemaining, false);
                if ($secondsLeft > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Please wait {$secondsLeft} seconds before your next lookup.",
                        'retry_after' => $secondsLeft,
                    ], 429);
                }
            }

            // Set cooldown for next request
            Cache::put($cooldownKey, now()->addSeconds($cooldownSeconds), $cooldownSeconds);
        }

        // Increment counter (expires at end of day)
        $secondsUntilMidnight = now()->endOfDay()->diffInSeconds(now());
        Cache::put($countKey, $lookupCount + 1, $secondsUntilMidnight);

        $response = $next($request);

        // Add rate limit headers
        $remaining = max(0, $freeLookups - $lookupCount - 1);
        $response->headers->set('X-PSN-Lookups-Remaining', $remaining);
        if ($lookupCount >= $freeLookups) {
            $response->headers->set('X-PSN-Cooldown', $cooldownSeconds);
        }

        return $response;
    }
}
