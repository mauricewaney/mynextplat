@php
    $appName = config('app.name', 'MyNextPlat');
    $title = "{$game->title} Trophy Guide | {$appName}";
    $image = $game->cover_url ?? $game->banner_url;
    $canonicalUrl = url("/game/{$game->slug}");

    // Build description mirroring GameDetail.vue buildDescription()
    $descParts = ["Trophy guide for {$game->title}."];

    if ($game->difficulty) {
        $descParts[] = "Difficulty: {$game->difficulty}/10.";
    }
    if ($game->time_min) {
        $time = ($game->time_max && $game->time_max !== $game->time_min)
            ? "{$game->time_min}-{$game->time_max}"
            : $game->time_min;
        $descParts[] = "Time: {$time} hours.";
    }
    if ($game->playthroughs_required) {
        $playthroughLabel = $game->playthroughs_required > 1 ? 'playthroughs' : 'playthrough';
        $descParts[] = "{$game->playthroughs_required} {$playthroughLabel} required.";
    }

    $guideUrls = array_filter([$game->psnprofiles_url, $game->playstationtrophies_url, $game->powerpyx_url]);
    $guideCount = count($guideUrls);
    if ($guideCount > 0) {
        $guideLabel = $guideCount > 1 ? 'guides' : 'guide';
        $descParts[] = "{$guideCount} {$guideLabel} available.";
    }

    $description = implode(' ', $descParts);

    // User score display logic mirroring GameDetail.vue
    $minUserRatings = 3;
    $displayUserScore = null;
    if ($game->user_score) {
        if ($game->user_score_count !== null && $game->user_score_count < $minUserRatings) {
            $displayUserScore = 'N/A';
        } else {
            $displayUserScore = $game->user_score;
        }
    }

    // JSON-LD VideoGame schema
    $videoGameSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoGame',
        'name' => $game->title,
        'description' => $description,
        'image' => $image,
        'gamePlatform' => $game->platforms->pluck('name')->toArray(),
        'genre' => $game->genres->pluck('name')->toArray(),
    ];

    if ($game->publisher) {
        $videoGameSchema['publisher'] = $game->publisher;
    }
    if ($game->developer) {
        $videoGameSchema['developer'] = ['@type' => 'Organization', 'name' => $game->developer];
    }
    if ($displayUserScore && $displayUserScore !== 'N/A') {
        $videoGameSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $displayUserScore,
            'bestRating' => 100,
            'worstRating' => 0,
            'ratingCount' => $game->user_score_count,
        ];
    }

    // JSON-LD BreadcrumbList schema
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $game->title,
                'item' => $canonicalUrl,
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($image)
        <meta property="og:image" content="{{ $image }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if($image)
        <meta name="twitter:image" content="{{ $image }}">
    @endif

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">{!! json_encode($videoGameSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; color: #e0e0e0; background: #1a1a2e; }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        h2 { font-size: 1.1rem; color: #a0a0b0; margin-top: 1.5rem; border-bottom: 1px solid #333; padding-bottom: 0.25rem; }
        .subtitle { color: #888; margin-bottom: 1rem; }
        .cover { max-width: 300px; border-radius: 8px; margin: 1rem 0; }
        .stats { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1rem 0; }
        .stat { background: #222244; padding: 0.5rem 1rem; border-radius: 6px; }
        .stat-label { font-size: 0.8rem; color: #888; }
        .stat-value { font-size: 1.1rem; font-weight: bold; }
        .platforms { display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 0.5rem 0; }
        .platform, .genre { background: #333355; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.85rem; }
        .guides a { display: block; color: #6ea8fe; margin: 0.25rem 0; }
        .genres { display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 0.5rem 0; }
    </style>
</head>
<body>
    <nav><a href="/" style="color:#6ea8fe;text-decoration:none;">{{ $appName }}</a> &rsaquo; {{ $game->title }}</nav>

    <h1>{{ $game->title }}</h1>
    <div class="subtitle">
        @if($game->developer){{ $game->developer }}@endif
        @if($game->developer && $game->publisher) &middot; @endif
        @if($game->publisher){{ $game->publisher }}@endif
    </div>

    @if($game->platforms->isNotEmpty())
        <div class="platforms">
            @foreach($game->platforms as $platform)
                <span class="platform">{{ $platform->name }}</span>
            @endforeach
        </div>
    @endif

    @if($image)
        <img class="cover" src="{{ $image }}" alt="{{ $game->title }} cover art" loading="lazy">
    @endif

    <h2>Trophy Stats</h2>
    <div class="stats">
        @if($game->difficulty)
            <div class="stat">
                <div class="stat-label">Difficulty</div>
                <div class="stat-value">{{ $game->difficulty }}/10</div>
            </div>
        @endif
        <div class="stat">
            <div class="stat-label">Time</div>
            <div class="stat-value">{{ $game->time_range }}</div>
        </div>
        @if($game->playthroughs_required)
            <div class="stat">
                <div class="stat-label">Playthroughs</div>
                <div class="stat-value">{{ $game->playthroughs_required }}</div>
            </div>
        @endif
        @if($game->missable_trophies)
            <div class="stat">
                <div class="stat-label">Missables</div>
                <div class="stat-value">Yes</div>
            </div>
        @endif
        @if($game->has_online_trophies)
            <div class="stat">
                <div class="stat-label">Online</div>
                <div class="stat-value">Yes</div>
            </div>
        @endif
    </div>

    @if($guideCount > 0)
        <h2>Trophy Guides</h2>
        <div class="guides">
            @if($game->psnprofiles_url)
                <a href="{{ $game->psnprofiles_url }}">PSNProfiles Guide</a>
            @endif
            @if($game->playstationtrophies_url)
                <a href="{{ $game->playstationtrophies_url }}">PlayStationTrophies Guide</a>
            @endif
            @if($game->powerpyx_url)
                <a href="{{ $game->powerpyx_url }}">PowerPyx Guide</a>
            @endif
        </div>
    @endif

    @if($game->genres->isNotEmpty())
        <h2>Genres</h2>
        <div class="genres">
            @foreach($game->genres as $genre)
                <span class="genre">{{ $genre->name }}</span>
            @endforeach
        </div>
    @endif

    @if($game->description ?? false)
        <h2>About</h2>
        <p>{{ $game->description }}</p>
    @endif
</body>
</html>
