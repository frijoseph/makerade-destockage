@extends('layouts.app')
@section('title', 'Mon panier — Makerade Destockage')

@section('content')
@php $title = 'Mon panier'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="cart-wrap">
        <div class="cart-list" id="cartList"></div>
        <aside class="summary reveal" id="cartSummary">
            <h3>Récapitulatif</h3>
            <div class="srow"><span>Sous-total</span><b data-sub>$0.00</b></div>
            <div class="srow"><span>Livraison</span><b data-ship>$0.00</b></div>
            <div class="ship-bar"><i id="shipBar"></i></div>
            <p class="ship-note" id="shipNote"></p>
            <div class="srow total"><span>Total</span><b data-grand>$0.00</b></div>
            <button class="btn btn-primary btn-lg block" id="checkout">Passer la commande</button>
            <form class="checkout-form" id="checkoutForm" hidden novalidate>
                <h3>Livraison</h3>
                <label>Nom complet<input name="name" required autocomplete="name" placeholder="Awa Koné"></label>
                <label>Téléphone<input name="phone" type="tel" required autocomplete="tel" placeholder="+225 07 00 00 00 00"></label>
                <label>Ville<input name="city" required autocomplete="address-level2" value="Abidjan"></label>
                <label>Adresse / point de repère<input name="address" required autocomplete="street-address" placeholder="Quartier, rue, repère"></label>
                <label>Paiement
                    <select name="payment_method">
                        @foreach (\App\PaymentMethod::cases() as $method)
                            @continue($method === \App\PaymentMethod::Card)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="checkout-hint">Nous vous appelons pour confirmer la commande avant la livraison.</p>
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
