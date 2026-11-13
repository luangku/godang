@extends('layouts.public')

@php
    $tagName = $tag->name;
@endphp

@section('title', $tagName . ' — ' . ($site['site_title'] ?? config('app.name')))
@section('meta_description', "Artikel dengan tag $tagName")

@section('content')
<div class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-12">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-(--color-text-muted) mb-8" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-(--color-accent) transition-colors">
            Beranda
        </a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ url('/blog') }}" class="hover:text-(--color-accent) transition-colors">Blog</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-(--color-text) truncate">{{ $tagName }}</span>
    </nav>

    {{-- Page Header --}}
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-(--color-accent-bg) text-(--color-accent) text-sm font-medium mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Tag
        </div>
        <h1 class="text-3xl md:text-4xl font-bold mb-2">
            <span class="text-(--color-accent)">#</span>{{ $tagName }}
        </h1>
        <p class="text-(--color-text-muted)">
            Semua artikel dengan tag {{ $tagName }}.
        </p>

        @if(isset($posts) && method_exists($posts, 'total'))
        <p class="text-sm text-(--color-text-muted) mt-2">
            {{ $posts->total() }} artikel
        </p>
        @endif
    </div>

    {{-- Posts Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="posts-grid">
        @forelse($posts ?? [] as $index => $post)
            <div class="opacity-0 animate-fade-in-up stagger-{{ ($index % 6) + 1 }}">
                @include('partials.post-card', ['post' => $post])
            </div>
        @empty
            <div class="col-span-full text-center py-16">
                <svg class="w-16 h-16 mx-auto text-(--color-text-muted)/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <h3 class="text-lg font-semibold mb-1">
                    Belum ada artikel
                </h3>
                <p class="text-sm text-(--color-text-muted) mb-4">
                    Belum ada artikel dengan tag ini.
                </p>
                <a href="{{ url('/blog') }}"
                   class="inline-flex items-center gap-1 text-sm font-medium text-(--color-accent) hover:underline">
                    ← Semua artikel
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if(isset($posts) && method_exists($posts, 'links'))
    <div class="mt-12 flex justify-center" id="pagination">
        {{ $posts->withQueryString()->links('partials.pagination') }}
    </div>
    @endif
</div>
@endsection
