<div class="nav-user" id="user"> 
    @auth 
      @php
         $notifications = auth()->user()->notifications()->where('status', 0)->orderByDesc('created_at')->take(5)->get();
      @endphp
    <div class="dropdown u-notify">
      <button class="btn nav-btn {{ $notifications->isNotEmpty() ? 'new' : '' }}" id="notification-btn" type="button" data-toggle="dropdown" aria-expanded="false" data-placeholder="false">
         <i class="fa-solid fa-bell"></i>
      </button>
      <div class="dropdown-menu noclose dropdown-menu-right py-0">
         <div class="head">
            <p class="mb-0">Thông báo</p>
            @if($notifications->isNotEmpty())
            <a class="small mark-as-read" href="#">
               <i class="fa-solid fa-check"></i> Đánh dấu đã đọc 
            </a>
            @endif
         </div>
         @if($notifications->isNotEmpty())
         <div class="items-wrap">
            <div class="original card-sm">
                @foreach($notifications as $notification)
               <a class="unit item " href="{{ route('manga.detail', ['slug' => $notification->manga->slug]) }}">
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
               @endforeach  
            </div>
         </div>
         <a href="{{ route('user.notification') }}" class="btn btn-primary w-100"><span>Xem tất cả</span><i class="fa-solid fa-chevron-right fa-xs"></i></a>
         @else
         <center class="p-3">Chưa có thông báo nào !</center>
         @endif
      </div>
   </div>

   <div class="dropdown u-menu">
      <button class="btn nav-btn" type="button" data-placeholder="false" data-toggle="dropdown" aria-expanded="false">
         <i class="fa-solid fa-user-vneck"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-right">
         <a class="dropdown-item" href="{{ route('user.profile') }}">
            <i class="fa-solid fa-user"></i> Hồ sơ </a>
         <a class="dropdown-item" href="{{ route('user.reading') }}">
            <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử đọc </a>
         <a class="dropdown-item" href="{{ route('user.bookmark') }}">
            <i class="fa-solid fa-bookmark"></i> Truyện đã lưu </a>
         <a class="dropdown-item" href="{{ route('user.notification') }}">
            <i class="fa-solid fa-bell"></i> Thông báo </a>
         <a class="dropdown-item" href="{{ route('user.settings') }}">
            <i class="fa-solid fa-gear"></i> Cài đặt </a>
         <a class="dropdown-item" href="{{ route('user.logout') }}">
            <i class="fa-solid fa-arrow-up-left-from-circle"></i>
            <span>Đăng xuất</span>
         </a>
      </div>
   </div> 
   @else 
   <button data-toggle="modal" data-target="#sign" class="btn btn-primary rounded-pill">
      <span class="d-none d-sm-inline pl-1 mr-1">Đăng nhập</span>
      <i class="d-inline d-sm-none fa-solid fa-user-vneck"></i>
      <i class="d-none d-sm-inline fa-solid fa-sm fa-angle-right"></i>
   </button>
   @endauth
</div> 

<script>
     $(document).ready(function() {
        $('#notification-btn').click(function() {
            $(this).removeClass('new');
        });

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

    });
</script>