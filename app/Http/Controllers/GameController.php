<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\Platform;
use App\Services\GameFilterService;
use App\Services\PSNService;
use Illuminate\Http\Request;

class GameController extends Controller
{
    protected $filterService;

    public function __construct(GameFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Lookup authenticated user's own PSN library (uses "me" endpoint)
     * Returns games with trophy data (games user has played)
     */
    public function psnMyLibrary(PSNService $psnService)
    {
        // Authenticate with PSN
        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'PSN service temporarily unavailable.',
            ], 503);
        }

        // Get own games using "me" endpoint
        $data = $psnService->getMyGames();

        if (isset($data['error'])) {
            return response()->json([
                'success' => false,
                'error' => $data['error'],
                'message' => $data['message'],
            ], 500);
        }

        return $this->processPsnGames($data);
    }

    /**
     * Lookup authenticated user's full game library (all owned games)
     * This includes games the user owns but hasn't played yet
     */
    public function psnMyOwnedGames(PSNService $psnService)
    {
        // Authenticate with PSN
        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'PSN service temporarily unavailable.',
            ], 503);
        }

        // Get full game library
        $data = $psnService->getMyGameLibrary();

        if (isset($data['error'])) {
            return response()->json([
                'success' => false,
                'error' => $data['error'],
                'message' => $data['message'],
            ], 500);
        }

        return $this->processOwnedGames($data);
    }

    /**
     * Process owned games data (from game library, not trophy list)
     */
    private function processOwnedGames(array $data)
    {
        $allMatchedGameIds = [];
        $hasGuideGameIds = [];
        $unmatchedTitles = [];
        $unmatchedCount = 0;

        foreach ($data['titles'] as $title) {
            // Game library uses different field names than trophy API
            $gameName = $title['name'] ?? $title['titleName'] ?? 'Unknown';
            $npTitleId = $title['titleId'] ?? null; // e.g., "PPSA01234_00" or "CUSA12345_00"
            $conceptId = $title['conceptId'] ?? null;

            // Try to match by title name
            $localMatch = $this->findLocalMatch($gameName, true, $npTitleId);

            if ($localMatch) {
                $gameId = $localMatch['id'];
                $allMatchedGameIds[] = $gameId;

                if ($localMatch['has_guide']) {
                    $hasGuideGameIds[] = $gameId;
                }
            } else {
                $unmatchedCount++;
                $unmatchedTitles[] = $gameName . ($npTitleId ? " [{$npTitleId}]" : '');
            }
        }

        // Remove duplicates
        $allMatchedGameIds = array_unique($allMatchedGameIds);
        $hasGuideGameIds = array_unique($hasGuideGameIds);

        // Log unmatched titles
        if (!empty($unmatchedTitles)) {
            file_put_contents(storage_path('logs/psn_owned_unmatched.txt'), implode("\n", $unmatchedTitles));
        }

        return response()->json([
            'success' => true,
            'source' => 'owned_games',
            'user' => [
                'username' => $data['user']['onlineId'] ?? 'Unknown',
                'avatar' => $data['user']['avatarUrl'] ?? null,
            ],
            'game_ids' => array_values($allMatchedGameIds),
            'has_guide_ids' => array_values($hasGuideGameIds),
            'unmatched_titles' => array_values($unmatchedTitles),
            'stats' => [
                'total_owned_games' => count($data['titles']),
                'matched_games' => count($allMatchedGameIds),
                'unmatched_games' => $unmatchedCount,
                'has_guide' => count($hasGuideGameIds),
            ],
        ]);
    }

    /**
     * Lookup PSN user's library and return matched game IDs
     */
    public function psnLookup(string $username, PSNService $psnService)
    {
        // Authenticate with PSN
        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'PSN service temporarily unavailable.',
            ], 503);
        }

        // Get user's games
        $data = $psnService->getGamesForUser($username);

        if (isset($data['error'])) {
            $status = match($data['error']) {
                'user_not_found' => 404,
                'private_trophies' => 403,
                default => 500,
            };
            return response()->json([
                'success' => false,
                'error' => $data['error'],
                'message' => $data['message'],
            ], $status);
        }

        return $this->processPsnGames($data);
    }

    /**
     * Process PSN games data and return JSON response
     */
    private function processPsnGames(array $data)
    {
        $allMatchedGameIds = [];
        $platinumedGameIds = []; // Games where platinum IS earned (any platform)
        $needsPlatinumGameIds = []; // Games where platinum exists but not earned
        $hasGuideGameIds = []; // Games that have at least one guide
        $unmatchedTitles = []; // PSN titles that didn't match any game
        $unmatchedCount = 0;
        $platinumsEarned = 0;

        foreach ($data['titles'] as $title) {
            $gameName = $title['trophyTitleName'] ?? 'Unknown';
            $npId = $title['npCommunicationId'] ?? null;

            // Try to match by NP ID first (most reliable), then by name
            $localMatch = $this->findLocalMatch($gameName, true, $npId);

            $hasPlatinum = ($title['definedTrophies']['platinum'] ?? 0) > 0;
            $earnedPlatinum = ($title['earnedTrophies']['platinum'] ?? 0) > 0;

            if ($earnedPlatinum) {
                $platinumsEarned++;
            }

            if ($localMatch) {
                $gameId = $localMatch['id'];
                $allMatchedGameIds[] = $gameId;

                // Track games with guides
                if ($localMatch['has_guide']) {
                    $hasGuideGameIds[] = $gameId;
                }

                // Track games where platinum is earned (on any platform version)
                if ($hasPlatinum && $earnedPlatinum) {
                    $platinumedGameIds[] = $gameId;
                }

                // Track games that need platinum (has platinum trophy but not earned)
                if ($hasPlatinum && !$earnedPlatinum) {
                    $needsPlatinumGameIds[] = $gameId;
                }
            } else {
                $unmatchedCount++;
                $unmatchedTitles[] = $gameName . ($npId ? " [{$npId}]" : '');
            }
        }

        // Remove duplicates
        $allMatchedGameIds = array_unique($allMatchedGameIds);
        $platinumedGameIds = array_unique($platinumedGameIds);
        $needsPlatinumGameIds = array_unique($needsPlatinumGameIds);
        $hasGuideGameIds = array_unique($hasGuideGameIds);

        // If platinum is earned on ANY version, remove from "needs platinum" list
        // This handles cross-platform games (e.g., PS4/PS5 versions)
        $needsPlatinumGameIds = array_diff($needsPlatinumGameIds, $platinumedGameIds);

        // Calculate needs platinum WITH guide
        $needsPlatinumWithGuide = array_values(array_intersect($needsPlatinumGameIds, $hasGuideGameIds));

        // Log unmatched titles to a file for debugging
        if (!empty($unmatchedTitles)) {
            file_put_contents(storage_path('logs/psn_unmatched.txt'), implode("\n", $unmatchedTitles));
        }

        return response()->json([
            'success' => true,
            'user' => [
                'username' => $data['user']['onlineId'] ?? 'Unknown',
                'avatar' => $data['user']['avatarUrl'] ?? null,
            ],
            'game_ids' => array_values($allMatchedGameIds),
            'needs_platinum_ids' => array_values($needsPlatinumGameIds),
            'has_guide_ids' => array_values($hasGuideGameIds),
            'needs_platinum_with_guide_ids' => $needsPlatinumWithGuide,
            'unmatched_titles' => array_values($unmatchedTitles), // For debugging
            'stats' => [
                'total_psn_games' => count($data['titles']),
                'matched_games' => count($allMatchedGameIds),
                'unmatched_games' => $unmatchedCount,
                'needs_platinum' => count($needsPlatinumGameIds),
                'needs_platinum_with_guide' => count($needsPlatinumWithGuide),
                'has_guide' => count($hasGuideGameIds),
                'platinums_earned' => $platinumsEarned,
            ],
        ]);
    }

    /**
     * Cached games list for matching
     */
    private ?array $gamesCache = null;
    private ?array $npIdIndex = null;

    /**
     * Get cached games list with normalized titles
     */
    private function getGamesCache(): array
    {
        if ($this->gamesCache === null) {
            $games = Game::all(['id', 'title', 'np_communication_ids', 'psnprofiles_url', 'playstationtrophies_url', 'powerpyx_url']);
            $this->gamesCache = [];
            $this->npIdIndex = [];

            foreach ($games as $game) {
                $this->gamesCache[] = [
                    'game' => $game,
                    'normalized' => $this->normalizeTitle($game->title),
                ];

                // Build NP ID index for fast lookup
                if (!empty($game->np_communication_ids)) {
                    foreach ($game->np_communication_ids as $npId) {
                        $this->npIdIndex[$npId] = $game;
                    }
                }
            }
        }

        return $this->gamesCache;
    }

    /**
     * Find game by NP Communication ID
     */
    private function findByNpId(string $npId): ?Game
    {
        $this->getGamesCache(); // Ensure cache is loaded
        return $this->npIdIndex[$npId] ?? null;
    }

    /**
     * Add NP ID to a game (for auto-population)
     */
    private function addNpIdToGame(Game $game, string $npId): void
    {
        $ids = $game->np_communication_ids ?? [];
        if (!in_array($npId, $ids)) {
            $ids[] = $npId;
            $game->np_communication_ids = $ids;
            $game->save();

            // Update the index
            $this->npIdIndex[$npId] = $game;
        }
    }

    /**
     * Normalize a title for comparison
     */
    private function normalizeTitle(string $str): string
    {
        // Replace ellipsis character with three dots
        $str = str_replace('…', '...', $str);
        // Replace trademark symbols with space (so "TEKKEN™7" becomes "TEKKEN 7")
        $str = preg_replace('/[\x{2122}\x{00AE}\x{00A9}]/u', ' ', $str);
        // Remove other special characters
        $str = preg_replace('/[\x{221A}\x{00B7}]/u', '', $str);
        // Normalize quotes and apostrophes to standard ones
        $str = preg_replace('/[\x{2018}\x{2019}\x{0060}]/u', "'", $str);
        $str = preg_replace('/[\x{201C}\x{201D}]/u', '"', $str);
        // Normalize colons and dashes to space (simpler matching)
        $str = preg_replace('/\s*[:\x{2013}\x{2014}-]\s*/u', ' ', $str);
        // Add space between letters and numbers (e.g., "Persona5" -> "Persona 5")
        $str = preg_replace('/([a-zA-Z])(\d)/u', '$1 $2', $str);
        $str = preg_replace('/(\d)([a-zA-Z])/u', '$1 $2', $str);
        // Remove extra whitespace
        $str = preg_replace('/\s+/', ' ', $str);
        // Lowercase
        return strtolower(trim($str));
    }

    /**
     * Try to find a matching game in the local database
     */
    private function findLocalMatch(string $psnName, bool $includeGuideInfo = false, ?string $npId = null): ?array
    {
        $game = null;
        $matchedByNpId = false;

        // Try NP ID match first (most reliable)
        if ($npId) {
            $game = $this->findByNpId($npId);
            if ($game) {
                $matchedByNpId = true;
            }
        }

        // Fall back to name matching
        if (!$game) {
            $normalizedPsn = $this->normalizeTitle($psnName);
            $gamesCache = $this->getGamesCache();

            // Try exact normalized match first (handles ™, ®, different quotes, etc.)
            foreach ($gamesCache as $entry) {
                if ($entry['normalized'] === $normalizedPsn) {
                    $game = $entry['game'];
                    break;
                }
            }

            // Try with common suffixes removed
            if (!$game) {
                $cleanName = preg_replace('/\s*(Trophies|Trophy|PS4|PS5|\(PS4\)|\(PS5\)|Remastered|Enhanced Edition|Director\'s Cut)$/i', '', $psnName);
                $normalizedClean = $this->normalizeTitle($cleanName);

                if ($normalizedClean !== $normalizedPsn) {
                    foreach ($gamesCache as $entry) {
                        if ($entry['normalized'] === $normalizedClean) {
                            $game = $entry['game'];
                            break;
                        }
                    }
                }
            }

            // Try contains match (PSN title contained in DB title or vice versa)
            if (!$game) {
                $bestMatch = null;
                $bestScore = 0;

                foreach ($gamesCache as $entry) {
                    $normalizedDb = $entry['normalized'];

                    // Skip very short titles to avoid false matches
                    if (strlen($normalizedDb) < 4 || strlen($normalizedPsn) < 4) {
                        continue;
                    }

                    // Check if one contains the other
                    if (str_contains($normalizedDb, $normalizedPsn) || str_contains($normalizedPsn, $normalizedDb)) {
                        // Score by similarity (prefer closer length matches)
                        $score = 1 - abs(strlen($normalizedDb) - strlen($normalizedPsn)) / max(strlen($normalizedDb), strlen($normalizedPsn));

                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestMatch = $entry['game'];
                        }
                    }
                }

                // Only accept if reasonably similar (at least 50% length match)
                if ($bestScore > 0.5) {
                    $game = $bestMatch;
                }
            }

            // Auto-populate NP ID for future matching
            if ($game && $npId && !$matchedByNpId) {
                $this->addNpIdToGame($game, $npId);
            }
        }

        if ($game) {
            $result = [
                'id' => $game->id,
                'title' => $game->title,
            ];

            if ($includeGuideInfo) {
                $result['has_guide'] = !empty($game->psnprofiles_url)
                    || !empty($game->playstationtrophies_url)
                    || !empty($game->powerpyx_url);
            }

            return $result;
        }

        return null;
    }

    /**
     * List games with public filters
     */
    public function index(Request $request)
    {
        $query = Game::with(['genres', 'tags', 'platforms']);

        // Apply filters (admin mode = false for public filters only)
        $this->filterService->applyFilters($query, $request, false);

        // Paginate (public default: 24 per page)
        return response()->json(
            $this->filterService->paginate($query, $request, 24, 100)
        );
    }

    /**
     * Get a single game by ID or slug
     */
    public function show($idOrSlug)
    {
        $game = Game::with(['genres', 'tags', 'platforms'])
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        return response()->json($game);
    }

    /**
     * Get filter options (genres, tags, platforms)
     */
    public function filterOptions()
    {
        return response()->json([
            'genres' => Genre::orderBy('name')->get(['id', 'name', 'slug']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'platforms' => Platform::orderBy('name')->get(['id', 'name', 'slug', 'short_name']),
        ]);
    }
}
