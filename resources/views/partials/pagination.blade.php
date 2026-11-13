{{-- Custom Pagination --}}
@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" class="flex items-center justify-center gap-1">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm text-(--color-text-muted)/40 cursor-not-allowed" aria-disabled="true">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
           class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm text-(--color-text-muted) hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200"
           aria-label="Previous page">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
    @endif

    {{-- Page Numbers --}}
    @foreach ($elements as $element)
        {{-- Dots --}}
        @if (is_string($element))
            <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-(--color-text-muted)" aria-disabled="true">
                {{ $element }}
            </span>
        @endif

        {{-- Links --}}
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-semibold bg-(--color-accent) text-(--color-on-accent)" aria-current="page">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $url }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-medium text-(--color-text-muted) hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200">
                        {{ $page }}
                    </a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
           class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm text-(--color-text-muted) hover:text-(--color-accent) hover:bg-(--color-accent-bg) transition-all duration-200"
           aria-label="Next page">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @else
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm text-(--color-text-muted)/40 cursor-not-allowed" aria-disabled="true">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
    @endif
</nav>
@endif
