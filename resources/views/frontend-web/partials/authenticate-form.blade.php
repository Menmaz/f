<div class="modal fade" id="sign">
 <div class="modal-dialog limit-w modal-dialog-centered cts-wrapper">
  <div class="modal-content p-4 cts-block" data-name="signin">
   <div class="modal-close" data-dismiss="modal">
    <i class="fa-solid fa-xmark"></i>
   </div>
   <h4 class="text-white">Chào mừng bạn quay trở lại!</h4>
   <p class="text-muted">Đăng nhập</p>
   <form class="ajax-login mt-2" action="{{ route('ajax.login') }}" method="post" data-broadcast="user:updated"> @csrf <div class="form-group">
     <input type="text" class="form-control" placeholder="Username hoặc Email" name="username" required="" />
    </div>
    <div class="form-group">
     <input type="password" class="form-control" placeholder="Mật khẩu" name="password" required="" />
    </div>
    <div class="form-group text-center">
     <a class="cts-switcher" data-target="forgot" href="#">Quên mật khẩu?</a>
    </div>
    <center class="form-group">
    <div class="g-recaptcha captcha d-flex justify-content-center" data-sitekey="{{ config('custom.recapcha_site_key') }}"></div>
    </center>
    <button class="btn my-3 btn-lg btn-primary w-100"> Đăng nhập <i class="fa-solid fa-angle-right"></i>
    </button>
   </form>
   <div class="text-center"> Chưa có tài khoản? <a class="text-primary1 cts-switcher" href="#" data-target="signup">Đăng ký</a>
   </div>
  </div>

  <div class="modal-content p-4 cts-block" data-name="signup" style="display: none">
   <div class="modal-close" data-dismiss="modal">
    <i class="fa-solid fa-xmark"></i>
   </div>
   <h4 class="text-white">Tạo tài khoản mới</h4>
   <p class="text-muted">Tạo tài khoản để dùng nhiều chức năng hơn</p>
   <form class="ajax-register mt-2" action="{{ route('ajax.register') }}" method="post" data-broadcast="user:updated">
    @csrf 
    <div class="form-group">
     <input type="text" class="form-control" placeholder="Tên tài khoản" name="username" required="" />
    </div>
    <div class="form-group">
     <input type="email" class="form-control" placeholder="Email" name="email" required="" />
    </div>
    <div class="form-group">
     <input type="password" class="form-control" placeholder="Mật khẩu" name="password" />
    </div>
    <div class="form-group">
     <input type="password" class="form-control" placeholder="Nhập lại mật khẩu" name="password_confirmation" />
    </div>
    <center class="form-group">
    <div class="g-recaptcha captcha d-flex justify-content-center" data-sitekey="{{ config('custom.recapcha_site_key') }}"></div>
    </center>
    <button class="btn my-3 btn-lg btn-primary w-100"> Đăng ký <i class="fa-solid fa-angle-right"></i>
    </button>
   </form>
   <div class="text-center"> Đã có tài khoản? <a href="#" class="text-primary1 cts-switcher" data-target="signin">Đăng nhập</a>
   </div>
  </div>

  <div class="modal-content p-4 cts-block" data-name="forgot" style="display: none">
   <div class="modal-close" data-dismiss="modal">
    <i class="fa-solid fa-xmark"></i>
   </div>
   <h4 class="text-white">Quên mật khẩu</h4>
   <p class="text-muted">Chúng tôi sẽ gửi cho bạn mã OTP đến email của bạn</p>
   <form class="ajax mt-2" action="ajax/user/forgot-password" method="post">
    <div class="form-group">
     <input type="text" class="form-control" placeholder="Email" name="account" required="" />
    </div>
    <center class="form-group">
    <div class="g-recaptcha captcha d-flex justify-content-center" data-sitekey="{{ config('custom.recapcha_site_key') }}"></div>
    </center>
    <button class="btn my-3 btn-lg btn-primary w-100"> Gửi mã <i class="fa-solid fa-angle-right"></i>
    </button>
   </form>
   <div class="text-center"> Đã có tài khoản? <a href="#" class="text-primary1 cts-switcher" data-target="signin">Đăng nhập</a>
   </div>
  </div>

 </div>
</div>

<script>
    //ĐĂNG NHẬP AJAX
  $('.ajax-login').on('submit', function(e) {
   $('.ajax-login button').text('Vui lòng đợi ...')
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
      toastr.success(response.message);
      setTimeout(()=> {
        location.reload();
      }, 500)
     } else {
      toastr.error(response.message);
     }
     $('.ajax-login button').text('Đăng nhập')
    },
    error: function() {
        toastr.error("Lỗi hệ thống !");
        $('.ajax-login button').text('Đăng nhập')
    },
   });
  });

  //ĐĂNG KÝ AJAX
  $('.ajax-register').on('submit', function(e) {
    $('.ajax-register button').text('Vui lòng đợi ...')
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
      form.trigger('reset');  
      toastr.success(response.message)
      setTimeout(() => {
        window.location.href = response.redirect;
      }, 500)
     } else {
        toastr.error(response.message)
     }
     $('.ajax-register button').text('Đăng ký')
    },
    error: function(xhr) { 
        toastr.error("Lỗi hệ thống !")
        $('.ajax-register button').text('Đăng ký')
    }
   });
  });  
</script>