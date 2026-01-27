<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags (baseline - overridden by Vue for dynamic pages) --}}
    <title>MyNextPlat - PlayStation Trophy Guides & Tracker</title>
    <meta name="description" content="Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx. Filter by difficulty, time, and more.">
    <meta name="keywords" content="playstation, trophy guide, platinum trophy, ps5, ps4, psnprofiles, powerpyx, trophy hunting">

    {{-- Open Graph --}}
    <meta property="og:title" content="MyNextPlat - PlayStation Trophy Guides & Tracker">
    <meta property="og:description" content="Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MyNextPlat">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="MyNextPlat - PlayStation Trophy Guides & Tracker">
    <meta name="twitter:description" content="Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

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
