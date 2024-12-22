<!DOCTYPE html>
<html >
  <head>
  @include('frontend-web.partials.seo')
  </head>
  <body>
    <span class="bg"></span>
    <div class="wrapper">

@include('frontend-web.home.components.header')

<main class="">
    <div class="container py-5">
        <section>
        <div class="head">
            <h1>Điều khoản và chính sách</h1>
        </div>     
        {!! $terms !!}
        </section>
      </div>
</main>

@include('frontend-web.home.components.footer')

    </div>
  </body>
</html>
