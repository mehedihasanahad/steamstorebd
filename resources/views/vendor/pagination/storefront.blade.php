@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3">
        <p class="text-meta text-ink-low">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="flex min-h-[36px] items-center rounded-control border border-surface-3 px-3 text-caption text-ink-low opacity-50">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="flex min-h-[36px] items-center rounded-control border border-surface-3 px-3 text-caption text-ink-mid transition-colors hover:border-accent/50 hover:text-ink-hi">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-caption text-ink-low">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="flex min-h-[36px] min-w-[36px] items-center justify-center rounded-control bg-accent px-2 text-caption font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="flex min-h-[36px] min-w-[36px] items-center justify-center rounded-control border border-surface-3 px-2 text-caption text-ink-mid transition-colors hover:border-accent/50 hover:text-ink-hi">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="flex min-h-[36px] items-center rounded-control border border-surface-3 px-3 text-caption text-ink-mid transition-colors hover:border-accent/50 hover:text-ink-hi">Next</a>
            @else
                <span class="flex min-h-[36px] items-center rounded-control border border-surface-3 px-3 text-caption text-ink-low opacity-50">Next</span>
            @endif
        </div>
    </nav>
@endif
