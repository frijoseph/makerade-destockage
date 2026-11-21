<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Tableau de bord') — Admin {{ $shopName }}</title>
    <link rel="icon" href="{{ \App\Models\Setting::imageUrl(\App\Models\Setting::get('favicon')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-ui.css') }}?v={{ filemtime(public_path('css/admin-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/notify.css') }}?v={{ filemtime(public_path('css/notify.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css">
    <link rel="stylesheet" href="{{ asset('css/selects.css') }}?v={{ filemtime(public_path('css/selects.css')) }}">
    <script>try{var t=localStorage.getItem('mk_admin_theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body class="admin">
@php
    $user = auth()->user();
    $groups = [
        ['label' => null, 'links' => [
            ['admin.dashboard', 'admin.dashboard', 'home', 'Vue d’ensemble', null],
        ]],
        ['label' => 'Ventes', 'links' => [
            ['admin.orders.index', 'admin.orders.*', 'orders', 'Commandes', $pendingOrders ?: null],
            ['admin.customers.index', 'admin.customers.*', 'users', 'Clients', null],
        ]],
        ['label' => 'Catalogue', 'links' => [
            ['admin.products.index', 'admin.products.*', 'box', 'Produits', null],
            ['admin.categories.index', 'admin.categories.*', 'grid', 'Catégories', null],
            ['admin.brands.index', 'admin.brands.*', 'tag', 'Marques', null],
            ['admin.stock.index', 'admin.stock.*', 'stock', 'Stock', $lowStockCount ?: null],
        ]],
        ['label' => 'Marketing', 'links' => [
            ['admin.coupons.index', 'admin.coupons.*', 'percent', 'Codes promo', null],
            ['admin.popups.index', 'admin.popups.*', 'gift', 'Popups & roue', null],
            ['admin.messages.index', 'admin.messages.*|admin.subscribers.*', 'mail', 'Messages', $unreadMessages ?: null],
            ['admin.pages.index', 'admin.pages.*', 'edit', 'Pages & blog', null],
        ]],
    ];
    if ($user->isAdmin()) {
        $groups[] = ['label' => 'Boutique', 'links' => [
            ['admin.settings.edit', 'admin.settings.*', 'settings', 'Paramètres', null],
        ]];
    }
    $notifications = array_values(array_filter([
        $pendingOrders ? ['icon' => 'orders', 'text' => $pendingOrders.' commande(s) en attente', 'url' => route('admin.orders.index', ['status' => 'pending']), 'tone' => 'amber'] : null,
        $unreadMessages ? ['icon' => 'mail', 'text' => $unreadMessages.' message(s) non lu(s)', 'url' => route('admin.messages.index'), 'tone' => 'violet'] : null,
        $lowStockCount ? ['icon' => 'alert', 'text' => $lowStockCount.' produit(s) sous le seuil de stock', 'url' => route('admin.stock.index'), 'tone' => 'red'] : null,
    ]));
@endphp

<aside class="side" id="side">
    <a href="{{ route('admin.dashboard') }}" class="side-brand">
        <img src="{{ \App\Models\Setting::imageUrl(\App\Models\Setting::get('logo')) }}" alt="" width="46" height="40">
        <span><b>{{ $shopName }}</b><small>Administration</small></span>
    </a>

    <nav class="side-nav" aria-label="Navigation de l'administration">
        @foreach ($groups as $group)
            @if ($group['label'])<p class="nav-title">{{ $group['label'] }}</p>@endif
            @foreach ($group['links'] as [$route, $pattern, $icon, $label, $count])
                @php $isCurrent = request()->routeIs(...explode('|', $pattern)); @endphp
                <a href="{{ route($route) }}" data-nav="{{ $label }}" @class(['is-active' => $isCurrent]) @if ($isCurrent) aria-current="page" @endif>
                    <x-admin.icon :name="$icon" />
                    <span>{{ $label }}</span>
                    @if ($count)<em>{{ $count }}</em>@endif
                </a>
            @endforeach
        @endforeach
        <p class="nav-title">Raccourcis</p>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" data-nav="Voir la boutique"><x-admin.icon name="external" /><span>Voir la boutique</span></a>
    </nav>

    <div class="side-foot">
        <div class="theme-switch">
            <x-admin.icon name="sun" :size="20" />
            <button type="button" class="switch" data-theme-toggle role="switch" aria-checked="false" aria-label="Mode sombre"><i></i></button>
            <x-admin.icon name="moon" :size="18" />
        </div>
        <div class="side-user">
            <span class="avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <span><b>{{ $user->name }}</b><small>{{ $user->role->label() }}</small></span>
            <form method="POST" action="{{ route('admin.logout') }}" data-confirm="Vous allez être déconnecté de l’administration." data-confirm-title="Se déconnecter ?" data-confirm-button="Me déconnecter">
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
            <button type="button" class="searchbtn" data-cmdk-open aria-label="Rechercher ou aller à… (Ctrl+K)">
                <x-admin.icon name="search" :size="18" /><span>Rechercher…</span><kbd>Ctrl K</kbd>
            </button>
            @yield('actions')
            <div class="notif" data-notif>
                <button type="button" class="icon-btn bell" data-notif-toggle aria-haspopup="true" aria-expanded="false" aria-label="Notifications ({{ count($notifications) }})">
                    <x-admin.icon name="bell" />
                    @if (count($notifications))<em>{{ count($notifications) }}</em>@endif
                </button>
                <div class="notif-panel" role="menu" hidden>
                    <p class="notif-head">Notifications</p>
                    @forelse ($notifications as $n)
                        <a href="{{ $n['url'] }}" role="menuitem" class="notif-item tone-{{ $n['tone'] }}"><span class="notif-ic"><x-admin.icon :name="$n['icon']" :size="18" /></span><span>{{ $n['text'] }}</span><x-admin.icon name="chev-right" :size="16" /></a>
                    @empty
                        <p class="notif-empty"><x-admin.icon name="check" :size="20" :stroke="2.4" /> Tout est à jour.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </header>

    <main class="content">
        @yield('content')
    </main>
</div>

{{-- Palette de commandes (Ctrl+K) --}}
<div class="cmdk" id="cmdk" data-search-url="{{ route('admin.search') }}" hidden>
    <div class="cmdk-box" role="dialog" aria-modal="true" aria-label="Rechercher ou aller à">
        <div class="cmdk-input"><x-admin.icon name="search" :size="20" /><input type="text" id="cmdkInput" placeholder="Aller à… commandes, produits, codes promo, paramètres" autocomplete="off" aria-label="Rechercher"><kbd>Échap</kbd></div>
        <ul class="cmdk-list" id="cmdkList" role="listbox"></ul>
        <p class="cmdk-foot"><span><kbd>↑</kbd><kbd>↓</kbd> naviguer</span><span><kbd>↵</kbd> ouvrir</span></p>
    </div>
</div>
<script type="application/json" id="cmdkQuick">{!! json_encode(array_values(array_filter([
    ['label' => 'Saisir une commande', 'url' => route('admin.orders.create'), 'hint' => 'Action'],
    ['label' => 'Nouveau produit', 'url' => route('admin.products.create'), 'hint' => 'Action'],
    ['label' => 'Nouvel article de blog', 'url' => route('admin.pages.create', ['type' => 'article']), 'hint' => 'Action'],
    ['label' => 'Nouveau popup / roue de la chance', 'url' => route('admin.popups.create'), 'hint' => 'Action'],
    $user->isAdmin() ? ['label' => 'Paramètres › Livraison et zones', 'url' => route('admin.settings.edit', 'delivery'), 'hint' => 'Réglages'] : null,
    $user->isAdmin() ? ['label' => 'Paramètres › Page d’accueil', 'url' => route('admin.settings.edit', 'home'), 'hint' => 'Réglages'] : null,
    $user->isAdmin() ? ['label' => 'Paramètres › Promotions et animations', 'url' => route('admin.settings.edit', 'marketing'), 'hint' => 'Réglages'] : null,
])), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>

@include('partials.flash', ['showErrors' => true])
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="{{ asset('js/notify.js') }}?v={{ filemtime(public_path('js/notify.js')) }}"></script>
<script src="{{ asset('js/selects.js') }}?v={{ filemtime(public_path('js/selects.js')) }}" defer></script>
<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
