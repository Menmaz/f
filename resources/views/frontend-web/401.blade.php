<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>10Truyen</title>
    </head>
    <body>
        <span class="bg"></span>
        <div class="wrapper">
            @include('frontend-web.home.components.header')

            <main class="">
                <div class="container my-5 pt-5 text-center h-100">
                    <h1 class="display-1 text-white"><b>401</b></h1>
                    <p>(Unauthorized)</p>
                    <p>Bạn không có quyền truy cập trang này</p>
                    <a class="btn btn-primary mt-2" href="{{ route('home') }}"><i class="fa-regular fa-arrow-left"></i> Về trang chủ</a>
                </div>
            </main>

            @include('frontend-web.home.components.footer')
        </div>
    </body>
</html>
