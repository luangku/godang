{{-- Post Card Component --}}
{{-- Usage: @include('partials.post-card', ['post' => $post]) --}}
@php
    $post =
        $post ??
        (object) [
            'title' => 'Judul Artikel Contoh',
            'slug' => 'artikel-contoh',
            'excerpt' => 'Ringkasan contoh untuk kartu artikel.',
            'cover_image' => null,
            'published_at' => now(),
            'reading_time' => 5,
            'category' => (object) ['name' => 'Laravel', 'slug' => 'laravel'],
            'tags' => collect([(object) ['name' => 'PHP', 'slug' => 'php']]),
        ];
@endphp

<article
    class="group bg-(--color-surface) rounded-xl border border-(--color-border) overflow-hidden hover:border-(--color-accent)/30 hover:shadow-lg hover:shadow-(--color-accent)/5 transition-all duration-300 hover:-translate-y-1"
    id="post-card-{{ $post->slug ?? 'unknown' }}">
    {{-- Cover Image --}}
    @if (!empty($post->cover_image))
        <a href="{{ url('/blog/' . $post->slug) }}" class="block aspect-[16/9] overflow-hidden">
            <img src="{{ method_exists($post, 'coverImageUrl') ? $post->coverImageUrl() : (str_starts_with($post->cover_image, 'http') ? $post->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($post->cover_image, '/'))) }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"
                width="640" height="360">
        </a>
    @else
        <a href="{{ url('/blog/' . $post->slug) }}"
            class="block aspect-[16/9] overflow-hidden bg-gradient-to-br from-(--color-accent-bg) to-(--color-surface)">
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-(--color-accent)/30" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
        </a>
    @endif

    {{-- Content --}}
    <div class="p-5">
        {{-- Category & Reading time --}}
        <div class="flex items-center gap-3 mb-3">
            @if (!empty($post->category))
                <a href="{{ url('/category/' . $post->category->slug) }}"
                    class="text-xs font-semibold uppercase tracking-wider text-(--color-accent) hover:underline">
                    {{ $post->category->name }}
                </a>
            @endif
            <span class="text-xs text-(--color-text-muted) flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $post->reading_time ?? 5 }} mnt baca
            </span>
        </div>

        {{-- Title --}}
        <h3
            class="text-lg font-bold leading-tight mb-2 group-hover:text-(--color-accent) transition-colors duration-200">
            <a href="{{ url('/blog/' . $post->slug) }}">
                {{ $post->title }}
            </a>
        </h3>

        {{-- Excerpt --}}
        <p class="text-sm text-(--color-text-muted) leading-relaxed mb-4 line-clamp-2">
            {{ $post->excerpt }}
        </p>

        {{-- Footer: date + tags --}}
        <div class="flex items-center justify-between">
            <time class="text-xs text-(--color-text-muted)" datetime="{{ $post->published_at }}">
                {{ \Carbon\Carbon::parse($post->published_at)->translatedFormat('d M Y') }}
            </time>
            @if (!empty($post->tags) && count($post->tags) > 0)
                <div class="flex gap-1.5 flex-wrap">
                    @foreach ($post->tags->take(3) as $tag)
                        <a href="{{ url('/tag/' . $tag->slug) }}"
                            class="text-xs px-2 py-0.5 rounded-md bg-(--color-accent-bg) text-(--color-accent) hover:bg-(--color-accent) hover:text-(--color-on-accent) transition-colors duration-200">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</article>
