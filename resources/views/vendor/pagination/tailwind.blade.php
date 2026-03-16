@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-end gap-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="@lang('pagination.previous')"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-(--border-strong) text-(--muted) bg-(--pagination-bg) opacity-60 cursor-not-allowed select-none">
                <span class="text-xs md:text-sm">&lsaquo;</span>
                <span class="hidden sm:inline text-xs md:text-sm font-medium">Prev</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-(--border-strong) text-(--text) bg-(--pagination-bg) hover:bg-(--pagination-hover-bg) transition-colors">
                <span class="text-xs md:text-sm">&lsaquo;</span>
                <span class="hidden sm:inline text-xs md:text-sm font-medium">Prev</span>
            </a>
        @endif

        {{-- Pagination Elements --}}
        <div class="hidden sm:flex items-center gap-2">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="px-2 text-(--muted) select-none">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="inline-flex items-center justify-center min-w-10 h-10 px-3 rounded-xl border border-purple-500/40 bg-(--pagination-active-bg) text-white font-semibold select-none">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="@lang('Go to page :page', ['page' => $page])"
                                class="inline-flex items-center justify-center min-w-10 h-10 px-3 rounded-xl border border-(--border-strong) bg-(--pagination-bg) text-(--text) hover:bg-(--pagination-hover-bg) transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-(--border-strong) text-(--text) bg-(--pagination-bg) hover:bg-(--pagination-hover-bg) transition-colors">
                <span class="hidden sm:inline text-xs md:text-sm font-medium">Next</span>
                <span class="text-xs md:text-sm">&rsaquo;</span>
            </a>
        @else
            <span aria-disabled="true" aria-label="@lang('pagination.next')"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-(--border-strong) text-(--muted) bg-(--pagination-bg) opacity-60 cursor-not-allowed select-none">
                <span class="hidden sm:inline text-xs md:text-sm font-medium">Next</span>
                <span class="text-xs md:text-sm">&rsaquo;</span>
            </span>
        @endif
    </nav>
@endif

