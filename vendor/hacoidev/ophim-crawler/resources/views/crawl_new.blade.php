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
        <h2>
            <span class="text-capitalize">Tải dữ liệu</span>
            <!-- <small>Crawler</small> -->
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 steps">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @csrf
                        <div class="form-group col-12 mb-3">
                            <label for="">Link</label>
                            <textarea class="form-control" rows="5" name="link">https://nettruyencc.com/tim-truyen</textarea>
                            <small><i>Mỗi link cách nhau 1 dòng</i></small>
                        </div>
                        <div class="form-group col-6">
                            <button class="btn btn-primary btn-load">Tải dữ liệu</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 steps d-none">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-12">
                            <h4>Chọn truyện</h4>
                            <p>Đã chọn <span class="selected-movie-count">0</span>/<span class="total-movie-count">0</span>
                                truyện</p>
                            <div class="form-group">
                                <input type="checkbox" id="check-all" checked>
                                <label class="form-check-label" for="check-all">Check All</label>
                            </div>
                            <div class="row px-3 my-3">
                                <div class="w-100 col-form-label overflow-auto" id="movie-list"
                                    style="height: 20rem;background-color: #f7f7f7">

                                </div>
                            </div>
                            <button class="btn btn-secondary btn-previous">Trước</button>
                            <button class="btn btn-primary btn-process">Tiếp</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-md-8 steps d-none">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-12">
                            <h4>Đang tiến hành...</h4>
                            <p>Crawl <span class="crawled-count">0</span>/<span class="total-crawl-count">0</span>
                                truyện (Thành công: <span class="crawl-success-count">0</span>, thất bại: <span
                                    class="crawl-failed-count">0</span>).</p>
                            <div class="form-group row">
                                <div class="w-100 px-3 col-form-label overflow-auto mb-2 mx-3" id="crawl-list"
                                    style="height: 20rem;background-color: #f7f7f7">

                                </div>
                                <small><i id="wait_message"></i></small>
                                <div class="w-100 px-3 col-form-label overflow-auto mx-3" id="logs"
                                    style="height: 5rem;background-color: #f7f7f7">

                                </div>
                            </div>
                            <h5 id="alert-after-completed"></h5>
                            <button class="btn btn-secondary btn-cancel btn-previous">Trước</button>
                            <button class="btn btn-primary bth-complete invisible">Xong</button>
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
        <script src="{{ asset('/packages/select2/dist/js/select2.full.min.js') }}"></script>
    @endpush
    <script>
        var isFetching = false;
        $('.btn-load').click(function(e) {
            if (isFetching) return;
            const btn = $(this);
            const link = $('textarea[name="link"]').val();
            const from = 1;
            const to = 1;

            if (!link) {
                $('textarea[name="link"]').addClass('is-invalid');
                return;
            }
            $('textarea[name="link"]').removeClass('is-invalid');

            const fetchApi = (link, from, to) => {
                isFetching = true;
                return $.ajax({
                    url: "{{ backpack_url('plugin/ophim-crawler/fetch') }}",
                    method: 'GET',
                    data: {
                        link: link,
                        from: from,
                        to: to
                    }
                });
            }

            const template = (data) => {
                let html = '';
                data.forEach((item, i) => {
                    html += `<div class="form-check checkbox">
                                        <input class="movie-checkbox" id="movie-${i}" type="checkbox" value="${encodeURI(JSON.stringify(item))}" checked>
                                        <label class="d-inline" for="movie-${i}">${item.name}</label>
                                    </div>`;
                })
                return html;
            }
            var listMovies = [];

            // 1 Link: Crawl từng page
            const crawlPages = (current) => {
                if (current > to) {
                    listMovies.sort(() => Math.random() - 0.5);
                    let movieList = $('#movie-list').html();
                    $('#movie-list').html(movieList + template(listMovies))
                    next(this)
                    $('.btn-load').html('Tải');
                    isFetching = false;
                    return
                }
                $('.btn-load').html(`Đang lấy dữ liệu, vui lòng đợi...`);
                $('.btn-load').prop('disabled', true);
                fetchApi(link, current, current).done(res => {
                    if (res.length > 0) {
                        listMovies = listMovies.concat(res);
                        let curTotal = parseInt($('.total-movie-count').html());
                        let selectedCount = parseInt($('.selected-movie-count').html());
                        $('.total-movie-count').html(curTotal + res.length)
                        $('.selected-movie-count').html(selectedCount + res.length)
                    }
                }).fail(err => {
                    $('textarea[name="link"]').addClass('is-invalid');
                }).always(() => {
                    crawlPages(current + 1)
                })
            }

            // Nhiều link: crawl từng link
            const crawlMultiLink = (arrLink, current) => {
                let currentLink = arrLink[current];
                if (!currentLink) {
                    listMovies.sort(() => Math.random() - 0.5);
                    let movieList = $('#movie-list').html();
                    $('#movie-list').html(movieList + template(listMovies))
                    next(this)
                    $('.btn-load').html('Tải');
                    isFetching = false;
                    return
                }
                $('.btn-load').html(`Đang tải...: Link ${current + 1}/${arrLink.length}`);
                fetchApi(currentLink, 1, 1).done(res => {
                    if (res.length > 0) {
                        listMovies = listMovies.concat(res);
                        let curTotal = parseInt($('.total-movie-count').html());
                        let selectedCount = parseInt($('.selected-movie-count').html());
                        $('.total-movie-count').html(curTotal + res.length)
                        $('.selected-movie-count').html(selectedCount + res.length)
                    }
                }).fail(err => {
                    $('textarea[name="link"]').addClass('is-invalid');
                }).always(() => {
                    crawlMultiLink(arrLink, current + 1)
                })
            }

            $('.total-movie-count').html(0);
            $('.selected-movie-count').html(0);
            $('#movie-list').html("");
            if (link.split("\n").length > 1) {
                crawlMultiLink(link.split("\n"), 0)
            } else {
                crawlPages(from);
            }
        })

        $('.btn-process').click(function() {
            const values = $(".movie-checkbox:checked")
                .map(function() {
                    return JSON.parse(decodeURI($(this).val()));
                }).get();

            const template = (data) => {
                let html = '';
                data.forEach((item, i) => {
                    html +=
                        `<p class="m-0 p-2 border-bottom">(${i + 1}) ${item.name} - <span class="text-muted"><i>Chờ</i></span></p>`;
                })
                return html;
            }

            $('.crawled-count').html(0);
            $('.total-crawl-count').html(values.length);
            $('.crawl-success-count').html(0);
            $('.crawl-failed-count').html(0);
            $('#crawl-list').html(template(values))
            next(this)
            const crawl = (current, data) => {
                if (!data[current]) {
                    $('#alert-after-completed').html("Đã hoàn tất!")
                    $('#wait_message').html("")
                    $('.bth-complete').removeClass('invisible');
                    $('.btn-cancel').prop('disabled', true);
                    return;
                }
                const url = data[current].url;
                const html = (status) =>
                    `(${current + 1}) ${data[current].name} - <span class="text-${status == 'Thành công' ? 'success' : 'danger'}"><i>${status}</i></span>`;
                const el = $('#crawl-list').children()[current];
                $('#wait_message').html(`Đang tải ${data[current].name}, vui lòng đợi...`)
                const process = (url) => {
                    return $.ajax({
                        url: "{{ backpack_url('plugin/ophim-crawler/crawl') }}",
                        method: 'POST',
                        data: {
                            url: url
                        }
                    });
                }
                process(url).done(res => {
                    $(el).html(html('Thành công'))
                    let crawlSuccessCount = parseInt($('.crawl-success-count').html());
                    $('.crawl-success-count').html(crawlSuccessCount + 1);
                }).fail(err => {
                    $(el).html(html('Thất bại'))
                    let crawlFailedCount = parseInt($('.crawl-failed-count').html());
                    $('.crawl-failed-count').html(crawlFailedCount + 1);
                }).always(() => {
                    let crawledCount = parseInt($('.crawled-count').html());
                    $('.crawled-count').html(crawledCount + 1);
                    crawl(current + 1, data);
                })
            }
            crawl(0, values);
        })

        $('.btn-previous').click(function() {
            prev(this)
        })

        $('#check-all').click(function() {
            const isChecked = $(this).is(':checked')
            $('.movie-checkbox').prop('checked', isChecked)
            $('.selected-movie-count').html(isChecked ? $('.total-movie-count').html() : 0)
        })

        $(document).on('change', '.movie-checkbox', function() {
            const checkedCount = $('.movie-checkbox:checked').length
            $('.selected-movie-count').html(checkedCount)
            $('#check-all').prop('checked', checkedCount == $('.movie-checkbox').length)
        })

        const next = (el) => {
            const current = $(el).closest('.steps');
            current.addClass('d-none');
            current.next().removeClass('d-none')
        }

        const prev = (el) => {
            const current = $(el).closest('.steps');
            current.addClass('d-none');
            current.prev().removeClass('d-none')
        }
    </script>
@endsection
