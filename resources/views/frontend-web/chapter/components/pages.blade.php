   <div class="pages longstrip" dir="ltr">
        @php 
            $server1Pages = $currentChapter->content;
        @endphp
        @foreach($server1Pages as $index => $page)
        <div class="page fit-w">
            <div class="img">
                <img
                    loading="lazy"
                    decoding="async"
                    data-number="{{ $index + 1 }}"
                    class="fit-w"
                    src="{{ $page }}"
                />
            </div>
        </div>
        @endforeach
        
        <div class="number-nav ltr" style="flex-direction: row; justify-content: center; align-items: center; gap: 20px;">
    @if ($previousChapter)
    <a class="next" href="{{ route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $previousChapter->chapter_number]) }}">
        <i class="ltr-icon fa-light fa-arrow-left mr-1"></i>
        <i class="rtl-icon fa-light fa-arrow-right ml-1"></i>
        Chapter trước
    </a>
    @endif

    @if ($nextChapter)
    <a class="next" href="{{ route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $nextChapter->chapter_number]) }}">
        Chapter tiếp theo
        <i class="ltr-icon fa-light fa-arrow-right ml-1"></i>
        <i class="rtl-icon fa-light fa-arrow-left mr-1"></i>
    </a>
    @endif
</div>
    </div>
    
    <div class="body p-3">
            <h4>Bình luận</h4>
        <!-- bình luận nè -->
         <livewire:comments :manga="$manga" />
         <br><br><br>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
     const lazyImages = document.querySelectorAll('img[loading="lazy"]');
     lazyImages.forEach(img => {
      img.addEventListener("load", function() {
       const parentDiv = img.closest('.img');
       if (parentDiv) {
        parentDiv.classList.add('loaded');
       }
      });
     });
    });
   </script>



