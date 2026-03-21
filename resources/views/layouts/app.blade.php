@php
    $authUser = auth()->user();
    $appName = config('app.name', 'MyNextPlat');
    $currentPath = request()->path();
    $showDonations = config('app.show_donations', false);
    $donationUrl = config('app.donation_url', 'https://ko-fi.com/mynextplat');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', $appName . ' - PlayStation Trophy Guides & Tracker')</title>
    <meta name="description" content="@yield('description', 'Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx. Filter by difficulty, time, and more.')">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', $appName . ' - PlayStation Trophy Guides & Tracker')">
    <meta property="og:description" content="@yield('og_description', 'Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:image" content="{{ url('/images/og-banner.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $appName . ' - PlayStation Trophy Guides & Tracker')">
    <meta name="twitter:description" content="@yield('og_description', 'Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.')">
    <meta name="twitter:image" content="{{ url('/images/og-banner.png') }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">

    @yield('meta')

    {{-- Preconnect --}}
    <link rel="preconnect" href="https://images.igdb.com" crossorigin>
    <link rel="dns-prefetch" href="https://images.igdb.com">

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />

    {{-- Ahrefs --}}
    <script src="https://analytics.ahrefs.com/analytics.js" data-key="EwmZjm+zQ4UD20MWX0JQZg" async></script>

    {{-- Prevent flash of wrong theme --}}
    <script>
        (function() {
            const stored = localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'true' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Critical CSS --}}
    <style>
        html { background-color: #f8fafc; }
        html.dark { background-color: #0f172a; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900 transition-colors">

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-primary-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-300 flex flex-col">
    {{-- Header --}}
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-lg border-b border-gray-200 shadow-sm dark:bg-slate-900/95 dark:border-slate-700/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity" aria-label="MyNextPlat Home">
                        <div class="w-[2.25rem] h-8 bg-primary-600 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-6 text-white" fill="currentColor" viewBox="0 0 512 512" aria-hidden="true">
                                <path d="M102.49,0c0,27.414,0,104.166,0,137.062c0,112.391,99.33,156.25,153.51,156.25c54.18,0,153.51-43.859,153.51-156.25c0-32.896,0-109.648,0-137.062H102.49z M256.289,50.551l-68.164,29.768v98.474l-0.049,19.53c-0.526-0.112-47.274-10.112-47.274-78.391c0-28.17,0-69.6,0-69.6h60.385L256.289,50.551z"/>
                                <polygon points="315.473,400.717 291.681,367.482 279.791,318.506 256,322.004 232.209,318.506 220.314,367.482 205.347,388.394 196.527,400.476 196.699,400.476 196.527,400.717"/>
                                <polygon points="366.93,432.24 366.93,432 145.07,432 145.07,511.598 145.07,511.76 145.07,511.76 145.07,512 366.93,512 366.93,432.402 366.93,432.24"/>
                                <path d="M511.638,96.668c-0.033-1.268-0.068-2.336-0.068-3.174V45.1h-73.889v38.736h35.152v9.658c0,1.127,0.037,2.557,0.086,4.258c0.389,13.976,1.303,46.707-21.545,70.203c-5.121,5.266-11.221,9.787-18.219,13.613c-3.883,17.635-10.109,33.564-18.104,47.814c26.561-6.406,48.026-17.898,64.096-34.422C513.402,159.734,512.121,113.918,511.638,96.668z"/>
                                <path d="M60.625,167.955c-22.848-23.496-21.934-56.227-21.541-70.203c0.047-1.701,0.082-3.131,0.082-4.258v-9.658h34.842h0.07h0.24V45.1H0.43v48.394c0,0.838-0.032,1.906-0.068,3.174c-0.482,17.25-1.76,63.066,32.494,98.293c16.068,16.524,37.531,28.014,64.092,34.422c-7.996-14.25-14.22-30.182-18.103-47.816C71.846,177.74,65.746,173.221,60.625,167.955z"/>
                            </svg>
                        </div>
                    </a>

                    @hasSection('nav-tabs')
                        @yield('nav-tabs')
                    @else
                        {{-- Default Navigation Tabs - Mobile --}}
                        <div class="sm:hidden flex items-center gap-1">
                            <a href="/"
                                class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ request()->is('/') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400' }}"
                            >
                                All Games
                            </a>
                            <a href="/browse"
                                class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ request()->is('browse') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400' }}"
                            >
                                Browse
                            </a>
                            @if($authUser)
                                <a href="/my-games"
                                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors {{ request()->is('my-games') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400' }}"
                                >
                                    My Games
                                </a>
                                <a href="/?view=psn"
                                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400"
                                >
                                    Load PSN
                                </a>
                            @endif
                        </div>

                        {{-- Default Navigation Tabs - Desktop --}}
                        <div class="hidden sm:flex items-center gap-1">
                            <a href="/"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('/') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                            >
                                All Games
                            </a>
                            <a href="/browse"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('browse') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                            >
                                Browse
                            </a>
                            @if($authUser)
                                <a href="/my-games"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->is('my-games') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                                >
                                    My Games
                                </a>
                                <a href="/?view=psn"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                >
                                    Load PSN
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Nav menu island: handles mobile hamburger, desktop links, dark mode, user dropdown --}}
                <div id="nav-menu"
                    data-authenticated="{{ $authUser ? 'true' : 'false' }}"
                    data-user="{{ $authUser ? json_encode(['name' => $authUser->name, 'email' => $authUser->email, 'avatar' => $authUser->avatar, 'is_admin' => $authUser->is_admin]) : '' }}"
                    data-show-donations="{{ $showDonations ? 'true' : 'false' }}"
                    data-donation-url="{{ $donationUrl }}"
                ></div>
            </div>
        </div>
    </header>

    {{-- Main Content — min-h-screen keeps the footer below the fold during async loads, preventing CLS --}}
    <main class="flex-1 min-h-screen">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-auto border-t border-gray-200 dark:border-slate-700/50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                {{-- Brand --}}
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <div class="w-6 h-6 bg-primary-600 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="-70 0 652 512">
                            <path d="M102.49,0c0,27.414,0,104.166,0,137.062c0,112.391,99.33,156.25,153.51,156.25c54.18,0,153.51-43.859,153.51-156.25c0-32.896,0-109.648,0-137.062H102.49z M256.289,50.551l-68.164,29.768v98.474l-0.049,19.53c-0.526-0.112-47.274-10.112-47.274-78.391c0-28.17,0-69.6,0-69.6h60.385L256.289,50.551z"/>
                            <polygon points="315.473,400.717 291.681,367.482 279.791,318.506 256,322.004 232.209,318.506 220.314,367.482 205.347,388.394 196.527,400.476 196.699,400.476 196.527,400.717"/>
                            <polygon points="366.93,432.24 366.93,432 145.07,432 145.07,511.598 145.07,511.76 145.07,511.76 145.07,512 366.93,512 366.93,432.402 366.93,432.24"/>
                            <path d="M511.638,96.668c-0.033-1.268-0.068-2.336-0.068-3.174V45.1h-73.889v38.736h35.152v9.658c0,1.127,0.037,2.557,0.086,4.258c0.389,13.976,1.303,46.707-21.545,70.203c-5.121,5.266-11.221,9.787-18.219,13.613c-3.883,17.635-10.109,33.564-18.104,47.814c26.561-6.406,48.026-17.898,64.096-34.422C513.402,159.734,512.121,113.918,511.638,96.668z"/>
                            <path d="M60.625,167.955c-22.848-23.496-21.934-56.227-21.541-70.203c0.047-1.701,0.082-3.131,0.082-4.258v-9.658h34.842h0.07h0.24V45.1H0.43v48.394c0,0.838-0.032,1.906-0.068,3.174c-0.482,17.25-1.76,63.066,32.494,98.293c16.068,16.524,37.531,28.014,64.092,34.422c-7.996-14.25-14.22-30.182-18.103-47.816C71.846,177.74,65.746,173.221,60.625,167.955z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ $appName }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">&copy; {{ date('Y') }}</span>
                </div>

                {{-- Data Sources Attribution --}}
                <div class="flex flex-wrap items-center justify-center gap-x-1 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                    <span>Game data from</span>
                    <a href="https://www.igdb.com" target="_blank" class="text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white font-medium">IGDB.com</a>
                    <span class="hidden sm:inline">&middot;</span>
                    <span class="hidden sm:inline">Guides from</span>
                    <span class="sm:hidden w-full text-center">Guides from</span>
                    <a href="https://psnprofiles.com" target="_blank" class="text-primary-500 hover:text-primary-400 font-medium">PSNProfiles</a>
                    <span>&middot;</span>
                    <a href="https://www.playstationtrophies.org" target="_blank" class="text-primary-500 hover:text-primary-400 font-medium">PlayStationTrophies</a>
                    <span>&middot;</span>
                    <a href="https://www.powerpyx.com" target="_blank" class="text-primary-500 hover:text-primary-400 font-medium">PowerPyx</a>
                </div>

                {{-- Contact & Links --}}
                <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                    @if($showDonations)
                        <a
                            href="{{ $donationUrl }}"
                            target="_blank"
                            class="flex items-center gap-1 px-2 py-1 bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 rounded-full hover:bg-pink-100 dark:hover:bg-pink-900/50 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span class="font-medium">Support</span>
                        </a>
                    @endif
                    <a href="/profiles" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">Libraries</a>
                    <a href="/contact" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">Contact</a>
                    <a href="/privacy" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">Privacy</a>
                </div>
            </div>
        </div>
    </footer>
</div>

{{-- Seed auth and page data for Vue islands --}}
@php
    $authData = $authUser ? [
        'id' => $authUser->id,
        'name' => $authUser->name,
        'email' => $authUser->email,
        'avatar' => $authUser->avatar,
        'is_admin' => $authUser->is_admin,
        'notify_new_guides' => $authUser->notify_new_guides,
        'profile_public' => $authUser->profile_public,
        'profile_slug' => $authUser->profile_slug,
    ] : null;
@endphp
<script>
    window.__AUTH_USER__ = @json($authData);
    window.__PAGE_DATA__ = @json($pageData ?? []);
</script>
</body>
</html>
