<!DOCTYPE html>
<html data-a="af266caa520a" data-g="bad">
  <head>
    <meta charset="utf-8">
    <title>Lịch sử đọc</title>
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
    <div class="wrapper"> @include('frontend-web.home.components.header') <main class="user-panel">
        <div class="container">
          <div class="main-inner"> @include('frontend-web.user.components.sidebar') <aside class="content">
              <section>
                <div class="head sm-column">
                  <h2>Lịch sử đọc</h2>
                  @if(session()->has('reading_mangas') && count(session('reading_mangas')) > 0)
                  <button class="btn btn-sm btn-secondary reading-clear">
                    <i class="fa-solid fa-broom-wide"></i> Xóa tất cả 
                  </button>
                  @endif
                </div>
                @if(session()->has('reading_mangas') && count(session('reading_mangas')) > 0)
                @include('frontend-web.partials.continue-reading-mangas')
                @else
                Chưa có
                @endif
              </section>
            </aside>
          </div>
        </div>
      </main>
    
      @include('frontend-web.home.components.footer')

    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.reading-clear').on('click', function() {
        if (confirm("Bạn có chắc muốn xóa tất cả?")) {
            $.ajax({
                url: "{{ url('user/clear-reading-mangas') }}",
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function(response) {
                    toastr.success('Xóa tất cả thành công!');
                    setTimeout(()=>{
                      location.reload();
                    }, 500)
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    toastr.error('Đã xảy ra lỗi. Vui lòng thử lại sau!');
                }
            });
        }
    });
    
    });
</script>
  </body>
</html>
