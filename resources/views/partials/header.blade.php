@php
    $nav = $shop->headerMenu();
    $cats = \App\Support\Catalog::categories();
@endphp

<header class="header" id="header">
    <div class="container header-main">
        <button class="icon-btn burger" id="burger" aria-label="Ouvrir le menu"><x-icon name="menu" :size="24" /></button>
        @include('partials.logo')

        <form class="search" action="{{ route('shop') }}" method="get" role="search" id="searchForm">
            <input type="search" name="q" id="searchInput" placeholder="{{ $shop->text('search_placeholder') }}" autocomplete="off" aria-label="Rechercher des produits">
            <button class="search-go" aria-label="Rechercher"><x-icon name="search" :size="20" :stroke="2.2" /></button>
            <div class="suggest" id="suggest" hidden></div>
        </form>

        <nav class="actions" aria-label="Compte">
            <a href="{{ route('contact') }}" class="act act-deliver"><x-icon name="pin" :size="26" :stroke="1.8" /><span><small>Livrer à</small><b>{{ $shop->delivery_zone }}</b></span></a>
            <a href="{{ route('account') }}" class="act act-acc"><x-icon name="user" :size="26" :stroke="1.8" /><span><small>{{ auth()->check() ? 'Bonjour' : 'Connexion' }}</small><b>{{ auth()->check() ? \Illuminate\Support\Str::before(auth()->user()->name, ' ') : 'Mon compte' }}</b></span></a>
            <a href="{{ route('cart') }}" class="act act-cart"><span class="ic-wrap"><x-icon name="cart" :size="28" :stroke="1.8" /><i class="badge" data-count="cart">0</i></span><span class="cart-lbl"><b data-total>{{ $shop->money(0) }}</b></span></a>
        </nav>
    </div>

    <div class="subnav">
        <div class="container subnav-in">
            <div class="catmenu" id="catmenu">
                <button class="catbtn" id="catbtn" aria-expanded="false"><x-icon name="menu" :size="18" :stroke="2.2" /> Parcourir par catégorie <x-icon name="chev-down" :size="14" class="caret" /></button>
                <div class="catdrop">
                    @foreach ($cats as $c)
                        <a href="{{ route('shop', ['cat' => $c['slug']]) }}">@if ($c['image'])<img class="dot" src="{{ $c['image'] }}" alt="" loading="lazy">@else<span class="dot" style="background: {{ $c['bg'] }}"></span>@endif{{ $c['name'] }}<x-icon name="chev-right" :size="14" /></a>
                    @endforeach
                </div>
            </div>
            <ul class="nav" id="nav">
                @foreach ($nav as $link)
                    @if ($link['children'])
                        <li class="has-sub">
                            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                            <button type="button" class="sub-toggle" aria-expanded="false" aria-label="Sous-menu {{ $link['label'] }}"><x-icon name="chev-down" :size="13" :stroke="2.2" /></button>
                            <ul class="subdrop">
                                @foreach ($link['children'] as $child)
                                    <li><a href="{{ $child['url'] }}">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endif
                @endforeach
            </ul>
            @if ($shop->special_link_label)
                <a href="{{ $shop->link($shop->special_link_url) }}" class="special"><x-icon name="leaf" :size="22" :stroke="1.6" /> {{ $shop->special_link_label }}</a>
            @endif
        </div>
    </div>
</header>

{{-- Tiroir mobile --}}
<div class="scrim" id="scrim"></div>
<aside class="drawer" id="drawer" aria-label="Menu">
    <div class="drawer-head">@include('partials.logo')<button class="icon-btn" id="drawerClose" aria-label="Fermer"><x-icon name="x" :size="22" /></button></div>
    <ul>
        <li><a href="{{ route('home') }}">Accueil<x-icon name="chev-right" :size="16" /></a></li>
        @foreach ($nav as $link)
            @if ($link['children'])
                <li class="d-sub">
                    <div class="d-sub-row"><a href="{{ $link['url'] }}">{{ $link['label'] }}</a><button type="button" class="d-sub-toggle" aria-expanded="false" aria-label="Sous-menu {{ $link['label'] }}"><x-icon name="chev-down" :size="16" /></button></div>
                    <ul class="d-sub-list">
                        @foreach ($link['children'] as $child)
                            <li><a href="{{ $child['url'] }}">{{ $child['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li><a href="{{ $link['url'] }}">{{ $link['label'] }}<x-icon name="chev-right" :size="16" /></a></li>
            @endif
        @endforeach
    </ul>
    <h4>Catégories</h4>
    <ul class="drawer-cats">
        @foreach ($cats as $c)
            <li><a href="{{ route('shop', ['cat' => $c['slug']]) }}">{{ $c['name'] }}</a></li>
        @endforeach
    </ul>
    <div class="drawer-foot">
        <a href="{{ route('account') }}" class="btn btn-primary block"><x-icon name="user" :size="18" /> {{ auth()->check() ? 'Mon compte' : 'Connexion / Inscription' }}</a>
        <a href="{{ route('track') }}" class="btn btn-ghost block"><x-icon name="package" :size="18" /> Suivre ma commande</a>
        <a href="{{ route('wishlist') }}" class="btn btn-ghost block"><x-icon name="heart" :size="18" /> Mes favoris <i class="badge in" data-count="wish" style="position:static;margin-left:6px">0</i></a>
    </div>
</aside>
