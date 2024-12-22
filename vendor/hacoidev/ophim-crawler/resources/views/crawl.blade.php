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
    <div class="row" >
        <div class="col-md-8 steps">
            <div class="card text-white" style="background-color: #42557B;">
                <div class="card-body">
                    <div class="row">
                        @csrf
                        <div class="form-group col-12 mb-3">
                            <label for="">Link</label>
                            <textarea class="form-control" rows="5" name="link">https://nettruyenhe.com/tim-truyen</textarea>
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
                                <!-- <small><i id="wait_message"></i></small>
                                <div class="w-100 px-3 col-form-label overflow-auto mx-3" id="logs"
                                    style="height: 5rem;background-color: #f7f7f7">

                                </div> -->
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

            const fetchApi = async (link, from, to) => {
                isFetching = true;
                const response = await fetch("{{ backpack_url('plugin/ophim-crawler/fetch') }}?" +
                    new URLSearchParams({
                        link
                    }));

                if (response.status >= 200 && response.status < 300) {
                    return {
                        response: response,
                        payload: await response.json()
                    }
                }

                throw {
                    response
                }
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
                fetchApi(link, current, current).then(res => {
                    if (res.payload.length > 0) {
                        listMovies = listMovies.concat(res.payload);
                        let curTotal = parseInt($('.total-movie-count').html());
                        let selectedCount = parseInt($('.selected-movie-count').html());
                        $('.total-movie-count').html(curTotal + res.payload.length)
                        $('.selected-movie-count').html(selectedCount + res.payload.length)
                    }
                }).catch(err => {
                    $('input[name="link"]').addClass('is-invalid');
                }).finally(() => {
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
                fetchApi(currentLink, 1, 1).then(res => {
                    if (res.payload.length > 0) {
                        listMovies = listMovies.concat(res.payload);
                        let curTotal = parseInt($('.total-movie-count').html());
                        let selectedCount = parseInt($('.selected-movie-count').html());
                        $('.total-movie-count').html(curTotal + res.payload.length)
                        $('.selected-movie-count').html(selectedCount + res.payload.length)
                    }
                }).catch(err => {
                    $('input[name="link"]').addClass('is-invalid');
                }).finally(() => {
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
                        `<p class="crawling-movie text-muted d-flex justify-content-between" data-slug="${item.slug}" data-url="${item.url}">
                            <span>${i+1}. ${item.name}</span>
                            <span class="status">Pending</span>
                        </p>`;
                })
                return html;
            }

            $('.total-crawl-count').html(values.length);
            $('#crawl-list').html(template(values));

            crawl($('.crawling-movie').first())

            next(this);
        })

        $('.btn-next').click(function() {
            next(this);
        })

        $('.btn-previous').click(function() {
            prev(this);
        })

        $('.bth-complete').click(function() {
            location.reload();
        })

        $('#check-all').change(function() {
            $('.movie-checkbox').prop('checked', $(this).prop('checked'))
            $('.selected-movie-count').html($('.movie-checkbox:checked').length)
        })

        $(document).on('change', '.movie-checkbox', function() {
            $('.selected-movie-count').html($('.movie-checkbox:checked').length)
        })

        $('.group-checkall').change(function() {
            $(`.${$(this).data('target')}`).prop('checked', this.checked);
        })

        const next = (el) => {
            $('.steps').addClass('d-none');
            $(el).closest('.steps').next().removeClass('d-none');
        }

        const prev = (el) => {
            $('.steps').addClass('d-none');
            $(el).closest('.steps').prev().removeClass('d-none');
        }

        
        const updateStatus = (slug, statusClass, statusText) => {
            const element = $(`.crawling-movie[data-slug="${slug}"]`);
            element.removeClass('text-muted text-info text-success text-danger').addClass(statusClass);
            element.find('.status').html(statusText);
        };

        const handleSuccess = (slug, startTime, payload) => {
            const endTime = Date.now();
            const elapsedSeconds = Math.round((endTime - startTime) / 1000);
            updateStatus(slug, 'text-success', `OK in ${elapsedSeconds} seconds`);
            $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-success');
            return payload.wait;
        };

        const handleError = (slug, err) => {
            console.log(err);
            updateStatus(slug, 'text-danger', 'Error');
            $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-failed');
            $(`#logs`).append(`<li class="text-danger">${slug} : ${err.payload?.message || 'Unknown error'}</li>`);
            return false;
        };

        const updateCounts = () => {
            $('.crawled-count').html($('.crawl-completed').length);
            $('.crawl-success-count').html($('.crawl-success').length);
            $('.crawl-failed-count').html($('.crawl-failed').length);
            $('.bth-complete').removeClass('invisible');
        };


        var wait = false;
        const crawl = (el) => {
            const slug = $(el).data('slug');
            const url = $(el).data('url')
            if (!slug) return;

            console.log(slug)
            console.log(url)

            var wait_timeout = 100;
            const startTime = Date.now();

            if (wait) {
                let timeout_from = $("input[name=timeout_from]").val();
                let timeout_to = $("input[name=timeout_to]").val();
                let maximum = Math.max(timeout_from, timeout_to);
                let minimum = Math.min(timeout_from, timeout_to);
                wait_timeout = Math.floor(Math.random() * (maximum - minimum + 1)) + minimum;
            }
            $(`.crawling-movie[data-slug="${slug}"] .status`).html(`Chờ ${wait_timeout}ms`);
            setTimeout(() => {
                processMovie(slug, url).then(res => {
                        $(`.crawling-movie[data-slug="${slug}"]`).removeClass('text-info');
                        $(`.crawling-movie[data-slug="${slug}"]`).addClass('text-success');
                        const endTime = Date.now();
                        const elapsedSeconds = Math.round((endTime - startTime) / 1000); // Tính thời gian trôi qua
                        $(`.crawling-movie[data-slug="${slug}"] .status`).html(`Đã xong trong ${elapsedSeconds} giây`);
                        $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-success');
                        // wait = res.payload.wait;
                        wait=false;
                }).catch(err => {
                    console.log('Error:', err);
                    $(`.crawling-movie[data-slug="${slug}"]`).removeClass('text-info');
                    $(`.crawling-movie[data-slug="${slug}"]`).addClass('text-danger');
                    $(`.crawling-movie[data-slug="${slug}"] .status`).html('Lỗi');
                    $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-failed');
                    // $(`#logs`).append(
                    //     `<li class="text-danger">${slug} : ${err}</li>`
                    // );
                    wait = false;
                }).finally(() => {
                    $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-completed');
                    $('.crawled-count').html($('.crawl-completed').length)
                    $('.crawl-success-count').html($('.crawl-success').length)
                    $('.crawl-failed-count').html($('.crawl-failed').length)
                    crawl($(el).next('.crawling-movie'))
                    $('.bth-complete').removeClass('invisible');
                })
            }, wait_timeout);


        }

        const processMovie = async (slug, url) => {
            $(`.crawling-movie[data-slug="${slug}"]`).removeClass('text-muted');
            $(`.crawling-movie[data-slug="${slug}"]`).addClass('text-info');
            $(`.crawling-movie[data-slug="${slug}"] .status`).html('Đang tải về... (0s)');

            let seconds = 0;
            const timerInterval = setInterval(() => {
                seconds++;
                $(`.crawling-movie[data-slug="${slug}"] .status`).html(`Đang tải về... (${seconds}s)`);
            }, 1000);

            const response = await fetch("{{ backpack_url('plugin/ophim-crawler/crawl') }}", {
                method: 'POST',
                headers: {
                    "Content-Type": "application/json",
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    slug,
                    url
                })
            });

            clearInterval(timerInterval)

            // const payload = await response.json();
            // const payload = await response.json();

            // if (response.status >= 200 && response.status < 300 && payload.message === 'OK') {
            // return {
            //     response: response,
            //     payload: payload
            // }
            // } else {
            //     throw {
            //         response: response,
            //         payload: payload
            //     }
            // }
        }
    </script>
@endsection
