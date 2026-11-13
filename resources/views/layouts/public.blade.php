<!DOCTYPE html>
<html lang="id" dir="ltr" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteTitle = $site['site_title'] ?? config('app.name', 'Godang');
        $defaultDescription = $site['default_meta_description']
            ?? 'Blog teknis pribadi: berbagi masalah dan solusi pemrograman.';
    @endphp

    {{-- SEO --}}
    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="@yield('meta_description', $defaultDescription)">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- Open Graph --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:title" content="@yield('og_title', $siteTitle)">
    <meta property="og:description" content="@yield('meta_description', $defaultDescription)">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    @endif

    {{-- hreflang --}}
    @hasSection('hreflang')
        @yield('hreflang')
    @endif

    {{-- Structured Data --}}
    @hasSection('structured_data')
        @yield('structured_data')
    @endif

    {{-- RSS --}}
    <link rel="alternate" type="application/rss+xml" title="{{ $siteTitle }} (ID)" href="{{ url('/feed.xml') }}">

    {{-- Favicon (versioned so browser favicon cache is busted when the files change) --}}
    @php
        $faviconVersion = @filemtime(public_path('favicon.svg')) ?: '1';
    @endphp
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $faviconVersion }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}?v={{ $faviconVersion }}">
    <meta name="theme-color" content="#0b1026">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Prevent FOUC for dark mode (default: dark) --}}
    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            if (saved !== 'light') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-(--color-bg) text-(--color-text) transition-colors duration-300">

    @include('partials.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @stack('scripts')
</body>
</html>
