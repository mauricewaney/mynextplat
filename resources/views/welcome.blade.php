<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags (baseline - overridden by Vue for dynamic pages) --}}
    <title>{{ config('app.name') }} - PlayStation Trophy Guides & Tracker</title>
    <meta name="description" content="Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx. Filter by difficulty, time, and more.">
    <meta name="keywords" content="playstation, trophy guide, platinum trophy, ps5, ps4, psnprofiles, powerpyx, trophy hunting">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ config('app.name') }} - PlayStation Trophy Guides & Tracker">
    <meta property="og:description" content="Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    {{-- Open Graph Image --}}
    <meta property="og:image" content="{{ url('/images/og-banner.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }} - PlayStation Trophy Guides & Tracker">
    <meta name="twitter:description" content="Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.">
    <meta name="twitter:image" content="{{ url('/images/og-banner.png') }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Preconnect to external domains for faster resource loading --}}
    <link rel="preconnect" href="https://images.igdb.com" crossorigin>
    <link rel="dns-prefetch" href="https://images.igdb.com">

    {{-- Prevent flash of wrong theme by applying dark mode before render --}}
    <script>
        (function() {
            const stored = localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'true' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Critical CSS to prevent white flash --}}
    <style>
        html { background-color: #f8fafc; }
        html.dark { background-color: #0f172a; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900 transition-colors">
<div id="app" class="h-full"></div>
</body>
</html>
