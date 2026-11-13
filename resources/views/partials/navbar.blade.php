{{-- Navbar --}}
<nav class="sticky top-0 z-50 bg-(--color-surface)/80 backdrop-blur-lg border-b border-(--color-border) transition-colors duration-300"
    id="navbar">
    <div class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            {{-- Logo (light/dark variants) --}}
            @php
                $logoAlt = $site['site_logo_alt'] ?? ($site['site_title'] ?? config('app.name', 'Godang'));
                $logoLight = $site['site_logo'] ?? null;
                $logoDark = $site['site_logo_dark'] ?? null;
                $logoLight = $logoLight ?: $logoDark;
                $logoDark = $logoDark ?: $logoLight;
                $logoUrl = function (?string $path): ?string {
                    if (empty($path)) {
                        return null;
                    }

                    $path = str_starts_with($path, 'storage/') ? substr($path, strlen('storage/')) : ltrim($path, '/');

                    return \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($path, '/'));
                };
            @endphp
            <a href="{{ url('/') }}" class="flex items-center gap-2 group" id="nav-logo">
                @if ($logoLight)
                    <img src="{{ $logoUrl($logoLight) }}" alt="{{ $logoAlt }}"
                        class="h-8 max-w-[160px] object-contain transition-transform duration-200 group-hover:scale-105 dark:hidden">
                    <img src="{{ $logoUrl($logoDark) }}" alt="{{ $logoAlt }}"
                        class="h-8 max-w-[160px] object-contain transition-transform duration-200 group-hover:scale-105 hidden dark:block">
                @else
                    <span
                        class="text-xl font-bold text-(--color-accent) transition-transform duration-200 group-hover:scale-105">
                        {{ $site['site_title'] ?? config('app.name', 'Godang') }}
                    </span>
                @endif
            </a>

            {{-- Right side: desktop nav + theme toggle --}}
            <div class="flex items-center gap-2">
                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ url('/blog') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-(--color-text-muted) hover:text-(--color-text) hover:bg-(--color-accent-bg) transition-all duration-200"
                        id="nav-blog">
                        Blog
                    </a>
                    <a href="{{ url('/about') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-(--color-text-muted) hover:text-(--color-text) hover:bg-(--color-accent-bg) transition-all duration-200"
                        id="nav-about">
                        About
                    </a>
                </div>
                {{-- Admin mode indicator (links to dashboard) --}}
                @auth
                    @if (auth()->user()->is_admin)
                        <a href="{{ url('/admin') }}"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-(--color-accent)/40 bg-(--color-accent-bg) text-(--color-accent) text-xs font-semibold hover:bg-(--color-accent) hover:text-(--color-on-accent) hover:border-(--color-accent) transition-all duration-200"
                            id="nav-admin-mode" title="Buka dashboard admin">
                            <span class="w-2 h-2 rounded-full bg-current animate-pulse" aria-hidden="true"></span>
                            <span class="sm:hidden">Admin</span>
                            <span class="hidden sm:inline-flex">Mode Admin</span>
                        </a>
                    @endif
                @endauth

                {{-- Theme Toggle --}}
                <button onclick="toggleTheme()"
                    class="p-2 rounded-lg text-(--color-text-muted) hover:text-(--color-text) hover:bg-(--color-accent-bg) transition-all duration-200"
                    id="theme-toggle" aria-label="Ubah mode gelap">
                    {{-- Sun icon (shown in dark mode) --}}
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    {{-- Moon icon (shown in light mode) --}}
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                {{-- Mobile menu toggle --}}
                <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                    class="md:hidden p-2 rounded-lg text-(--color-text-muted) hover:text-(--color-text) hover:bg-(--color-accent-bg) transition-all duration-200"
                    id="mobile-menu-toggle" aria-label="Buka menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden md:hidden pb-4 animate-slide-down">
            <div class="flex flex-col gap-1">
                <a href="{{ url('/blog') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-(--color-text-muted) hover:text-(--color-text) hover:bg-(--color-accent-bg) transition-all duration-200">
                    Blog
                </a>
                <a href="{{ url('/about') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-(--color-text-muted) hover:text-(--color-text) hover:bg-(--color-accent-bg) transition-all duration-200">
                    Tentang
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleTheme() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    }
</script>
