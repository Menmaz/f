<!DOCTYPE html>
<html data-a="af266caa520a" data-g="bad">
  <head>
    <meta charset="utf-8">
    <title>Cài đặt</title>
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
    <div class="wrapper"> @include('frontend-web.home.components.header') 
      
    <main class="user-panel">
    <div class="container">
     <div class="main-inner"> @include('frontend-web.user.components.sidebar') <aside class="content">
       <section>
        <div class="head sm-column">
         <h2>Cài đặt</h2>
        </div>
        <form class="advance-setting max-sm ajax" action="ajax/user/update" method="post">
         <div class="form-group">
          <div class="custom-control custom-switch">
           <input type="checkbox" class="custom-control-input" id="show_reading_in_home" name="settings[show_reading_in_home]" value="1" checked="">
           <label class="custom-control-label" for="show_reading_in_home">Hiện lịch sử đọc ở trang chủ</label>
          </div>
         </div>
        
         <div>
          <button class="btn w-100 mt-3 btn-lg btn-primary">
           <i class="fa-solid fa-check"></i> Lưu thay đổi </button>
         </div>
        </form>
       </section>
      </aside>
     </div>
    </div>
  </div>
  </main>
    
      @include('frontend-web.home.components.footer')

    </div>
  </body>
</html>