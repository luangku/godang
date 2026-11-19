@extends('layouts.public')

@section('title', ($post->meta_title ?? $post->title) . ' — ' . ($site['site_title'] ?? config('app.name')))
@section('meta_description', $post->meta_description ?? $post->excerpt)
@section('canonical', url('/blog/' . $post->slug))
@section('og_type', 'article')
@section('og_title', $post->meta_title ?? $post->title)
@php
    $ogImage = null;
    if (method_exists($post, 'ogImageUrl')) {
        $ogImage = $post->ogImageUrl();
    }
    if (!$ogImage && method_exists($post, 'coverImageUrl')) {
        $ogImage = $post->coverImageUrl();
    }
    if (!$ogImage) {
        $value = $post->og_image ?: $post->cover_image;
        if ($value) {
            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                $ogImage = $value;
            } else {
                $path = ltrim($value, '/');
                if (str_starts_with($path, 'storage/')) {
                    $path = substr($path, strlen('storage/'));
                }
                $ogImage = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        }
    }
@endphp
@if ($ogImage)
    @section('og_image', $ogImage)
@endif

@section('hreflang')
    <link rel="alternate" hreflang="id" href="{{ url('/blog/' . $post->slug) }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/blog/' . $post->slug) }}">
@endsection

@section('structured_data')
    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ $post->title }}",
    "description": "{{ $post->meta_description ?? $post->excerpt }}",
    "datePublished": "{{ $post->published_at }}",
    "dateModified": "{{ $post->updated_at }}",
    @if($ogImage)
    "image": "{{ $ogImage }}",
    @endif
    "author": {
        "@type": "Person",
        "name": "{{ $post->author->name ?? ($site['author_name'] ?? config('app.name')) }}"
    },
    "publisher": {
        "@type": "Organization",
        "name": "{{ $site['site_title'] ?? config('app.name') }}"
    },
    "inLanguage": "id-ID",
    "wordCount": "{{ str_word_count(strip_tags($post->content ?? '')) }}"
}
</script>
@endsection

@section('content')
    @php
        $isAdmin = auth()->check() && (bool) auth()->user()->is_admin;
    @endphp

    {{-- Reading Progress Bar --}}
    <div class="reading-progress" id="reading-progress" style="width: 0%"></div>

    <article class="max-w-[var(--width-wide)] mx-auto px-4 sm:px-6 lg:px-8 py-12" id="article">
        <div class="flex gap-12">
            {{-- Main Content --}}
            <div class="flex-1 min-w-0">
                {{-- Breadcrumb --}}
                <nav class="flex items-center gap-2 text-sm text-(--color-text-muted) mb-8" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}" class="hover:text-(--color-accent) transition-colors">
                        Beranda
                    </a>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ url('/blog') }}" class="hover:text-(--color-accent) transition-colors">Blog</a>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-(--color-text) truncate">{{ $post->title }}</span>
                </nav>

                {{-- Article Header --}}
                <header class="mb-10">
                    {{-- Category --}}
                    @if ($post->category)
                        <a href="{{ url('/category/' . $post->category->slug) }}"
                            class="inline-block text-xs font-semibold uppercase tracking-wider text-(--color-accent) mb-4 hover:underline">
                            {{ $post->category->name }}
                        </a>
                    @endif

                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight mb-4">
                        {{ $post->title }}
                    </h1>

                    {{-- Meta --}}
                    <div class="flex flex-wrap items-center gap-4 text-sm text-(--color-text-muted) mb-6">
                        <time datetime="{{ $post->published_at }}">
                            {{ \Carbon\Carbon::parse($post->published_at)->translatedFormat('d F Y') }}
                        </time>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $post->reading_time ?? 5 }} menit baca
                        </span>
                    </div>

                    {{-- Tags --}}
                    @if (isset($post->tags) && count($post->tags) > 0)
                        <div class="flex flex-wrap gap-2 mb-6">
                            @foreach ($post->tags as $tag)
                                <a href="{{ url('/tag/' . $tag->slug) }}"
                                    class="text-xs px-2.5 py-1 rounded-md bg-(--color-accent-bg) text-(--color-accent) hover:bg-(--color-accent) hover:text-(--color-on-accent) transition-colors duration-200 font-medium">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Cover Image --}}
                    @if ($post->cover_image)
                        <figure class="mb-8">
                            <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->title }}" class="w-full rounded-xl"
                                fetchpriority="high" width="1200" height="630">
                        </figure>
                    @endif
                </header>

                {{-- Article Body --}}
                <div class="prose-article" id="article-content">
                    {!! $post->content !!}
                </div>

                {{-- Share & Copy Link --}}
                <div class="mt-12 pt-8 border-t border-(--color-border)" id="share-section">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm font-medium text-(--color-text-muted)">
                            Bagikan:
                        </span>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url('/blog/' . $post->slug)) }}&text={{ urlencode($post->title) }}"
                            target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:border-(--color-accent)/30 hover:text-(--color-accent) transition-all duration-200"
                            id="share-twitter">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                            Twitter
                        </a>
                        <button
                            onclick="navigator.clipboard.writeText(window.location.href).then(() => { this.textContent = '✓ Tersalin!'; setTimeout(() => { this.innerHTML = '<svg class=\'w-4 h-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3\'/></svg> Salin link'; }, 2000); })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:border-(--color-accent)/30 hover:text-(--color-accent) transition-all duration-200 cursor-pointer"
                            id="copy-link">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            Salin link
                        </button>
                    </div>
                </div>

                {{-- Related Posts --}}
                @if (isset($relatedPosts) && count($relatedPosts) > 0)
                    <section class="mt-16" id="related-posts">
                        <h2 class="text-2xl font-bold mb-6">
                            Artikel Terkait
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach ($relatedPosts->take(2) as $related)
                                @include('partials.post-card', ['post' => $related])
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Comments Section --}}
                <section class="mt-16" id="comments-section">
                    {{-- Comment Form --}}
                    <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-6 mb-4"
                        id="comment-form-wrapper">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <h3 class="text-lg font-semibold">
                                Tulis Komentar
                            </h3>
                            @if ($isAdmin)
                                <span
                                    class="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent) font-medium">
                                    Mode Admin — {{ auth()->user()->name }}
                                </span>
                            @endif
                        </div>
                        <form action="{{ url('/api/posts/' . $post->id . '/comments') }}" method="POST"
                            id="comment-form">
                            @csrf
                            {{-- Honeypot --}}
                            <div class="hidden" aria-hidden="true">
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </div>
                            <input type="hidden" name="parent_id" value="">

                            {{-- Status message (success / error) --}}
                            <div id="comment-status" class="hidden mb-4 rounded-lg px-4 py-3 text-sm" role="status"
                                aria-live="polite"></div>

                            @unless ($isAdmin)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="comment-name" class="block text-sm font-medium mb-1.5">
                                            Nama <span
                                                class="text-(--color-error)">*</span>
                                        </label>
                                        <input type="text" id="comment-name" name="name" required
                                            class="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none transition-all duration-200"
                                            placeholder="Nama kamu">
                                    </div>
                                    <div>
                                        <label for="comment-email" class="block text-sm font-medium mb-1.5">
                                            Email <span class="text-(--color-error)">*</span>
                                            <span
                                                class="text-xs text-(--color-text-muted) font-normal">(tidak ditampilkan)</span>
                                        </label>
                                        <input type="email" id="comment-email" name="email" required
                                            class="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none transition-all duration-200"
                                            placeholder="email@contoh.com">
                                    </div>
                                </div>
                            @endunless
                            <div class="mb-4">
                                <label for="comment-body" class="block text-sm font-medium mb-1.5">
                                    Komentar <span
                                        class="text-(--color-error)">*</span>
                                </label>
                                <textarea id="comment-body" name="body" rows="4" required
                                    class="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none transition-all duration-200 resize-y"
                                    placeholder="Tulis komentar atau pertanyaan..."></textarea>
                            </div>
                            {{--
                            Notifikasi balasan untuk pembaca (field notify_replies).
                            Checkbox "Kirim notifikasi jika ada balasan" sengaja disembunyikan, namun notifikasi
                            tetap terkirim otomatis karena input hidden di bawah selalu bernilai 1.

                            CARA MENAMPILKAN KEMBALI CHECKBOX:
                            1. Hapus input hidden "notify_replies" di bawah.
                            2. Hapus tanda buka dan tutup komentar Blade pada blok di bawah ini
                               (bagian div yang berisi checkbox), agar checkbox kembali tampil.
                        --}}
                            <input type="hidden" name="notify_replies" value="1">
                            {{-- <div class="mb-4 flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="comment-notify"
                                name="notify_replies"
                                value="1"
                                class="rounded border-(--color-border) text-(--color-accent) focus:ring-(--color-accent)"
                            >
                            <label for="comment-notify" class="text-sm text-(--color-text) flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4 text-(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A3.032 3.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                Kirim notifikasi jika ada balasan
                            </label>
                        </div> --}}
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-semibold text-sm hover:opacity-90 transition-all duration-200 cursor-pointer"
                                id="comment-submit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Kirim Komentar
                            </button>
                        </form>
                    </div>

                    {{-- Comments toolbar: toggle + sort --}}
                    <div class="flex items-center justify-between mb-6" id="comments-toolbar">
                        <button type="button" id="comments-toggle" aria-expanded="true" aria-controls="comments-list"
                            class="inline-flex items-center gap-2 text-lg font-semibold text-(--color-text) hover:text-(--color-accent) transition-colors duration-200 cursor-pointer">
                            Komentar
                            <span class="text-sm font-normal text-(--color-text-muted)">({{ $totalComments ?? 0 }})</span>
                            <svg id="comments-toggle-chevron" class="w-4 h-4 rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-1 text-xs" id="comments-sort">
                            <span
                                class="text-(--color-text-muted) mr-1">Urutkan:</span>
                            <button type="button" data-sort="desc"
                                class="comment-sort-btn px-2 py-1 rounded-md font-medium text-(--color-accent) bg-(--color-accent-bg) cursor-pointer">
                                Terbaru
                            </button>
                            <button type="button" data-sort="asc"
                                class="comment-sort-btn px-2 py-1 rounded-md font-medium text-(--color-text-muted) hover:text-(--color-text) cursor-pointer">
                                Terlama
                            </button>
                        </div>
                    </div>

                    {{-- Comments List --}}
                    <div class="space-y-6" id="comments-list">
                        @forelse($comments ?? [] as $comment)
                            <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-5 comment-card"
                                id="comment-{{ $comment->id }}"
                                data-created-at="{{ $comment->created_at->timestamp }}">
                                <div class="flex items-start gap-3">
                                    {{-- Avatar --}}
                                    @if (($comment->is_author ?? false) && ($comment->user?->avatar_url))
                                        <img src="{{ $comment->user->avatar_url }}"
                                            alt="{{ $comment->user->avatar_alt ?: $comment->name }}"
                                            class="w-10 h-10 rounded-full object-cover border border-(--color-border) flex-shrink-0">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-(--color-accent-bg) to-(--color-accent)/20 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-bold text-(--color-accent)">
                                                {{ strtoupper(substr($comment->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="font-semibold text-sm">{{ $comment->name }}</span>
                                            @if ($comment->is_author ?? false)
                                                <span
                                                    class="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent) font-medium">
                                                    Admin
                                                </span>
                                            @endif
                                            <time class="text-xs text-(--color-text-muted)"
                                                datetime="{{ $comment->created_at }}">
                                                {{ \Carbon\Carbon::parse($comment->created_at)->diffForHumans() }}
                                            </time>
                                        </div>
                                        <p class="comment-body text-sm text-(--color-text) leading-relaxed">{{ $comment->body }}</p>

                                        <div class="mt-3 flex flex-wrap items-center gap-3">
                                            <button type="button"
                                                class="reply-toggle inline-flex items-center gap-1.5 text-xs font-medium text-(--color-accent) hover:underline cursor-pointer"
                                                data-comment-id="{{ $comment->id }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                Balas
                                            </button>
                                            @if ($isAdmin)
                                                @if ($comment->is_author ?? false)
                                                    <button type="button"
                                                        class="comment-admin-edit inline-flex items-center gap-1 text-xs font-medium text-(--color-accent) hover:underline cursor-pointer"
                                                        data-comment-id="{{ $comment->id }}">
                                                        Edit
                                                    </button>
                                                @endif
                                                <button type="button"
                                                    class="comment-admin-spam inline-flex items-center gap-1 text-xs font-medium text-(--color-warning) hover:underline cursor-pointer"
                                                    data-comment-id="{{ $comment->id }}">
                                                    Spam
                                                </button>
                                                <button type="button"
                                                    class="comment-admin-delete inline-flex items-center gap-1 text-xs font-medium text-(--color-error) hover:underline cursor-pointer"
                                                    data-comment-id="{{ $comment->id }}">
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>

                                        <div class="reply-form-container hidden mt-4"
                                            id="reply-form-{{ $comment->id }}">
                                            <form action="{{ url('/api/posts/' . $post->id . '/comments') }}"
                                                method="POST"
                                                class="reply-form bg-(--color-bg) rounded-lg border border-(--color-border) p-4">
                                                @csrf
                                                <div class="hidden" aria-hidden="true">
                                                    <input type="text" name="website" tabindex="-1"
                                                        autocomplete="off">
                                                </div>
                                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">

                                                @unless ($isAdmin)
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                                        <div>
                                                            <label class="block text-xs font-medium mb-1">
                                                                Nama <span
                                                                    class="text-(--color-error)">*</span>
                                                            </label>
                                                            <input type="text" name="name" required
                                                                class="w-full px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium mb-1">
                                                                Email <span class="text-(--color-error)">*</span>
                                                                <span
                                                                    class="text-[10px] text-(--color-text-muted)">(tidak ditampilkan)</span>
                                                            </label>
                                                            <input type="email" name="email" required
                                                                class="w-full px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none">
                                                        </div>
                                                    </div>
                                                @endunless
                                                <div class="mb-3">
                                                    <label class="block text-xs font-medium mb-1">
                                                        Balasan <span
                                                            class="text-(--color-error)">*</span>
                                                    </label>
                                                    <textarea name="body" rows="3" required
                                                        class="w-full px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none resize-y"></textarea>
                                                </div>
                                                {{--
                                            Notifikasi balasan untuk pembaca (field notify_replies).
                                            Checkbox "Kirim notifikasi jika ada balasan" sengaja disembunyikan, namun notifikasi
                                            tetap terkirim otomatis karena input hidden di bawah selalu bernilai 1.

                                            CARA MENAMPILKAN KEMBALI CHECKBOX:
                                            1. Hapus input hidden "notify_replies" di bawah.
                                            2. Hapus tanda buka dan tutup komentar Blade pada blok di bawah ini
                                               (bagian div yang berisi checkbox), agar checkbox kembali tampil.
                                        --}}
                                                <input type="hidden" name="notify_replies" value="1">
                                                {{-- <div class="mb-3 flex items-center gap-2">
                                            <input type="checkbox" name="notify_replies" value="1" class="rounded border-(--color-border) text-(--color-accent) focus:ring-(--color-accent)">
                                            <label class="text-xs text-(--color-text) flex items-center gap-1.5 cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A3.032 3.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                                </svg>
                                                Kirim notifikasi jika ada balasan
                                            </label>
                                        </div> --}}
                                                <div class="flex items-center gap-2">
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-medium text-xs hover:opacity-90">
                                                        Kirim Balasan
                                                    </button>
                                                    <button type="button"
                                                        class="reply-cancel px-3 py-2 rounded-lg border border-(--color-border) text-xs text-(--color-text-muted) hover:text-(--color-text)">
                                                        Batal
                                                    </button>
                                                </div>
                                                <div class="reply-status hidden mt-2 text-xs"></div>
                                            </form>
                                        </div>

                                        {{-- Replies --}}
                                        @if (isset($comment->replies) && count($comment->replies) > 0)
                                            <div class="comments-replies">
                                                @include('partials.comment-thread', [
                                                    'replies' => $comment->replies,
                                                    'post' => $post,
                                                    'isAdmin' => $isAdmin,
                                                ])
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 mx-auto text-(--color-text-muted)/30 mb-3" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-sm text-(--color-text-muted)">
                                    Belum ada komentar. Jadilah yang pertama!
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- Sidebar: TOC (Desktop only) --}}
            <aside class="hidden xl:block w-64 flex-shrink-0" id="toc-sidebar">
                <div class="sticky top-24">
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-(--color-text-muted) mb-4">
                        Daftar Isi
                    </h4>
                    <nav class="space-y-1" id="toc-nav">
                        {{-- TOC items will be populated by JavaScript --}}
                    </nav>
                </div>
            </aside>
        </div>
    </article>

@endsection

@push('scripts')
    <script>
        // Reading progress bar
        window.addEventListener('scroll', function() {
            const article = document.getElementById('article-content');
            if (!article) return;
            const rect = article.getBoundingClientRect();
            const total = article.scrollHeight - window.innerHeight;
            const current = Math.max(0, -rect.top);
            const progress = Math.min(100, (current / total) * 100);
            document.getElementById('reading-progress').style.width = progress + '%';
        });

        // Generate TOC from headings
        document.addEventListener('DOMContentLoaded', function() {
            const content = document.getElementById('article-content');
            const tocNav = document.getElementById('toc-nav');
            if (!content || !tocNav) return;

            const headings = content.querySelectorAll('h2, h3');
            headings.forEach(function(heading, index) {
                const id = heading.id || 'heading-' + index;
                heading.id = id;

                // Add anchor link
                const anchor = document.createElement('a');
                anchor.href = '#' + id;
                anchor.className = 'heading-anchor';
                anchor.textContent = '#';
                anchor.setAttribute('aria-label', 'Link to this section');
                heading.appendChild(anchor);

                // Add TOC item
                const link = document.createElement('a');
                link.href = '#' + id;
                link.textContent = heading.textContent.replace('#', '');
                link.className =
                    'block text-sm py-1 text-(--color-text-muted) hover:text-(--color-accent) transition-colors duration-200 border-l-2 border-transparent hover:border-(--color-accent) ' +
                    (heading.tagName === 'H3' ? 'pl-6' : 'pl-3');
                tocNav.appendChild(link);
            });
        });

        // Comment form: submit via fetch so the reader stays on the page
        document.addEventListener('DOMContentLoaded', function() {
            // Comments list toggle (closed by default)
            const commentsToggle = document.getElementById('comments-toggle');
            const commentsList = document.getElementById('comments-list');
            const commentsChevron = document.getElementById('comments-toggle-chevron');

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[character]);

            const avatarMarkup = (comment, size = 'md') => {
                const dimension = size === 'sm' ? 'w-8 h-8' : 'w-10 h-10';
                const textSize = size === 'sm' ? 'text-xs' : 'text-sm';

                if (comment.is_author && comment.user && comment.user.avatar_url) {
                    return `<img src="${escapeHtml(comment.user.avatar_url)}" alt="${escapeHtml(comment.user.avatar_alt || comment.name || 'Avatar')}" class="${dimension} rounded-full object-cover border border-(--color-border) flex-shrink-0">`;
                }

                return `<div class="${dimension} rounded-full bg-gradient-to-br from-(--color-accent-bg) to-(--color-accent)/20 flex items-center justify-center flex-shrink-0">
                    <span class="${textSize} font-bold text-(--color-accent)">${escapeHtml((comment.name || '?').substring(0, 1).toUpperCase())}</span>
                </div>`;
            };

            @if ($isAdmin)
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const adminActions = (commentId, isAuthor = false) => `
                    <div class="mt-2 flex flex-wrap items-center gap-3 comment-admin-actions">
                        ${isAuthor ? `<button type="button" class="comment-admin-edit text-xs font-medium text-(--color-accent) hover:underline cursor-pointer" data-comment-id="${commentId}">Edit</button>` : ''}
                        <button type="button" class="comment-admin-spam text-xs font-medium text-(--color-warning) hover:underline cursor-pointer" data-comment-id="${commentId}">Spam</button>
                        <button type="button" class="comment-admin-delete text-xs font-medium text-(--color-error) hover:underline cursor-pointer" data-comment-id="${commentId}">Hapus</button>
                    </div>`;

                const removeCommentCard = (commentId) => {
                    const card = document.getElementById('comment-' + commentId);
                    if (!card) return;
                    const removed = 1 + card.querySelectorAll('[id^="comment-"]').length;
                    card.remove();
                    const countEl = document.querySelector('#comments-toggle span');
                    if (countEl) {
                        const current = parseInt(countEl.textContent.replace(/[()]/g, '')) || 0;
                        countEl.textContent = '(' + Math.max(0, current - removed) + ')';
                    }
                };

                const startCommentEdit = (button) => {
                    const card = button.closest('[id^="comment-"]');
                    const bodyEl = card?.querySelector('.comment-body');
                    if (!card || !bodyEl || card.querySelector('.comment-edit-form')) return;

                    const original = bodyEl.textContent;
                    const textarea = document.createElement('textarea');
                    textarea.className = 'comment-edit-input w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none resize-y';
                    textarea.rows = 3;
                    textarea.value = original;

                    const actions = document.createElement('div');
                    actions.className = 'comment-edit-form mt-2 flex items-center gap-2';

                    const saveButton = document.createElement('button');
                    saveButton.type = 'button';
                    saveButton.className = 'px-3 py-1.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-xs font-medium hover:opacity-90 disabled:opacity-60';
                    saveButton.textContent = 'Simpan';

                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'px-2.5 py-1.5 rounded-lg border border-(--color-border) text-xs text-(--color-text-muted) hover:text-(--color-text)';
                    cancelButton.textContent = 'Batal';

                    actions.append(saveButton, cancelButton);
                    bodyEl.replaceWith(textarea);
                    textarea.after(actions);
                    textarea.focus();

                    const closeEdit = () => {
                        actions.remove();
                        textarea.replaceWith(bodyEl);
                    };

                    cancelButton.addEventListener('click', closeEdit);

                    saveButton.addEventListener('click', async () => {
                        const body = textarea.value.trim();
                        if (!body) {
                            window.alert('Komentar tidak boleh kosong.');
                            return;
                        }

                        saveButton.disabled = true;

                        try {
                            const response = await fetch(`/api/admin/comments/${card.id.replace('comment-', '')}`, {
                                method: 'PUT',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ body }),
                            });

                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(data.message || 'Request failed');
                            }

                            bodyEl.textContent = data.comment?.body ?? body;
                            closeEdit();
                        } catch (error) {
                            window.alert('Gagal menyimpan komentar. Silakan coba lagi.');
                            saveButton.disabled = false;
                        }
                    });
                };

                commentsList?.addEventListener('click', async (event) => {
                    const editButton = event.target.closest('.comment-admin-edit');
                    if (editButton) {
                        startCommentEdit(editButton);
                        return;
                    }

                    const spamButton = event.target.closest('.comment-admin-spam');
                    const deleteButton = event.target.closest('.comment-admin-delete');
                    if (!spamButton && !deleteButton) return;

                    const button = spamButton || deleteButton;
                    const commentId = button.dataset.commentId;
                    if (!commentId) return;

                    const isSpam = Boolean(spamButton);
                    if (!isSpam && !window.confirm('Hapus komentar ini beserta balasannya?')) {
                        return;
                    }

                    button.disabled = true;

                    try {
                        const response = await fetch(
                            isSpam ? `/api/admin/comments/${commentId}/spam` :
                            `/api/admin/comments/${commentId}`, {
                                method: isSpam ? 'PATCH' : 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                            }
                        );

                        if (!response.ok) {
                            throw new Error('Request failed');
                        }

                        removeCommentCard(commentId);
                    } catch (error) {
                        window.alert('Gagal memproses komentar. Silakan coba lagi.');
                        button.disabled = false;
                    }
                });
            @else
                const adminActions = () => '';
            @endif

            function openCommentsList() {
                if (!commentsList) return;
                commentsList.classList.remove('hidden');
                if (commentsToggle) commentsToggle.setAttribute('aria-expanded', 'true');
                if (commentsChevron) commentsChevron.classList.add('rotate-180');
            }

            if (commentsToggle && commentsList) {
                commentsToggle.addEventListener('click', function() {
                    const isHidden = commentsList.classList.toggle('hidden');
                    this.setAttribute('aria-expanded', String(!isHidden));
                    commentsChevron?.classList.toggle('rotate-180', !isHidden);
                });
            }

            // Comments sorting (Terbaru default / Terlama)
            function sortComments(direction) {
                if (!commentsList) return;

                const cards = Array.from(commentsList.querySelectorAll(':scope > .comment-card'));
                cards.sort((a, b) => {
                    const aTime = Number(a.dataset.createdAt || 0);
                    const bTime = Number(b.dataset.createdAt || 0);
                    return direction === 'asc' ? aTime - bTime : bTime - aTime;
                });
                cards.forEach(card => commentsList.appendChild(card));

                cards.forEach(card => {
                    const repliesWrap = card.querySelector('.comments-replies');
                    if (!repliesWrap) return;
                    const replies = Array.from(repliesWrap.children);
                    replies.sort((a, b) => {
                        const aTime = Number(a.dataset.createdAt || 0);
                        const bTime = Number(b.dataset.createdAt || 0);
                        return direction === 'asc' ? aTime - bTime : bTime - aTime;
                    });
                    replies.forEach(reply => repliesWrap.appendChild(reply));
                });
            }

            document.querySelectorAll('.comment-sort-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const direction = this.dataset.sort;
                    document.querySelectorAll('.comment-sort-btn').forEach(b => {
                        const active = b === this;
                        b.classList.toggle('text-(--color-accent)', active);
                        b.classList.toggle('bg-(--color-accent-bg)', active);
                        b.classList.toggle('text-(--color-text-muted)', !active);
                    });
                    sortComments(direction);
                });
            });

            const form = document.getElementById('comment-form');
            const status = document.getElementById('comment-status');
            const submit = document.getElementById('comment-submit');
            if (form && status && submit) {
                const showStatus = (type, message) => {
                    status.textContent = message;
                    status.className = 'mb-4 rounded-lg px-4 py-3 text-sm border ' + (type === 'success' ?
                        'bg-(--color-accent-bg) border-(--color-success) text-(--color-success)' :
                        'bg-(--color-accent-bg) border-(--color-error) text-(--color-error)');
                    status.classList.remove('hidden');
                };

                function prependComment(comment) {
                    if (!commentsList) return;
                    const emptyState = commentsList.querySelector('.text-center');
                    if (emptyState) emptyState.remove();
                    const createdAt = comment.created_at ? new Date(comment.created_at).getTime() / 1000 : Math
                        .floor(Date.now() / 1000);
                    const card = document.createElement('div');
                    card.className =
                        'bg-(--color-surface) rounded-xl border border-(--color-border) p-5 comment-card';
                    card.id = 'comment-' + comment.id;
                    card.dataset.createdAt = createdAt;
                    card.innerHTML = `
                    <div class="flex items-start gap-3">
                        ${avatarMarkup(comment)}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-semibold text-sm">${escapeHtml(comment.name)}</span>
                                ${comment.is_author ? `<span class="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent) font-medium">Admin</span>` : ''}
                                <time class="text-xs text-(--color-text-muted)">Baru saja</time>
                            </div>
                            <p class="comment-body text-sm text-(--color-text) leading-relaxed">${escapeHtml(comment.body)}</p>
                            ${adminActions(comment.id, comment.is_author)}
                        </div>
                    </div>`;
                    commentsList.prepend(card);
                    const countEl = document.querySelector('#comments-toggle span');
                    if (countEl) {
                        const current = parseInt(countEl.textContent.replace(/[()]/g, '')) || 0;
                        countEl.textContent = '(' + (current + 1) + ')';
                    }
                }

                function addReplyToThread(parentId, reply) {
                    const parentCard = document.getElementById('comment-' + parentId);
                    if (!parentCard) return;
                    let repliesWrap = parentCard.querySelector('.comments-replies');
                    if (!repliesWrap) {
                        repliesWrap = document.createElement('div');
                        repliesWrap.className = 'comments-replies';
                        const commentBody = parentCard.querySelector('.flex-1');
                        if (commentBody) commentBody.appendChild(repliesWrap);
                    }
                    const createdAt = reply.created_at ? new Date(reply.created_at).getTime() / 1000 : Math.floor(
                        Date.now() / 1000);
                    const replyEl = document.createElement('div');
                    replyEl.className = 'mt-4 ml-4 pl-4 border-l-2 border-(--color-accent)/20';
                    replyEl.id = 'comment-' + reply.id;
                    replyEl.dataset.createdAt = createdAt;
                    replyEl.innerHTML = `
                    <div class="flex items-start gap-2">
                        ${avatarMarkup(reply, 'sm')}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-semibold text-sm">${escapeHtml(reply.name)}</span>
                                ${reply.is_author ? `<span class="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent) font-medium">Admin</span>` : ''}
                                ${reply.reply_to_name ? `<span class="text-xs text-(--color-text-muted)">membalas ke @${escapeHtml(reply.reply_to_name)}</span>` : ''}
                                <time class="text-xs text-(--color-text-muted)">Baru saja</time>
                            </div>
                            <p class="comment-body text-sm text-(--color-text) leading-relaxed">${escapeHtml(reply.body)}</p>
                            ${adminActions(reply.id, reply.is_author)}
                        </div>
                    </div>`;
                    repliesWrap.prepend(replyEl);
                }

                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    status.classList.add('hidden');
                    submit.disabled = true;
                    submit.classList.add('opacity-60', 'cursor-wait');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (response.ok) {
                            showStatus('success', data.message ||
                                'Komentar berhasil dikirim dan menunggu persetujuan.'
                                );
                            const parentInput = form.querySelector('input[name="parent_id"]');
                            const parentId = parentInput ? parentInput.value : '';
                            form.reset();
                            if (parentInput) parentInput.value = '';
                            openCommentsList();
                            if (parentId && data.comment) {
                                addReplyToThread(parentId, data.comment);
                            } else if (data.comment && data.comment.status === 'approved') {
                                prependComment(data.comment);
                            }
                        } else {
                            const errors = data.errors ? Object.values(data.errors).flat() : [];
                            showStatus('error', errors[0] || data.message ||
                                'Gagal mengirim komentar. Silakan coba lagi.'
                                );
                        }
                    } catch (error) {
                        showStatus('error',
                            'Gagal mengirim komentar. Silakan coba lagi.'
                            );
                    } finally {
                        submit.disabled = false;
                        submit.classList.remove('opacity-60', 'cursor-wait');
                    }
                });
            }

            // Reply toggles
            document.querySelectorAll('.reply-toggle').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.commentId;
                    const container = document.getElementById('reply-form-' + id);
                    if (container) {
                        container.classList.toggle('hidden');
                    }
                });
            });

            document.querySelectorAll('.reply-cancel').forEach(btn => {
                btn.addEventListener('click', function() {
                    const form = this.closest('.reply-form');
                    if (form) {
                        form.closest('.reply-form-container')?.classList.add('hidden');
                        form.querySelector('.reply-status')?.classList.add('hidden');
                    }
                });
            });

            // Reply form submissions
            document.querySelectorAll('.reply-form').forEach(form => {
                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const status = form.querySelector('.reply-status');
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (status) {
                        status.classList.add('hidden');
                        status.textContent = '';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-60');
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (response.ok) {
                            if (status) {
                                status.textContent = data.message ||
                                    'Balasan berhasil dikirim dan menunggu persetujuan.';
                                status.className =
                                    'reply-status mt-2 text-xs text-(--color-success)';
                                status.classList.remove('hidden');
                            }
                            const parentInputReply = form.querySelector(
                                'input[name="parent_id"]');
                            const parentIdReply = parentInputReply ? parentInputReply.value :
                            '';
                            form.reset();
                            if (parentIdReply && data.comment && data.comment.status ===
                                'approved') {
                                addReplyToThread(parentIdReply, data.comment);
                            }
                        } else {
                            const errors = data.errors ? Object.values(data.errors).flat() : [];
                            if (status) {
                                status.textContent = errors[0] || data.message ||
                                    'Gagal mengirim balasan.';
                                status.className =
                                    'reply-status mt-2 text-xs text-(--color-error)';
                                status.classList.remove('hidden');
                            }
                        }
                    } catch (error) {
                        if (status) {
                            status.textContent =
                                'Gagal mengirim balasan.';
                            status.className = 'reply-status mt-2 text-xs text-(--color-error)';
                            status.classList.remove('hidden');
                        }
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-60');
                        }
                    }
                });
            });
        });
    </script>
@endpush
