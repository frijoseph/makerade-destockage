@extends('layouts.app')
@section('title', 'Commande '.$order->number.' — Makerade Destockage')

@section('content')
@php
    $title = 'Merci pour votre commande !';
    $sub = 'Commande '.$order->number.' enregistrée. Nous vous appelons très vite pour confirmer la livraison.';
    $money = fn ($amount) => '$'.number_format((float) $amount, 2);
@endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="cart-wrap">
        <div class="cart-list">
            @foreach ($order->items as $item)
                <div class="citem plain">
                    <div><h4>{{ $item->product_name }}</h4><p>{{ $item->quantity }} × {{ $money($item->unit_price) }}</p></div>
                    <div class="citem-r"><b>{{ $money($item->line_total) }}</b></div>
                </div>
            @endforeach
        </div>
        <aside class="summary">
            <h3>Récapitulatif</h3>
            <div class="srow"><span>Sous-total</span><b>{{ $money($order->subtotal) }}</b></div>
            <div class="srow"><span>Livraison</span><b>{{ (float) $order->shipping_fee ? $money($order->shipping_fee) : 'Gratuite' }}</b></div>
            <div class="srow total"><span>Total</span><b>{{ $money($order->total) }}</b></div>
            <div class="srow"><span>Paiement</span><b>{{ $order->payment_method->label() }}</b></div>
            <div class="srow"><span>Livraison à</span><b>{{ $order->shipping_city }}</b></div>
            <a href="{{ route('shop') }}" class="btn btn-primary block">Continuer mes achats</a>
        </aside>
    </div>
</section>
@endsection
