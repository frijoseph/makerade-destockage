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
