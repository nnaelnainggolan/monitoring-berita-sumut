@if($paginator->hasPages())
<nav class="pagination-bar" role="navigation" aria-label="Halaman daftar berita">
    <p>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ number_format($paginator->total(), 0, ',', '.') }} berita</p>
    <div class="pagination-links">
        @if($paginator->onFirstPage())<span class="pagination-control" aria-disabled="true" aria-label="Halaman sebelumnya"><x-icon name="chevron" class="rotate-90"/></span>@else<a class="pagination-control" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><x-icon name="chevron" class="rotate-90"/></a>@endif
        @foreach($elements as $element)
            @if(is_string($element))<span class="pagination-ellipsis">{{ $element }}</span>@endif
            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())<span class="pagination-control is-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>@else<a class="pagination-control pagination-number" href="{{ $url }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())<a class="pagination-control" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><x-icon name="chevron" class="-rotate-90"/></a>@else<span class="pagination-control" aria-disabled="true" aria-label="Halaman berikutnya"><x-icon name="chevron" class="-rotate-90"/></span>@endif
    </div>
</nav>
@endif
