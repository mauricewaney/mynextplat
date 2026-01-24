<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PSNService
{
    // PSN API endpoints
    private const AUTH_URL = 'https://ca.account.sony.com/api/authz/v3/oauth/authorize';
    private const TOKEN_URL = 'https://ca.account.sony.com/api/authz/v3/oauth/token';
    private const USER_TITLES_URL = 'https://m.np.playstation.com/api/trophy/v1/users/{accountId}/trophyTitles';
    private const GAME_LIST_URL = 'https://m.np.playstation.com/api/gamelist/v2/users/{accountId}/titles';
    private const PURCHASED_URL = 'https://m.np.playstation.com/api/entitlement/v1/users/{accountId}/entitlements';
    private const SEARCH_URL = 'https://m.np.playstation.com/api/search/v1/universalSearch';
    private const PROFILE_URL = 'https://m.np.playstation.com/api/userProfile/v1/internal/users/{accountId}/profiles';

    // PlayStation credentials from psnawp (actively maintained Python library)
    private const CLIENT_ID = '09515159-7237-4370-9b40-3806e67c0891';
    private const CLIENT_SECRET = 'ucPjka5tntB2KqsP';
    private const REDIRECT_URI = 'com.scee.psxandroid.scecompcall://redirect';
    private const SCOPE = 'psn:mobile.v2.core psn:clientapp';

    // Default headers required by PSN API
    private const DEFAULT_HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Country' => 'US',
    ];

    private ?string $accessToken = null;
    private ?string $authenticatedAccountId = null;

    /**
     * Exchange NPSSO token for access token
     */
    public function authenticate(string $npsso): bool
    {
        try {
            // Step 1: Get authorization code using NPSSO
            $authCode = $this->getAuthCode($npsso);
            if (!$authCode) {
                return false;
            }

            // Step 2: Exchange auth code for access token
            $this->accessToken = $this->getAccessToken($authCode);
            return $this->accessToken !== null;

        } catch (\Exception $e) {
            \Log::error('PSN Auth failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticate using stored NPSSO from config
     */
    public function authenticateFromConfig(): bool
    {
        $npsso = config('services.psn.npsso');
        if (!$npsso) {
            return false;
        }

        // Check cache for valid token
        $cached = Cache::get('psn_auth_data');
        if ($cached && isset($cached['access_token'])) {
            $this->accessToken = $cached['access_token'];
            $this->authenticatedAccountId = $cached['account_id'] ?? null;
            return true;
        }

        if ($this->authenticate($npsso)) {
            // Cache for 55 minutes (tokens last 60 min)
            Cache::put('psn_auth_data', [
                'access_token' => $this->accessToken,
                'account_id' => $this->authenticatedAccountId,
            ], now()->addMinutes(55));
            return true;
        }

        return false;
    }

    /**
     * Get auth code from NPSSO
     */
    private function getAuthCode(string $npsso): ?string
    {
        $params = [
            'access_type' => 'offline',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope' => self::SCOPE,
        ];

        \Log::info('PSN Auth: Requesting auth code', ['npsso_length' => strlen($npsso)]);

        $response = Http::withHeaders([
            'Cookie' => 'npsso=' . $npsso,
        ])->withOptions([
            'allow_redirects' => false,
        ])->get(self::AUTH_URL, $params);

        \Log::info('PSN Auth: Response status', [
            'status' => $response->status(),
            'headers' => $response->headers(),
        ]);

        // The auth code is in the redirect location header
        $location = $response->header('Location');
        \Log::info('PSN Auth: Location header', ['location' => $location]);

        if ($location && preg_match('/code=([^&]+)/', $location, $matches)) {
            \Log::info('PSN Auth: Got auth code');
            return $matches[1];
        }

        \Log::error('PSN Auth: Failed to get auth code', ['body' => $response->body()]);
        return null;
    }

    /**
     * Exchange auth code for access token
     */
    private function getAccessToken(string $authCode): ?string
    {
        \Log::info('PSN Auth: Exchanging code for token', ['code_length' => strlen($authCode)]);

        // Use Basic auth header like psnawp does
        $credentials = base64_encode(self::CLIENT_ID . ':' . self::CLIENT_SECRET);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post(self::TOKEN_URL, [
            'code' => $authCode,
            'redirect_uri' => self::REDIRECT_URI,
            'grant_type' => 'authorization_code',
            'token_format' => 'jwt',
        ]);

        \Log::info('PSN Token: Response', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'] ?? null;

            // Extract account ID from JWT token for self-lookup detection
            if ($token) {
                $this->authenticatedAccountId = $this->extractAccountIdFromToken($token);
            }

            return $token;
        }

        \Log::error('PSN Token exchange failed: ' . $response->body());
        return null;
    }

    /**
     * Extract account ID from JWT access token
     */
    private function extractAccountIdFromToken(string $token): ?string
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return $payload['account_id'] ?? $payload['sub'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Search for a user by username and get their account ID
     */
    public function searchUser(string $username): ?array
    {
        if (!$this->accessToken) {
            \Log::error('PSN Search: No access token');
            return null;
        }

        \Log::info('PSN Search: Searching for user', ['username' => $username]);

        $response = Http::withHeaders(array_merge(self::DEFAULT_HEADERS, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]))->post(self::SEARCH_URL, [
            'searchTerm' => $username,
            'domainRequests' => [
                [
                    'domain' => 'SocialAllAccounts',
                ],
            ],
        ]);

        \Log::info('PSN Search: Response', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $results = $data['domainResponses'][0]['results'] ?? [];

            // Find exact match
            foreach ($results as $result) {
                if (strtolower($result['socialMetadata']['onlineId'] ?? '') === strtolower($username)) {
                    return [
                        'accountId' => $result['socialMetadata']['accountId'],
                        'onlineId' => $result['socialMetadata']['onlineId'],
                        'avatarUrl' => $result['socialMetadata']['avatarUrl'] ?? null,
                    ];
                }
            }

            // Return first result if no exact match
            if (!empty($results)) {
                $first = $results[0];
                return [
                    'accountId' => $first['socialMetadata']['accountId'],
                    'onlineId' => $first['socialMetadata']['onlineId'],
                    'avatarUrl' => $first['socialMetadata']['avatarUrl'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Get user's trophy titles (games with trophies)
     * Returns array with 'data' or 'error' key
     */
    public function getUserTitles(string $accountId, int $limit = 800, int $offset = 0): array
    {
        if (!$this->accessToken) {
            return ['error' => 'not_authenticated', 'message' => 'Not authenticated'];
        }

        $url = str_replace('{accountId}', $accountId, self::USER_TITLES_URL);

        $response = Http::withHeaders(array_merge(self::DEFAULT_HEADERS, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]))->get($url, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if ($response->successful()) {
            return ['data' => $response->json()];
        }

        $status = $response->status();
        $body = $response->json() ?? [];
        $errorCode = $body['error']['code'] ?? null;

        \Log::error('Failed to get user titles', [
            'accountId' => $accountId,
            'status' => $status,
            'body' => $response->body(),
        ]);

        // Parse specific error codes from Sony
        if ($status === 403 || $errorCode === 2240525) {
            return ['error' => 'private_trophies', 'message' => 'Trophy data is private. Enable "Trophies" visibility in PSN privacy settings.'];
        }

        if ($status === 404) {
            return ['error' => 'not_found', 'message' => 'User not found'];
        }

        return ['error' => 'unknown', 'message' => 'Failed to fetch trophy data'];
    }

    /**
     * Get games for a username (convenience method)
     * Returns array with 'user', 'titles' on success, or 'error', 'message' on failure
     */
    public function getGamesForUser(string $username): array
    {
        // Search for user
        $user = $this->searchUser($username);
        if (!$user) {
            return ['error' => 'user_not_found', 'message' => 'User not found. Check the username and try again.'];
        }

        // Determine account ID to use - "me" for self-lookup bypasses privacy settings
        $accountId = $user['accountId'];
        if ($this->authenticatedAccountId && $this->authenticatedAccountId === $user['accountId']) {
            \Log::info('PSN: Self-lookup detected, using "me" as account ID');
            $accountId = 'me';
        }

        // Get their titles
        $result = $this->getUserTitles($accountId);
        if (isset($result['error'])) {
            // If using "me" still failed, it might be a different issue
            if ($accountId === 'me' && $result['error'] === 'private_trophies') {
                return ['error' => 'private_trophies', 'message' => 'Could not access trophy data. Try regenerating your NPSSO token.'];
            }
            return $result; // Pass through the specific error
        }

        $titles = $result['data'];
        return [
            'user' => $user,
            'titles' => $titles['trophyTitles'] ?? [],
            'totalItemCount' => $titles['totalItemCount'] ?? 0,
        ];
    }

    /**
     * Get the authenticated user's own games (uses "me" endpoint)
     * This bypasses privacy settings since it's your own account
     */
    public function getMyGames(): array
    {
        if (!$this->accessToken) {
            return ['error' => 'not_authenticated', 'message' => 'Not authenticated'];
        }

        // Get titles using "me" - this always works for own account
        $result = $this->getUserTitles('me');
        if (isset($result['error'])) {
            return $result;
        }

        $titles = $result['data'];

        // Get profile info for the authenticated user
        $profile = $this->getMyProfile();

        return [
            'user' => $profile ?? [
                'accountId' => $this->authenticatedAccountId ?? 'me',
                'onlineId' => 'My Account',
                'avatarUrl' => null,
            ],
            'titles' => $titles['trophyTitles'] ?? [],
            'totalItemCount' => $titles['totalItemCount'] ?? 0,
        ];
    }

    /**
     * Get the authenticated user's profile
     */
    public function getMyProfile(): ?array
    {
        if (!$this->accessToken) {
            return null;
        }

        $url = str_replace('{accountId}', 'me', self::PROFILE_URL);

        try {
            $response = Http::withHeaders(array_merge(self::DEFAULT_HEADERS, [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ]))->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'accountId' => $data['accountId'] ?? $this->authenticatedAccountId,
                    'onlineId' => $data['onlineId'] ?? 'Unknown',
                    'avatarUrl' => $data['avatars'][0]['url'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get own profile: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get user's game library (all owned games, not just trophy titles)
     * This includes games the user owns but hasn't played yet
     */
    public function getGameLibrary(string $accountId = 'me', int $limit = 200, int $offset = 0): array
    {
        if (!$this->accessToken) {
            return ['error' => 'not_authenticated', 'message' => 'Not authenticated'];
        }

        $url = str_replace('{accountId}', $accountId, self::GAME_LIST_URL);

        \Log::info('PSN GameList: Fetching library', ['accountId' => $accountId, 'limit' => $limit]);

        $response = Http::withHeaders(array_merge(self::DEFAULT_HEADERS, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]))->get($url, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if ($response->successful()) {
            return ['data' => $response->json()];
        }

        $status = $response->status();
        \Log::error('PSN GameList: Failed', [
            'accountId' => $accountId,
            'status' => $status,
            'body' => $response->body(),
        ]);

        if ($status === 403) {
            return ['error' => 'private_library', 'message' => 'Game library is private'];
        }

        return ['error' => 'unknown', 'message' => 'Failed to fetch game library'];
    }

    /**
     * Get the authenticated user's full game library (owned games)
     * Handles pagination to get all games
     */
    public function getMyGameLibrary(): array
    {
        if (!$this->accessToken) {
            return ['error' => 'not_authenticated', 'message' => 'Not authenticated'];
        }

        $allTitles = [];
        $offset = 0;
        $limit = 200;

        // Fetch all pages
        do {
            $result = $this->getGameLibrary('me', $limit, $offset);
            if (isset($result['error'])) {
                // If we already got some results, return them
                if (!empty($allTitles)) {
                    break;
                }
                return $result;
            }

            $data = $result['data'];
            $titles = $data['titles'] ?? [];
            $allTitles = array_merge($allTitles, $titles);

            $totalCount = $data['totalItemCount'] ?? count($titles);
            $offset += $limit;

            \Log::info('PSN GameList: Fetched page', [
                'fetched' => count($titles),
                'total_so_far' => count($allTitles),
                'total_available' => $totalCount
            ]);

        } while (count($titles) === $limit && $offset < $totalCount);

        $profile = $this->getMyProfile();

        return [
            'user' => $profile ?? [
                'accountId' => $this->authenticatedAccountId ?? 'me',
                'onlineId' => 'My Account',
                'avatarUrl' => null,
            ],
            'titles' => $allTitles,
            'totalItemCount' => count($allTitles),
        ];
    }

    /**
     * Filter to games without platinum earned
     */
    public function getUnplatinumedGames(string $username): array
    {
        $data = $this->getGamesForUser($username);
        if (isset($data['error'])) {
            return $data;
        }

        // Filter to games where platinum exists but not earned
        $unplatinumed = array_filter($data['titles'], function ($title) {
            $defined = $title['definedTrophies']['platinum'] ?? 0;
            $earned = $title['earnedTrophies']['platinum'] ?? 0;
            return $defined > 0 && $earned === 0;
        });

        return [
            'user' => $data['user'],
            'titles' => array_values($unplatinumed),
            'totalCount' => count($unplatinumed),
        ];
    }
}
