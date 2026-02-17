<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class IGDBService
{
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;

    // IGDB Platform IDs for PlayStation
    protected const PLATFORM_PS5 = 167;
    protected const PLATFORM_PS4 = 48;
    protected const PLATFORM_PS3 = 9;
    protected const PLATFORM_VITA = 46;
    protected const PLATFORM_PSVR = 165;

    protected const PLAYSTATION_PLATFORMS = [
        self::PLATFORM_PS5,
        self::PLATFORM_PS4,
        self::PLATFORM_PS3,
        self::PLATFORM_VITA,
        self::PLATFORM_PSVR,
    ];

    public function __construct()
    {
        $this->clientId = config('services.igdb.client_id');
        $this->clientSecret = config('services.igdb.client_secret');
    }

    /**
     * Escape a string for use in IGDB Apicalypse queries
     */
    protected function escapeForIGDB(string $value): string
    {
        // Remove or replace problematic characters for IGDB queries
        // IGDB uses double quotes for strings, so we need to escape them
        $value = str_replace('\\', '\\\\', $value); // Escape backslashes first
        $value = str_replace('"', '\\"', $value);   // Escape double quotes
        return $value;
    }

    /**
     * Get access token from Twitch (IGDB uses Twitch auth)
     */
    protected function getAccessToken(): ?string
    {
        // Cache the token for 50 days (tokens last ~60 days)
        return Cache::remember('igdb_access_token', 60 * 60 * 24 * 50, function () {
            $response = Http::post('https://id.twitch.tv/oauth2/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            return null;
        });
    }

    /**
     * Search for a game and get its cover image (filtered to PlayStation platforms)
     */
    public function searchAndGetCover(string $gameTitle): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Failed to get IGDB access token. Check your Twitch credentials.');
        }

        // Build platform filter for PlayStation consoles
        $platformFilter = implode(',', self::PLAYSTATION_PLATFORMS);
        $escapedTitle = $this->escapeForIGDB($gameTitle);

        // Search with PlayStation platform filter first
        $query = 'search "' . $escapedTitle . '"; '
            . 'fields name,cover.url,screenshots.url,platforms; '
            . 'where platforms = (' . $platformFilter . '); '
            . 'limit 5;';

        $game = $this->executeSearch($accessToken, $query, $gameTitle);

        // If no PlayStation game found, try without platform filter as fallback
        if (!$game) {
            $fallbackQuery = 'search "' . $escapedTitle . '"; '
                . 'fields name,cover.url,screenshots.url,platforms; '
                . 'limit 5;';
            $game = $this->executeSearch($accessToken, $fallbackQuery, $gameTitle);
        }

        if (!$game) {
            return [
                'cover_url' => null,
                'banner_url' => null,
                'game_name' => null,
            ];
        }

        return $this->extractImageUrls($game);
    }

    /**
     * Execute IGDB search and find best matching game
     */
    protected function executeSearch(string $accessToken, string $query, string $searchTitle): ?array
    {
        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $accessToken,
        ])->withBody($query, 'text/plain')
          ->post('https://api.igdb.com/v4/games');

        if (!$response->successful()) {
            throw new \Exception('IGDB API error: ' . $response->body());
        }

        $games = $response->json();

        if (empty($games)) {
            return null;
        }

        // Find best match: prefer exact title match, then closest match with cover
        $searchTitleLower = strtolower($searchTitle);

        // First pass: look for exact match with cover
        foreach ($games as $game) {
            if (isset($game['cover']['url']) && strtolower($game['name']) === $searchTitleLower) {
                return $game;
            }
        }

        // Second pass: look for title that starts with search term and has cover
        foreach ($games as $game) {
            if (isset($game['cover']['url']) && str_starts_with(strtolower($game['name']), $searchTitleLower)) {
                return $game;
            }
        }

        // Third pass: any game with a cover
        foreach ($games as $game) {
            if (isset($game['cover']['url'])) {
                return $game;
            }
        }

        // Last resort: first result
        return $games[0];
    }

    /**
     * Extract and format image URLs from game data
     */
    protected function extractImageUrls(array $game): array
    {
        $coverUrl = null;
        $bannerUrl = null;

        // Get cover URL (convert to high-res)
        if (isset($game['cover']['url'])) {
            // IGDB returns URLs like //images.igdb.com/igdb/image/upload/t_thumb/co1234.jpg
            // Convert to high-res cover: t_cover_big (264x374)
            $coverUrl = 'https:' . str_replace('t_thumb', 't_cover_big', $game['cover']['url']);
        }

        // Get first screenshot as banner
        if (isset($game['screenshots'][0]['url'])) {
            // Convert to high-res screenshot: t_screenshot_big (889x500)
            $bannerUrl = 'https:' . str_replace('t_thumb', 't_screenshot_big', $game['screenshots'][0]['url']);
        }

        return [
            'cover_url' => $coverUrl,
            'banner_url' => $bannerUrl,
            'game_name' => $game['name'] ?? null,
        ];
    }

    /**
     * Get PlayStation platform IDs (public accessor)
     */
    public function getPlayStationPlatforms(): array
    {
        return self::PLAYSTATION_PLATFORMS;
    }

    /**
     * Map IGDB platform ID to local platform slug
     */
    public function mapPlatformIdToSlug(int $igdbPlatformId): ?string
    {
        return match ($igdbPlatformId) {
            self::PLATFORM_PS5 => 'ps5',
            self::PLATFORM_PS4 => 'ps4',
            self::PLATFORM_PS3 => 'ps3',
            self::PLATFORM_VITA => 'ps-vita',
            self::PLATFORM_PSVR, 165 => 'ps-vr',
            default => null,
        };
    }

    /**
     * Get full platform data for creating platforms
     */
    public function getPlatformData(int $igdbPlatformId): ?array
    {
        return match ($igdbPlatformId) {
            self::PLATFORM_PS5 => ['name' => 'PlayStation 5', 'slug' => 'ps5', 'short_name' => 'PS5'],
            self::PLATFORM_PS4 => ['name' => 'PlayStation 4', 'slug' => 'ps4', 'short_name' => 'PS4'],
            self::PLATFORM_PS3 => ['name' => 'PlayStation 3', 'slug' => 'ps3', 'short_name' => 'PS3'],
            self::PLATFORM_VITA => ['name' => 'PlayStation Vita', 'slug' => 'ps-vita', 'short_name' => 'Vita'],
            self::PLATFORM_PSVR, 165 => ['name' => 'PlayStation VR', 'slug' => 'ps-vr', 'short_name' => 'PSVR'],
            default => null,
        };
    }

    // Minimum date for trophy-era games (PS3 trophies launched July 2008)
    // Unix timestamp for 2008-01-01
    protected const TROPHY_ERA_START = 1182960000;

    /**
     * Fetch PlayStation games from IGDB in bulk using cursor-based pagination
     *
     * @param int $limit Number of games to fetch
     * @param int $offset Offset for pagination within current cursor window
     * @param int|null $sinceTimestamp Continue from this Unix timestamp (cursor-based)
     * @param array $excludeIds IGDB IDs to exclude (only for games at cursor boundary)
     */
    public function fetchPlayStationGames(int $limit = 100, int $offset = 0, ?int $sinceTimestamp = null, array $excludeIds = []): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Failed to get IGDB access token.');
        }

        $platformFilter = implode(',', self::PLAYSTATION_PLATFORMS);

        // Base fields
        $fields = 'fields name,slug,summary,cover.url,screenshots.url,platforms,genres.name,'
            . 'involved_companies.company.name,involved_companies.developer,involved_companies.publisher,'
            . 'first_release_date,aggregated_rating,aggregated_rating_count,rating,rating_count,'
            . 'release_dates.date,release_dates.platform,release_dates.region; ';

        // Build where clause
        $whereConditions = [
            'platforms = (' . $platformFilter . ')',
            'cover != null',
            'first_release_date >= ' . self::TROPHY_ERA_START,
        ];

        // Use IGDB's created_at to find recently added entries (incremental sync)
        if ($sinceTimestamp) {
            $whereConditions[] = 'created_at >= ' . $sinceTimestamp;
        }

        // Only exclude IDs if we have a reasonable number (for boundary games)
        if (!empty($excludeIds) && count($excludeIds) <= 500) {
            $excludeIdsList = implode(',', $excludeIds);
            $whereConditions[] = 'id != (' . $excludeIdsList . ')';
        }

        $query = $fields
            . 'where ' . implode(' & ', $whereConditions) . '; '
            . 'sort created_at asc; '
            . 'limit ' . $limit . '; '
            . 'offset ' . $offset . ';';

        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $accessToken,
        ])->withBody($query, 'text/plain')
          ->post('https://api.igdb.com/v4/games');

        if (!$response->successful()) {
            throw new \Exception('IGDB API error: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Parse IGDB game data into our database format
     */
    public function parseGameData(array $igdbGame): array
    {
        $coverUrl = null;
        $bannerUrl = null;
        $developer = null;
        $publisher = null;

        // Extract cover URL
        if (isset($igdbGame['cover']['url'])) {
            $coverUrl = 'https:' . str_replace('t_thumb', 't_cover_big', $igdbGame['cover']['url']);
        }

        // Extract screenshot as banner
        if (isset($igdbGame['screenshots'][0]['url'])) {
            $bannerUrl = 'https:' . str_replace('t_thumb', 't_screenshot_big', $igdbGame['screenshots'][0]['url']);
        }

        // Extract developer and publisher from involved companies
        if (isset($igdbGame['involved_companies'])) {
            foreach ($igdbGame['involved_companies'] as $company) {
                if (isset($company['developer']) && $company['developer'] && !$developer) {
                    $developer = $company['company']['name'] ?? null;
                }
                if (isset($company['publisher']) && $company['publisher'] && !$publisher) {
                    $publisher = $company['company']['name'] ?? null;
                }
            }
        }

        // Extract release date (prefer first_release_date, fallback to release_dates for PlayStation)
        $releaseDate = null;
        if (isset($igdbGame['first_release_date'])) {
            $releaseDate = date('Y-m-d', $igdbGame['first_release_date']);
        } elseif (isset($igdbGame['release_dates'])) {
            // Find PlayStation release date
            foreach ($igdbGame['release_dates'] as $rd) {
                if (isset($rd['platform']) && in_array($rd['platform'], self::PLAYSTATION_PLATFORMS) && isset($rd['date'])) {
                    $releaseDate = date('Y-m-d', $rd['date']);
                    break;
                }
            }
        }

        // Extract platforms (filter to PlayStation only) with full data for creation
        $platformsData = [];
        if (isset($igdbGame['platforms'])) {
            foreach ($igdbGame['platforms'] as $platformId) {
                $platformData = $this->getPlatformData($platformId);
                if ($platformData) {
                    $platformsData[] = $platformData;
                }
            }
        }

        // Extract genres
        $genreNames = [];
        if (isset($igdbGame['genres'])) {
            foreach ($igdbGame['genres'] as $genre) {
                $genreNames[] = $genre['name'];
            }
        }

        return [
            'igdb_id' => $igdbGame['id'] ?? null,
            'title' => $igdbGame['name'],
            'slug' => $igdbGame['slug'] ?? \Illuminate\Support\Str::slug($igdbGame['name']),
            'developer' => $developer,
            'publisher' => $publisher,
            'release_date' => $releaseDate,
            'cover_url' => $coverUrl,
            'banner_url' => $bannerUrl,
            'critic_score' => isset($igdbGame['aggregated_rating']) ? (int) round($igdbGame['aggregated_rating']) : null,
            'critic_score_count' => $igdbGame['aggregated_rating_count'] ?? null,
            'user_score' => isset($igdbGame['rating']) ? (int) round($igdbGame['rating']) : null,
            'user_score_count' => $igdbGame['rating_count'] ?? null,
            'platforms_data' => $platformsData,
            'genre_names' => $genreNames,
        ];
    }

    /**
     * Search IGDB for games matching a query (returns multiple results for user selection)
     */
    public function searchGames(string $query, int $limit = 10): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Failed to get IGDB access token.');
        }

        $platformFilter = implode(',', self::PLAYSTATION_PLATFORMS);
        $escapedQuery = $this->escapeForIGDB($query);

        $fields = 'fields name,slug,cover.url,screenshots.url,platforms,genres.name,'
            . 'involved_companies.company.name,involved_companies.developer,involved_companies.publisher,'
            . 'first_release_date,aggregated_rating,aggregated_rating_count,rating,rating_count; ';

        $headers = [
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        // 1. Fuzzy search
        $searchQuery = 'search "' . $escapedQuery . '"; '
            . $fields
            . 'where platforms = (' . $platformFilter . '); '
            . 'limit ' . $limit . ';';

        $response = Http::withHeaders($headers)
            ->withBody($searchQuery, 'text/plain')
            ->post('https://api.igdb.com/v4/games');

        if (!$response->successful()) {
            throw new \Exception('IGDB API error: ' . $response->body());
        }

        $games = $response->json() ?? [];

        // 2. If fuzzy search didn't fill results, also try exact name match
        if (count($games) < $limit) {
            $seenIds = array_column($games, 'id');

            $nameQuery = $fields
                . 'where name ~ *"' . $escapedQuery . '"* & platforms = (' . $platformFilter . '); '
                . 'limit ' . ($limit - count($games)) . ';';

            $nameResponse = Http::withHeaders($headers)
                ->withBody($nameQuery, 'text/plain')
                ->post('https://api.igdb.com/v4/games');

            if ($nameResponse->successful()) {
                foreach ($nameResponse->json() ?? [] as $game) {
                    if (!in_array($game['id'], $seenIds)) {
                        $games[] = $game;
                        $seenIds[] = $game['id'];
                    }
                }
            }
        }

        // Parse each game into our format
        return array_map(function ($game) {
            $parsed = $this->parseGameData($game);
            $parsed['igdb_id'] = $game['id'];
            return $parsed;
        }, $games);
    }

    /**
     * Test the IGDB connection
     */
    public function testConnection(): array
    {
        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token. Check your IGDB_CLIENT_ID and IGDB_CLIENT_SECRET in .env',
                ];
            }

            // Try a simple search
            $response = Http::withHeaders([
                'Client-ID' => $this->clientId,
                'Authorization' => 'Bearer ' . $accessToken,
            ])->withBody(
                'fields name; limit 1;',
                'text/plain'
            )->post('https://api.igdb.com/v4/games');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'IGDB connection successful!',
                ];
            }

            return [
                'success' => false,
                'message' => 'IGDB API error: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}
