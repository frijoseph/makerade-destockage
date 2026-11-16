@extends('layouts.app')
@section('title', $product['name'].' — Makerade Destockage')

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
            <div class="stars">@for ($i = 1; $i <= 5; $i++)<x-icon name="star" :size="16" class="{{ $i <= floor($product['rating']) ? 'on' : '' }}" />@endfor <small>{{ $product['rating'] }} · {{ number_format($product['reviews']) }} avis</small></div>
            <div class="price big"><b>${{ number_format($product['price'], 2) }}</b>@if ($product['old_price'])<s>${{ number_format($product['old_price'], 2) }}</s><em>Économisez {{ number_format($product['old_price'] - $product['price'], 2) }} $</em>@endif</div>
            <p class="desc">Une qualité de confiance pour le quotidien. Sélectionné avec soin par Makerade Destockage, livré rapidement et couvert par notre politique de retour sous 30 jours.</p>
            <div class="buy">
                <div class="qty" data-qty><button type="button" data-q="-1" aria-label="Moins"><x-icon name="minus" :size="16" /></button><input id="qtyVal" value="1" readonly><button type="button" data-q="1" aria-label="Plus"><x-icon name="plus" :size="16" /></button></div>
                <button class="btn btn-primary btn-lg" data-add="{{ $product['slug'] }}" data-qty-from="#qtyVal"><x-icon name="cart" :size="18" /> Ajouter au panier</button>
                <button class="btn btn-ghost btn-lg wish-lg" data-wish="{{ $product['slug'] }}"><x-icon name="heart" :size="18" /> Favoris</button>
            </div>
            <ul class="pd-perks"><li><x-icon name="truck" :size="18" /> Livraison gratuite dès 49 $ d'achat</li><li><x-icon name="return" :size="18" /> Retours faciles sous 30 jours</li><li><x-icon name="shield" :size="18" /> Paiement 100 % sécurisé</li></ul>
        </div>
    </div>

    @if (count($related))
    <div class="sec-head reveal" style="margin-top:56px"><h2>Vous aimerez aussi</h2></div>
    <div class="pgrid">@foreach ($related as $p)<div class="grid-item"><x-product-card :p="$p" /></div>@endforeach</div>
    @endif
</section>
@endsection
