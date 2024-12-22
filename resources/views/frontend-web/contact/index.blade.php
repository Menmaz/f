<!DOCTYPE html>
<html data-a="af266caa520a" data-g="good">
  <head>
  @include('frontend-web.partials.seo')
  </head>
  <body>
    <span class="bg"></span>
    <div class="wrapper">

@include('frontend-web.home.components.header')

<main class="">
 <div class="container mt-5 d-flex flex-column align-items-center">
  <h1 class="text-white">Liên hệ với chúng tôi</h1>
  <span>Nếu bạn muốn hỏi về điều gì đó, cung cấp phản hồi, vui lòng liên hệ với chúng tôi.</span>
  <div class="default-style p-4 mt-5 max-sm w-100">
   <form method="post" class="ajax-contact" action="{{ route('ajax.send-contact') }}">
    @csrf
    <div class="form-group">
     <input type="text" class="form-control" name="email" placeholder="Email">
    </div>
    <div class="form-group">
     <input type="text" class="form-control" name="subject" placeholder="Tiêu đề">
    </div>
    <div class="form-group">
     <textarea class="form-control" name="message" placeholder="Tôi có thể giúp gì cho bạn" rows="3"></textarea>
    </div>
    <center class="form-group">
    <div class="g-recaptcha" data-sitekey="{{ config('custom.recapcha_site_key') }}"></div>
    </center>
    <button class="btn w-100 btn-lg btn-primary">Gửi <i class="fa-solid fa-paper-plane-top"></i>
    </button>
    <div class="loading" style="display: none;"></div>
   </form>
  </div>
 </div>
</main>

@include('frontend-web.home.components.footer')

    </div>

    <script>
        $(document).ready(function() {
    $('form.ajax-contact').on('submit', function(event) {
        event.preventDefault();
        var $form = $(this);
        var $button = $form.find('button');
        var $loading = $form.find('.loading');

        $loading.show();
        $button.prop('disabled', true);

        $.ajax({
            type: $form.attr('method'),
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function(response) {
                const status = response.status;
                const message = response.message;
                if(status == 'success'){
                    toastr.success(message)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000)
                } else {
                    if (Array.isArray(message)) {
                        $.each(message, function(index, ms) {
                            toastr.error(ms);
                        });
                    } else {
                        toastr.error(message);
                    }
                }
                
            },
            error: function(xhr, status, error) {
                toastr.error('Lỗi hệ thống');
            }, 
            complete: function(){
                $loading.hide();
                    $button.prop('disabled', false);
                    $form[0].reset();
            }
        });
    });
});

    </script>

  </body>
</html>
