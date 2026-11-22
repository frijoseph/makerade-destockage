<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! Route::currentRouteName() === 'home' ? 'data-home' : '' !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — {{ $shop->shop_name }}@else{{ $shop->shop_name }}@endif</title>
    <meta name="description" content="{{ $shop->text('meta_description') }}">
    <meta name="theme-color" content="{{ $shop->theme_color }}">
    <link rel="icon" href="{{ $shop->image('favicon') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fresh.css') }}?v={{ filemtime(public_path('css/fresh.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fx.css') }}?v={{ filemtime(public_path('css/fx.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/live.css') }}?v={{ filemtime(public_path('css/live.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/promo.css') }}?v={{ filemtime(public_path('css/promo.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/notify.css') }}?v={{ filemtime(public_path('css/notify.css')) }}">
    @if ($shop->ai_enabled)<link rel="stylesheet" href="{{ asset('css/assistant.css') }}?v={{ filemtime(public_path('css/assistant.css')) }}">@endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css" integrity="sha384-86bgzEpZNdMv4V0h2BgSxQUadlX29O0I89TbSGZ4gFkGtpN8U+KAGcuDbqMdbl/M" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/selects.css') }}?v={{ filemtime(public_path('css/selects.css')) }}">
    <style id="primary-color">{!! $shop->primaryCss() !!}</style>
    <script>try{if(!sessionStorage.getItem('mk_splash')&&document.documentElement.hasAttribute('data-home'))document.documentElement.classList.add('splash-on')}catch(e){}</script>
</head>
<body data-page="{{ Route::currentRouteName() }}">
<div class="progress" id="progress"></div>
<div class="splash" id="splash" aria-hidden="true"><div class="bubbles" data-bubbles="18"></div><img src="{{ $shop->image('logo') }}" alt=""></div>

<div class="topstack" id="topstack">
@include('partials.announcement')
@include('partials.header')
</div>

<main id="main" class="page">
    @yield('content')
</main>

@include('partials.footer')

<script id="catalog" type="application/json">{!! json_encode(\App\Support\Catalog::clientCatalog(), JSON_HEX_TAG) !!}</script>
<script>
window.MK_BASE = @json(rtrim(url('/'), '/'));
window.MK_SHOP = {!! json_encode($shop->forScripts() + [
    'token' => csrf_token(),
    'endpoints' => [
        'checkout' => route('checkout.store'),
        'contact' => route('contact.store'),
        'newsletter' => route('newsletter.store'),
        'track' => route('track.lookup'),
        'coupon' => route('promo.coupon'),
        'spin' => route('wheel.spin'),
        'popupEvent' => url('/popups'),
        'account' => route('account'),
    ],
], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
window.mkMoney = function (n) {
    var m = window.MK_SHOP.money, parts = Math.abs(Number(n) || 0).toFixed(m.decimals).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, m.thousands);
    var s = (Number(n) < 0 ? '-' : '') + parts.join(m.decimal);
    return m.position === 'after' ? s + ' ' + m.symbol : m.symbol + s;
};
</script>
@include('partials.mobile-nav')
@include('partials.floating')
<div class="toast-wrap" id="toasts" aria-live="polite"></div>
<button class="to-top" id="toTop" aria-label="Retour en haut"><x-icon name="chev-down" style="transform:rotate(180deg)" /></button>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" integrity="sha384-YB/DdIkloKoRpclWB8bNcYXWakt57USgtQPDzvnIDHYU0lasD5eWlXVo1S4ODukY" crossorigin="anonymous"></script>
@include('partials.flash')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" integrity="sha384-cnROoUgVILyibe3J0zhzWoJ9p2WmdnK7j/BOTSWqVDbC1pVw2d+i6Q/1ESKJKCYf" crossorigin="anonymous"></script>
<script src="{{ asset('js/notify.js') }}?v={{ filemtime(public_path('js/notify.js')) }}" defer></script>
<script src="{{ asset('js/selects.js') }}?v={{ filemtime(public_path('js/selects.js')) }}" defer></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
<script src="{{ asset('js/fx.js') }}?v={{ filemtime(public_path('js/fx.js')) }}" defer></script>
<script src="{{ asset('js/live.js') }}?v={{ filemtime(public_path('js/live.js')) }}" defer></script>
<script src="{{ asset('js/promo.js') }}?v={{ filemtime(public_path('js/promo.js')) }}" defer></script>
@if ($shop->ai_enabled)<script src="{{ asset('js/assistant.js') }}?v={{ filemtime(public_path('js/assistant.js')) }}" defer></script>@endif
@stack('scripts')
</body>
</html>
