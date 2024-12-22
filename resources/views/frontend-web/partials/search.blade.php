<div id="nav-search">
 <div class="search-inner">
  <form id="searchForm" action="{{ route('mangas.filter') }}" autocomplete="off">
   <i class="fa-regular fa-magnifying-glass text-muted mr-1"></i>
   <input type="text" placeholder="Tìm kiếm truyện..." name="keyword" />
   <a id="search-btn" href="{{ route('mangas.filter') }}" class="btn btn-primary2">
    <i class="fa-regular fa-circles-overlap fa-xs"></i>
    <span>Tìm kiếm</span>
   </a>
   <br>
  </form>
  <div class="suggestion">
   <div class="original card-sm body"></div>
   <div>
    <a id="filter-button" class="btn btn-primary w-100">
     <span>Xem tất cả kết quả</span>
     <i class="fa-solid fa-chevron-right fa-xs"></i>
    </a>
   </div>
  </div>
 </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
    var $navSearch = $('#nav-search');
    var $navSearchForm = $('#nav-search form');
    var $main = $('main');
    var $filterButton = $('#filter-button');

    $('#nav-search-btn').click(function(e) {
        $navSearch.addClass('active');

        var $link = $('#nav-search #search-btn'); 
        var $icon = $link.find('i'); 
        var $span = $link.find('span'); 

        var originalClasses = $link.attr('class');
        var originalHref = $link.attr('href');
        var originalIconClasses = $icon.attr('class');
        var originalSpanText = $span.text();
        
        $link.removeClass('btn-primary2').addClass('btn-light').removeAttr('href');
        $icon.removeClass('fa-regular fa-circles-overlap fa-xs').addClass('fa-regular fa-close fa-xs');
        $span.text("Đóng");

        $link.off('click').click(function(e) {
        e.preventDefault();

        $link.attr('class', originalClasses);
        if (originalHref) {
            $link.attr('href', originalHref);
        }
        $icon.attr('class', originalIconClasses);
        $span.text(originalSpanText);
        $navSearch.removeClass('active');
        });
    });

    // Click outside hides the search bar
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#nav-search').length) {
            $navSearch.removeClass('active');
            $('#nav-search .suggestion').hide(); // Ẩn gợi ý khi ẩn thanh tìm kiếm
        }
    });

    // Prevent hiding when clicking inside the search bar
    $('#nav-search, #nav-search-btn').click(function(e) {
        e.stopPropagation();
    });
    
//tìm kiếm ajax
$('#nav-search form input').on('input', function() {
    var keyword = $(this).val().trim(); // Lấy giá trị của input và loại bỏ khoảng trắng ở đầu và cuối

    if (keyword !== '') {
        $('#nav-search .suggestion').slideDown(200);
        $.ajax({
            url: "{{ route('ajax.search') }}",
            method: 'GET',
            data: {
                keyword: keyword
            },
            dataType: 'json',
            success: function(response) {
                var resultsDropdown = $('#nav-search .suggestion .body');
                let html = ``;
                if (response && response.length > 0) {
                    $.each(response, function(index, manga) {
                        html += `<a class="unit" href="/truyen/${manga.slug}">
                  <div class="poster"> <div> <img src="${manga.cover}"> 
                    </div> 
                  </div>
                   <div class="info"> <h6>${manga.title}</h6> <div> 
                    <span>${manga.statuses[0].name}</span> 
                    <span>Chapter ${manga.chapters_max_chapter_number}</span>
                   </div> 
                  </div> 
                </a>`;
                    });
                } else {
                    html = `<p class="text-center p-3">Không có kết quả!</p>`; 
                }
                resultsDropdown.html(html)
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    } else {
        $('#nav-search .suggestion').hide();
    }

});

document.getElementById('filter-button').addEventListener('click', function() {
    var form = document.getElementById('searchForm');
    form.submit();
});

})
</script>