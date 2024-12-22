<link rel="stylesheet" type="text/css" href="{{ asset('frontend-web/css/swiper.min.css') }}" />
    <link
      rel="stylesheet"
      type="text/css"
      href="{{ asset('/frontend-web/css/tooltipster.bundle.min.css')}}"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,700;1,600&amp;display=swap"
      rel="stylesheet"/>

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
  
  <header>
        <div class="container">
          <div class="component">
            <button id="nav-menu-btn" class="btn nav-btn">
              <i class="fa-regular fa-bars fa-lg"></i>
            </button>
            <a href="{{ route('home') }}" class="logo"
              ><img src="{{ asset('storage/images/icon/logo.png') }}" alt="{{ config('frontend_name') }}"/></a>
            <div id="nav-menu">
              <ul>
                <li>
                  <a href="javascript:;">Thể loại</a>
                  <ul class="lg">
                    @foreach($categories as $category)
                    <li>
                      <a title="Action mangas" href="{{ route('mangas.category', ['category_slug' => $category->slug]) }}">{{ $category->name }}</a>
                    </li>
                    @endforeach
                  </ul>
                </li>
                <li>
                  <a href="{{ route('manga.random') }}" title="Truyện random">
                    <i class="mr-1 fa-regular fa-shuffle"></i> Random</a>
                </li>
                <li>
                  <a href="{{ route('mangas.scheduled') }}" title="Lịch truyện">
                    <i class="mr-1 fa-regular fa-calendar"></i> Lịch truyện</a>
                </li>
                <li><a href="" title="Recently Added Manga">Group</a></li>
                <li>
                  <a href="" title="Fanpage">Fanpage</a>
                </li>
              </ul>
            </div>
            
            @include('frontend-web.partials.search')
            <button id="nav-search-btn" class="btn nav-btn">
            <i class="fa-regular fa-magnifying-glass"></i>
            </button>

              @include('frontend-web.partials.user-header')

          </div>
        </div>
      </header>

<script>
$(document).ready(function() {
  $('#nav-menu-btn').click(function(e) {
    e.preventDefault();
    $('#nav-menu > ul > li > ul').hide(); 
    $('#nav-menu > ul').slideToggle(200); 
  });

  $('#nav-menu > ul > li').click(function(e) {
      e.stopPropagation();
      $(this).children('ul').slideToggle(200);
  });
});
      </script>
