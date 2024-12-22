@php $totalPages = count($currentChapter->content); @endphp
 <!DOCTYPE html>
 <html>
  <head> 
    @include('frontend-web.partials.seo')
   <link rel="stylesheet" type="text/css" href="{{ asset('frontend-web/css/swiper.min.css') }}" />
   <link rel="stylesheet" type="text/css" href="{{ asset('/frontend-web/css/tooltipster.bundle.min.css')}}" />
   <link rel="preconnect" href="https://fonts.googleapis.com" />
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
   <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,700;1,600&amp;display=swap" rel="stylesheet" />
   <link href="{{ asset('frontend-web/css/fontawesome.min.css') }}" rel="stylesheet" />
   <link href="{{ asset('frontend-web/css/solid.min.css') }}" rel="stylesheet" />
   <link href="{{ asset('frontend-web/css/regular.min.css') }}" rel="stylesheet" />
   <link href="{{ asset('frontend-web/css/brands.min.css') }}" rel="stylesheet" />
   <link href="{{ asset('frontend-web/css/all.css') }}" rel="stylesheet" />
   <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

   <script type="text/javascript" src="{{ asset('frontend-web/js/jquery.min.js') }}"></script>
   <script type="text/javascript" src="{{ asset('frontend-web/js/bootstrap.bundle.min.js') }}"></script>
   <script type="text/javascript" src="{{ asset('frontend-web/js/tooltipster.bundle.min.js') }}"></script>
   <script type="text/javascript" src="{{ asset('frontend-web/js/swiper.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src='https://www.google.com/recaptcha/api.js' async defer ></script>
  </head>

  <style>
   @media (min-width: 768px) {
    body {
     overflow: hidden;
    }
   }
  </style>

  <body class="read">
   <span class="bg"></span>
   <div class="wrapper"> 
    @include('frontend-web.chapter.components.header')
     <div class="st-placement standard_1 inTop" id="standard_1" style="direction: ltr; all: initial !important">
     <style>
      #standard_1.st-placement .st-reset {
       all: unset;
       all: initial;
       max-width: unset !important;
       max-height: unset !important;
       position: absolute;
       z-index: 2147483647;
      }

      #standard_1.st-placement style {
       display: none !important;
      }

      #standard_1.st-placement script {
       display: none !important;
      }

      .st-adunit-intop {
       z-index: 9999999999 !important;
      }
     </style>
    </div>
    <main class="longstrip">
     <div class="m-content">
      <div id="page-wrapper"> 

        @include('frontend-web.chapter.components.pages') 

    </div>
      <script type="text/template" id="number-nav"> <div class="number-nav">
								<a class="prev" href="javascript:;">
									<i class="ltr-icon fa-light fa-arrow-left mr-1"></i>
									<i class="rtl-icon fa-light fa-arrow-right ml-1"></i>
                                    Previous VIEW_TYPE
                                
								</a>
								<a class="next" href="javascript:;">
                                    Next VIEW_TYPE
                                    
									<i class="ltr-icon fa-light fa-arrow-right ml-1"></i>
									<i class="rtl-icon fa-light fa-arrow-left mr-1"></i>
								</a>
							</div>
						</script>
      <div id="progress-bar" class="bottom ltr">
       <div>
        <p>0</p>
        <ul> @for($index = 0; $index < count($currentChapter->content); $index ++) <li data-page="{{ $index }}" data-name="{{ $index }}">
           <div>{{ $index }}</div>
          </li> @endfor </ul>
        <p class="total-page">
         {{ count($currentChapter->content) }}
        </p>
       </div>
      </div>
      <div class="sub-panel scroll-sm" id="number-panel">
       <div class="head">
        <form autocomplete="off" onsubmit="return false">
         <div class="form-group">
          <i class="fa-regular fa-magnifying-glass"></i>
          <input type="text" class="form-control" placeholder="Tên chapter..." />
         </div>
        </form>
        <button class="close-primary btn btn-secondary1" id="number-close">
         <i class="fa-solid fa-chevron-right"></i>
        </button>
       </div>
       <ul> @foreach($chapters as $chapter) <li>
         <a href="{{ route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $chapter->chapter_number]) }}" data-number="{{ $chapter->chapter_number }}" data-id="" title="" class="@if($currentChapter->chapter_number == $chapter->chapter_number)
                                        active @endif">Chap {{ $chapter->chapter_number }}</a>
        </li> @endforeach </ul>
      </div>
      <div class="sub-panel scroll-sm" id="page-panel">
       <div class="head">
        <span></span>
        <button class="close-primary btn btn-secondary1" id="page-close">
         <i class="fa-solid fa-chevron-right"></i>
        </button>
       </div>
       <ul id="page-items"> @for($index = 0; $index <= $totalPages; $index ++) <li>
         <a data-page="{{ $index }}" data-name="{{ $index }}" href="#" class="">Hình {{ $index }}</a>
         </li> @endfor </ul>
      </div>

     </div>
     <div id="ctrl-menu">
      <div class="head">
       <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}">{{ $manga->title }}</a>
       <div class="close-primary btn btn-secondary1 tooltipz" id="ctrl-menu-close" title="" data-original-title="Use M button to toggle">
        <i class="fa-solid fa-chevron-right"></i>
       </div>
      </div>
      <br />
      <nav>
       <button id="page-go-left">
        <i class="fa-regular fa-chevron-left"></i>
       </button>
       <button class="page-toggler">
        <b>Hình <span class="current-page">0</span>
        </b>
        <i class="fa-solid fa-sort fa-sm"></i>
       </button>
       <button id="page-go-right">
        <i class="fa-regular fa-chevron-right"></i>
       </button>
      </nav>

    <nav>
    <button id="number-go-left" 
            data-url="{{ $previousChapter ? route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $previousChapter->chapter_number]) : '#' }}"
            @if(!$previousChapter) disabled @endif>
        <i class="fa-regular fa-chevron-left"></i>
    </button>
    <button class="number-toggler">
        <b class="current-type-number text-title">Chapter {{ $currentChapter->chapter_number }}</b>
        <i class="fa-solid fa-sort fa-sm"></i>
    </button>
    <button id="number-go-right" 
            data-url="{{ $nextChapter ? route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $nextChapter->chapter_number]) : '#' }}"
            @if(!$nextChapter) disabled @endif>
        <i class="fa-regular fa-chevron-right"></i>
    </button>
    </nav>

      <div class="dropdown bookmark" data-id="42" data-fetch="true">
       <button class="jb-btn btn" type="button" data-toggle="dropdown" data-placeholder="false" aria-expanded="false" data-add='
								<i class="fa-light fa-folder-bookmark fa-lg"></i>
								<span>Bookmark</span>' data-edit='
								<i class="fa-light fa-folder-bookmark fa-lg"></i>
								<span>Edit Bookmark</span>'>
        <i class="fa-light fa-folder-bookmark fa-lg"></i>
        <span>Lưu truyện</span>
       </button>
       <div class="dropdown-menu dropdown-menu-right folders" style="display: none;">
                        @auth
                        @php
                          $bookmark = auth()->user()->bookmarks()->where('bookmarkable_id', $manga->id)->first();
                        @endphp
                        <a class="dropdown-item {{ $bookmark && $bookmark->status == 'reading' ? 'active' : '' }}" href="#" data-action="reading">Đang đọc</a>
                        <a class="dropdown-item {{ $bookmark && $bookmark->status == 'completed' ? 'active' : '' }}" href="#" data-action="completed">Đã đọc xong</a>
                        <a class="dropdown-item {{ $bookmark && $bookmark->status == 'plan-to-read' ? 'active' : '' }}" href="#" data-action="plan-to-read">Dự tính đọc</a>
                        <a class="dropdown-item remove-bookmark" href="#" data-action="delete" data-id="0" style="{{ $bookmark ? '' : 'display: none;' }}">
                            <i class="fa-solid fa-xmark"></i> Xóa
                        </a>
                        @endauth
      </div>
      </div>
      <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}" class="jb-btn">
       <i class="fa-light fa-lg fa-circle-info"></i>
       <span>Chi tiết truyện</span>
      </a>
      <button class="jb-btn" data-toggle="modal" data-target="#report">
       <i class="fa-light fa-lg fa-triangle-exclamation"></i>
       <span>Báo cáo lỗi</span>
      </button>
      <hr />
      
      @include('frontend-web.partials.social-buttons')
      
     </div>
    </main>
   
   <div class="modal fade" id="report">
    <div class="modal-dialog limit-w modal-dialog-centered">
     <div class="modal-content p-4">
      <div class="modal-close" data-dismiss="modal">
       <i class="fa-solid fa-xmark"></i>
      </div>
      <h5 class="text-white mb-0">Báo cáo lỗi</h5>
      <div class="mt-2">
       <div>
        <b>{{ $manga->title }}</b>
       </div>
       <div class="text-primary current-type-number text-title"></div>
      </div>
      <form class="ajax-report-chapter mt-3" action="{{ route('chapter.report') }}">
       <input type="hidden" name="chapter_id" value="{{ $currentChapter->id }}"/>
       <div class="form-group mt-4 mb-2">
        <textarea class="form-control" name="report_message" rows="3" placeholder="Mô tả lỗi"></textarea>
       </div>
       <button class="submit btn mt-3 btn-lg btn-primary w-100"> Gửi <i class="fa-solid fa-chevron-right fa-xs"></i>
       </button>
      </form>
     </div>
    </div>
   </div>
 
 <script>
  $(document).ready(function() {
    function adjustMenuClass() {
      var windowWidth = $(window).width();
      $("body").toggleClass("ctrl-menu-active", windowWidth >= 768);
      $('#ctrl-menu').toggleClass('active', windowWidth >= 768);
      if (windowWidth < 768) {
        $("#page-panel, #number-panel").removeClass("active");
      }
    }

    // Toggle ctrl-menu and panels
    $("#show-ctrl-menu, #ctrl-menu-close").click(function() {
      $("body").toggleClass("ctrl-menu-active");
      $("#ctrl-menu").toggleClass("active");
      $("#page-panel, #number-panel").removeClass("active");
    });

    // Toggle page panel
    $(".page-toggler, #page-close").click(function() {
      $("#page-panel").toggleClass("active");
    });

    // Toggle number panel
    $(".number-toggler, #number-close").click(function() {
      $("#number-panel").toggleClass("active");
    });

    // Toggle comment panel
    $("#comment-toggler, #comment-close").click(function() {
      $("#comment-panel").toggleClass("active");
    });

    var imageFitCount = 1;
    $('.btn-options[data-name="image_fit"]').on('click', function() {
      var $divs = $(this).find('div');
      $divs.hide().eq(imageFitCount - 1).show();
      imageFitCount = imageFitCount % $divs.length + 1;
    });

    $('#number-go-left, #number-go-right').on('click', function() {
      window.location.href = $(this).data('url');
    });
  });
</script>
   
   <script>
    document.addEventListener('DOMContentLoaded', function() {
     const images = document.querySelectorAll('.page img');
     var totalPages = "{{ count($currentChapter->content) }}";
     var currentPage = 1;

     function updatePage(pageNumber) {
      const targetImg = Array.from(images).find(img => img.dataset.number == pageNumber);
      if (targetImg) {
       currentPage = pageNumber;
       targetImg.scrollIntoView({
        behavior: 'instant'
       });
       updateActiveState(currentPage);
      }
     }
     updatePage(currentPage)
     $("#page-items li").click(function() {
      var pageNumber = $(this).find("a").data("page");
      updatePage(pageNumber);
     });
     $("#progress-bar li").click(function() {
      var pageNumber = $(this).data("page");
      updatePage(pageNumber);
     });
     $("#page-go-left").click(function() {
      if (currentPage > 0) {
       currentPage--;
       updatePage(currentPage);
      }
     });
     $("#page-go-right").click(function() {
      if (currentPage < totalPages - 1) {
       currentPage++;
       updatePage(currentPage);
      }
     });
     const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
       if (entry.isIntersecting) {
        currentPage = entry.target.dataset.number;        
        updateActiveState(currentPage);
        $(".current-page").text(currentPage);
       }
      });
     }, {
      threshold: 0.1
     });
     images.forEach(image => {
      observer.observe(image);
     });

     function updateActiveState(pageNumber) {
      $(".img.loaded img").removeClass("active");
      $('.img.loaded img[data-number="' + pageNumber + '"]').addClass("active");
      $("#progress-bar li").removeClass("active");
      $('#progress-bar li[data-page="' + pageNumber + '"]').addClass("active");
      $("#page-items li a").removeClass("active");
      $('#page-items li a[data-page="' + pageNumber + '"]').addClass("active");
     }
     //
     function progressPosition(progress_position) {
      localStorage.setItem('progress_position', progress_position);
      $("#progress-bar").addClass(progress_position);
     }
     const progress_position = localStorage.getItem('progress_position');
     if (progress_position) {
      progressPosition(progress_position);
     }
     $('.btn-options[data-name="progress_position"] div').on('click', function() {
      // progressPosition($(this).data('value'))
      var inputValue = $(this).data("value");
      $('.btn-options[data-name="progress_position"] div[data-value="' + inputValue + '"]').toggle();
     });
    });
   </script>

    @include('frontend-web.partials.authenticate-form')

    <script>
        toastr.options = {
        "positionClass": "toast-bottom-right",
        };
         $(document).ready(function() {
            @auth
            $(".bookmark .btn").click(function(e) {
                e.stopPropagation();
                $(this).siblings('.dropdown-menu').slideToggle(100);
            });

            $(".bookmark .dropdown-item").click(function(e) {
              e.preventDefault();
              var action = $(this).data('action');
              var mangaId = $(this).closest('.favourite').data('id');
              
              if (action) {
                  $.ajax({
                      url: "{{ route('user.save-bookmark') }}",
                      type: 'POST',
                      data: {
                          manga_id: mangaId,
                          action: action,
                          _token: '{{ csrf_token() }}'
                      },
                      success: function(response) {
                          if (response.success) {
                            if(action == 'delete'){
                                $(".bookmark .remove-bookmark").hide();
                            } else {
                                $(".bookmark .remove-bookmark").show(); // Hiển thị nút Remove
                              $(".bookmark .dropdown-item[data-action='" + action + "']").addClass('active'); // Thêm lớp active vào mục đã chọn
                            }
                            $(".bookmark .dropdown-item").removeClass('active'); // Xóa lớp active khỏi tất cả các mục  
                              toastr.success(response.message);
                          } else {
                              toastr.error('Có lỗi xảy ra.');
                          }
                      }
                    });
                }
            });
            @else
            $(".bookmark .btn").click(function(e) {
                e.preventDefault();
                toastr.error('Bạn cần đăng nhập để thực hiện thao tác này.');
            });
            @endif
        });
        
        $('.ajax-report-chapter').on('submit', function (e) {
        e.preventDefault();

        let formData = {
            chapter_id: $('input[name="chapter_id"]').val(),
            report_message: $('textarea[name="report_message"]').val(),
            _token: $('meta[name="csrf-token"]').attr('content') 
        };

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (response) {
                if(response.status == 'error'){
                    toastr.error(response.message);
                } else {
                    toastr.success(response.message);
                    $('textarea[name="report_message"]').val('');
                } 
            },
            error: function (response) {
                toastr.error('Có lỗi xảy ra');
            }
        });
    });
    </script>

@livewireScripts
</div>
</body>
</html>