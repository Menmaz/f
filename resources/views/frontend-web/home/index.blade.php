<!DOCTYPE html>
<html >
  <head>
  @include('frontend-web.partials.seo')
  </head>
  <body>
    <span class="bg"></span>
    <div class="wrapper">

@include('frontend-web.home.components.header')

      <main class="">
      @if(!empty($sliderMangas))
        <div id="top-trending">
          <div class="container">
            <div class="swiper trending swiper-container">
              <div class="swiper-wrapper">
              @foreach($sliderMangas as $manga)
                @php
                  $genres = $manga->taxanomies->where('type', 'genre')->take(3)->values();
                  $status = $manga->taxanomies->where('type', 'status')->first();
                @endphp
                
                <div class="swiper-slide">
                  <div class="swiper-inner">
                    
                    <div class="bookmark">
                      <div class="dropleft width-limit favourite" data-id="{{ $manga->id }}">
                        <button
                          class="btn"
                          data-toggle="dropdown"
                          data-placeholder="false">
                          <i class="fa-solid fa-circle-bookmark"></i>
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
                    </div>

                    <div class="info">
                      <div class="above">
                        <span>
                        @if(isset($status))  
                        {{ $status->name }}
                        @endif
                      </span>
                        <a class="unit" href="{{ route('manga.detail', ['slug' => $manga->slug]) }}">{{$manga->title}}</a>
                      </div>
                      <div class="below">
                      <span> {!! strip_tags($manga->description) !!} </span>
                        <p>Chapter {{ $manga->chapters_max_chapter_number }}</p>
                        <div>
                        @foreach($genres as $genre)
                            <a href="{{ route('mangas.category', ['category_slug' => $genre->slug ]) }}">{{ $genre->name }}</a>
                        @endforeach
                        </div>
                      </div>
                    </div>
                    <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}" class="poster">
                      <div>
                        <img loading="lazy" decoding="async" src="{{ $manga->cover }}" alt="{{$manga->title}}" style="object-fit: cover;"/>
                      </div>
                    </a>
                  </div>
                </div>
                @endforeach
              </div>
            </div>
            <div class="trending-button-next"></div>
            <div class="trending-button-prev"></div>
          </div>
        </div>
        @endif

        <div class="container">
          <div class="alert bg-secondary text-center">
            <p>
             {{ strip_tags($notication_message) }}
            </p>
            
            @include('frontend-web.partials.social-buttons')
            
          </div>
          <section class="home-swiper" id="most-viewed">
            <div class="head">
              <h2>Top Xem Nhiều</h2>
              <div class="tabs" data-tabs="">
                <div class="tab active" data-name="day">Ngày</div>
                <div class="tab" data-name="week">Tuần</div>
                <div class="tab" data-name="month">Tháng</div>
              </div>
            </div>

            <div class="tab-content" data-name="day" style="display: block">
              <div class="swiper-container">
                <div class="swiper most-viewed">
                  <div class="card-md swiper-wrapper">
                  @foreach($popularDayMangas as $index => $manga)
                    @php
                      $increasedIndex = $index + 1;
                    @endphp
                    <div class="swiper-slide unit">
                      <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}">
                        <b>{{ $increasedIndex }}</b>
                        <div class="poster">
                          <div>
                            <img loading="lazy" decoding="async" style="height: 100%;"
                              src="{{ $manga->cover }}"
                              alt="{{$manga->title}} #{{ $increasedIndex }}"/>
                              <div style="position: absolute; bottom: 0; left: 0; z-index: 3; color: white; background: rgba(0, 0, 0, 0.7); padding: 5px; font-size:0.8rem;border-top-right-radius: 5px">
                                <i class="fa fa-eye"></i>
                                {{ \App\Helpers\ViewHelper::formatViews($manga->views_sum_views) }}
                            </div>
                          </div>
                        </div>
                        <span>{{$manga->title}}</span>
                      </a>
                    </div>
                    @endforeach
                  </div>
                  <div class="swiper-pagination"></div>
                </div>
              </div>
            </div>

            <div class="tab-content" data-name="week" style="display: none">
              <div class="swiper-container">
                <div class="swiper most-viewed">
                  <div class="card-md swiper-wrapper">
                  @foreach($popularWeekMangas as $index => $manga)
                    @php
                      $increasedIndex = $index + 1;
                    @endphp
                    <div class="swiper-slide unit">
                      <a href="{{ url('truyen/' . $manga->slug) }}">
                        <b>{{ $increasedIndex }}</b>
                        <div class="poster">
                          <div>
                            <img loading="lazy" decoding="async" style="height: 100%;"
                              src="{{ $manga->cover }}"
                              alt="{{$manga->title}} #{{ $increasedIndex }}"
                            />
                            <div style="position: absolute; bottom: 0; left: 0; z-index: 3; color: white; background: rgba(0, 0, 0, 0.7); padding: 5px; font-size:0.8rem;border-top-right-radius: 5px">
                                <i class="fa fa-eye"></i>
                                {{ \App\Helpers\ViewHelper::formatViews($manga->views_sum_views) }}
                            </div>
                          </div>
                        </div>
                        <span>{{$manga->title}}</span>
                      </a>
                    </div>
                    @endforeach
                  </div>
                  <div class="swiper-pagination"></div>
                </div>
              </div>
            </div>

            <div class="tab-content" data-name="month" style="display: none">
              <div class="swiper-container">
                <div class="swiper most-viewed">
                  <div class="card-md swiper-wrapper">
                  @foreach($popularMonthMangas as $index => $manga)
                    @php
                      $increasedIndex = $index + 1;
                    @endphp
                    <div class="swiper-slide unit">
                      <a href="{{ url('truyen/' . $manga->slug) }}">
                        <b>{{ $increasedIndex }}</b>
                        <div class="poster">
                          <div>
                            <img loading="lazy" decoding="async" style="height: 100%;" src="{{ $manga->cover }}" alt="{{$manga->title}} #{{ $increasedIndex }}"/>
                            <div style="position: absolute; bottom: 0; left: 0; z-index: 3; color: white; background: rgba(0, 0, 0, 0.7); padding: 5px; font-size:0.8rem;border-top-right-radius: 5px">
                                <i class="fa fa-eye"></i>
                                {{ \App\Helpers\ViewHelper::formatViews($manga->views_sum_views) }}
                            </div>
                          </div>
                        </div>
                        <span>{{$manga->title}}</span>
                      </a>
                    </div>
                    @endforeach
                  </div>
                  <div class="swiper-pagination"></div>
                </div>
              </div>
            </div>
          </section>
          
          @auth
          @if(session()->has('reading_mangas') && count(session('reading_mangas')) > 0)
          <div id="continue-reading">
          <section>
            <div class="head">
              <h2>Lịch sử đọc</h2>
              <a href="user/reading">Xem tất cả <i class="fa-solid fa-xs fa-arrow-right"></i>
              </a>
            </div>
            @include('frontend-web.partials.continue-reading-mangas')
          </section>
        </div>
        @endif
        @endauth
          
          @php
          $mangaTabs = [
              'all' => ['title' => 'Tất cả', 'data' => $allUpdatedMangas],
              'manga' => ['title' => 'Manga', 'data' => $updatedMangas],
              'manhua' => ['title' => 'Manhua', 'data' => $updatedManhuas],
              'manhwa' => ['title' => 'Manhwa', 'data' => $updatedManhwas]
          ];
          @endphp

          <section>
              <div class="head">
                  <h2>Mới Cập Nhật</h2>
                  <div class="tabs recent-updated">
                      @foreach($mangaTabs as $type => $info)
                          <div class="tab {{ $type === 'all' ? 'active' : '' }}" 
                               data-name="{{ $type }}">
                              {{ $info['title'] }}
                          </div>
                      @endforeach
                  </div>
              </div>

              @foreach($mangaTabs as $type => $info)
                  <div class="tab-content recent-updated" 
                       data-name="{{ $type }}" 
                       {!! $type !== 'all' ? 'style="display: none;"' : '' !!}>
                      @include('frontend-web.partials.manga-list', ['updatedMangas' => $info['data']])
                  </div>
              @endforeach
          </section>
          <section class="home-swiper">
            <div class="head">
              <h2>Truyện Mới</h2>
              <div class="tabs">
                <div class="s-pagi">
                  <div class="complete-button-prev">
                    <i class="fa-solid fa-square-chevron-left fa-lg"></i>
                  </div>
                  <div class="complete-button-next">
                    <i class="fa-solid fa-square-chevron-right fa-lg"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="swiper-container">
              <div class="swiper completed">
                <div class="card-md swiper-wrapper">
                @if($latestMangas->count())
                  @foreach($latestMangas as $manga)
                  <div class="swiper-slide unit">
                    <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}">
                      <div class="poster">
                        <div>
                          <img loading="lazy" decoding="async" style="height: 100%;" src="{{$manga->cover}}" alt="{{ $manga->title }}"/>
                          <div style="position: absolute; bottom: 0; left: 0; z-index: 3; color: white; background: rgba(0, 0, 0, 0.7); padding: 5px; font-size:0.8rem;border-top-right-radius: 5px;">
                          <i class="fa fa-eye"></i>
                          {{ \App\Helpers\ViewHelper::formatViews($manga->views_sum_views) }}
                          </div>
                        </div>
                      </div>
                      <span style="overflow:hidden; text-overflow:ellipsis; white-space: nowrap;padding:.7rem 1rem; display:block; text-align:center">{{ $manga->title }}</span
                      >
                    </a>
                  </div>
                  @endforeach
                  @endif
                </div>
              </div>
              <div class="completed-pagination"></div>
              <br>
            <div class="d-flex justify-content-center">
              <a href="{{ route('mangas.latest') }}" class="btn btn-primary2" style="margin-right: 15px;">
                  <span>Xem thêm</span><i style="margin-left: 5px;" class="fa-solid fa-xs fa-arrow-right"></i>
              </a>
          </div>
            </div>
          </section>
        </div>
      </main>
      
    
      @include('frontend-web.home.components.footer')

    </div>

     <script type="text/javascript">
         // document.addEventListener("DOMContentLoaded", function () {
// Khởi tạo Swiper cho Trending
const swiper = new Swiper(".swiper.trending", {
    loop: true,
    width: 400,
    autoplay: {
        delay: 5000,
    },
    slidesPerView: 1,
    breakpoints: {
        width: 640,
        640: {
            slidesPerView: 2,
        },
        1024: {
            width: 1440,
            slidesPerView: 3,
        },
    },
    direction: "horizontal",
    navigation: {
        nextEl: ".trending-button-next",
        prevEl: ".trending-button-prev",
    },
});

// Khởi tạo Swiper cho Top xem nhiều
const $mostViewed = $("#most-viewed");
let swipers = {
    day: new Swiper(
        "#most-viewed .tab-content[data-name='day'] .swiper.most-viewed",
        {
            pagination: {
                el: "#most-viewed .swiper-pagination",
                type: "progressbar",
            },
            slidesPerView: "auto",
        }
    ),
    week: null,
    month: null,
};

$mostViewed.find(".tab").click(function () {
    $mostViewed.find(".tab").removeClass("active");
    $(this).addClass("active");
    const tabName = $(this).data("name");
    $mostViewed.find(".tab-content").hide();
    $mostViewed.find(`.tab-content[data-name="${tabName}"]`).fadeIn(300);
    if (!swipers[tabName]) {
        swipers[tabName] = new Swiper(
            `#most-viewed .tab-content[data-name='${tabName}'] .swiper.most-viewed`,
            {
                pagination: {
                    el: "#most-viewed .swiper-pagination",
                    type: "progressbar",
                },
                slidesPerView: "auto",
            }
        );
    }
});

// Khởi tạo Swiper cho Top Xem Nhiều và Truyện mới
new Swiper(".swiper.completed", {
    pagination: {
        el: ".completed-pagination",
        type: "bullets",
        clickable: true,
        bulletClass: "swiper-pagination-bullet",
        bulletActiveClass: "swiper-pagination-bullet-active",
    },
    slidesPerView: "auto",
    navigation: {
        nextEl: ".complete-button-next",
        prevEl: ".complete-button-prev",
    },
});

// Function to initialize Tooltipster (hover cho các manga trong truyện mới cập nhật)
function initializeTooltipster() {
    $(".tooltipstered").tooltipster({
        contentCloning: false,
        interactive: true,
        side: "right",
    });
}
initializeTooltipster();

// });

     </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const recentUpdatedSection = $('.recent-updated');
    if (!recentUpdatedSection.length) return;

    const tabs = recentUpdatedSection.find('.tab');
    const pagination = recentUpdatedSection.find('.s-pagi.js');
    let url;
    let currentPage = 1;

    tabs.on('click', function(event) {
        event.preventDefault();
        const type = $(this).data('name');
        tabs.removeClass('active');
        $(this).addClass('active');
        $('.tab-content.recent-updated').hide();
        $('.tab-content.recent-updated[data-name="' + type + '"]').show();
    });
});
</script>


  </body>
</html>
