<!DOCTYPE html>
<html lang="fr" {!! Route::currentRouteName() === 'home' ? 'data-home' : '' !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Makerade Destockage — Entretien & bien-être à prix déstockés')</title>
    <meta name="description" content="Makerade Destockage — le déstockage au meilleur prix : tous vos produits du quotidien à prix cassés. Livraison rapide, retours faciles.">
    <meta name="theme-color" content="#1f7a34">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fresh.css') }}?v={{ filemtime(public_path('css/fresh.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fx.css') }}?v={{ filemtime(public_path('css/fx.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/live.css') }}?v={{ filemtime(public_path('css/live.css')) }}">
    <script>try{if(!sessionStorage.getItem('mk_splash')&&document.documentElement.hasAttribute('data-home'))document.documentElement.classList.add('splash-on')}catch(e){}</script>
</head>
<body data-page="{{ Route::currentRouteName() }}">
<div class="progress" id="progress"></div>
<div class="splash" id="splash" aria-hidden="true"><div class="bubbles" data-bubbles="18"></div><img src="{{ asset('img/logo.png') }}" alt=""></div>

@include('partials.header')

<main id="main" class="page">
    @yield('content')
</main>

@include('partials.footer')

<script id="catalog" type="application/json">{!! json_encode(collect(\App\Support\Catalog::products())->keyBy("slug")->all(), JSON_HEX_TAG) !!}</script>
<div class="toast-wrap" id="toasts" aria-live="polite"></div>
<button class="to-top" id="toTop" aria-label="Retour en haut"><x-icon name="chev-down" style="transform:rotate(180deg)" /></button>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
<script src="{{ asset('js/fx.js') }}?v={{ filemtime(public_path('js/fx.js')) }}" defer></script>
<script src="{{ asset('js/live.js') }}?v={{ filemtime(public_path('js/live.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
