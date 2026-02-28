@php
    $appName = config('app.name', 'MyNextPlat');
@endphp

@extends('layouts.app')

@section('title', "Browse Trophy Guides & Games | {$appName}")
@section('description', "Explore PlayStation trophy guides by genre, platform, or curated collections. Find your next platinum from {$genres->sum('games_count')}+ games across {$genres->count()} genres and {$platforms->count()} platforms.")

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

    {{-- Page Header --}}
    <div class="mb-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Browse</h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Explore trophy guides by genre, platform, or curated collection.</p>
    </div>

    {{-- Trophy Guides (Presets) --}}
    <section class="mb-12">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4">Trophy Guides</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($presets as $preset)
                <a href="/guides/{{ $preset['slug'] }}"
                    class="group block p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-md transition-all"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $preset['title'] }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $preset['description'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Browse by Genre --}}
    <section class="mb-12">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4">Browse by Genre</h2>
        <div class="flex flex-wrap gap-2.5">
            @foreach($genres as $genre)
                <a href="/games/genre/{{ $genre->slug }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 text-gray-700 dark:text-gray-300 hover:border-primary-400 dark:hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 hover:shadow-sm transition-all"
                >
                    {{ $genre->name }}
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $genre->games_count }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Browse by Platform --}}
    <section>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4">Browse by Platform</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($platforms as $platform)
                <a href="/games/platform/{{ $platform->slug }}"
                    class="group flex items-center justify-between p-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-md transition-all"
                >
                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $platform->short_name ?? $platform->name }}</h3>
                    <span class="text-sm text-gray-400 dark:text-gray-500">{{ number_format($platform->games_count) }} games</span>
                </a>
            @endforeach
        </div>
    </section>

</div>
@endsection
