@php
    $pageStart = max(1, $paginator->currentPage() - 2);
    $pageEnd = min($paginator->lastPage(), $paginator->currentPage() + 2);
@endphp

<div class="admin-pagination-wrap">
    <span class="admin-pagination__summary">
        Mostrando
        <b>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</b>
        de <b>{{ $paginator->total() }}</b>
    </span>

    @if($paginator->hasPages())
        <nav class="admin-pagination" aria-label="Paginación">
            @if($paginator->onFirstPage())
                <span class="admin-pagination__arrow admin-pagination__disabled" aria-hidden="true">←</span>
            @else
                <a class="admin-pagination__arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">←</a>
            @endif

            @if($pageStart > 1)
                <a class="admin-pagination__page" href="{{ $paginator->url(1) }}" aria-label="Página 1">1</a>
                @if($pageStart > 2)
                    <span class="admin-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @foreach($paginator->getUrlRange($pageStart, $pageEnd) as $page => $url)
                @if($page === $paginator->currentPage())
                    <span class="admin-pagination__current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="admin-pagination__page" href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($pageEnd < $paginator->lastPage())
                @if($pageEnd < $paginator->lastPage() - 1)
                    <span class="admin-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="admin-pagination__page" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Página {{ $paginator->lastPage() }}">{{ $paginator->lastPage() }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a class="admin-pagination__arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">→</a>
            @else
                <span class="admin-pagination__arrow admin-pagination__disabled" aria-hidden="true">→</span>
            @endif
        </nav>
    @endif
</div>
