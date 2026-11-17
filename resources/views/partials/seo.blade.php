<title>{{ $seo['title'] }}</title>
@if($seo['description'])<meta name="description" content="{{ $seo['description'] }}">@endif
<meta name="robots" content="{{ $seo['noindex'] ? 'noindex,follow' : 'index,follow,max-image-preview:large' }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
@if($seo['prev'])<link rel="prev" href="{{ $seo['prev'] }}">@endif
@if($seo['next'])<link rel="next" href="{{ $seo['next'] }}">@endif
<meta property="og:site_name" content="{{ setting('site_name', 'Turtle Boss') }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:title" content="{{ $seo['og_title'] }}">
@if($seo['og_description'])<meta property="og:description" content="{{ $seo['og_description'] }}">@endif
<meta property="og:url" content="{{ $seo['canonical'] }}">
@if($seo['image'])<meta property="og:image" content="{{ $seo['image'] }}">@endif
<meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['og_title'] }}">
@if($seo['og_description'])<meta name="twitter:description" content="{{ $seo['og_description'] }}">@endif
@if($seo['image'])<meta name="twitter:image" content="{{ $seo['image'] }}">@endif
@if($g = setting('gsc_verification'))<meta name="google-site-verification" content="{{ $g }}">@endif
@foreach($seo['schema'] as $s)<script type="application/ld+json">{!! json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
