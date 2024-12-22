<nav class="navigation">
              <ul class="pagination">
        @php
            $start = max(1, $updatedMangas->currentPage() - 2);
            $end = min($start + 4, $updatedMangas->lastPage());
        @endphp

        @if ($updatedMangas->currentPage() >= 4)
        <li class="page-item">
            <a class="page-link" href="{{ $updatedMangas->url(1) }}" rel="first">«</a>
        </li>
        <li class="page-item">
            <a class="page-link" href="{{ $updatedMangas->url($updatedMangas->currentPage() - 1) }}">‹</a>
        </li>
    @endif

        @for ($i = $start; $i <= $end; $i++)
          @if($updatedMangas->currentPage() == $i)
          <li class="page-item active" aria-current="page">
                  <span class="page-link">{{ $i }}</span>
          </li>
          @else
          <li class="page-item"><a class="page-link" href="{{ $updatedMangas->url($i) }}">{{ $i }}</a></li>
          @endif
        @endfor
        
        <li class="page-item {{ $updatedMangas->hasMorePages() ? '' : 'disabled' }}"> <a class="page-link" href="{{ $updatedMangas->nextPageUrl() }}" rel="next" aria-label="Next »">›</a> </li>
        <li class="page-item {{ $updatedMangas->currentPage() == $updatedMangas->lastPage() ? 'disabled' : '' }}">
          <a class="page-link" href="{{ $updatedMangas->url($updatedMangas->lastPage()) }}" rel="last">»</a>
      </li>

              </ul>
            </nav>