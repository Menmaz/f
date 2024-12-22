<footer>
 
    <div class="gotop" style="bottom: 40px; margin-right: 10px">
  <button class="btn" id="go-top" style="padding: 0; ">
   <i class="fa-solid fa-rocket-launch fa-xl" ></i>
  </button>
</div>

 <div class="wrap">
  <div class="container">
   <div class="inner">
    <div>
     <div class="logo">
      <img src="{{ asset('storage/images/icon/logo.png') }}" alt="{{ config('custom.frontend_name') }}" />
     </div>
     <p>© {{ date('Y') }} {{ config('custom.frontend_name') }}</p>
    </div>
    <nav>
     <ul>
      <li>
       <a href="{{ route('contact') }}">Liên hệ với chúng tôi</a>
      </li>
      <li>
       <a href="{{ route('terms') }}">Điều khoản và chính sách</a>
      </li>
      <li>
       <a data-toggle="modal" data-target="#request" href="#">Gửi yêu cầu</a>
      </li>
     </ul>
    </nav>
   </div>
  </div>
  <div class="abs-footer">
   <div class="container">
    <div class="wrapper">
     <span>Copyright © {{ config('custom.frontend_name') }} {{ date('Y') }} </span>
     <span><i class="fa-solid fa-heart"></i> {{ config('custom.frontend_name') }} - All rights reserved.</span>
    </div>
   </div>
  </div>
 </div>
</footer>

@include('frontend-web.partials.authenticate-form')

<div class="modal fade" id="request">
 <div class="modal-dialog limit-w modal-dialog-centered">
  <div class="modal-content p-4">
   <div class="modal-close" data-dismiss="modal">
    <i class="fa-solid fa-xmark"></i>
   </div>
   <h4 class="text-white">Gửi yêu cầu</h4>
   <p class="text-muted mt-2"> Nếu bạn không tìm thấy truyện bạn muốn, vui lòng gửi yêu cầu. Chúng tôi sẽ cố gắng cung cấp nó sớm nhất có thể. </p>
   <form class="ajax mt-3 ajax-manga-request" action="{{ route('ajax.send-manga-request') }}" method="post">
   @csrf 
   <div class="form-group">
     <input type="text" class="form-control" placeholder="Nhập tên truyện" name="title" required="" />
    </div>
    <div class="form-group">
     <textarea class="form-control" name="message" rows="3" placeholder="Viết gì thêm nếu có"></textarea>
    </div>
    <center class="form-group">
    <div class="g-recaptcha captcha d-flex justify-content-center" data-sitekey="{{ config('custom.recapcha_site_key') }}"></div>
    </center>
    <button type="submit" class="submit btn mt-3 btn-lg btn-primary w-100"> Gửi yêu cầu <i class="fa-solid fa-angle-right"></i>
    </button>
   </form>
  </div>
 </div>
</div>
<div id="toast" class="toast">
 <div class="alert alert-danger">
  <i class="fa-solid fa-exclamation-circle"></i>
  <span class="mx-2">Please login to use this feature.</span>
  <button type="button" class="close" data-dismiss="alert">
   <span>×</span>
  </button>
 </div>
</div>

    <script>
         $(document).ready(function() {
            @auth
            $(".bookmark .btn").click(function(e) {
                e.stopPropagation();
                $(this).siblings('.dropdown-menu').slideToggle(100);
            });

            $(".bookmark .dropdown-item").click(function(e) {
              e.preventDefault();
              var action = $(this).data('action');
              var mangaId = $(this).closest('.favourite').data('id');
              
              if (action) {
                  $.ajax({
                      url: "{{ route('user.save-bookmark') }}",
                      type: 'POST',
                      data: {
                          manga_id: mangaId,
                          action: action,
                          _token: '{{ csrf_token() }}'
                      },
                      success: function(response) {
                          if (response.success) {
                            if(action == 'delete'){
                                $(".bookmark .remove-bookmark").hide();
                            } else {
                                $(".bookmark .remove-bookmark").show(); // Hiển thị nút Remove
                              $(".bookmark .dropdown-item[data-action='" + action + "']").addClass('active'); // Thêm lớp active vào mục đã chọn
                            }
                            $(".bookmark .dropdown-item").removeClass('active'); // Xóa lớp active khỏi tất cả các mục  
                              toastr.success(response.message);
                          } else {
                              toastr.error('Có lỗi xảy ra.');
                          }
                      }
                    });
                }
            });
            @else
            $(".bookmark .btn").click(function(e) {
                e.preventDefault();
                toastr.error('Bạn cần đăng nhập để thực hiện thao tác này.');
            });
            @endauth
        });
    </script>

<script>
 //cấu hình toastr
 toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": false,
  "progressBar": true,
  "positionClass": "toast-bottom-right",
  "preventDuplicates": false,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": "5000",
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
 };

 $(document).ready(function() {
  var $goTopButton = $('.gotop');
  $(window).on('scroll', function() {
   if ($(this).scrollTop() > 200) {
    $goTopButton.addClass('show');
   } else {
    $goTopButton.removeClass('show');
   }
  });
  $goTopButton.on('click', function() {
   $('html, body').animate({
    scrollTop: 0
   }, 'smooth');
  });
 });
</script>

<script>
 $(document).ready(function() {
  $('.cts-switcher').click(function(event) {
            event.preventDefault();
            var target = $(this).attr('data-target');
            $('.cts-block').hide();
            $('[data-name="' + target + '"]').fadeIn();
  });
    
  //GỬI YÊU CẦU
  $('.ajax-manga-request').on('submit', function(e) {
    $('.ajax-manga-request button').text('Đang gửi yêu cầu...')
   e.preventDefault();
   let form = $(this);
   let actionUrl = form.attr('action');
   let formData = form.serialize();
   $.ajax({
    url: actionUrl,
    type: 'POST',
    data: formData,
    success: function(response) {
     if (response.status === 'success') {
    //   form.trigger('reset'); 
      toastr.success(response.message)
     } else {
        toastr.error(response.message)
     }
     $('.ajax-manga-request button').text('Gửi yêu cầu')
    },
    error: function(xhr) { 
        toastr.error("Lỗi hệ thống !")
        $('.ajax-manga-request button').text('Gửi yêu cầu')
    }
   });
  });   
 });
</script>