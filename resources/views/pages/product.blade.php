@extends('layouts.app')
@section('title', $product['name'])

@section('content')
@php
    $title = $product['name'];
    $variants = $product['variants'] ?? [];
    $gallery = $product['images'] ?? [];
    $axisNames = $product['option_names'] ?? [];
    $axisCount = $variants ? max(array_map(fn ($variant) => count($variant['options']), $variants)) : 0;
@endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="pdetail" data-id="{{ $product['slug'] }}" data-low-stock="{{ (int) $shop->low_stock_badge }}">
        <div class="pd-gal reveal">
            @if ($product['off'] > 0)<span class="off">-{{ $product['off'] }} %</span>@endif
            <div class="pd-stage" id="tilt"><x-product-art :kind="$product['kind']" :c1="$product['c1']" :c2="$product['c2']" :label="$product['name']" :image="$product['image'] ?? ($gallery[0] ?? null)" id="pdMain" /></div>
            @if (count($gallery) > 1)
                <div class="pd-thumbs" role="list" aria-label="Photos du produit">
                    @foreach ($gallery as $i => $url)
                        <button type="button" role="listitem" @class(['on' => $i === 0]) data-thumb="{{ $url }}" aria-label="Photo {{ $i + 1 }}"><img src="{{ $url }}" alt="" loading="lazy"></button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="pd-info reveal" style="--d:100ms">
            <span class="pill">{{ $product['brand'] }}</span>
            <h2>{{ $product['name'] }}</h2>
            <p class="pvar">{{ $product['variant'] }}</p>
            @if ($shop->show_ratings && ($product['reviews'] ?? 0) > 0)
                <div class="stars">@for ($i = 1; $i <= 5; $i++)<x-icon name="star" :size="16" class="{{ $i <= floor($product['rating']) ? 'on' : '' }}" />@endfor <small>{{ $product['rating'] }} · {{ $shop->number($product['reviews']) }} avis</small></div>
            @endif
            <div class="price big" id="pdPrice"><b>{{ $shop->money($product['price']) }}</b>@if ($product['old_price'])<s>{{ $shop->money($product['old_price']) }}</s><em>Économisez {{ $shop->money($product['old_price'] - $product['price']) }}</em>@endif</div>
            @if ($variants)
                <div class="pd-variants" data-pd-variants data-slug="{{ $product['slug'] }}" data-variants='@json($variants)'>
                    @for ($axis = 0; $axis < $axisCount; $axis++)
                        <div class="pv-axis" data-axis="{{ $axis }}">
                            <b>{{ $axisNames[$axis] ?? 'Option '.($axis + 1) }} : <span data-axis-value></span></b>
                            <div class="pv-chips">
                                @foreach (collect($variants)->pluck('options.'.$axis)->filter()->unique()->values() as $value)
                                    <button type="button" class="pv-chip" data-value="{{ $value }}">{{ $value }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            @endif
            @php $descriptionText = ! empty($product['description']) ? $product['description'] : $shop->text('product_default_description'); @endphp
            <div class="desc rich">{{ \App\Support\RichText::render($descriptionText) }}</div>
            @php $stock = $product['stock'] ?? null; @endphp
            <p class="stock-t" id="pdStock" @if (! $variants) hidden @endif></p>
            @if ($variants)
                @php $stock = null; @endphp
            @elseif ($stock === 0)
                <p class="stock-t">Rupture de stock — revenez bientôt.</p>
            @elseif ($stock !== null && (int) $shop->low_stock_badge > 0 && $stock <= (int) $shop->low_stock_badge)
                <p class="stock-t">Plus que {{ $stock }} en stock</p>
            @endif
            <div class="buy">
                <div class="qty" data-qty><button type="button" data-q="-1" aria-label="Moins"><x-icon name="minus" :size="16" /></button><input id="qtyVal" value="1" max="{{ $stock ?? 99 }}" readonly><button type="button" data-q="1" aria-label="Plus"><x-icon name="plus" :size="16" /></button></div>
                <button class="btn btn-primary btn-lg" id="pdAdd" data-add="{{ $product['slug'] }}" data-qty-from="#qtyVal" @disabled($stock === 0 || $variants)><x-icon name="cart" :size="18" /> Ajouter au panier</button>
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
        <div><b>{{ $product['name'] }}</b><span id="pdPriceSticky">{{ $shop->money($product['price']) }}</span></div>
        <button class="btn btn-primary" id="pdAddSticky" data-add="{{ $product['slug'] }}" @disabled((bool) $variants)><x-icon name="cart" :size="18" /> Ajouter</button>
    </div>
@endif
@endsection

@if ($variants)
    @push('scripts')
        <script src="{{ asset('js/variants.js') }}?v={{ filemtime(public_path('js/variants.js')) }}" defer></script>
    @endpush
@endif
