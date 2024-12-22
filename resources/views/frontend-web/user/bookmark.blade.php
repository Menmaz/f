<!DOCTYPE html>
<html data-a="af266caa520a" data-g="bad">
  <head>
    <meta charset="utf-8">
    <title>Truyện đã lưu</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="1 days">
    <base href="">
    <meta property="og:type" content="website">
    <meta property="og:title" content="">
    <meta property="og:description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1 ">
    <link rel="icon" type="image/png" href="{{ asset('frontend-web/images/favicon.png') }}">
    
    
  </head>
  <body>
    <span class="bg"></span>
    <div class="wrapper"> 
      @include('frontend-web.home.components.header') 
      
      <main class="user-panel">
        <div class="container">
          <div class="main-inner"> @include('frontend-web.user.components.sidebar') 
            <aside class="content">
              <section>
                <div class="head">
                  <h2>Truyện đã lưu</h2>
                  <span class="text-muted">{{ count($bookmarks) }} Truyện</span>
                </div>

                <div id="user-folders">
                <div>
                  <a class="{{ request()->is('user/bookmark') && !request()->has('folder') ? 'active' : '' }}" href="{{ route('user.bookmark') }}">Tất cả</a>
                </div>
                <div>
                  <a class="{{ request()->get('folder') === 'reading' ? 'active' : '' }}" href="{{ route('user.bookmark', ['folder' => 'reading']) }}">Đang đọc</a>
                </div>
                <div>
                  <a class="{{ request()->get('folder') === 'completed' ? 'active' : '' }}" href="{{ route('user.bookmark', ['folder' => 'completed']) }}">Đã đọc xong</a>
                </div>
                <div>
                  <a class="{{ request()->get('folder') === 'plan-to-read' ? 'active' : '' }}" href="{{ route('user.bookmark', ['folder' => 'plan-to-read']) }}">Dự tính đọc</a>
                </div>
              </div>

                <script type="text/template" id="folder-item"> <li class="folder-item" data-id="@id">
										<a class="name" href="user/bookmark?folder=@id">@name</a>
										<div class="actions">
											<button class="action edit">
												<i class="fa-solid fa-pen"></i>
											</button>
											<button class="action delete">
												<i class="fa-solid fa-trash"></i>
											</button>
										</div>
									</li>
								</script>
                <script type="text/template" id="folder-edit"> <div class="folder-edit">
										<input type="text" class="form-control" placeholder="Folder name">
											<div class="actions">
												<button class="action save">
													<i class="fa-solid fa-circle-check"></i>
												</button>
												<button class="action delete">
													<i class="fa-solid fa-circle-xmark"></i>
												</button>
											</div>
										</div>
									</script>
                                    
                <form id="filters" action="{{ route('user.filter-bookmark') }}" method="GET" autocomplete="off">
                  
                  <div>
                    <div class="search">
                      <input type="text" class="form-control" placeholder="Tìm kiếm..." name="keyword" value="">
                    </div>

                    <div>
                  <div class="dropdown responsive">
                    <button data-toggle="dropdown">
                      <span class="value"
                        data-placeholder="Loại"
                        data-label-placement="true">{{ $type->name ?? 'Loại' }}
                      </span>
                    </button>
                    <div
                      class="dropdown-menu noclose c1">
                      <ul class="types">
                        @foreach($categories->whereIn('slug', ['manga', 'manhwa', 'manhua', 'comic']) as $index => $type)
                        <li>
                          <input
                            type="checkbox"
                            id="type-{{ $type->slug }}"
                            name="type[]"
                            value="{{ $type->slug }}"
                            @if(isset($types_selected) && in_array($type->slug, $types_selected)) checked @endif
                          />
                          <label for="type-{{ $type->slug }}">{{ $type->name }}</label>
                        </li>
                        @endforeach
                      </ul>
                      <div class="clearfix"></div>
                    </div>
                  </div>
                </div>

                <div>
                  <div class="dropdown responsive">
                    <button data-toggle="dropdown">
                      <span class="value"
                        data-placeholder="Thể loại"
                        data-label-placement="true">{{ $category->name ?? 'Thể loại' }}
                      </span>
                    </button>
                    <div
                      class="dropdown-menu noclose lg c4 dropdown-menu-right dropdown-menu-md-left">
                      <ul class="genres">
                        @foreach($categories->whereNotIn('slug', ['manga', 'manhwa', 'manhua', 'comic']) as $index => $cat)
                        <li>
                          <input
                            type="checkbox"
                            id="genre-{{ $cat->slug }}"
                            name="genre[]"
                            value="{{ $cat->slug }}"
                            @if(isset($category) && $category->slug == $cat->slug) checked @endif
                            @if(isset($genres_selected) && in_array($cat->slug, $genres_selected)) checked @endif
                          />
                          <label for="genre-{{ $cat->slug }}">{{ $cat->name }}</label>
                        </li>
                        @endforeach
                      </ul>
                      <div class="clearfix"></div>
                      <ul>
                        <li class="w-100">
                          <input
                            type="checkbox"
                            id="genre-mode"
                            name="genre_mode"
                            value="and"
                            @if(isset($genre_mode))checked @endif 
                            />
                          <label for="genre-mode" class="text-success">Phải có tất cả các thể loại được chọn</label>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div>
                  <div class="dropdown">
                    <button data-toggle="dropdown">
                      <span
                        class="value"
                        data-placeholder="Tình trạng"
                        data-label-placement="true"
                        >Tình trạng</span
                      >
                    </button>
                    <ul class="dropdown-menu noclose c1">
                    @foreach($statuses as $status)
                      <li>
                        <input
                          type="checkbox"
                          id="status-{{ $status->slug }}"
                          name="status[]"
                          value="{{ $status->slug }}"
                          @if(isset($statuses_selected) && in_array($status->slug, $statuses_selected)) checked @endif
                        />
                        <label for="status-{{ $status->slug }}">{{ $status->name }}</label>
                      </li>
                      @endforeach
                    </ul>
                  </div>
                </div>

                <div>
                  <div class="dropdown responsive">
                    @php
                     $years = [];
                     for($i = date("Y"); $i >= 2004 ; $i--){
                      $years[] = $i;
                     }
                     $decades = ['2000s', '1990s', '1980s', '1970s', '1960s', '1950s', '1940s', '1930s'];
                     $years = array_merge($years, $decades);
                    @endphp
                    <button data-toggle="dropdown">
                      <span
                        class="value"
                        data-placeholder="Năm"
                        data-label-placement="true">Năm</span>
                    </button>
                    <ul class="dropdown-menu noclose md c3 dropdown-menu-right dropdown-menu-md-left">
                    @foreach($years as $year)
                    <li>
                        <input
                          type="checkbox"
                          id="year-{{ $year }}"
                          name="year[]"
                          value="{{ $year }}"
                          @if(isset($years_selected) && in_array($year, $years_selected)) checked @endif
                        />
                        <label for="year-{{ $year }}">{{ $year }}</label>
                      </li>
                    @endforeach        
                    </ul>
                  </div>
                </div>

                <div>
                  <div class="dropdown">
                    <button data-toggle="dropdown">
                      @php 
                      $sortBys = [
                        'default' => 'Mặc định',
                        'recently_added' => 'Truyện mới',
                        'title_az' => 'Tên A-Z',
                        'scores' => 'Đánh giá sao',
                        'most_viewed' => 'Lượt xem nhiều nhất'
                        ];
                      @endphp
                      <span
                        class="value"
                        data-placeholder="@if(isset($sort_by) && isset($sortBys[$sort_by]))
                          {{ $sortBys[$sort_by]}}
                        @endif"
                        data-label-placement="true">
                        </span
                      >
                    </button>
                    <ul class="dropdown-menu noclose c1 dropdown-menu-right dropdown-menu-xs-left">
                      @foreach($sortBys as $key => $label)
                      <li>
                        <input
                        type="radio"
                        id="sort-{{ $key }}"
                        name="sort"
                        value="{{ $key }}"
                        @if(isset($sort_by) && $sort_by === $key) checked @endif  
                        />
                        <label for="sort-{{ $key }}">{{ $label }}</label>
                      </li>
                      @endforeach
                    </ul>
                  </div>
                </div>

                            <div><button class="btn btn-primary"><i class="fa-regular fa-circles-overlap fa-xs"></i><span>Lọc</span><i class="ml-2 bi bi-intersect"></i></button></div>
                  
                </div>
                </form>
                
                <div class="original card-xs">
                  @foreach($bookmarks as $bookmark)
                    <div class="unit" data-id="{{ $bookmark->id }}">
                        <div class="inner unread">
                            <a href="" class="poster tooltipstered" data-tip="">
                                <div><img src="{{ $bookmark->manga->cover }}"></div>
                            </a>
                            <div class="info">
                                <div class="dropdown width-limit favourite" data-id="{{ $bookmark->id }}" >
                                    <button class="btn folder-name" data-toggle="dropdown" data-placeholder="false">
                                    @if($bookmark->status == 'reading')
                                        Đang đọc
                                    @elseif($bookmark->status == 'completed')
                                        Đã đọc xong
                                    @elseif($bookmark->status == 'plan_to_read')
                                        Dự tính đọc
                                    @endif
                                    </button>
                                    <div class="dropdown-menu folders">
                                        <a class="dropdown-item" href="#" data-action="reading">Đang đọc</a>
                                        <a class="dropdown-item" href="#" data-action="completed">Đã đọc xong</a>
                                        <a class="dropdown-item active" href="#" data-action="plan_to_read">Dự tính đọc</a>
                                        <a class="dropdown-item" href="delete" data-action="delete"><i class="fa-solid fa-xmark"></i> Xóa</a>
                                    </div>
                                </div>
                                <a href="{{ route('manga.detail', ['slug' => $bookmark->manga->slug]) }}">{{ $bookmark->manga->title }}</a>
                                <div class="richdata">
                                    <span>Chapter {{ $bookmark->manga->latestChapter->chapter_number }}</span>
                                        <button data-toggle="tooltip" title="" class="read-status unread" data-id="50" data-original-title="Unread"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

              </section>
            </aside></div>
        </div></div>
      </main>
    
      @include('frontend-web.home.components.footer')

    </div>
  </body>
</html>

<script>
  $(document).ready(function() {
    $('.folder-name').click(function(e) {
        e.preventDefault();
    });

    $('.folders .dropdown-item').click(function(e) {
        e.preventDefault();

        var action = $(this).data('action');
        var bookmarkId = $(this).closest('.dropdown').data('id');

        if (action === 'delete') {
                $.ajax({
                    url: "{{ route('user.delete-bookmark') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        bookmark_id: bookmarkId
                    },
                    success: function(response) {
                        $('.unit[data-id="' + bookmarkId + '"]').remove();
                    }
                });
        } else {
            $.ajax({
                url: "{{ route('user.update-bookmark-status') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    bookmark_id: bookmarkId,
                    status: action
                },
                success: function(response) {
                    var folderNameBtn = $('.dropdown.width-limit.favourite[data-id="' + bookmarkId + '"] .folder-name');
                    $('.folders .dropdown-item').removeClass('active');
                    $("[data-action='" + action + "']").addClass('active');

                    switch (action) {
                        case 'reading':
                            folderNameBtn.text('Đang đọc');
                            break;
                        case 'completed':
                            folderNameBtn.text('Đã đọc xong');
                            break;
                        case 'plan_to_read':
                            folderNameBtn.text('Dự tính đọc');
                            break;
                        default:
                            break;
                    }
                    toastr.success("Cập nhật thành công")
                }
            });
        }
    });

    $('.dropdown-menu').click(function(event) {
        event.stopPropagation();
    });

    $('.dropdown-menu').each(function() {
        var $dropdownMenu = $(this);
        var $checkboxes = $dropdownMenu.find('input[type="checkbox"]');
        var $button = $dropdownMenu.prev('button');
        var $buttonSpan = $button.find('span.value');
        var $selectAllCheckbox = $dropdownMenu.find('input[value="and"]');

        function updateButtonCheckedText() {
            var selectedItems = $dropdownMenu.find('input[type="checkbox"]:checked');
            var selectedCount = selectedItems.length;

            if (selectedCount <= 2) {
                var newText = selectedItems.map(function() {
                    return $(this).next('label').text();
                }).get().join(', ');
                if(selectedCount === 0){
                  newText = $button.find('span').data('placeholder');
                }
                $buttonSpan.text(newText);
            } else {
                $buttonSpan.text('Đã chọn ' + selectedCount);
            }
        }

        $checkboxes.change(function() {
          updateButtonCheckedText()
        });

        updateButtonCheckedText()
    });

});
</script>