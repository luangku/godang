@php
    $homeUrl = url('/');
    $blogUrl = url('/blog');
@endphp
<!DOCTYPE html>
<html lang="id" dir="ltr" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — {{ config('app.name', 'Godang') }}</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            if (saved !== 'light') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-(--color-bg) text-(--color-text) flex flex-col items-center justify-center px-4 transition-colors duration-300">
    <div class="text-center max-w-lg animate-fade-in-up">
        {{-- 404 Number --}}
        <div class="mb-8">
            <span class="text-[8rem] md:text-[10rem] font-bold leading-none bg-gradient-to-br from-(--color-accent) to-purple-500 bg-clip-text text-transparent select-none">
                404
            </span>
        </div>

        {{-- Icon --}}
        <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-(--color-accent-bg) flex items-center justify-center">
            <svg class="w-8 h-8 text-(--color-accent)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Message --}}
        <h1 class="text-2xl md:text-3xl font-bold mb-3">
            Halaman tidak ditemukan
        </h1>
        <p class="text-(--color-text-muted) mb-2">
            Halaman tidak ditemukan
        </p>
        <p class="text-sm text-(--color-text-muted) mb-8 max-w-md mx-auto">
            Halaman yang kamu cari mungkin telah dipindahkan atau tidak tersedia.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ $homeUrl }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-semibold text-sm hover:opacity-90 transition-all duration-200 hover:shadow-lg hover:shadow-(--color-accent)/20"
               id="go-home">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Beranda
            </a>
            <a href="{{ $blogUrl }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border border-(--color-border) text-(--color-text) font-semibold text-sm hover:bg-(--color-surface) hover:border-(--color-accent)/30 transition-all duration-200"
               id="go-blog">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                Blog
            </a>
        </div>
    </div>

    {{-- Theme Toggle --}}
    <button
        onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')"
        class="fixed bottom-6 right-6 p-3 rounded-xl bg-(--color-surface) border border-(--color-border) text-(--color-text-muted) hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200 shadow-lg cursor-pointer"
        aria-label="Ubah mode gelap"
        id="theme-toggle-404"
    >
        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </button>
</body>
</html>
