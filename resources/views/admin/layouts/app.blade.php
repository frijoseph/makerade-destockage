<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Tableau de bord') — Admin {{ $shopName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <script>try{var t=localStorage.getItem('mk_admin_theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body class="admin">
<aside class="side" id="side">
    <a href="{{ route('admin.dashboard') }}" class="side-brand">
        <img src="{{ asset('img/logo.png') }}" alt="" width="46" height="40">
        <span><b>Makerade</b><small>Déstocker. Vendre. Grandir.</small></span>
    </a>

    <nav class="side-nav" aria-label="Navigation de l'administration">
        @php
            $links = [
                ['admin.dashboard', 'admin.dashboard', 'home', 'Vue d’ensemble', null],
                ['admin.orders.index', 'admin.orders.*', 'orders', 'Commandes', $pendingOrders ?: null],
                ['admin.products.index', 'admin.products.*', 'box', 'Produits', null],
                ['admin.categories.index', 'admin.categories.*', 'grid', 'Catégories', null],
                ['admin.brands.index', 'admin.brands.*', 'tag', 'Marques', null],
                ['admin.customers.index', 'admin.customers.*', 'users', 'Clients', null],
                ['admin.stock.index', 'admin.stock.*', 'stock', 'Stock', $lowStockCount ?: null],
            ];
            if (auth()->user()->isAdmin()) {
                $links[] = ['admin.settings.edit', 'admin.settings.*', 'settings', 'Paramètres', null];
            }
        @endphp
        @foreach ($links as [$route, $pattern, $icon, $label, $count])
            <a href="{{ route($route) }}" @class(['is-active' => request()->routeIs($pattern)]) @if (request()->routeIs($pattern)) aria-current="page" @endif>
                <x-admin.icon :name="$icon" />
                <span>{{ $label }}</span>
                @if ($count)<em>{{ $count }}</em>@endif
            </a>
        @endforeach
        <a href="{{ route('home') }}" target="_blank" rel="noopener">
            <x-admin.icon name="external" />
            <span>Voir la boutique</span>
        </a>
    </nav>

    <div class="side-promo">
        <div class="promo-art" aria-hidden="true">
            <span class="b1"></span><span class="b2"></span><span class="b3"></span>
            <x-admin.icon name="basket" :size="44" :stroke="1.6" />
        </div>
        <h3>Écoulez votre stock plus vite</h3>
        <p>Ajoutez vos arrivages et suivez ce qui part le mieux.</p>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Nouveau produit</a>
    </div>

    <div class="side-foot">
        <div class="theme-switch">
            <x-admin.icon name="sun" :size="20" />
            <button type="button" class="switch" data-theme-toggle role="switch" aria-checked="false" aria-label="Mode sombre"><i></i></button>
            <x-admin.icon name="moon" :size="18" />
        </div>
        <div class="side-user">
            <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role->label() }}</small></span>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="icon-btn" title="Déconnexion" aria-label="Déconnexion"><x-admin.icon name="logout" :size="18" /></button>
            </form>
        </div>
    </div>
</aside>
<div class="side-scrim" data-side-close></div>

<div class="main">
    <header class="top">
        <button type="button" class="icon-btn burger" data-side-open aria-label="Ouvrir le menu"><x-admin.icon name="menu" /></button>
        <div class="top-title">
            <h1>@yield('heading')</h1>
            @hasSection('subheading')<p>@yield('subheading')</p>@endif
        </div>
        <div class="top-actions">
            @yield('actions')
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="icon-btn bell" title="Commandes en attente" aria-label="{{ $pendingOrders }} commande(s) en attente">
                <x-admin.icon name="bell" />
                @if ($pendingOrders)<em>{{ $pendingOrders }}</em>@endif
            </a>
        </div>
    </header>

    @if (session('status'))
        <div class="flash flash-ok" role="status"><x-admin.icon name="check" :size="18" :stroke="2.4" /> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash flash-err" role="alert">
            <x-admin.icon name="alert" :size="18" />
            <div>
                @if ($errors->count() === 1)
                    {{ $errors->first() }}
                @else
                    <b>Merci de corriger les points suivants :</b>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
            </div>
        </div>
    @endif

    <main class="content">
        @yield('content')
    </main>
</div>

<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
