@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Crawler' => backpack_url('plugin/ophim-crawler'),
    ];

    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h3>
            <span>Tải chapter cho truyện <a target="_blank" href="{{url('/admin/chapters/' . $manga->slug)}}">{{$manga->title}}</a></span>
        </h3>
    </section>
@endsection

@section('content')
<!-- <div class="col-md-8">	
    <div class="card bg-green text-white">
	<div class="card-body">Hệ thống sẽ tải các chapter bị thiếu, sửa các ảnh bị lỗi hoặc thêm các ảnh bị thiếu cho các chapter</div>
	  		</div>
</div> -->

    <div class="row">
        <div class="col-md-8 steps" id="fetch-chapters">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @csrf
                        <div class="form-group col-12 mb-3">
                            <label for="">Đường dẫn truyện</label>
                            <input id="linkInput" class="form-control" name="link" placeholder="https://nettruyencc.com/truyen-tranh/{{$manga->slug}}"></input>
                        </div>
                        <div class="form-group col-12 mb-3">
                            <label for="">Tải vào:</label>
                            <select class="form-control" name="manga">							
<option name="manga" value="{{$manga->slug}}">{{$manga->title}}</option>
</select>
                        </div>
                        
                        <div class="form-group col-12 mb-3">
                        <label for="">Hãy kiểm tra cẩn thận trước khi tải, nếu sai sẽ sửa rất nhiều.</label>
                        </div>

                        <div class="form-group col-6">
                            <button class="btn btn-primary btn-load">Lấy dữ liệu</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-10 steps d-none" id="show-chapter-urls">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-12">
                            <h5>Danh sách các chapter bị thiếu (Tổng: <span id="total-chapters-count">0</span>)</h5>
                            <b>(Bạn có thể nhấp vào mỗi liên kết để kiểm tra xem chapter có tương ứng với truyện không)</b>
                            <div class="form-group row">
                                <div class="w-100 px-3 col-form-label overflow-auto mb-2 mx-3" id="chapter-url-list"
                                    style="height: 20rem;background-color: #f7f7f7">
                                </div>
                            </div>
                            
                            <div class="form-group col-12 mb-3">
                            <label for="">Tải vào:</label>
                            <select class="form-control" name="manga">							
                            <option name="manga" value="{{$manga->slug}}">{{$manga->title}}</option>
                            </select>
                            </div>

                            <button class="btn btn-secondary btn-cancel btn-previous">Trước</button>
                            <button class="btn btn-primary btn-crawl">Tải các chapter này</button>
                            <button class="btn btn-primary btn-complete invisible">Xong</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <link rel="stylesheet" href="{{ asset('/packages/select2/dist/css/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('/packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('/packages/select2/dist/js/select2.full.min.js') }}"></script>

    @push('after_scripts')
    @endpush
    <script>
        var missing_chapters;
        var chapter_updated_at;

        $('.btn-load').click(function(event) {
            event.preventDefault();
            var button = $(this);
            button.html(`Đang lấy dữ liệu, vui lòng đợi...`);
            button.prop('disabled', true);
            var link = document.getElementById('linkInput').value;
            if(link.trim() == ''){
                alert('Vui lòng nhập URL truyện')
                button.html(`Lấy dữ liệu`);
                button.prop('disabled', false);
                return false;
            }

                $.ajax({
                url: "{{ backpack_url('manga/crawl_chapter/'.$manga->slug. '/fetch') }}?link=" + link,
                type: 'GET',
                contentType: false,
                processData: false,
                success: function(response) {
                    const error = response.error;
                    if(error){
                        button.text('Lấy dữ liệu');
                        button.prop('disabled', false);
                        alert(error)
                        return;
                    }
                    missing_chapters = response.missing_chapters;
                    console.log(response)
                    button.html(`Lấy dữ liệu`);
                    button.prop('disabled', false);

                    $('#show-chapter-urls').removeClass('d-none')
                    $('#total-chapters-count').text(missing_chapters.length);
                    const chapterUrlsList = missing_chapters.map(chapter => {
                    return `<p class="crawling-chapter text-muted d-flex justify-content-between" data-chapter_url="${chapter.chapter_api_data}">
                            <span class="chapter_number text-dark">Chapter ${chapter.chapter_name} </span>
                            <span><a target="_blank"  href="${chapter.chapter_api_data}">Nhấp vào liên kết để kiểm tra lại</a></span>
                            <span class="status text-dark">Đang đợi tải về</span>
                        </p>`;
                        
                        chapter_updated_at = chapter.chapter_updated_at;
                    })

                    $('#chapter-url-list').html(chapterUrlsList)
                    $('#fetch-chapters').addClass('d-none')
                    // const error = response.error;
                },
                error: function(xhr, status, error) {
                    button.html(`Lấy dữ liệu`);
                    button.prop('disabled', false);
        }
            });
        // window.location.href = this.getAttribute('href');
    });

    $('.btn-crawl').click(function(event) {
    var button = $(this);
    button.html(`Đang tải các chapter...`);
    button.prop('disabled', true);

    var index = 0;
    function processChapter(index) {
        if (index < missing_chapters.length) {
            var missing_chapter = missing_chapters[index];
            const chapter_url = missing_chapter.chapter_api_data;
            const chapter_number = missing_chapter.chapter_name;
            const chapter_updated_at = missing_chapter.chapter_updated_at;
            $(`.crawling-chapter[data-chapter_url="${chapter_url}"] .status`).removeClass('text-muted').addClass('text-info').html('Đang tải...');

            // var mangaDataToSend = JSON.stringify({ manga_data: manga_data });
            console.log(chapter_updated_at)

            $.ajax({
                url: `{{ backpack_url('manga/crawl_chapter/'.$manga->slug. '/${chapter_number}/crawl') }}?link=${chapter_url}`,
                type: 'POST',
                data: JSON.stringify({ chapter_updated_at: chapter_updated_at }),
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    $(`.crawling-chapter[data-chapter_url="${chapter_url}"] .status`).removeClass('text-info').addClass('text-success').html('Thành công').addClass('crawl-success');
                    console.log(response);
                    processChapter(index + 1);
                },
                error: function(xhr, status, error) {
                    $(`.crawling-chapter[data-chapter_url="${chapter_url}"] .status`).removeClass('text-info').addClass('text-danger').html('Thất bại');
                    console.error(`Error processing chapter ${chapter_number}:`, error);
                    processChapter(index + 1);
                }
            });
        } else {
            // Khi đã xử lý tất cả các chapters
            alert('Tải chapter thành công !');
            button.hide();
            $('.btn-complete').removeClass('invisible');
        }
    }

    processChapter(index);
});

const prev = (el) => {
            $('.steps').addClass('d-none');
            $(el).closest('.steps').prev().removeClass('d-none');
        }

$('.btn-previous').click(function() {
            prev(this);
        })

        
$('.btn-complete').click(function() {
    location.reload();
        })

            
    </script>
@endsection
