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
        $cached = Cache::get('psn_access_token');
        if ($cached) {
            $this->accessToken = $cached;
            return true;
        }

        if ($this->authenticate($npsso)) {
            // Cache for 55 minutes (tokens last 60 min)
            Cache::put('psn_access_token', $this->accessToken, now()->addMinutes(55));
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
            return $data['access_token'] ?? null;
        }

        \Log::error('PSN Token exchange failed: ' . $response->body());
        return null;
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
     */
    public function getUserTitles(string $accountId, int $limit = 800, int $offset = 0): ?array
    {
        if (!$this->accessToken) {
            return null;
        }

        $url = str_replace('{accountId}', $accountId, self::USER_TITLES_URL);

        $response = Http::withHeaders(array_merge(self::DEFAULT_HEADERS, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]))->get($url, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        \Log::error('Failed to get user titles: ' . $response->body());
        return null;
    }

    /**
     * Get games for a username (convenience method)
     */
    public function getGamesForUser(string $username): ?array
    {
        // Search for user
        $user = $this->searchUser($username);
        if (!$user) {
            return null;
        }

        // Get their titles
        $titles = $this->getUserTitles($user['accountId']);
        if (!$titles) {
            return null;
        }

        return [
            'user' => $user,
            'titles' => $titles['trophyTitles'] ?? [],
            'totalItemCount' => $titles['totalItemCount'] ?? 0,
        ];
    }

    /**
     * Filter to games without platinum earned
     */
    public function getUnplatinumedGames(string $username): ?array
    {
        $data = $this->getGamesForUser($username);
        if (!$data) {
            return null;
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
