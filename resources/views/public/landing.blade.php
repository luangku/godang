@extends('layouts.public')

@php
    $home = [
        'badge' => 'Blog Teknis',
        'title_line_1' => 'Masalah Teknis,',
        'title_line_2' => 'Solusi Nyata.',
        'description' => 'Catatan masalah teknis yang pernah saya hadapi beserta solusinya. Semoga bisa membantu kamu yang mengalami masalah serupa.',
        'about_heading' => 'Tentang Saya',
        'about_text' => 'Saya seorang developer yang senang mendokumentasikan masalah teknis dan solusinya. Blog ini dibuat agar catatan saya bisa bermanfaat untuk orang lain yang mengalami masalah serupa.',
    ];
@endphp

@section('title', 'Beranda — ' . ($site['site_title'] ?? config('app.name')))
@section('meta_description', $home['description'])

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden" id="hero">
        {{-- Background gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-(--color-accent-bg) via-transparent to-transparent opacity-50">
        </div>
        <div class="absolute top-20 right-10 w-72 h-72 bg-(--color-accent)/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-10 w-96 h-96 bg-(--color-accent)/3 rounded-full blur-3xl"></div>

        <div class="relative max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
            <div class="max-w-2xl animate-fade-in-up">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-(--color-accent-bg) text-(--color-accent) text-sm font-medium mb-6">
                    <span class="w-2 h-2 rounded-full bg-(--color-accent) animate-pulse"></span>
                    {{ $home['badge'] }}
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
                    {{ $home['title_line_1'] }}
                    <br>
                    <span class="text-(--color-accent)">
                        {{ $home['title_line_2'] }}
                    </span>
                </h1>

                <p class="text-lg md:text-xl text-(--color-text-muted) leading-relaxed mb-8 max-w-xl">
                    {{ $home['description'] }}
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ url('/blog') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-semibold text-sm hover:opacity-90 transition-all duration-200 hover:shadow-lg hover:shadow-(--color-accent)/20"
                        id="hero-cta">
                        Baca Artikel
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    {{-- <a href="{{ url('/about') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border border-(--color-border) text-(--color-text) font-semibold text-sm hover:bg-(--color-surface) hover:border-(--color-accent)/30 transition-all duration-200"
                        id="hero-about">
                        Tentang Saya
                    </a> --}}
                </div>
            </div>
        </div>
    </section>

    {{-- Latest Posts --}}
    <section class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-16" id="latest-posts">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold">
                    Artikel Terbaru
                </h2>
                <p class="text-(--color-text-muted) mt-1 text-sm">
                    Tulisan terbaru seputar masalah dan solusi teknis.
                </p>
            </div>
            <a href="{{ url('/blog') }}"
                class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-(--color-accent) hover:underline"
                id="view-all-posts">
                Lihat semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($latestPosts ?? [] as $index => $post)
                <div class="opacity-0 animate-fade-in-up stagger-{{ $index + 1 }}">
                    @include('partials.post-card', ['post' => $post])
                </div>
            @empty
                {{-- Placeholder cards when no posts exist yet --}}
                @for ($i = 0; $i < 3; $i++)
                    <div class="opacity-0 animate-fade-in-up stagger-{{ $i + 1 }}">
                        <div class="bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden">
                            <div
                                class="aspect-[16/9] bg-gradient-to-br from-(--color-accent-bg) to-(--color-surface) flex items-center justify-center">
                                <svg class="w-12 h-12 text-(--color-accent)/20" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div class="p-5">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="h-4 w-16 rounded bg-(--color-accent-bg)"></span>
                                    <span class="h-4 w-20 rounded bg-(--color-border)"></span>
                                </div>
                                <div class="h-5 w-3/4 rounded bg-(--color-border) mb-2"></div>
                                <div class="h-4 w-full rounded bg-(--color-border)/60 mb-1"></div>
                                <div class="h-4 w-2/3 rounded bg-(--color-border)/40 mb-4"></div>
                                <div class="flex items-center justify-between">
                                    <span class="h-3 w-20 rounded bg-(--color-border)/40"></span>
                                    <div class="flex gap-1.5">
                                        <span class="h-5 w-12 rounded-md bg-(--color-accent-bg)"></span>
                                        <span class="h-5 w-14 rounded-md bg-(--color-accent-bg)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            @endforelse
        </div>

        <div class="mt-8 text-center sm:hidden">
            <a href="{{ url('/blog') }}"
                class="inline-flex items-center gap-1 text-sm font-medium text-(--color-accent) hover:underline">
                Lihat semua artikel
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </section>

    {{-- Popular Topics --}}
    <section class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-16" id="popular-topics">
        <h2 class="text-2xl md:text-3xl font-bold mb-2">
            Topik Populer
        </h2>
        <p class="text-(--color-text-muted) text-sm mb-8">
            Jelajahi artikel berdasarkan topik.
        </p>

        <div class="flex flex-wrap gap-3">
            @forelse($categories ?? [] as $category)
                <a href="{{ url('/category/' . $category->slug) }}"
                    class="group inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-(--color-surface) border border-(--color-border) hover:border-(--color-accent)/30 hover:bg-(--color-accent-bg) transition-all duration-200">
                    <span
                        class="text-sm font-medium text-(--color-text) group-hover:text-(--color-accent) transition-colors">
                        {{ $category->name }}
                    </span>
                    @if (isset($category->posts_count))
                        <span class="text-xs text-(--color-text-muted) bg-(--color-bg) px-1.5 py-0.5 rounded-md">
                            {{ $category->posts_count }}
                        </span>
                    @endif
                </a>
            @empty
                @foreach (['Laravel', 'PHP', 'JavaScript', 'DevOps', 'Database', 'Linux'] as $topic)
                    <span
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-(--color-surface) border border-(--color-border) text-sm font-medium text-(--color-text-muted)">
                        {{ $topic }}
                    </span>
                @endforeach
            @endforelse
        </div>
    </section>

    {{-- About section --}}
    <section class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-16" id="about-section">
        <div
            class="bg-(--color-surface) rounded-2xl border border-(--color-border) p-8 md:p-12 flex flex-col md:flex-row items-center gap-8">
            {{-- Avatar --}}
            @if (!empty($site['author_avatar_url']))
                <img src="{{ $site['author_avatar_url'] }}"
                    alt="{{ $site['author_avatar_alt'] ?: $site['author_name'] ?? '' }}"
                    class="w-24 h-24 md:w-32 md:h-32 rounded-full object-cover flex-shrink-0">
            @else
                <div
                    class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-gradient-to-br from-(--color-accent) to-purple-500 flex items-center justify-center flex-shrink-0">
                    <span class="text-3xl md:text-4xl font-bold text-white">{{ $site['author_initial'] ?? 'G' }}</span>
                </div>
            @endif

            <div>
                <h2 class="text-2xl font-bold mb-3">
                    {{ $home['about_heading'] }}
                </h2>
                <p class="text-(--color-text-muted) leading-relaxed mb-4">
                    {{ $home['about_text'] }}
                </p>
                <a href="{{ url('/about') }}"
                    class="inline-flex items-center gap-1 text-sm font-medium text-(--color-accent) hover:underline"
                    id="about-link">
                    Selengkapnya
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

@endsection
