@php
    $appName = config('app.name', 'MyNextPlat');
    $pageTitle = "{$title} | {$appName}";
    $gameCount = $games->count();

    // JSON-LD ItemList schema
    $itemListSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $title,
        'description' => $description,
        'numberOfItems' => $gameCount,
        'itemListElement' => $games->map(fn ($game, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => url("/game/{$game->slug}"),
            'name' => $game->title,
        ])->toArray(),
    ];

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
                'name' => $breadcrumbType,
                'item' => url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $breadcrumbLabel,
                'item' => $canonicalUrl,
            ],
        ],
    ];
@endphp

@extends('layouts.app')

@section('title', $pageTitle)
@section('description', $description)
@section('og_title', $pageTitle)
@section('og_description', $description)

@section('canonical', $canonicalUrl)

@section('meta')
    <script type="application/ld+json">{!! json_encode($itemListSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <style>
        .list-game { display: flex; gap: 0.75rem; padding: 0.75rem 0; align-items: center; }
    </style>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="text-sm text-gray-400 dark:text-gray-500 mb-6">
            <a href="/" class="text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">{{ $appName }}</a>
            <span class="mx-1">&rsaquo;</span>
            <span>{{ $breadcrumbType }}</span>
            <span class="mx-1">&rsaquo;</span>
            <span class="text-gray-600 dark:text-gray-300">{{ $breadcrumbLabel }}</span>
        </nav>

        {{-- Hero: two-column layout on desktop --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {{-- Left column: description + stats --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $title }}</h1>
                <p class="text-gray-500 mb-4">{{ $gameCount }} {{ Str::plural('game', $gameCount) }}</p>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-6">{{ $intro }}</p>

                @if(!empty($stats) && ($stats['avg_difficulty'] !== null || $stats['avg_time'] !== null || $stats['pct_no_online'] !== null))
                    <div class="grid grid-cols-3 gap-3">
                        @if($stats['avg_difficulty'] !== null)
                            <div class="bg-gray-100 dark:bg-slate-800/60 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['avg_difficulty'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">Avg Difficulty</div>
                            </div>
                        @endif
                        @if($stats['avg_time'] !== null)
                            <div class="bg-gray-100 dark:bg-slate-800/60 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['avg_time'] }}h</div>
                                <div class="text-xs text-gray-500 mt-1">Avg Time</div>
                            </div>
                        @endif
                        @if($stats['pct_no_online'] !== null)
                            <div class="bg-gray-100 dark:bg-slate-800/60 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['pct_no_online'] }}%</div>
                                <div class="text-xs text-gray-500 mt-1">No Online</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right column: featured games --}}
            @if($featuredGames->isNotEmpty())
                <div class="flex flex-col gap-3">
                    <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Featured Games</h2>
                    @foreach($featuredGames as $game)
                        @include('seo.partials.featured-game-card', ['game' => $game])
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Curated Sections --}}
        @if(isset($sections) && $sections->isNotEmpty())
            @foreach($sections as $section)
                <h2 class="section-title-text text-lg font-bold text-gray-700 dark:text-gray-400 mt-8 mb-4 border-b border-gray-200 dark:border-slate-700/50 pb-2">{{ $section['title'] }}</h2>
                @foreach($section['games'] as $game)
                    @include('seo.partials.list-game-row', ['game' => $game])
                @endforeach
            @endforeach
        @endif

        {{-- All remaining games: two columns (desktop) / tabs (mobile) --}}
        @if($listGamesByDate->isNotEmpty())
            {{-- Mobile tabs --}}
            <div class="lg:hidden mt-8">
                <div class="flex gap-2 mb-4 border-b border-slate-700/50">
                    <button id="tab-recent" onclick="document.getElementById('panel-recent').style.display='';document.getElementById('panel-popular').style.display='none';this.className='px-3 py-1.5 text-sm font-bold transition-colors text-primary-400 border-b-2 border-primary-400';document.getElementById('tab-popular').className='px-3 py-1.5 text-sm font-bold transition-colors text-gray-500';" class="px-3 py-1.5 text-sm font-bold transition-colors text-primary-400 border-b-2 border-primary-400">Newest Releases</button>
                    <button id="tab-popular" onclick="document.getElementById('panel-popular').style.display='';document.getElementById('panel-recent').style.display='none';this.className='px-3 py-1.5 text-sm font-bold transition-colors text-primary-400 border-b-2 border-primary-400';document.getElementById('tab-recent').className='px-3 py-1.5 text-sm font-bold transition-colors text-gray-500';" class="px-3 py-1.5 text-sm font-bold transition-colors text-gray-500">Most Popular</button>
                </div>
                <div id="panel-recent">
                    @foreach($listGamesByDate as $game)
                        @include('seo.partials.list-game-row', ['game' => $game])
                    @endforeach
                </div>
                <div id="panel-popular" style="display: none;">
                    @foreach($listGamesByPopularity as $game)
                        @include('seo.partials.list-game-row', ['game' => $game])
                    @endforeach
                </div>
            </div>

            {{-- Desktop two columns --}}
            <div class="hidden lg:grid lg:grid-cols-2 gap-8 mt-8">
                <div>
                    <h2 class="text-lg font-bold text-gray-700 dark:text-gray-400 mb-4 border-b border-gray-200 dark:border-slate-700/50 pb-2">Newest Releases</h2>
                    @foreach($listGamesByDate as $game)
                        @include('seo.partials.list-game-row', ['game' => $game])
                    @endforeach
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-700 dark:text-gray-400 mb-4 border-b border-gray-200 dark:border-slate-700/50 pb-2">Most Popular</h2>
                    @foreach($listGamesByPopularity as $game)
                        @include('seo.partials.list-game-row', ['game' => $game])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Related Categories --}}
        @if(!empty($relatedPages))
            <div class="mt-8 mb-4">
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-400 mb-4 border-b border-gray-200 dark:border-slate-700/50 pb-2">Related Categories</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($relatedPages as $related)
                        @php
                            $relatedUrl = match($related['type']) {
                                'genre' => "/games/genre/{$related['slug']}",
                                'platform' => "/games/platform/{$related['slug']}",
                                'preset' => "/guides/{$related['slug']}",
                                default => '/',
                            };
                        @endphp
                        <a href="{{ $relatedUrl }}" class="inline-block bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 px-3 py-1.5 rounded-full text-sm font-medium transition-colors">
                            {{ $related['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- CTA --}}
        <a class="block text-center bg-primary-600 hover:bg-primary-500 text-white font-bold text-lg py-3 px-6 rounded-xl my-8 transition-colors no-underline" href="{{ $ctaUrl }}">
            Explore all {{ $title }} with filters &rarr;
        </a>
    </div>
@endsection
