@extends('layouts.public')

@php
    $blogHeading = 'Blog';
    $blogDescription = 'Kumpulan catatan masalah teknis beserta solusinya.';
    $blogMetaDescription = 'Semua artikel tentang masalah teknis dan solusinya.';
@endphp

@section('title', $blogHeading . ' — ' . ($site['site_title'] ?? config('app.name')))
@section('meta_description', $blogMetaDescription)

@section('content')
    <div class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{-- Page Header --}}
        <div class="mb-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $blogHeading }}</h1>
            <p class="text-(--color-text-muted)">
                {{ $blogDescription }}
            </p>
        </div>

        {{-- Search & Filters --}}
        <form action="{{ url('/blog') }}" method="GET" class="flex flex-col sm:flex-row gap-4 mb-10"
            id="blog-filters">
            @if (request('tag'))
                <input type="hidden" name="tag" value="{{ request('tag') }}">
            @endif
            {{-- Search bar --}}
            <div class="relative w-full sm:max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-(--color-text-muted)" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" name="q" value="{{ request('q') }}"
                    placeholder="Cari artikel..." aria-label="Cari artikel"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) placeholder:text-(--color-text-muted) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none transition-all duration-200"
                    id="search-input">
            </div>

            {{-- Category filter --}}
            <div class="relative">
                <select name="category"
                    class="appearance-none pl-4 pr-10 py-2.5 rounded-xl bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none transition-all duration-200 cursor-pointer"
                    id="category-filter">
                    <option value="">Semua kategori</option>
                    @foreach ($categories ?? [] as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="sr-only">Cari artikel</button>
                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-(--color-text-muted) pointer-events-none"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var form = document.getElementById('blog-filters');
                    var search = document.getElementById('search-input');
                    var category = document.getElementById('category-filter');
                    var grid = document.getElementById('posts-grid');
                    if (!form || !category) return;

                    var pendingRequest = null;
                    var pendingUrl = null;

                    category.addEventListener('change', function() {
                        form.submit();
                    });

                    if (search) {
                        // Tombol "x" pada input search memicu event "search"; menghapus
                        // teks secara manual memicu event "input". Keduanya langsung
                        // menampilkan semua artikel tanpa reload halaman.
                        search.addEventListener('search', handleSearchCleared);
                        search.addEventListener('input', handleSearchCleared);
                    }

                    function handleSearchCleared() {
                        if (search.value.trim() !== '') return;

                        var url = new URL(window.location.href);
                        if (!url.searchParams.has('q')) return;

                        url.searchParams.delete('q');
                        url.searchParams.delete('page');

                        loadResults(url);
                    }

                    function loadResults(url) {
                        var target = url.toString();

                        if (!grid) {
                            window.location.href = target;
                            return;
                        }

                        if (pendingUrl === target && pendingRequest) return;
                        if (pendingRequest) pendingRequest.abort();

                        var controller = new AbortController();
                        pendingRequest = controller;
                        pendingUrl = target;

                        grid.setAttribute('aria-busy', 'true');
                        grid.classList.add('opacity-60');

                        fetch(target, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                signal: controller.signal,
                            })
                            .then(function(response) {
                                if (!response.ok) throw new Error('Request failed');
                                return response.text();
                            })
                            .then(function(html) {
                                var doc = new DOMParser().parseFromString(html, 'text/html');
                                var newGrid = doc.getElementById('posts-grid');
                                if (!newGrid) {
                                    window.location.href = target;
                                    return;
                                }

                                grid.innerHTML = newGrid.innerHTML;
                                replacePagination(doc.getElementById('pagination'));
                                window.history.replaceState({}, '', target);
                            })
                            .catch(function(error) {
                                if (error.name !== 'AbortError') {
                                    window.location.href = target;
                                }
                            })
                            .finally(function() {
                                if (pendingRequest !== controller) return;
                                pendingRequest = null;
                                pendingUrl = null;
                                grid.removeAttribute('aria-busy');
                                grid.classList.remove('opacity-60');
                            });
                    }

                    function replacePagination(newPagination) {
                        var current = document.getElementById('pagination');

                        if (!newPagination) {
                            if (current) current.remove();
                            return;
                        }

                        if (current) {
                            current.replaceWith(newPagination);
                            return;
                        }

                        grid.insertAdjacentElement('afterend', newPagination);
                    }
                });
            </script>
        @endpush

        {{-- Active tag filters --}}
        @if (request('tag'))
            <div class="flex items-center gap-2 mb-6">
                <span class="text-sm text-(--color-text-muted)">Tag:</span>
                <span
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-(--color-accent-bg) text-(--color-accent) text-sm font-medium">
                    {{ request('tag') }}
                    <a href="{{ url('/blog') }}" class="ml-1 hover:text-(--color-text)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </span>
            </div>
        @endif

        {{-- Posts Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="posts-grid">
            @forelse($posts ?? [] as $index => $post)
                <div class="opacity-0 animate-fade-in-up stagger-{{ ($index % 6) + 1 }}">
                    @include('partials.post-card', ['post' => $post, 'featured' => $index === 0])
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-(--color-text-muted)/30 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-1">
                        Belum ada artikel
                    </h3>
                    <p class="text-sm text-(--color-text-muted)">
                        Artikel akan segera hadir.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if (isset($posts) && method_exists($posts, 'links'))
            <div class="mt-12 flex justify-center" id="pagination">
                {{ $posts->withQueryString()->links('partials.pagination') }}
            </div>
        @endif
    </div>
@endsection
