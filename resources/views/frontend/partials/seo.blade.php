<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $frontendSeo['title'] }}</title>
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="title" content="{{ $frontendSeo['title'] }}">
@if(filled($frontendSeo['description']))
    <meta name="description" content="{{ $frontendSeo['description'] }}">
@endif
@if(filled($frontendSeo['keywords']))
    <meta name="keywords" content="{{ $frontendSeo['keywords'] }}">
@endif
@if(filled($frontendSeo['author']))
    <meta name="author" content="{{ $frontendSeo['author'] }}">
@endif
@if(filled($frontendSeo['publisher']))
    <meta name="publisher" content="{{ $frontendSeo['publisher'] }}">
    <meta property="article:publisher" content="{{ $frontendSeo['publisher'] }}">
@endif
@if(filled($frontendSeo['copyright']))
    <meta name="copyright" content="{{ $frontendSeo['copyright'] }}">
@endif
<link rel="canonical" href="{{ $frontendSeo['canonical'] }}">
<meta property="og:title" content="{{ $frontendSeo['title'] }}">
@if(filled($frontendSeo['description']))
    <meta property="og:description" content="{{ $frontendSeo['description'] }}">
@endif
<meta property="og:url" content="{{ $frontendSeo['canonical'] }}">
<meta property="og:site_name" content="{{ $frontendSeo['siteName'] }}">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="website">
@if(filled($frontendSeo['image']))
    <meta property="og:image" content="{{ $frontendSeo['image'] }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="628">
@endif
<meta name="twitter:card" content="{{ $frontendSeo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $frontendSeo['title'] }}">
@if(filled($frontendSeo['description']))
    <meta name="twitter:description" content="{{ $frontendSeo['description'] }}">
@endif
@if(filled($frontendSeo['image']))
    <meta name="twitter:image" content="{{ $frontendSeo['image'] }}">
@endif
@if(filled($frontendSeo['favicon']))
    <link rel="shortcut icon" href="{{ $frontendSeo['favicon'] }}">
@endif

@if($frontendAnalytics && $frontendAnalytics['provider'] === 'ga4')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $frontendAnalytics['id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $frontendAnalytics['id'] }}');
    </script>
@elseif($frontendAnalytics && $frontendAnalytics['provider'] === 'gtm')
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $frontendAnalytics['id'] }}');
    </script>
@endif
