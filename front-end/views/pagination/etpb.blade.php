@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="pager pager-etpb" role="navigation" aria-label="Pagination">
        <p class="pager-summary">
            @if ($paginator->firstItem())
                Showing
                <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
                of <strong>{{ number_format($paginator->total()) }}</strong>
            @else
                No records
            @endif
        </p>

        @if ($paginator->hasPages())
            <div class="pager-links">
                @if ($paginator->onFirstPage())
                    <span class="pager-link is-disabled" aria-disabled="true">Prev</span>
                @else
                    <a class="pager-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="pager-link is-disabled" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pager-link is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="pager-link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a class="pager-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pager-link is-disabled" aria-disabled="true">Next</span>
                @endif
            </div>
        @endif
    </nav>
@endif
