<!DOCTYPE html>
<html lang="id" dir="ltr" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0b1026">
    <script>
        (function () {
            var theme = 'dark';
            try {
                theme = localStorage.getItem('theme') === 'light' ? 'light' : 'dark';
            } catch (error) {
                theme = 'dark';
            }
            var scheme = document.querySelector('meta[name="color-scheme"]');
            if (scheme) scheme.content = theme;
            if (theme === 'dark') document.documentElement.classList.add('dark');
            var themeColor = document.querySelector('meta[name="theme-color"]');
            if (themeColor) themeColor.content = theme === 'dark' ? '#0b1026' : '#fafafa';
        })();
    </script>

    {{-- Favicon (versioned so browser favicon cache is busted when the files change) --}}
    @php
        $faviconVersion = @filemtime(public_path('favicon.svg')) ?: '1';
    @endphp
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $faviconVersion }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}?v={{ $faviconVersion }}">

    @php
        $adminSiteTitle = $site['site_title'] ?? config('app.name', 'Godang');
    @endphp

    {{-- Site branding rendered up front so the SPA never flashes a placeholder title. --}}
    <meta name="site-title" content="{{ $adminSiteTitle }}">
    <meta name="site-logo" content="{{ $site['site_logo'] ?? '' }}">
    <meta name="site-logo-dark" content="{{ $site['site_logo_dark'] ?? '' }}">
    <meta name="site-logo-alt" content="{{ $site['site_logo_alt'] ?? '' }}">
    <meta name="author-name" content="{{ $site['author_name'] ?? '' }}">
    <meta name="turnstile-site-key" content="{{ config('services.turnstile.site_key') }}">

    <title>{{ __('Admin') }} — {{ $adminSiteTitle }}</title>

    @vite(['resources/css/app.css', 'resources/js/admin/main.jsx'])
</head>
<body class="min-h-screen bg-(--color-bg) text-(--color-text) transition-colors duration-300">
    <div id="admin-root"></div>
</body>
</html>
