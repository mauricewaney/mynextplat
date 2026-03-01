@extends('layouts.app')

@section('nav-tabs')
    {{-- Mobile — All Games controlled by Vue (for PSN view toggle) --}}
    <div class="sm:hidden flex items-center gap-1">
        <span id="home-tab-allgames-mobile"></span>
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

    {{-- Desktop — All Games controlled by Vue (for PSN view toggle) --}}
    <div class="hidden sm:flex items-center gap-1">
        <span id="home-tab-allgames-desktop"></span>
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
