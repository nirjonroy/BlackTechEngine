@php
    
    $seo = \Illuminate\Support\Facades\Schema::hasTable('site_settings') ? DB::table('site_settings')->first() : null;
    $SeoSettings = \Illuminate\Support\Facades\Schema::hasTable('seo_settings') ? DB::table('seo_settings')->first() : null;
    $googleAnalytic = \Illuminate\Support\Facades\Schema::hasTable('google_analytics') ? DB::table('google_analytics')->first() : null;
    
    $defaultTitle = $SeoSettings->seo_title ?? $seo->site_name ?? 'BlackTechEngine';
@endphp

<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', $defaultTitle)</title>

@hasSection('seos')
    @yield('seos')
@else
    @php
        $metaTitle = $SeoSettings->meta_title ?? $defaultTitle;
        $metaDescription = $SeoSettings->meta_description ?? $SeoSettings->seo_description ?? '';
        
       
        $metaImage = '';
        if (isset($SeoSettings->meta_image) && $SeoSettings->meta_image) {
            $metaImage = Str::startsWith($SeoSettings->meta_image, ['http://', 'https://']) 
                ? $SeoSettings->meta_image 
                : asset('storage/' . $SeoSettings->meta_image);
        }
        
        $siteName = $SeoSettings->site_name ?? $seo->site_name ?? 'BlackTechEngine';
    @endphp

    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="title" content="{{ $defaultTitle }}">
    @if($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if(isset($SeoSettings->keywords) && $SeoSettings->keywords)
        <meta name="keywords" content="{{ $SeoSettings->keywords }}">
    @endif
    @if(isset($SeoSettings->author) && $SeoSettings->author)
        <meta name="author" content="{{ $SeoSettings->author }}">
    @endif
    @if(isset($SeoSettings->publisher) && $SeoSettings->publisher)
        <meta name="publisher" content="{{ $SeoSettings->publisher }}">
        <meta property="article:publisher" content="{{ $SeoSettings->publisher }}">
    @endif
    @if(isset($SeoSettings->copyright) && $SeoSettings->copyright)
        <meta name="copyright" content="{{ $SeoSettings->copyright }}">
    @endif

    <meta property="og:title" content="{{ $metaTitle }}">
    @if($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
    @endif
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="628">

    <meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    @if($metaDescription)
        <meta name="twitter:description" content="{{ $metaDescription }}">
    @endif
    @if($metaImage)
        <meta name="twitter:image" content="{{ $metaImage }}">
    @endif
@endif

{{-- Google Analytics Integration --}}
@if(isset($googleAnalytic) && $googleAnalytic->status == 1)
    @php
        $analyticPayload = $googleAnalytic->analytic_script ?? $googleAnalytic->analytic_id ?? null;
        $hasScriptTag = $analyticPayload && stripos($analyticPayload, '<script') !== false;
    @endphp
    @if($analyticPayload)
        @if($hasScriptTag)
            {!! $analyticPayload !!}
        @else
            <script type="application/ld+json">{!! $analyticPayload !!}</script>
        @endif
    @endif
@endif

{{-- Dynamic Favicon from Site Settings --}}
@if(isset($seo) && $seo->favicon)
    <link rel="shortcut icon" href="{{ asset('storage/' . $seo->favicon) }}">
@endif