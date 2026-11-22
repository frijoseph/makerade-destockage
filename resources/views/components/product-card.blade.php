@props(['p', 'hot' => false, 'flash' => false])
@php
    $stock = $p['stock'] ?? null;
    $sold = (int) ($p['sold'] ?? 0);
    $lowStockBadge = (int) $shop->low_stock_badge;
    $showRating = $shop->show_ratings && ($p['reviews'] ?? 0) > 0;
@endphp
<article class="pcard reveal" data-id="{{ $p['slug'] }}" data-name="{{ $p['name'] }}" data-price="{{ $p['price'] }}" data-variant="{{ $p['variant'] }}">
    @if ($hot || !empty($p['hot']))<span class="tag-hot">Promo choc</span>@endif
    <button class="wish" data-wish="{{ $p['slug'] }}" aria-label="Ajouter aux favoris"><x-icon name="heart" :size="17" /></button>
    <a href="{{ route('product', $p['slug']) }}" class="pimg">
        @if ($p['off'] > 0)<span class="off">-{{ $p['off'] }} %</span>@endif
        <x-product-art :kind="$p['kind']" :c1="$p['c1']" :c2="$p['c2']" :label="$p['name']" :image="$p['image'] ?? null" />
    </a>
    <div class="pbody">
        <h3><a href="{{ route('product', $p['slug']) }}">{{ $p['name'] }}</a></h3>
        <p class="pvar">{{ $p['variant'] }}</p>
        @if ($showRating)
            <div class="stars" aria-label="Note {{ $p['rating'] }} sur 5">
                @for ($i = 1; $i <= 5; $i++)<x-icon name="star" :size="12" class="{{ $i <= floor($p['rating']) ? 'on' : ($i - $p['rating'] < 1 ? 'half' : '') }}" />@endfor
                <small>({{ $shop->number($p['reviews']) }})</small>
            </div>
        @endif
        <div class="price">@if (! empty($p['price_from']))<small class="from">À partir de</small> <b>{{ $shop->money($p['price_from']) }}</b>@else<b>{{ $shop->money($p['price']) }}</b>@if ($p['old_price'])<s>{{ $shop->money($p['old_price']) }}</s>@endif @endif</div>
        @if ($flash && $stock !== null && $stock > 0 && $sold > 0)
            <div class="stock"><i style="--w: {{ (int) round($sold / ($sold + $stock) * 100) }}%"></i></div>
        @endif
        @if ($stock !== null && $stock > 0 && $lowStockBadge > 0 && $stock <= $lowStockBadge)
            <small class="stock-t">Plus que {{ $stock }} en stock</small>
        @elseif ($stock === 0)
            <small class="stock-t">Rupture de stock</small>
        @endif
        @if (! empty($p['variants']))
            <a class="btn-add" href="{{ route('product', $p['slug']) }}">{{ $stock === 0 ? 'Indisponible' : 'Choisir une option' }} <x-icon name="chev-right" :size="17" :stroke="1.8" /></a>
        @else
            <div class="stepper" data-stepper="{{ $p['slug'] }}"><button type="button" data-s="-1" aria-label="Moins">−</button><b>1</b><button type="button" data-s="1" aria-label="Plus">+</button></div>
            <button class="btn-add" data-add="{{ $p['slug'] }}" @disabled($stock === 0)>{{ $stock === 0 ? 'Indisponible' : 'Ajouter au panier' }} <x-icon name="cart" :size="17" :stroke="1.8" /></button>
        @endif
    </div>
</article>
