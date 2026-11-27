@if($paginator->hasPages())
<nav aria-label="Pagination" class="pager">
    @if(!$paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr;</a>@endif
    @foreach($elements as $el)
        @if(is_string($el))<span>{{ $el }}</span>@endif
        @if(is_array($el))@foreach($el as $page => $url)
            @if($page == $paginator->currentPage())<span aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
        @endforeach @endif
    @endforeach
    @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">&rarr;</a>@endif
</nav>
@endif
