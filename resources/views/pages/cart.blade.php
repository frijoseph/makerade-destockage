@extends('layouts.app')
@section('title', 'Mon panier')

@section('content')
@php $title = 'Mon panier'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="cart-wrap">
        <div class="cart-list" id="cartList"></div>
        <aside class="summary reveal" id="cartSummary">
            <h3>Récapitulatif</h3>
            <div class="srow"><span>Sous-total</span><b data-sub>{{ $shop->money(0) }}</b></div>
            <div class="srow"><span>Livraison</span><b data-ship>{{ $shop->money(0) }}</b></div>
            <div class="srow disc" data-discount-row hidden><span data-discount-label>Réduction</span><b data-discount>—</b></div>
            <div class="ship-bar"><i id="shipBar"></i></div>
            <p class="ship-note" id="shipNote"></p>
            <p class="zone-info" id="zoneInfo"></p>
            <div class="promo-box" id="promoBox">
                <button type="button" class="promo-toggle" id="promoToggle"><x-icon name="percent" :size="16" :stroke="2" /> J’ai un code promo</button>
                <form class="promo-form" id="promoForm" hidden>
                    <input id="promoInput" placeholder="VOTRE CODE" autocomplete="off" autocapitalize="characters" aria-label="Code promo">
                    <button class="btn btn-primary">Appliquer</button>
                </form>
                <div class="promo-applied" id="promoApplied" hidden>
                    <span class="promo-chip"><x-icon name="tag" :size="15" :stroke="2" /><b data-promo-code></b><small data-promo-benefit></small></span>
                    <button type="button" id="promoRemove" aria-label="Retirer le code">×</button>
                </div>
            </div>
            <div class="srow total"><span>Total</span><b data-grand>{{ $shop->money(0) }}</b></div>
            <button class="btn btn-primary btn-lg block" id="checkout">Passer la commande</button>
            <form class="checkout-form" id="checkoutForm" hidden novalidate>
                <h3>Livraison</h3>
                <label>Nom complet<input name="name" required autocomplete="name" placeholder="Prénom et nom"></label>
                <label>Téléphone <x-phone-input name="phone" required /></label>
                <label>Ville<input name="city" required autocomplete="address-level2" value="{{ $shop->default_city }}" list="deliveryCities"></label>
                <datalist id="deliveryCities">@foreach ($shop->zones() as $zone)<option value="{{ $zone['name'] }}">@endforeach</datalist>
                <label>Adresse / point de repère<input name="address" required autocomplete="street-address" placeholder="Quartier, rue, repère"></label>
                <fieldset class="pay-field">
                    <legend>Moyen de paiement</legend>
                    <div class="pay-grid" role="radiogroup">
                        @foreach ($shop->paymentMethods() as $value => $label)
                            <label class="pay-opt">
                                <input type="radio" name="payment_method" value="{{ $value }}" @checked($loop->first)>
                                <span><x-payment-icon :method="$value" :size="34" /><b>{{ $label }}</b><i class="pay-check" aria-hidden="true"></i></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @if (trim((string) $shop->checkout_guest_note) !== '' && ! auth()->check())<p class="guest-note"><x-icon name="check" :size="16" /> {{ $shop->text('checkout_guest_note') }}</p>@endif
                <p class="checkout-hint">{{ $shop->text('checkout_hint') }}</p>
                <p class="checkout-err" id="checkoutErr" role="alert" hidden></p>
                <button class="btn btn-primary btn-lg block" id="checkoutSubmit">Confirmer la commande</button>
            </form>
            <a href="{{ route('shop') }}" class="btn btn-ghost block">Continuer mes achats</a>
        </aside>
    </div>
    <div id="artStore" hidden>
        @foreach ($catalog as $p)
            <div data-art="{{ $p['slug'] }}"><x-product-art :kind="$p['kind']" :c1="$p['c1']" :c2="$p['c2']" :label="$p['name']" :image="$p['image'] ?? null" /></div>
        @endforeach
    </div>
</section>
@endsection
