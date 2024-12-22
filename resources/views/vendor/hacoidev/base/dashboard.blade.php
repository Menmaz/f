@extends(backpack_view('blank'))

@php
    $widgets['before_content'] = [
        [
            'type' => 'alert',
            'class' => 'alert alert-light mb-2 col-12 text-dark',
            'heading' => '10 CMS',
            'content' =>
                '
                Phiên bản: <span class="text-danger text-break">' .
                config('custom.cms_version') .
                '</span><br/>
                Trang chủ: <a href="https://trieubui.top">Manga</a><br/>
            ',
            'close_button' => true, // show close button or not
        ],
    ];
@endphp

@section('content')
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"
        integrity="sha512-d8F1J2kyiRowBB/8/pAWsqUl0wSEOkG5KATkVV4slfblq9VRQ6MyDZVxWl2tWd+mPhuCbpTB4M7uU/x9FlgQ9Q=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        // jQuery(document).ready(function($) {
        //     $('.counter').counterUp({
        //         delay: 10,
        //         time: 500
        //     });
        // });
    </script>
    <style>
        .card-counter {
            box-shadow: 2px 2px 10px #DADADA;
            margin: 5px 0;
            padding: 20px 10px;
            background-color: #fff;
            height: 100px;
            border-radius: 5px;
            transition: .3s linear all;
        }

        .card-counter:hover {
            box-shadow: 4px 4px 20px #DADADA;
            transition: .3s linear all;
        }

        .card-counter.primary {
            background-color: #007bff;
            color: #FFF;
        }

        .card-counter.danger {
            background-color: #ef5350;
            color: #FFF;
        }

        .card-counter.success {
            background-color: #66bb6a;
            color: #FFF;
        }

        .card-counter.info {
            background-color: #26c6da;
            color: #FFF;
        }

        .card-counter i {
            font-size: 5em;
            opacity: 0.2;
        }

        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 32px;
            display: block;
        }

        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            font-style: italic;
            text-transform: capitalize;
            opacity: 0.5;
            display: block;
            font-size: 18px;
        }
    </style>
    
    <div class="row">
        <div class="col-md-2">
            <div class="card-counter primary">
                <i class="la la-book"></i>
                <span class="count-numbers counter" id="count_mangas">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <span class="count-name">Tổng số truyện</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter info">
                <i class="las la-server"></i>
                <span class="count-numbers counter" id="count_chapters">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <span class="count-name">Tổng số tập</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter light bg-dark">
                <i class="la la-eye"></i>
                <span class="count-numbers counter" id="count_total_views">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <span class="count-name">Tổng số lượt xem</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter danger">
                <i class="las la-bug"></i>
                <span class="count-numbers counter" id="count_error_mangas">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <span class="count-name">Truyện lỗi</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter success">
                <i class="las la-user"></i>
                <span class="count-numbers counter" id="count_users">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <span class="count-name">Users</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter text-dark">
                <i class="las la-puzzle-piece"></i>
                <span class="count-numbers counter">{{ count(config('plugins', [])) }}</span>
                <span class="count-name">Plugins</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter info">
                <i class="las la-server"></i>
                <span class="count-numbers counter" id="count_waiting_to_upload_chapters">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <a href="{{ url('admin/chapter?status=waiting_to_upload') }}" class="count-name" style="color:white">Chapter đang đợi tải lên storage</a>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter success">
                <i class="las la-server"></i>
                <span class="count-numbers counter" id="count_uploaded_to_storage_chapters">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </span>
                <a href="{{ url('admin/chapter?status=uploaded_to_storage') }}" class="count-name" style="color:white">Chapter đã tải lên storage</a>
                <!-- <a class="btn btn-sm btn-link" target="_blank" href="{{ url('admin/chapters/') }}" data-toggle="tooltip" title="Danh sách chương">Danh sách chương</a> -->
               
            </div>
        </div>

    </div>
    <div class="row">
        <div class="p-3 col-md-2">
            <table id="top-day" class="table table-sm table-dark border-light">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP NGÀY</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Hàng sẽ được cập nhật bởi JavaScript -->
                </tbody>
            </table>

        </div>
        <div class="p-3 col-md-2">
            <table id="top-week" class="table table-sm table-dark border-light">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP TUẦN</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Hàng sẽ được cập nhật bởi JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="p-3 col-md-2">
            <!-- <table class="table table-sm">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP THÁNG</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                        <tr>
                            <td><a target="_blank" href=""></a></td>
                            <td class="text-right"><span class="badge badge-success"><i class="las la-eye"></i> </span></td>
                        </tr>
                </tbody>
            </table> -->
            <table id="top-month" class="table table-sm table-dark border-light">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP THÁNG</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Hàng sẽ được cập nhật bởi JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="p-3 col-md-2">
            <table id="top-year" class="table table-sm table-dark border-light">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP NĂM</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Hàng sẽ được cập nhật bởi JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="p-3 col-md-2">
            <table id="top-all" class="table table-sm table-dark border-light">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP TỔNG</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Hàng sẽ được cập nhật bởi JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
@endsection

<script>
    // Hàm fetch dữ liệu từ API
async function fetchDashboardCounterInfo() {
    try {
        const response = await fetch('{{ backpack_url("/dashboard-counter-info")}}')
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        updateDashboardCounter(data); 

        fetchDashboardMangasInfo(); 
    } catch (error) {
        console.error('Fetch error:', error); // Xử lý lỗi
    }
}

async function fetchDashboardMangasInfo() {
    try {
        const response = await fetch('{{ backpack_url("/dashboard-mangas-info")}}')
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        updateDashboardMangas(data); 
    } catch (error) {
        console.error('Fetch error:', error); // Xử lý lỗi
    }
}

// Hàm cập nhật giao diện với dữ liệu từ API
function updateDashboardCounter(data) {
    // Cập nhật giá trị vào các thành phần HTML tương ứng
    document.querySelector('#count_mangas').textContent = data.count_movies;
    document.querySelector('#count_chapters').textContent = data.count_episodes;
    document.querySelector('#count_total_views').textContent = data.count_total_views;
    document.querySelector('#count_users').textContent = data.count_users;
    document.querySelector('#count_error_mangas').textContent = data.count_episodes_error;
    document.querySelector('#count_waiting_to_upload_chapters').textContent = data.count_waiting_to_upload_chapters;
    document.querySelector('#count_uploaded_to_storage_chapters').textContent = data.count_uploaded_to_storage_chapters;
}

function updateDashboardMangas(data) {

    // Cập nhật danh sách các bảng   
    const topDay = Object.values(data.top_view_day).map(createTableRow).join('');
    document.querySelector('#top-day tbody').innerHTML = topDay;

    const topWeek = Object.values(data.top_view_week).map(createTableRow).join('');
    document.querySelector('#top-week tbody').innerHTML = topWeek;

    const topMonth = Object.values(data.top_view_month).map(createTableRow).join('');
    document.querySelector('#top-month tbody').innerHTML = topMonth;

    const topYear = Object.values(data.top_view_year).map(createTableRow).join('');
    document.querySelector('#top-year tbody').innerHTML = topYear;

    const topAll = Object.values(data.top_view_all).map(createTableRow).join('');
    document.querySelector('#top-all tbody').innerHTML = topAll;
}

// Hàm tạo hàng của bảng từ dữ liệu
function createTableRow(item) {
    return `
        <tr>
            <td><a class="text-white" target="_blank" href="https://trieubui.top/truyen/${item.slug}">${item.title}</a></td>
            <td class="text-right"><span class="badge badge-success"><i class="las la-eye"></i> ${item.views}</span></td>
        </tr>
    `;
}

// Gọi hàm fetch dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', fetchDashboardCounterInfo);
</script>
