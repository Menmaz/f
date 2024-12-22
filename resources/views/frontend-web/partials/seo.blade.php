<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $seoData['title'] }}</title> 
{!! ltrim($seoData['head_tags_meta']) !!}
<meta name="keywords" content="{{ $seoData['keywords_meta'] }}"/>
<meta name="description" content="{{ $seoData['title'] }}" />
<link rel="canonical" href="{{ url()->current() }}" />
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=6.0, user-scalable=yes" />
<link rel="icon" type="image/png" href="{{ asset('frontend-web/images/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('frontend-web/images/favicon.png') }}"/>

<meta name="author" content="{{ config('custom.frontend_name') }}" />
<meta property="og:title" content="{{ $seoData['title'] }}" />
<meta property="og:description" content="{{ $seoData['description_meta'] }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset('frontend-web/images/favicon.png') }}" />
<meta property="og:image:height" content="360" />
<meta property="og:image:width" content="540" />
<meta property="og:site_name" content="{{ $seoData['title'] }}" />
<meta property="og:type" content="article" />

<meta itemprop="description" content="{{ $seoData['description_meta'] }}" />
<meta itemprop="name" content="{{ $seoData['title'] }}"/>
<meta itemprop="image" content="{{ $seoData['image_meta'] }}"/>
<meta itemprop="thumbnail" content="{{ $seoData['image_meta'] }}"/>
<link href="{{ url()->current() }}" rel="amphtml" />

<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta http-equiv="x-dns-prefetch-control" content="on" />
<![endif]-->
<meta name="copyright" content="Copyright © {{ date('Y') }} {{ config('custom.frontend_name') }}" />
<meta name="Author" content="Truyện Tranh {{ config('custom.frontend_name') }}" />
<script type="application/ld+json">
 {
  "@context": "http://schema.org",
  "@type": "WebSite",
  "url": "{{ config('custom.frontend_url') }}",
  "potentialAction": {
   "@type": "SearchAction",
   "target": "{{ config('custom.frontend_url') }}/tim-kiem?keyword={search_term_string}",
   "query-input": "required name=search_term_string"
  }
 }
</script>

{!! $seoData['site_script'] !!}