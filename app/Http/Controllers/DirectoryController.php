<?php

namespace App\Http\Controllers;

use App\Models\DirectoryPage;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Platform;
use App\Services\DirectorySectionService;
use App\Services\GameFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DirectoryController extends Controller
{
    private const PRESETS = [
        'fast-and-easy' => [
            'title' => 'Fast & Easy Platinum Trophies',
            'filters' => [
                'difficulty_max' => 4,
                'time_max' => 15,
                'has_online_trophies' => 'false',
                'missable_trophies' => 'false',
                'max_playthroughs' => 1,
            ],
            'cta_params' => ['diff_max' => '4', 'time_max' => '15', 'online' => 'false', 'missable' => 'false', 'runs' => '1'],
        ],
        'must-play' => [
            'title' => 'Must Play Trophy Games',
            'filters' => [
                'user_score_min' => 80,
                'critic_score_min' => 80,
                'has_online_trophies' => 'false',
                'missable_trophies' => 'false',
            ],
            'cta_params' => ['uscore_min' => '80', 'cscore_min' => '80', 'online' => 'false', 'missable' => 'false'],
        ],
        'no-stress' => [
            'title' => 'No Stress Platinum Trophies',
            'filters' => [
                'has_online_trophies' => 'false',
                'missable_trophies' => 'false',
            ],
            'cta_params' => ['online' => 'false', 'missable' => 'false'],
        ],
        'easy-platinums' => [
            'title' => 'Easy Platinum Trophies',
            'filters' => [
                'difficulty_max' => 3,
                'has_platinum' => 'true',
            ],
            'cta_params' => ['diff_max' => '3', 'has_platinum' => 'true'],
        ],
        'quick-platinums' => [
            'title' => 'Quick Platinum Trophies',
            'filters' => [
                'time_max' => 10,
                'has_platinum' => 'true',
            ],
            'cta_params' => ['time_max' => '10', 'has_platinum' => 'true'],
        ],
        'offline-only' => [
            'title' => 'Offline Only Platinum Trophies',
            'filters' => [
                'has_online_trophies' => 'false',
            ],
            'cta_params' => ['online' => 'false'],
        ],
        'no-missables' => [
            'title' => 'No Missable Trophies',
            'filters' => [
                'missable_trophies' => 'false',
            ],
            'cta_params' => ['missable' => 'false'],
        ],
        'hidden-gems' => [
            'title' => 'Hidden Gem Trophy Games',
            'filters' => [
                'user_score_min' => 75,
                'critic_score_max' => 75,
            ],
            'cta_params' => ['uscore_min' => '75', 'cscore_max' => '75'],
        ],
        'quality-epics' => [
            'title' => 'Quality Epic Trophy Games',
            'filters' => [
                'critic_score_min' => 80,
                'time_min' => 40,
                'genre_ids' => [4, 3, 14, 5],
            ],
            'cta_params' => ['cscore_min' => '80', 'time_min' => '40', 'genres' => '4,3,14,5'],
        ],
    ];

    private const GENRE_INTROS = [
        'adventure' => 'Explore vast worlds and unravel compelling stories with our curated list of Adventure trophy games. From narrative-driven epics to open-world exploration, these titles offer some of the most rewarding platinum journeys on PlayStation.',
        'arcade' => 'Relive the golden age of gaming with Arcade trophy games. These fast-paced, score-chasing titles deliver quick thrills and challenging platinums that test your reflexes and determination.',
        'card-and-board-game' => 'Discover trophy opportunities in Card & Board Game adaptations. These strategic titles offer a unique change of pace from action-heavy games, with platinums that reward patience and clever play.',
        'fighting' => 'Step into the ring with Fighting game trophies. From mastering combos to conquering arcade modes, these competitive titles offer platinums that prove your skills in one-on-one combat.',
        'hack-and-slash' => 'Unleash devastating combos in Hack and Slash trophy games. These action-packed titles reward aggressive gameplay and stylish combat, with platinums that demand mastery of fluid combat systems.',
        'indie' => 'Explore the creative world of Indie trophy games. These unique, often shorter titles frequently offer some of the most enjoyable and accessible platinums on PlayStation.',
        'moba' => 'Compete in the fast-paced world of MOBA trophy games. These team-based strategy titles offer challenging trophies that test your teamwork and tactical decision-making.',
        'music' => 'Feel the rhythm with Music game trophies. From guitar heroes to beat-matching challenges, these titles offer fun platinums that combine gaming with your love of music.',
        'pinball' => 'Tilt and score your way through Pinball trophy games. These digital recreations of classic tables offer unique platinums for players who enjoy precision and high-score chasing.',
        'platform' => 'Jump, run, and collect your way through Platform trophy games. From classic 2D side-scrollers to modern 3D adventures, these titles deliver satisfying platinums through precise movement and exploration.',
        'puzzle' => 'Challenge your mind with Puzzle trophy games. These brain-teasing titles offer some of the most satisfying platinums on PlayStation, rewarding creative thinking and problem-solving.',
        'quiz' => 'Test your knowledge with Quiz game trophies. These trivia-based titles offer entertaining platinums that reward your general knowledge and quick thinking.',
        'racing' => 'Hit the track with Racing trophy games. From realistic simulators to arcade speedsters, these high-octane titles offer platinums that test your driving skills and dedication.',
        'real-time-strategy' => 'Command your forces in Real Time Strategy trophy games. These tactical titles offer deep platinums that reward strategic thinking and resource management under pressure.',
        'role-playing' => 'Embark on epic quests with Role-Playing trophy games. These story-rich titles offer some of the most immersive platinum journeys, with hundreds of hours of content to explore.',
        'shooter' => 'Lock and load with Shooter trophy games. From first-person action to tactical shooters, these titles offer platinums that test your aim, reflexes, and combat strategy.',
        'simulation' => 'Experience realistic worlds in Simulation trophy games. From life sims to vehicle simulators, these titles offer unique platinums for players who enjoy methodical, detail-oriented gameplay.',
        'sport' => 'Score big with Sport trophy games. From football to basketball and beyond, these titles offer competitive platinums that combine athletic knowledge with gaming skill.',
        'strategy' => 'Plan your path to victory with Strategy trophy games. These thoughtful titles offer platinums that reward careful planning, resource management, and tactical brilliance.',
        'survival' => 'Brave the elements in Survival trophy games. These intense titles challenge you to scavenge, craft, and endure, with platinums that prove your resilience against all odds.',
        'tactical' => 'Execute precise maneuvers in Tactical trophy games. These thoughtful combat titles reward careful positioning and smart decision-making with deeply satisfying platinums.',
        'turn-based-strategy' => 'Outsmart your opponents in Turn-Based Strategy trophy games. These cerebral titles offer platinums that reward patience, forward thinking, and mastery of complex systems.',
        'visual-novel' => 'Immerse yourself in interactive storytelling with Visual Novel trophy games. These narrative-focused titles often offer accessible platinums through branching storylines and multiple endings.',
    ];

    private const PLATFORM_INTROS = [
        'ps5' => 'Browse the latest PlayStation 5 trophy games. With stunning next-gen graphics and lightning-fast load times, PS5 offers the premium trophy hunting experience with both exclusive titles and enhanced cross-gen games.',
        'ps4' => 'Explore the massive PlayStation 4 trophy library. With thousands of titles spanning every genre, PS4 remains the ultimate platform for trophy hunters looking for variety and value.',
        'ps3' => 'Revisit classic PlayStation 3 trophy games. The console that introduced the trophy system still offers hundreds of rewarding platinums, from beloved exclusives to genre-defining multiplatform titles.',
        'ps-vita' => 'Discover PlayStation Vita trophy games. Sony\'s handheld powerhouse features a unique library of portable platinums, from console-quality adventures to Vita-exclusive gems.',
        'ps-vr' => 'Step into virtual reality with PS VR trophy games. These immersive titles offer a completely unique trophy hunting experience that puts you inside the game world.',
    ];

    private const PRESET_INTROS = [
        'fast-and-easy' => 'Looking for a quick platinum? These games can be completed in under 15 hours with a difficulty rating of 4 or below, no online trophies, no missables, and only one playthrough required. Perfect for building your platinum collection fast.',
        'must-play' => 'The best of the best — games that score 80+ from both critics and players, with no online trophies or missables to worry about. These are the titles every trophy hunter should experience.',
        'no-stress' => 'Enjoy trophy hunting without the anxiety. These games have no online trophies and no missable trophies, so you can play at your own pace without worrying about servers shutting down or missing something permanently.',
        'easy-platinums' => 'Perfect for beginners or anyone looking to boost their platinum count. These games have a difficulty rating of 3 or below and a guaranteed platinum trophy waiting for you.',
        'quick-platinums' => 'Short on time? These platinum trophies can be earned in 10 hours or less. Ideal for trophy hunters who want to maximize their platinums per hour.',
        'offline-only' => 'Never worry about server shutdowns or finding online lobbies. Every trophy in these games can be earned completely offline, making them future-proof platinum choices.',
        'no-missables' => 'Play without a guide on your first run. These games have no missable trophies, meaning you can enjoy the experience naturally and clean up anything you missed afterward.',
        'hidden-gems' => 'Overlooked by critics but loved by players. These games have user scores of 75+ but critic scores under 75, making them hidden gems worth discovering for their unique platinum journeys.',
        'quality-epics' => 'For those who love long, critically acclaimed adventures. These RPGs, adventures, and strategy games score 80+ with critics and take 40+ hours to complete — epic platinum journeys for dedicated trophy hunters.',
    ];

    private const GENERIC_INTRO = 'Browse our curated selection of PlayStation trophy games. Find your next platinum with detailed difficulty ratings, time estimates, and trophy guides.';

    private const PRESET_DESCRIPTIONS = [
        'fast-and-easy' => 'Under 15 hours, low difficulty, no online or missable trophies.',
        'must-play' => 'Scored 80+ by critics and players, no online or missables.',
        'no-stress' => 'No online trophies, no missable trophies — play at your own pace.',
        'easy-platinums' => 'Difficulty 3 or below with a guaranteed platinum.',
        'quick-platinums' => 'Earn a platinum in 10 hours or less.',
        'offline-only' => 'Every trophy earnable offline — future-proof platinums.',
        'no-missables' => 'No missable trophies — enjoy without a guide.',
        'hidden-gems' => 'Loved by players, overlooked by critics.',
        'quality-epics' => 'Critically acclaimed 40+ hour RPGs and adventures.',
    ];

    public function browse()
    {
        $version = Cache::get('games:cache_version', 1);
        $cacheKey = "directory:browse:v{$version}";

        $data = Cache::remember($cacheKey, 86400, function () {
            $genres = Genre::withCount('games')->orderBy('name')->get();
            $platforms = Platform::withCount('games')->orderByDesc('games_count')->get();

            $filterService = app(GameFilterService::class);

            $presets = collect(self::PRESETS)->map(function ($preset, $slug) use ($filterService) {
                $baseQuery = Game::query();
                $syntheticRequest = Request::create('/', 'GET', $preset['filters']);
                $filterService->applyFilters($baseQuery, $syntheticRequest);
                $baseQuery->where(fn ($q) => $q->whereNotNull('psnprofiles_url')->orWhereNotNull('playstationtrophies_url')->orWhereNotNull('powerpyx_url'));

                $gameCount = (clone $baseQuery)->count();

                $covers = (clone $baseQuery)
                    ->whereNotNull('cover_url')
                    ->orderByRaw('critic_score IS NULL')
                    ->orderBy('critic_score', 'desc')
                    ->limit(4)
                    ->pluck('cover_url')
                    ->toArray();

                return [
                    'slug' => $slug,
                    'title' => $preset['title'],
                    'description' => self::PRESET_DESCRIPTIONS[$slug] ?? '',
                    'covers' => $covers,
                    'game_count' => $gameCount,
                ];
            })->values();

            return compact('genres', 'platforms', 'presets');
        });

        return view('pages.browse', $data);
    }

    public function genre(string $slug)
    {
        $genre = Genre::where('slug', $slug)->first();

        if (!$genre) {
            abort(404);
        }

        $version = Cache::get('games:cache_version', 1);
        $cacheKey = "directory:genre:v{$version}:{$slug}";

        $data = Cache::remember($cacheKey, 86400, function () use ($genre, $slug) {
            $games = Game::with(['genres:id,name,slug', 'platforms:id,name,slug,short_name'])
                ->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id))
                ->where(fn ($q) => $q->whereNotNull('psnprofiles_url')->orWhereNotNull('playstationtrophies_url')->orWhereNotNull('powerpyx_url'))
                ->orderByRaw('critic_score IS NULL')
                ->orderBy('critic_score', 'desc')
                ->limit(50)
                ->get();

            $title = "{$genre->name} Games Trophy Guides";
            $ctaUrl = '/?' . http_build_query(['genres' => $genre->id]);
            $fallbackIntro = self::GENRE_INTROS[$slug] ?? self::GENERIC_INTRO;

            $editorial = $this->loadEditorialContent('genre', $slug, $games, $fallbackIntro);

            return array_merge([
                'title' => $title,
                'description' => "Browse {$games->count()} {$genre->name} games with trophy guides, difficulty ratings, and platinum info on MyNextPlat.",
                'canonicalUrl' => url("/games/genre/{$slug}"),
                'breadcrumbType' => 'Genre',
                'breadcrumbLabel' => "{$genre->name} Games",
                'games' => $games,
                'ctaUrl' => $ctaUrl,
            ], $editorial);
        });

        return view('seo.directory', $data);
    }

    public function platform(string $slug)
    {
        $platform = Platform::where('slug', $slug)->first();

        if (!$platform) {
            abort(404);
        }

        $version = Cache::get('games:cache_version', 1);
        $cacheKey = "directory:platform:v{$version}:{$slug}";
        $displayName = $platform->short_name ?? $platform->name;

        $data = Cache::remember($cacheKey, 86400, function () use ($platform, $slug, $displayName) {
            $games = Game::with(['genres:id,name,slug', 'platforms:id,name,slug,short_name'])
                ->whereHas('platforms', fn ($q) => $q->where('platforms.id', $platform->id))
                ->where(fn ($q) => $q->whereNotNull('psnprofiles_url')->orWhereNotNull('playstationtrophies_url')->orWhereNotNull('powerpyx_url'))
                ->orderByRaw('critic_score IS NULL')
                ->orderBy('critic_score', 'desc')
                ->limit(50)
                ->get();

            $title = "{$displayName} Games Trophy Guides";
            $ctaUrl = '/?' . http_build_query(['platforms' => $platform->id]);
            $fallbackIntro = self::PLATFORM_INTROS[$slug] ?? self::GENERIC_INTRO;

            $editorial = $this->loadEditorialContent('platform', $slug, $games, $fallbackIntro);

            return array_merge([
                'title' => $title,
                'description' => "Browse {$games->count()} {$displayName} games with trophy guides, difficulty ratings, and platinum info on MyNextPlat.",
                'canonicalUrl' => url("/games/platform/{$slug}"),
                'breadcrumbType' => 'Platform',
                'breadcrumbLabel' => "{$displayName} Games",
                'games' => $games,
                'ctaUrl' => $ctaUrl,
            ], $editorial);
        });

        return view('seo.directory', $data);
    }

    public function preset(string $slug)
    {
        if (!isset(self::PRESETS[$slug])) {
            abort(404);
        }

        $version = Cache::get('games:cache_version', 1);
        $cacheKey = "directory:preset:v{$version}:{$slug}";

        $data = Cache::remember($cacheKey, 86400, function () use ($slug) {
            $preset = self::PRESETS[$slug];

            $syntheticRequest = Request::create('/', 'GET', $preset['filters']);
            $filterService = app(GameFilterService::class);

            $query = Game::with(['genres:id,name,slug', 'platforms:id,name,slug,short_name']);
            $filterService->applyFilters($query, $syntheticRequest);

            $games = $query
                ->where(fn ($q) => $q->whereNotNull('psnprofiles_url')->orWhereNotNull('playstationtrophies_url')->orWhereNotNull('powerpyx_url'))
                ->orderByRaw('critic_score IS NULL')
                ->orderBy('critic_score', 'desc')
                ->limit(50)
                ->get();

            $title = $preset['title'];
            $ctaUrl = '/?' . http_build_query($preset['cta_params']);
            $fallbackIntro = self::PRESET_INTROS[$slug] ?? self::GENERIC_INTRO;

            $editorial = $this->loadEditorialContent('preset', $slug, $games, $fallbackIntro);

            return array_merge([
                'title' => $title,
                'description' => "Discover {$games->count()} {$title} — find your next platinum with difficulty ratings, time estimates, and trophy guides on MyNextPlat.",
                'canonicalUrl' => url("/guides/{$slug}"),
                'breadcrumbType' => 'Guides',
                'breadcrumbLabel' => $title,
                'games' => $games,
                'ctaUrl' => $ctaUrl,
            ], $editorial);
        });

        return view('seo.directory', $data);
    }

    /**
     * Load editorial content from DB (or fall back to PHP constants).
     * Returns: intro, featuredGames, listGames, sections, stats, relatedPages
     */
    private function loadEditorialContent(string $type, string $slug, Collection $games, string $fallbackIntro): array
    {
        $page = DirectoryPage::findByKey($type, $slug);

        // Intro: DB override or PHP constant fallback
        $intro = ($page && $page->intro_text) ? $page->intro_text : $fallbackIntro;

        // Featured games: DB picks or top-5 fallback
        $featuredGameIds = ($page && !empty($page->featured_game_ids)) ? $page->featured_game_ids : null;

        if ($featuredGameIds) {
            // Load featured games in specified order
            $featuredById = Game::with(['genres:id,name,slug', 'platforms:id,name,slug,short_name'])
                ->whereIn('id', $featuredGameIds)
                ->get()
                ->keyBy('id');

            $featuredGames = collect();
            foreach ($featuredGameIds as $id) {
                if ($featuredById->has($id)) {
                    $featuredGames->push($featuredById->get($id));
                }
            }

            // Exclude featured from "All Games" list, sort by release date (newest first)
            $listGames = $games->filter(fn ($g) => !in_array($g->id, $featuredGameIds))
                ->sortByDesc('release_date')->values();
        } else {
            $featuredGames = $games->take(3);
            $listGames = $games->slice(3)->sortByDesc('release_date')->values();
        }

        // Resolve curated sections
        $sections = collect();
        if ($page) {
            $sectionService = app(DirectorySectionService::class);
            $page->load('sections');

            foreach ($page->sections as $section) {
                $sections->push([
                    'title' => $section->title,
                    'games' => $sectionService->resolveSection($section),
                ]);
            }
        }

        // Compute aggregate stats
        $sectionService = app(DirectorySectionService::class);
        $stats = $sectionService->computeStats($games);

        // Related pages
        $relatedPages = ($page && !empty($page->related_pages)) ? $page->related_pages : [];

        $listGamesByPopularity = $listGames->sortByDesc('user_score')->values();
        $listGamesByDate = $listGames->sortByDesc('release_date')->values();

        return [
            'intro' => $intro,
            'featuredGames' => $featuredGames,
            'listGames' => $listGames,
            'listGamesByDate' => $listGamesByDate,
            'listGamesByPopularity' => $listGamesByPopularity,
            'sections' => $sections,
            'stats' => $stats,
            'relatedPages' => $relatedPages,
        ];
    }
}
