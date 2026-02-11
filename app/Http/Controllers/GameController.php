<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\Platform;
use App\Models\PsnTitle;
use App\Services\GameFilterService;
use App\Services\PSNService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
     * Combines trophy data + library for most complete list
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

        // Get combined trophy + library data
        $data = $psnService->getMyFullLibrary();

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
        // Debug: Log ALL raw titles to file
        $allRawTitles = array_map(function($t) {
            $name = $t['name'] ?? $t['titleName'] ?? 'Unknown';
            $id = $t['titleId'] ?? $t['npCommunicationId'] ?? 'no-id';
            return "{$name} [{$id}]";
        }, $data['titles']);
        file_put_contents(storage_path('logs/psn_owned_all_titles.txt'),
            "Total: " . count($data['titles']) . "\n\n" . implode("\n", $allRawTitles));

        $allMatchedGameIds = [];
        $hasGuideGameIds = [];
        $unmatchedTitles = [];
        $unmatchedCount = 0;

        foreach ($data['titles'] as $title) {
            // Game library uses different field names than trophy API
            $gameName = $title['name'] ?? $title['titleName'] ?? 'Unknown';
            $npTitleId = $title['titleId'] ?? null; // e.g., "PPSA01234_00" or "CUSA12345_00"
            $conceptId = $title['conceptId'] ?? null;

            // Debug: Track Octopath specifically
            $isOctopath = stripos($gameName, 'octopath') !== false;
            if ($isOctopath) {
                \Log::info('OCTOPATH DEBUG: Processing', [
                    'name' => $gameName,
                    'titleId' => $npTitleId,
                    'raw_title' => $title,
                ]);
            }

            // Try to match by title name
            $localMatch = $this->findLocalMatch($gameName, true, $npTitleId);

            if ($isOctopath) {
                \Log::info('OCTOPATH DEBUG: Match result', [
                    'name' => $gameName,
                    'matched' => $localMatch ? true : false,
                    'match_details' => $localMatch,
                ]);
            }

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

        // Debug: Check if Octopath IDs are in the final arrays
        \Log::info('OCTOPATH FINAL CHECK', [
            'id_8825_in_matched' => in_array(8825, $allMatchedGameIds),
            'id_17329_in_matched' => in_array(17329, $allMatchedGameIds),
            'total_matched' => count($allMatchedGameIds),
            'total_unmatched' => $unmatchedCount,
        ]);

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
        try {
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
        } catch (\Exception $e) {
            \Log::error('PSN lookup failed: ' . $e->getMessage(), [
                'username' => $username,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to lookup PSN profile. Please try again.',
            ], 500);
        }
    }

    /**
     * Get user's library with full game details and trophy progress
     * This is the main endpoint for the user library feature
     */
    public function psnUserLibrary(string $username, PSNService $psnService)
    {
        if (!$psnService->authenticateFromConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'PSN service temporarily unavailable.',
            ], 503);
        }

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

        return $this->processUserLibrary($data, $username);
    }

    /**
     * Process user library with full details
     */
    private function processUserLibrary(array $data, string $username)
    {
        // Debug: Log ALL raw PSN titles to file
        $allRawTitles = array_map(function($t) {
            return ($t['trophyTitleName'] ?? 'Unknown') . ' [' . ($t['npCommunicationId'] ?? 'no-id') . ']';
        }, $data['titles']);
        file_put_contents(storage_path('logs/psn_library_all_titles.txt'),
            "Total: " . count($data['titles']) . " for user: {$username}\n\n" . implode("\n", $allRawTitles));

        $library = [];
        $unmatchedTitles = [];
        $totalTrophies = ['bronze' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0];
        $earnedTrophies = ['bronze' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0];

        foreach ($data['titles'] as $title) {
            $gameName = $title['trophyTitleName'] ?? 'Unknown';
            $npId = $title['npCommunicationId'] ?? null;
            $platform = $title['trophyTitlePlatform'] ?? null;

            // Collect NP ID into database
            if ($npId) {
                $psnTitle = PsnTitle::where('np_communication_id', $npId)->first();
                if ($psnTitle) {
                    $psnTitle->incrementSeen();
                } else {
                    PsnTitle::upsertFromTrophy($title, $username);
                }
            }

            // Count trophies
            $defined = $title['definedTrophies'] ?? [];
            $earned = $title['earnedTrophies'] ?? [];

            $totalTrophies['bronze'] += $defined['bronze'] ?? 0;
            $totalTrophies['silver'] += $defined['silver'] ?? 0;
            $totalTrophies['gold'] += $defined['gold'] ?? 0;
            $totalTrophies['platinum'] += $defined['platinum'] ?? 0;

            $earnedTrophies['bronze'] += $earned['bronze'] ?? 0;
            $earnedTrophies['silver'] += $earned['silver'] ?? 0;
            $earnedTrophies['gold'] += $earned['gold'] ?? 0;
            $earnedTrophies['platinum'] += $earned['platinum'] ?? 0;

            // Try to match
            $localMatch = $this->findLocalMatch($gameName, true, $npId);

            $hasPlatinum = ($defined['platinum'] ?? 0) > 0;
            $earnedPlatinum = ($earned['platinum'] ?? 0) > 0;

            $libraryItem = [
                'psn_title' => $gameName,
                'np_communication_id' => $npId,
                'platform' => $platform,
                'icon_url' => $title['trophyTitleIconUrl'] ?? null,
                'progress' => $title['progress'] ?? 0,
                'has_platinum' => $hasPlatinum,
                'earned_platinum' => $earnedPlatinum,
                'trophies' => [
                    'defined' => $defined,
                    'earned' => $earned,
                ],
                'game' => null,
            ];

            if ($localMatch) {
                // Load full game data
                $game = Game::with(['genres', 'platforms'])->find($localMatch['id']);
                if ($game) {
                    $libraryItem['game'] = [
                        'id' => $game->id,
                        'title' => $game->title,
                        'slug' => $game->slug,
                        'cover_url' => $game->cover_url ?? $game->trophy_icon_url,
                        'difficulty' => $game->difficulty,
                        'time_min' => $game->time_min,
                        'time_max' => $game->time_max,
                        'time_range' => $game->time_range,
                        'has_platinum' => $game->has_platinum,
                        'has_guide' => $game->hasGuides(),
                        'platforms' => $game->platforms->pluck('short_name'),
                        'genres' => $game->genres->pluck('name'),
                    ];
                }
            } else {
                $unmatchedTitles[] = $gameName;
            }

            $library[] = $libraryItem;
        }

        // Sort: unplatinumed games with guides first, then by progress
        usort($library, function ($a, $b) {
            // Prioritize games that need platinum and have a guide
            $aScore = (!$a['earned_platinum'] && $a['has_platinum'] && $a['game'] && $a['game']['has_guide']) ? 1000 : 0;
            $bScore = (!$b['earned_platinum'] && $b['has_platinum'] && $b['game'] && $b['game']['has_guide']) ? 1000 : 0;

            // Then by progress (lower progress = more work to do = higher priority)
            $aScore += (100 - $a['progress']);
            $bScore += (100 - $b['progress']);

            return $bScore <=> $aScore;
        });

        return response()->json([
            'success' => true,
            'user' => [
                'username' => $data['user']['onlineId'] ?? $username,
                'avatar' => $data['user']['avatarUrl'] ?? null,
            ],
            'library' => $library,
            'stats' => [
                'total_games' => count($library),
                'matched_games' => count(array_filter($library, fn($l) => $l['game'] !== null)),
                'unmatched_games' => count($unmatchedTitles),
                'platinums_earned' => $earnedTrophies['platinum'],
                'platinums_available' => $totalTrophies['platinum'],
                'total_trophies' => $totalTrophies,
                'earned_trophies' => $earnedTrophies,
            ],
        ]);
    }

    /**
     * Process PSN games data and return JSON response
     */
    private function processPsnGames(array $data)
    {
        // Debug: Log ALL raw PSN titles to file
        $allRawTitles = array_map(function($t) {
            return ($t['trophyTitleName'] ?? 'Unknown') . ' [' . ($t['npCommunicationId'] ?? 'no-id') . ']';
        }, $data['titles']);
        file_put_contents(storage_path('logs/psn_all_titles.txt'),
            "Total: " . count($data['titles']) . "\n\n" . implode("\n", $allRawTitles));

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
        // Replace underscores with spaces (e.g., "WATCH_DOGS" -> "WATCH DOGS")
        $str = str_replace('_', ' ', $str);
        // Replace trademark symbols with space (so "TEKKEN™7" becomes "TEKKEN 7")
        $str = preg_replace('/[\x{2122}\x{00AE}\x{00A9}]/u', ' ', $str);
        // Remove other special characters
        $str = preg_replace('/[\x{221A}\x{00B7}]/u', '', $str);
        // Convert Roman numerals to Arabic (Ⅰ-Ⅻ unicode block)
        $romanMap = [
            'Ⅰ' => '1', 'Ⅱ' => '2', 'Ⅲ' => '3', 'Ⅳ' => '4', 'Ⅴ' => '5',
            'Ⅵ' => '6', 'Ⅶ' => '7', 'Ⅷ' => '8', 'Ⅸ' => '9', 'Ⅹ' => '10',
            'Ⅺ' => '11', 'Ⅻ' => '12',
            'ⅰ' => '1', 'ⅱ' => '2', 'ⅲ' => '3', 'ⅳ' => '4', 'ⅴ' => '5',
            'ⅵ' => '6', 'ⅶ' => '7', 'ⅷ' => '8', 'ⅸ' => '9', 'ⅹ' => '10',
            'ⅺ' => '11', 'ⅻ' => '12',
        ];
        $str = strtr($str, $romanMap);
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
            // Handle trademark symbols (®™©) and non-breaking spaces before suffix
            // Also handle various PSN-specific suffixes: Trophy pack., Expansion, DLC, etc.
            if (!$game) {
                $cleanName = preg_replace('/[\s\x{2122}\x{00AE}\x{00A9}\x{00A0}]*(Trophies|Trophy|Trophy pack\.?|PS4|PS5|\(PS4\)|\(PS5\)|Remastered|Enhanced Edition|Director\'s Cut|Expansion|Game of the Year Edition|GOTY Edition|Complete Edition|Definitive Edition|Ultimate Edition|Standard Edition|Digital Edition|Deluxe Edition)$/iu', '', $psnName);
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
        // Check if this is the default query (no filters, first page, default sort)
        $isDefaultQuery = $this->isDefaultQuery($request);

        if ($isDefaultQuery) {
            // Cache the default "all games" page for 5 minutes
            $cached = Cache::remember('games:default:page1', 300, function () use ($request) {
                $query = Game::with(['genres', 'tags', 'platforms']);
                $this->filterService->applyFilters($query, $request, false);
                return $this->filterService->paginate($query, $request, 24, 100);
            });

            return response()->json($cached);
        }

        // For filtered queries, run normally without cache
        $query = Game::with(['genres', 'tags', 'platforms']);
        $this->filterService->applyFilters($query, $request, false);

        return response()->json(
            $this->filterService->paginate($query, $request, 24, 100)
        );
    }

    /**
     * Check if request is for the default unfiltered games list
     */
    private function isDefaultQuery(Request $request): bool
    {
        // Only cache page 1 with no filters
        if ($request->input('page', 1) != 1) {
            return false;
        }

        // Check for any active filters beyond the default has_guide=true
        $filterParams = [
            'search', 'platform_ids', 'genre_ids', 'tag_ids',
            'difficulty_min', 'difficulty_max', 'time_min', 'time_max',
            'has_online_trophies', 'missable_trophies',
            'max_playthroughs', 'min_score', 'game_ids',
            'guide_psnp', 'guide_pst', 'guide_ppx',
            'user_score_min', 'user_score_max',
            'critic_score_min', 'critic_score_max',
        ];

        foreach ($filterParams as $param) {
            if ($request->has($param) && $request->input($param) !== null && $request->input($param) !== '') {
                return false;
            }
        }

        // has_guide=true is the default homepage state — allow it
        $hasGuide = $request->input('has_guide');
        if ($hasGuide !== null && $hasGuide !== '' && $hasGuide !== 'true') {
            return false;
        }

        // Check sort - cache default sort (critic_score desc, matching frontend default)
        $sortBy = $request->input('sort_by', 'critic_score');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy !== 'critic_score' || $sortOrder !== 'desc') {
            return false;
        }

        return true;
    }

    /**
     * Get a single game by ID or slug
     */
    public function show($idOrSlug)
    {
        $query = Game::with(['genres', 'tags', 'platforms']);

        // Only search by ID if the input is purely numeric
        if (ctype_digit((string) $idOrSlug)) {
            $query->where('id', $idOrSlug);
        } else {
            $query->where('slug', $idOrSlug);
        }

        return response()->json($query->firstOrFail());
    }

    /**
     * Get recommended games based on collaborative filtering
     * "Players who have this game also have..."
     */
    public function recommendations($idOrSlug)
    {
        // Only search by ID if the input is purely numeric
        if (ctype_digit((string) $idOrSlug)) {
            $game = Game::where('id', $idOrSlug)->firstOrFail();
        } else {
            $game = Game::where('slug', $idOrSlug)->firstOrFail();
        }

        $recommendations = $game->getRecommendations(6, 2);

        return response()->json([
            'game_id' => $game->id,
            'game_title' => $game->title,
            'recommendations' => $recommendations,
            'total_users' => $game->users()->count(),
        ]);
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

    /**
     * Get guide vote statistics for a game
     */
    public function guideVotes($idOrSlug)
    {
        // Find game
        if (ctype_digit((string) $idOrSlug)) {
            $game = Game::where('id', $idOrSlug)->firstOrFail();
        } else {
            $game = Game::where('slug', $idOrSlug)->firstOrFail();
        }

        // Count available guides
        $availableGuides = [];
        if ($game->psnprofiles_url) $availableGuides[] = 'psnprofiles';
        if ($game->playstationtrophies_url) $availableGuides[] = 'playstationtrophies';
        if ($game->powerpyx_url) $availableGuides[] = 'powerpyx';

        // Only show voting if 2+ guides
        if (count($availableGuides) < 2) {
            return response()->json([
                'voting_enabled' => false,
                'available_guides' => $availableGuides,
            ]);
        }

        // Get vote counts
        $votes = \DB::table('user_game')
            ->where('game_id', $game->id)
            ->whereNotNull('preferred_guide')
            ->select('preferred_guide', \DB::raw('COUNT(*) as count'))
            ->groupBy('preferred_guide')
            ->pluck('count', 'preferred_guide')
            ->toArray();

        $totalVotes = array_sum($votes);

        // Calculate percentages
        $results = [];
        foreach ($availableGuides as $guide) {
            $count = $votes[$guide] ?? 0;
            $results[$guide] = [
                'count' => $count,
                'percentage' => $totalVotes > 0 ? round(($count / $totalVotes) * 100) : 0,
            ];
        }

        // Find winner
        $winner = null;
        $winnerCount = 0;
        foreach ($results as $guide => $data) {
            if ($data['count'] > $winnerCount) {
                $winnerCount = $data['count'];
                $winner = $guide;
            }
        }

        return response()->json([
            'voting_enabled' => true,
            'available_guides' => $availableGuides,
            'total_votes' => $totalVotes,
            'results' => $results,
            'winner' => $totalVotes >= 3 ? $winner : null, // Only show winner with 3+ votes
            'winner_percentage' => $totalVotes >= 3 && $winner ? $results[$winner]['percentage'] : null,
        ]);
    }
}
