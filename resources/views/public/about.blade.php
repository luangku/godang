@extends('layouts.public')

@php
    $authorName = ($site['author_name'] ?? null) ?: ($site['site_title'] ?? config('app.name', 'Godang'));
    $aboutDescription = ($site['about_meta_description'] ?? null)
        ?: 'Tentang penulis blog ini.';
    $socialLinks = array_values(array_filter([
        $site['github_url'] ?? null,
        $site['twitter_url'] ?? null,
        $site['linkedin_url'] ?? null,
    ]));
    $personSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $authorName,
        'jobTitle' => $site['author_role'] ?? null,
        'description' => $site['about_bio'] ?? null,
        'image' => $site['author_avatar_url'] ?? null,
        'url' => url()->current(),
        'sameAs' => $socialLinks,
    ], fn ($value) => $value !== null && $value !== '' && $value !== []);
@endphp

@section('title', 'Tentang — ' . ($site['site_title'] ?? config('app.name')))
@section('meta_description', $aboutDescription)

@if (! empty($site['author_avatar_url']))
    @section('og_image', $site['author_avatar_url'])
@endif

@section('structured_data')
<script type="application/ld+json">
{!! json_encode($personSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endsection

@section('content')
<div class="max-w-[var(--width-content)] mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="animate-fade-in-up">
        {{-- Avatar & Name --}}
        <div class="flex flex-col items-center text-center mb-12">
            @if (! empty($site['author_avatar_url']))
                <img
                    src="{{ $site['author_avatar_url'] }}"
                    alt="{{ $site['author_avatar_alt'] ?: $authorName }}"
                    class="w-28 h-28 rounded-full object-cover mb-6 ring-4 ring-(--color-accent-bg)"
                >
            @else
                <div class="w-28 h-28 rounded-full bg-gradient-to-br from-(--color-accent) to-purple-500 flex items-center justify-center mb-6 ring-4 ring-(--color-accent-bg)">
                    <span class="text-4xl font-bold text-white">{{ $site['author_initial'] ?? 'G' }}</span>
                </div>
            @endif
            <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $authorName }}</h1>
            <p class="text-(--color-accent) font-medium">
                {{ $site['author_role'] ?? '' }}
            </p>
        </div>

        {{-- Bio --}}
        <div class="prose-article mx-auto">
            @foreach (preg_split('/\R{2,}/', trim((string) ($site['about_bio'] ?? ''))) as $paragraph)
                @if (trim($paragraph) !== '')
                    <p>{{ $paragraph }}</p>
                @endif
            @endforeach

            @if (! empty($site['about_topics_heading']) || ! empty($site['about_topics']))
                <h2>{{ $site['about_topics_heading'] ?? '' }}</h2>
                <ul>
                    @foreach (preg_split('/\R/', trim((string) ($site['about_topics'] ?? ''))) as $topic)
                        @if (trim($topic) !== '')
                            <li>{{ trim($topic) }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif

            @if (! empty($site['about_contact_heading']) || ! empty($site['about_contact']))
                <h2>{{ $site['about_contact_heading'] ?? '' }}</h2>
                @if (! empty($site['about_contact']))
                    <p>{{ $site['about_contact'] }}</p>
                @endif
            @endif
        </div>

        {{-- Social Links --}}
        @if ($socialLinks !== [])
        <div class="flex justify-center gap-4 mt-10">
            @if (! empty($site['github_url']))
            <a href="{{ $site['github_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:border-(--color-accent)/30 hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200" id="about-github">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                GitHub
            </a>
            @endif
            @if (! empty($site['twitter_url']))
            <a href="{{ $site['twitter_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:border-(--color-accent)/30 hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200" id="about-twitter">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                Twitter
            </a>
            @endif
            @if (! empty($site['linkedin_url']))
            <a href="{{ $site['linkedin_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-(--color-border) text-sm text-(--color-text-muted) hover:border-(--color-accent)/30 hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200" id="about-linkedin">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
