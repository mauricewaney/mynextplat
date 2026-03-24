@extends('layouts.app')

@section('nav-tabs')
    {{-- Mobile --}}
    <div class="sm:hidden flex items-center gap-1">
        <a href="/" id="home-tab-allgames-mobile"
            class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-primary-600 dark:text-primary-400"
        >
            All Games
        </a>
        <a href="/browse"
            class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400"
        >
            Browse
        </a>
        @if(auth()->user())
            <a href="/my-games"
                class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400"
            >
                My Games
            </a>
            <a href="/?view=psn"
                id="home-psn-link"
                class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400"
            >
                Load PSN
            </a>
        @endif
    </div>

    {{-- Desktop --}}
    <div class="hidden sm:flex items-center gap-1">
        <a href="/" id="home-tab-allgames-desktop"
            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-primary-600 dark:text-primary-400"
        >
            All Games
        </a>
        <a href="/browse"
            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
        >
            Browse
        </a>
        @if(auth()->user())
            <a href="/my-games"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
            >
                My Games
            </a>
            <a href="/?view=psn"
                id="home-psn-link-desktop"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
            >
                Load PSN
            </a>
        @endif
    </div>
@endsection

@section('content')
<div id="vue-home"></div>
@endsection
