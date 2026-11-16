@if ($paginator->hasPages())
    <nav aria-label="{{ $paginationLabel ?? 'Daily Discover pagination' }}" class="flex flex-col items-center gap-4 rounded-xl border border-line bg-white p-4 sm:p-5">
        <p class="text-center text-xs text-charcoal/60 sm:text-sm">
            Showing <span class="font-semibold text-charcoal">{{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}</span>
            of <span class="font-semibold text-charcoal">{{ number_format($paginator->total()) }}</span> {{ $itemLabel ?? 'products' }}
        </p>

        <div class="grid w-full grid-cols-[1fr_auto_1fr] items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex min-h-10 items-center justify-self-start gap-1 rounded-lg border border-line px-3 text-sm font-semibold text-charcoal/30">
                    <span aria-hidden="true">&larr;</span> Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-10 items-center justify-self-start gap-1 rounded-lg border border-line px-3 text-sm font-semibold text-charcoal transition-colors hover:border-primary hover:bg-ice-blue hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    <span aria-hidden="true">&larr;</span> Previous
                </a>
            @endif

            <span class="px-1 text-xs font-semibold tabular-nums text-charcoal/60 md:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            <div class="hidden items-center gap-1 md:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-sm text-charcoal/40" aria-hidden="true">{{ $element }}</span>
                    @elseif (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" aria-label="Page {{ $page }}" class="flex size-10 items-center justify-center rounded-lg bg-primary text-sm font-bold tabular-nums text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="flex size-10 items-center justify-center rounded-lg border border-line text-sm font-semibold tabular-nums text-charcoal transition-colors hover:border-primary hover:bg-ice-blue hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-10 items-center justify-self-end gap-1 rounded-lg border border-primary bg-primary px-3 text-sm font-semibold text-white transition-colors hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    Next <span aria-hidden="true">&rarr;</span>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex min-h-10 items-center justify-self-end gap-1 rounded-lg border border-line px-3 text-sm font-semibold text-charcoal/30">
                    Next <span aria-hidden="true">&rarr;</span>
                </span>
            @endif
        </div>
    </nav>
@endif
