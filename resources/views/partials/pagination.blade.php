@if ($paginator->hasPages())
    <div class="pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination__btn" aria-disabled="true">Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__btn">Prev</a>
        @endif
        <span class="text-muted" style="padding:0 0.5rem;">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination__btn">Next</a>
        @else
            <span class="pagination__btn" aria-disabled="true">Next</span>
        @endif
    </div>
@endif
