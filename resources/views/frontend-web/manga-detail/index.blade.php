<!DOCTYPE html>
<html>
  <head>
    @include('frontend-web.partials.seo')
  
  </head>
<body>
    <span class="bg"></span>
    <div class="wrapper">

@include('frontend-web.home.components.header')

        @php
          $genres = $manga['taxanomies']->where('type', 'genre')->whereNotIn('slug', ['manga', 'manhwa', 'manhua', 'comic'])->values();
          $status = $manga['taxanomies']->where('type', 'status')->first();
          $type = $manga['taxanomies']->where('type', 'genre')->whereIn('slug', ['manga', 'manhwa', 'manhua', 'comic'])->first();

          $latestChapter =$manga['chapters']->first();
          $oldestChapter = $manga['chapters']->last();
        @endphp

<main class="">
 <div id="manga-page" >
  <div class="manga-detail" >
   <div class="detail-bg">
    <img loading="lazy" src="{{ $manga->cover }}" alt="{{ $manga->title }}" />
   </div>
   <div class="container">
    <div class="main-inner">
     <aside class="content">
      <div class="poster">
       <div>
        <img loading="lazy" src="{{ $manga->cover }}" itemprop="image" alt="{{ $manga->title }}" />
       </div>
      </div>
      <div class="info">
       <p>@if($status) {{ $status->name }} @else Đang phát hành @endif </p>
       <h1 itemprop="name">{{ $manga->title }}</h1>
       <h6>{{ $manga->alternative_titles }}</h6>
       <div class="actions">
        <a class="btn btn-lg btn-primary readnow" href="{{ route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $oldestChapter->chapter_number ]) }}">
         <span>Từ đầu</span>
         <span>Từ đầu</span>
         <i class="fa-solid fa-play fa-xs"></i>
        </a>
        <a class="btn btn-lg btn-primary readnow" href="{{ route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $latestChapter->chapter_number ]) }}">
         <span>Mới nhất</span>
         <span>Mới nhất</span>
         <i class="fa-solid fa-play fa-xs"></i>
        </a>
        <div class="bookmark dropright favourite" data-id="{{ $manga->id }}" data-fetch="true">
         <button class="btn btn-lg btn-secondary1 h-100" type="button" data-toggle="dropdown" data-placeholder="false" aria-expanded="false">
          <span>Lưu truyện</span>
          <i class="fa-solid fa-bookmark fa-xs"></i>
         </button>
         <div class="dropdown-menu dropdown-menu-right folders" style="display: none;"> 
         @auth 
         @php $bookmark = auth()->user()->bookmarks()->where('bookmarkable_id', $manga->id)->first(); @endphp 
         <a class="dropdown-item {{ $bookmark && $bookmark->status == 'reading' ? 'active' : '' }}" href="#" data-action="reading">Đang đọc</a>
          <a class="dropdown-item {{ $bookmark && $bookmark->status == 'completed' ? 'active' : '' }}" href="#" data-action="completed">Đã đọc xong</a>
          <a class="dropdown-item {{ $bookmark && $bookmark->status == 'plan-to-read' ? 'active' : '' }}" href="#" data-action="plan-to-read">Dự tính đọc</a>
          <a class="dropdown-item remove-bookmark" href="#" data-action="delete" style="{{ $bookmark ? '' : 'display: none;' }}">
           <i class="fa-solid fa-xmark"></i> Xóa </a>
         @endauth
         </div>
        </div>
       </div>
       <div class="min-info">
       @if(isset($type)) <a href="{{ route('mangas.category', ['category_slug' => $type->slug]) }}"> {{ $type->name }} </a>
       @endif
        <span>
         {{ \App\Helpers\ViewHelper::formatViews($manga->views_sum_views) ?? 0}} lượt xem
        </span>
       </div>
       <div class="description">
        {{ Str::limit(strip_tags($manga->description), 153, '...') }}
       </div>
       <a class="readmore" data-toggle="modal" href="#synopsis">Đọc thêm +</a>
       <div class="sharethis-inline-share-buttons mt-3 text-center text-md-left" data-url=""></div>
      </div>
      <button id="info-rating-btn" class="btn" type="button" data-toggle="collapse" data-target="#info-rating" aria-expanded="false" aria-controls="info-rating">
       <i class="fa-solid fa-circle-info"></i>
       <span class="mx-2">Thông tin &amp; Đánh giá sao</span>
       <i class="fa-solid fa-star"></i>
      </button>
     </aside>
     <aside class="sidebar">
      <div class="collapse" id="info-rating">
       <div class="meta">
        <div>
         <span><i class="fa-address-book fa-solid"></i> Tác giả:</span>
         <span>
          <span style="color: white" itemprop="author">{{ $manga->author }}</span>
         </span>
        </div>
        <div>
         <span><i class="fa-clock fa-solid"></i> Cập nhật:</span>
         <span>{{ $manga->updated_at }}</span>
        </div>
        <div>
         <span><i class="fa-feed fa-solid"></i> Thể loại:</span>
         <span> 
         @foreach($genres as $key => $genre)
            <a href="{{ url('the-loai/' . $genre->slug) }}">{{ $genre->name }}</a>
            @if(!$loop->last)
                , 
            @endif
        @endforeach
         </span>
        </div>
       </div>

       <!-- đánh giá sao nè -->
      @include('frontend-web.manga-detail.components.rating')

      </div>
     </aside>
    </div>
   </div>
  </div>
  <div class="container">
   <div class="main-inner manga-bottom">
    <aside class="content">
     <section class="m-list">
      <nav class="chapvol-tab" data-tabs="">
       <a href="javascript:;" class="tab active" data-name="chapter">Chapter</a>
      </nav>
      <div class="tab-content" data-name="chapter">
       <div class="list-menu">
        <form class="form-inline" id="searchForm">
         <input class="form-control" type="text" placeholder="Tên chapter..." id="searchInput" />
         <button class="btn" type="submit">
          <i class="fa-regular fa-magnifying-glass"></i>
         </button>
        </form>
       </div>

       @include('frontend-web.manga-detail.components.chapters')

      </div>
     </section>

     <!-- bình luận nè -->
     <div class="default-style">
        <div class="head">
            <h2>Bình luận</h2>
        </div>
     <livewire:comments :manga="$manga" />
     </div>

    </aside>
    <aside class="sidebar">
     <section class="m-related default-style" style="margin-top: 30px;">
      <div class="head">
       <h2>Truyện Liên Quan</h2>
      </div> 
      <ul class="tab-content scroll-sm" data-name="Side Story" style="display: block">
        @foreach($relativeMangas as $m) 
       <li>
        <a href="{{ url('truyen/' . $m->slug) }}">{{ $m->title }}</a>
       </li>
       @endforeach
      </ul> 
     </section>
     <section class="side-manga default-style">
      <div class="head">
       <h2>Bạn cũng có thể thích</h2>
      </div>
      <div class="original card-sm body"> @foreach($latestMangas as $m) <a class="unit" href="{{ url('truyen/' . $m->slug) }}">
        <div class="poster">
         <div>
          <img loading="lazy" src="{{ $m->cover }}" alt="{{ $m->title }}" />
         </div>
        </div>
        <div class="info">
         <h6>{{ $m->title }}</h6>
         <div>
          <span>Chapter {{ $m->chapters_max_chapter_number }}</span>
         </div>
        </div>
       </a> @endforeach </div>
     </section>
    </aside>
   </div>
  </div>
 </div>
</main>

<div class="modal fade" id="synopsis" style="display: none;" aria-hidden="true">
 <div class="modal-dialog limit-w modal-dialog-centered">
  <div class="modal-content p-4">
   <div class="modal-close" data-dismiss="modal">
    <i class="fa-solid fa-xmark"></i>
   </div>
   {{ strip_tags($manga->description) ?: 'Không có nội dung' }}
  </div>
 </div>
</div> 

@include('frontend-web.home.components.footer') 

<script>
 document.addEventListener('DOMContentLoaded', function() {
  var searchInput = document.getElementById('searchInput');
  var chapterList = document.getElementById('chapterList').getElementsByTagName('li');
  searchInput.addEventListener('input', function() {
   var searchText = searchInput.value.trim();
   for (var i = 0; i < chapterList.length; i++) {
    var chapter = chapterList[i];
    var chapterNumber = chapter.getAttribute('data-number');
    if (chapterNumber.match(/^\d+$/) && chapterNumber.startsWith(searchText)) {
     chapter.style.display = 'block';
    } else {
     chapter.style.display = 'none';
    }
   }
  });
 }); 
</script>

@livewireScripts