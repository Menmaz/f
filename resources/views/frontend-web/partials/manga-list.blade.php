@if($updatedMangas->isNotEmpty())
    <div class="original card-lg" data-last-page="{{ $updatedMangas->lastPage() }}">
        @auth
            @php
                $bookmarks = auth()->user()->bookmarks()
                    ->whereIn('bookmarkable_id', $updatedMangas->pluck('id'))
                    ->get()
                    ->keyBy('bookmarkable_id');
            @endphp
        @endauth

        @foreach($updatedMangas as $manga)
            @php
                $genres = $manga->taxanomies
                    ->where('type', 'genre')
                    ->whereNotIn('slug', ['manga', 'manhwa', 'manhua', 'comic'])
                    ->take(3)
                    ->values();
                $status = $manga->taxanomies->where('type', 'status')->first();
                $type = $manga->taxanomies
                    ->where('type', 'genre')
                    ->whereIn('slug', ['manga', 'manhwa', 'manhua', 'comic'])
                    ->first();
            @endphp

            <div class="unit item-{{$manga->slug}}">
                <div class="inner">
                    <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}" class="poster tooltipstered" data-tooltip-content="#tooltipster_content_{{$manga->id}}">
                        <div> 
                            <img loading="lazy" decoding="async" style="height: 100%;" src="{{ $manga->cover }}" alt="{{ $manga->title }}"/> 
                        </div>
                    </a>
                    <div class="info">
                        <div style="position: relative;">
                            <span class="type">
                                @if($type)
                                    {{ $type->name }} 
                                @else
                                    <p></p>
                                @endif
                            </span>
                            <span style="position: absolute; top: 0; right: 0;">
                                <i class="fa fa-eye type"></i>
                                {{ \App\Helpers\ViewHelper::formatViews($manga->views_sum_views) ?? 0 }}
                            </span>
                        </div>
                        <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}">{{ Str::limit($manga->title, 37, '...') }}</a>
                        <ul class="content" data-name="chap">
                            @foreach($manga->chapters as $chapter)
                                <li>
                                    <a href="{{ route('manga.chapter', ['slug' => $manga->slug, 'chapter_number' => $chapter->chapter_number]) }}">
                                        <span>Chapter {{ $chapter->chapter_number }}</span>
                                        <span>{{ \Carbon\Carbon::parse($chapter->created_at)->diffForHumans() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Tooltip content -->
            <div class="tooltip_templates tooltipster-base tooltipster-sidetip tooltipster-right tooltipster-fade">
                <div id="tooltipster_content_{{$manga->id}}" class="">
                    <div class="bookmark"> 
                        <div class="dropleft height-limit favourite" data-id="{{ $manga->id }}"> 
                            <button class="btn" type="button" data-toggle="dropdown" data-placeholder="false" aria-expanded="false">
                                <i class="fa-solid fa-circle-bookmark fa-xl"></i> 
                            </button> 
                            <div class="dropdown-menu dropdown-menu-right folders" style="display: none;"> 
                                @auth  
                                    @php $bookmark = $bookmarks[$manga->id] ?? null; @endphp
                                    <a class="dropdown-item {{ $bookmark && $bookmark->status == 'reading' ? 'active' : '' }}" href="#" data-action="reading">Đang đọc</a>
                                    <a class="dropdown-item {{ $bookmark && $bookmark->status == 'completed' ? 'active' : '' }}" href="#" data-action="completed">Đã đọc xong</a>
                                    <a class="dropdown-item {{ $bookmark && $bookmark->status == 'plan-to-read' ? 'active' : '' }}" href="#" data-action="plan-to-read">Dự tính đọc</a>
                                    <a class="dropdown-item remove-bookmark" href="#" data-action="delete" style="{{ $bookmark ? '' : 'display: none;' }}">
                                        <i class="fa-solid fa-xmark"></i> Xóa </a>
                                @endauth
                            </div>
                        </div> 
                    </div> 
                    <span> 
                        @if($status)
                            {{ $status->name }}
                        @endif
                    </span>
                    <a href="{{ route('manga.detail', ['slug' => $manga->slug]) }}">{{$manga->title }}</a>
                    <p></p>
                    <p> <b class="text-primary"> 
                        @if (isset($manga->star_ratings_avg_rating))
                            {{ round($manga->star_ratings_avg_rating) }}
                        @else
                            5
                        @endif
                        <i class="fa-star fa-solid" style="color: #efc300;"></i> </b> 
                        @if($manga->star_ratings_count > 0)
                            ({{ $manga->star_ratings_count }} 
                        @else
                            1
                        @endif
                        đánh giá) </p>
                    <nav>
                        @foreach($genres as $genre)
                            <a style="background: #182335; padding: 0.2rem 0.8rem; border-radius: 50rem;border: 1px solid #1e2c43; font-weight: 300;display: inline-block;" 
                            href="{{ route('mangas.category', ['category_slug' => $genre->slug]) }}">{{ $genre->name }}</a>
                        @endforeach
                    </nav>
                </div>
            </div>
        @endforeach
    </div>
@endif
