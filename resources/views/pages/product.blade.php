@extends('layouts.app')
@section('title', $product['name'])

@section('content')
@php $title = $product['name']; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="pdetail" data-id="{{ $product['slug'] }}">
        <div class="pd-gal reveal">
            @if ($product['off'] > 0)<span class="off">-{{ $product['off'] }} %</span>@endif
            <div class="pd-stage" id="tilt"><x-product-art :kind="$product['kind']" :c1="$product['c1']" :c2="$product['c2']" :label="$product['name']" :image="$product['image'] ?? null" /></div>
        </div>
        <div class="pd-info reveal" style="--d:100ms">
            <span class="pill">{{ $product['brand'] }}</span>
            <h2>{{ $product['name'] }}</h2>
            <p class="pvar">{{ $product['variant'] }}</p>
            @if ($shop->show_ratings && ($product['reviews'] ?? 0) > 0)
                <div class="stars">@for ($i = 1; $i <= 5; $i++)<x-icon name="star" :size="16" class="{{ $i <= floor($product['rating']) ? 'on' : '' }}" />@endfor <small>{{ $product['rating'] }} · {{ $shop->number($product['reviews']) }} avis</small></div>
            @endif
            <div class="price big"><b>{{ $shop->money($product['price']) }}</b>@if ($product['old_price'])<s>{{ $shop->money($product['old_price']) }}</s><em>Économisez {{ $shop->money($product['old_price'] - $product['price']) }}</em>@endif</div>
            <p class="desc">{{ ! empty($product['description']) ? $product['description'] : $shop->text('product_default_description') }}</p>
            @php $stock = $product['stock'] ?? null; @endphp
            @if ($stock === 0)
                <p class="stock-t">Rupture de stock — revenez bientôt.</p>
            @elseif ($stock !== null && (int) $shop->low_stock_badge > 0 && $stock <= (int) $shop->low_stock_badge)
                <p class="stock-t">Plus que {{ $stock }} en stock</p>
            @endif
            <div class="buy">
                <div class="qty" data-qty><button type="button" data-q="-1" aria-label="Moins"><x-icon name="minus" :size="16" /></button><input id="qtyVal" value="1" max="{{ $stock ?? 99 }}" readonly><button type="button" data-q="1" aria-label="Plus"><x-icon name="plus" :size="16" /></button></div>
                <button class="btn btn-primary btn-lg" data-add="{{ $product['slug'] }}" data-qty-from="#qtyVal" @disabled($stock === 0)><x-icon name="cart" :size="18" /> Ajouter au panier</button>
                <button class="btn btn-ghost btn-lg wish-lg" data-wish="{{ $product['slug'] }}"><x-icon name="heart" :size="18" /> Favoris</button>
            </div>
            <ul class="pd-perks">
                @foreach ((array) $shop->product_perks as $perk)
                    @continue(str_contains($perk['text'] ?? '', '{free_shipping_threshold}') && (float) $shop->free_shipping_threshold <= 0)
                    <li><x-icon :name="$perk['icon'] ?? 'leaf'" :size="18" /> {{ $shop->interpolate($perk['text'] ?? '') }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($shop->recently_viewed_enabled)
        <section id="recentSec" hidden><div class="sec-head reveal" style="margin-top:56px"><h2>Récemment consultés</h2></div><div class="recent-grid" id="recentGrid"></div></section>
    @endif

    @if (count($related))
    <div class="sec-head reveal" style="margin-top:56px"><h2>Vous aimerez aussi</h2></div>
    <div class="pgrid">@foreach ($related as $p)<div class="grid-item"><x-product-card :p="$p" /></div>@endforeach</div>
    @endif
</section>

@if ($shop->sticky_cta_enabled && ($product['stock'] ?? 1) !== 0)
    <div class="sticky-buy" id="stickyBuy">
        <div><b>{{ $product['name'] }}</b><span>{{ $shop->money($product['price']) }}</span></div>
        <button class="btn btn-primary" data-add="{{ $product['slug'] }}"><x-icon name="cart" :size="18" /> Ajouter</button>
    </div>
@endif
@endsection
