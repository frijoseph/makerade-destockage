@if ($shop->mobile_nav_enabled)
<nav class="mnav" aria-label="Navigation mobile">
    <a href="{{ route('home') }}" @class(['is-on' => request()->routeIs('home')])><x-icon name="home" :size="22" :stroke="1.8" /><span>Accueil</span></a>
    <a href="{{ route('shop') }}" @class(['is-on' => request()->routeIs('shop', 'deals', 'arrivals', 'product', 'brands')])><x-icon name="grid" :size="22" :stroke="1.8" /><span>Boutique</span></a>
    <a href="{{ route('cart') }}" class="mnav-cart act-cart" aria-label="Panier"><span class="mnav-fab"><x-icon name="cart" :size="24" :stroke="1.9" /><i class="badge" data-count="cart">0</i></span><span>Panier</span></a>
    <a href="{{ route('wishlist') }}" @class(['is-on' => request()->routeIs('wishlist')])><x-icon name="heart" :size="22" :stroke="1.8" /><span>Favoris</span><i class="badge" data-count="wish">0</i></a>
    <a href="{{ route('account') }}" @class(['is-on' => request()->routeIs('account', 'password.*')])><x-icon name="user" :size="22" :stroke="1.8" /><span>{{ auth()->check() ? 'Compte' : 'Connexion' }}</span></a>
</nav>
@endif
