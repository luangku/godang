@extends('layouts.public')

@php
    $categoryName = $category->name;
    $categoryDescription = $category->description;
    $metaDescription = $categoryDescription ?: "Artikel tentang $categoryName";
@endphp

@section('title', $categoryName . ' — ' . ($site['site_title'] ?? config('app.name')))
@section('meta_description', $metaDescription)

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
        <span class="text-(--color-text) truncate">{{ $categoryName }}</span>
    </nav>

    {{-- Page Header --}}
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-(--color-accent-bg) text-(--color-accent) text-sm font-medium mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Kategori
        </div>
        <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $categoryName }}</h1>
        @if($categoryDescription)
        <p class="text-(--color-text-muted) max-w-2xl">{{ $categoryDescription }}</p>
        @else
        <p class="text-(--color-text-muted)">
            Semua artikel dalam kategori {{ $categoryName }}.
        </p>
        @endif

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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <h3 class="text-lg font-semibold mb-1">
                    Belum ada artikel
                </h3>
                <p class="text-sm text-(--color-text-muted) mb-4">
                    Belum ada artikel dalam kategori ini.
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
