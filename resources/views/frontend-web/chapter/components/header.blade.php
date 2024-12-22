<header>
 <div class="inner px-3">
  <div class="component">
   <button id="nav-menu-btn" class="btn nav-btn">
    <i class="fa-regular fa-bars fa-lg"></i>
   </button>
   <a href="{{ route('home') }}" class="logo">
    <img src="{{ asset('storage/images/icon/logo-sm.png') }}" alt="{{ config('custom.frontend_name') }}" />
   </a>
   <div id="nav-menu">
    <ul>
     <li>
      <a href="javascript:;">Thể loại</a>
      <ul class="lg"> @foreach($categories as $category) <li>
        <a title="Action mangas" href="{{ route('mangas.category', ['category_slug' => $category->slug]) }}">{{ $category->name }}</a>
       </li> @endforeach </ul>
     </li>
     <li>
      <a href="{{ route('manga.random') }}" title="Random Manga">
       <i class="mr-1 fa-regular fa-shuffle"></i> Random </a>
     </li>
    <li>
    <a href="{{ route('mangas.scheduled') }}" title="Lịch truyện">
    <i class="mr-1 fa-regular fa-calendar"></i> Lịch truyện</a>
    </li>
    </ul>
   </div>
      @include('frontend-web.partials.search') 
      
      <div class="viewing number-toggler">
    <span class="current-viewtype text-title">chapter</span>
    <span>
     <b class="current-number">{{ $currentChapter->chapter_number }}</b>/ <b class="latest-number">{{ $chapters->first()->chapter_number }}</b>
    </span>
   </div>
   <div class="viewing mr-3 page-toggler">
    <span>Trang</span>
    <span>
     <b class="current-page">1</b>/ <b class="total-page">{{ $totalPages }}</b>
    </span>
   </div>

   <button id="nav-search-btn" class="btn nav-btn mr-2">
    <i class="fa-regular fa-magnifying-glass"></i>
   </button>
   <script>
     $('#nav-search-btn').click(function(e) {
        e.stopPropagation();
        $('#nav-search').toggleClass('active');
    });
   </script>

  @include('frontend-web.partials.user-header')

   <button id="show-ctrl-menu" class="btn btn-primary tooltipz" title="" data-original-title="Use M button to toggle">
    <i class="fa-solid fa-grid-2"></i>
    <span>Menu</span>
   </button>
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