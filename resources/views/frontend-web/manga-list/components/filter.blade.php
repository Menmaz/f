<form id="filters" action="{{ route('mangas.filter')}}" >
              <div>
                <div class="search">
                  <input
                    type="text"
                    class="form-control"
                    placeholder="Tìm kiếm..."
                    name="keyword"
                    value=""
                  />
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
                            name="type"
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
                            name="genre"
                            value="{{ $cat->slug }}"
                            @if(isset($category) && $category->slug == $cat->slug) checked @endif
                            @if(isset($genres_selected) && in_array($cat->slug, $genres_selected)) checked @endif
                          />
                          <label for="genre-{{ $cat->slug }}">{{ $cat->name }}</label>
                        </li>
                        @endforeach
                      </ul>
                      <div class="clearfix"></div>
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
                          name="status"
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
                          name="year"
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
                        'moi-cap-nhat' => 'Mới cập nhật',
                        'truyen-moi' => 'Truyện mới',
                        'a-z' => 'Tên A-Z',
                        'danh-gia-sao' => 'Đánh giá sao',
                        'luot-xem' => 'Lượt xem nhiều nhất'
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
                <div>
                  <button class="btn btn-primary">
                    <i class="fa-regular fa-circles-overlap fa-xs"></i>
                    <span>Tìm kiếm</span> <i class="ml-2 bi bi-intersect"></i>
                  </button>
                </div>
              </div>
            </form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
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