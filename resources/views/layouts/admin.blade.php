@php
    $authUser = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Admin | ' . config('app.name', 'MyNextPlat'))</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />

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

@yield('content')

{{-- Seed auth data for Vue --}}
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
