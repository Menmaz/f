<!DOCTYPE html>
<html data-a="af266caa520a" data-g="bad">
  <head>
    <meta charset="utf-8">
    <title>Thông báo</title>
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
        @php
           $notifications = auth()->user()->notifications()->where('status', 0)->orderByDesc('created_at')->take(5)->get();
        @endphp

    <span class="bg"></span>
    <div class="wrapper"> @include('frontend-web.home.components.header') <main class="user-panel">
        <div class="container">
          <div class="main-inner"> @include('frontend-web.user.components.sidebar') <aside class="content">
          <section class="u-notify">
        <div class="head">
         <h2>Thông báo</h2>
         @if($notifications->isNotEmpty())
         <button class="btn btn-sm btn-secondary mark-as-read">
          <i class="fa-solid fa-check"></i> Đã đọc tất cả 
        </button>
        @endif
        </div>
        <div class="original card-sm">
        @forelse($notifications as $notification)
         <a data-id="{{ $notification->id }}" class="unit item " href="{{ route('manga.detail', ['slug' => $notification->manga->slug]) }}">
          <div class="poster">
           <div>
            <img src="{{ $notification->manga->cover }}">
           </div>
          </div>
          <div class="info">
           <h6>{{ $notification->manga->title }}</h6>
           <span>{{ $notification->message }}</span>
           <p>{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</p>
          </div>
         </a>
         @empty
         Chưa có thông báo nào !
         @endforelse
        </div>
       </section>
            </aside>
          </div>
        </div>
      </main>
    
      @include('frontend-web.home.components.footer')

    </div>

    <script>
      $('.mark-as-read').click(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('user.read-notifications') }}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('.mark-as-read').hide();
                        toastr.success("Đã cập nhật");
                    } else {
                     toastr.error("Có lỗi");
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    toastr.error("Có lỗi");
                }
            });
        });
    </script>
  </body>
</html>