<!DOCTYPE html>
<html>
  <head>
    @include('frontend-web.partials.seo')
  
  </head>
<body>
    <span class="bg"></span>
    <div class="wrapper">

@include('frontend-web.home.components.header')

      <main class="">
        <div class="container">
          <section class="mt-5">
            <div class="head">
              <h2>
                {{ $title }}
              </h2>
              <span>{{ $updatedMangas->total() }} truyện</span>
            </div>
            
            @include('frontend-web.manga-list.components.filter')
            @include('frontend-web.partials.manga-list')
            @include('frontend-web.manga-list.components.pagination')
            
          </section>
        </div>
      </main>

      <script src="{{ asset('frontend-web/js/home.js') }}"></script>
    
      @include('frontend-web.home.components.footer')

</div>
</body>
</html>