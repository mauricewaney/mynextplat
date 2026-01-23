<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PSNService;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PSNController extends Controller
{
    public function lookup(string $username, PSNService $psnService)
    {
        // Authenticate with PSN
        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with PSN. Check PSN_NPSSO token.',
            ], 401);
        }

        // Get user's games
        $data = $psnService->getGamesForUser($username);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or profile is private.',
            ], 404);
        }

        // Process games and try to match with local database
        $processedGames = [];
        foreach ($data['titles'] as $title) {
            $gameName = $title['trophyTitleName'] ?? 'Unknown';
            $platform = $title['trophyTitlePlatform'] ?? '';

            // Try to find matching game in local database
            $localMatch = $this->findLocalMatch($gameName);

            $defined = $title['definedTrophies'] ?? [];
            $earned = $title['earnedTrophies'] ?? [];
            $progress = $title['progress'] ?? 0;

            $hasPlatinum = ($defined['platinum'] ?? 0) > 0;
            $earnedPlatinum = ($earned['platinum'] ?? 0) > 0;

            $processedGames[] = [
                'psn_name' => $gameName,
                'psn_platform' => $platform,
                'psn_image' => $title['trophyTitleIconUrl'] ?? null,
                'progress' => $progress,
                'has_platinum' => $hasPlatinum,
                'earned_platinum' => $earnedPlatinum,
                'trophies' => [
                    'bronze' => ['earned' => $earned['bronze'] ?? 0, 'total' => $defined['bronze'] ?? 0],
                    'silver' => ['earned' => $earned['silver'] ?? 0, 'total' => $defined['silver'] ?? 0],
                    'gold' => ['earned' => $earned['gold'] ?? 0, 'total' => $defined['gold'] ?? 0],
                    'platinum' => ['earned' => $earned['platinum'] ?? 0, 'total' => $defined['platinum'] ?? 0],
                ],
                'local_match' => $localMatch,
            ];
        }

        return response()->json([
            'success' => true,
            'user' => [
                'username' => $data['user']['onlineId'],
                'account_id' => $data['user']['accountId'],
                'avatar' => $data['user']['avatarUrl'] ?? null,
            ],
            'total_games' => count($data['titles']),
            'games' => $processedGames,
            'stats' => $this->calculateStats($processedGames),
        ]);
    }

    /**
     * Try to find a matching game in the local database
     */
    private function findLocalMatch(string $psnName): ?array
    {
        // Normalize the name for matching
        $normalized = $this->normalizeName($psnName);

        // Try exact match first
        $game = Game::where('title', $psnName)->first();

        // Try normalized match
        if (!$game) {
            $game = Game::whereRaw('LOWER(title) = ?', [strtolower($psnName)])->first();
        }

        // Try LIKE match
        if (!$game) {
            $game = Game::where('title', 'LIKE', '%' . $psnName . '%')->first();
        }

        // Try with common suffixes removed
        if (!$game) {
            $cleanName = preg_replace('/\s*(Trophies|Trophy|PS4|PS5|\(PS4\)|\(PS5\))$/i', '', $psnName);
            $cleanName = trim($cleanName);
            if ($cleanName !== $psnName) {
                $game = Game::where('title', 'LIKE', '%' . $cleanName . '%')->first();
            }
        }

        if ($game) {
            return [
                'id' => $game->id,
                'title' => $game->title,
                'cover_url' => $game->cover_url,
                'difficulty' => $game->difficulty,
                'time_range' => $game->time_range,
                'has_guide' => !empty($game->playstationtrophies_url) || !empty($game->powerpyx_url),
                'playstationtrophies_url' => $game->playstationtrophies_url,
                'powerpyx_url' => $game->powerpyx_url,
            ];
        }

        return null;
    }

    /**
     * Normalize game name for better matching
     */
    private function normalizeName(string $name): string
    {
        // Remove trademark symbols, special characters
        $name = preg_replace('/[®™©]/u', '', $name);
        // Remove extra whitespace
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    /**
     * Calculate stats for the user's library
     */
    private function calculateStats(array $games): array
    {
        $total = count($games);
        $withPlatinum = 0;
        $earnedPlatinum = 0;
        $matchedGames = 0;
        $withGuide = 0;

        foreach ($games as $game) {
            if ($game['has_platinum']) {
                $withPlatinum++;
                if ($game['earned_platinum']) {
                    $earnedPlatinum++;
                }
            }
            if ($game['local_match']) {
                $matchedGames++;
                if ($game['local_match']['has_guide']) {
                    $withGuide++;
                }
            }
        }

        return [
            'total_games' => $total,
            'games_with_platinum' => $withPlatinum,
            'platinums_earned' => $earnedPlatinum,
            'platinums_remaining' => $withPlatinum - $earnedPlatinum,
            'matched_in_database' => $matchedGames,
            'with_guide' => $withGuide,
            'match_rate' => $total > 0 ? round(($matchedGames / $total) * 100) : 0,
        ];
    }

    /**
     * Manually match PSN games to local database
     */
    public function matchGames(Request $request)
    {
        $request->validate([
            'matches' => 'required|array',
            'matches.*.psn_name' => 'required|string',
            'matches.*.game_id' => 'required|integer|exists:games,id',
        ]);

        $matched = 0;
        foreach ($request->matches as $match) {
            // Store the match - could add a pivot table for PSN<->Game mappings
            // For now, just return success
            $matched++;
        }

        return response()->json([
            'success' => true,
            'message' => "Matched {$matched} games.",
        ]);
    }
}
