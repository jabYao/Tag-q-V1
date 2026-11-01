@if ($paginator->hasPages())
    <nav class="catalog-pager" role="navigation" aria-label="Paginación del catálogo">
        <p class="catalog-pager__summary">
            Página <b>{{ $paginator->currentPage() }}</b> de <b>{{ $paginator->lastPage() }}</b>
        </p>

        <div class="catalog-pager__pages">
            @if ($paginator->onFirstPage())
                <span class="catalog-pager__edge is-disabled" aria-disabled="true" aria-label="Página anterior">←</span>
            @else
                <a class="catalog-pager__edge" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">←</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="catalog-pager__ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="catalog-pager__page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="catalog-pager__page" href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="catalog-pager__edge" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">→</a>
            @else
                <span class="catalog-pager__edge is-disabled" aria-disabled="true" aria-label="Página siguiente">→</span>
            @endif
        </div>
    </nav>
@endif
