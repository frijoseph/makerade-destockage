@extends('layouts.app')
@section('title', 'Commande '.$order->number)

@section('content')
@php
    $title = 'Merci pour votre commande !';
    $sub = 'Commande '.$order->number.' enregistrée. Nous vous appelons très vite pour confirmer la livraison.';
    $money = fn ($amount) => $shop->money($amount);
@endphp
@include('partials.page-head')
<section class="container section tight" data-celebrate>
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
            @if ((float) $order->discount)<div class="srow disc"><span>Réduction{{ $order->coupon_code ? ' · '.$order->coupon_code : '' }}</span><b>−{{ $money($order->discount) }}</b></div>@endif
            <div class="srow total"><span>Total</span><b>{{ $money($order->total) }}</b></div>
            <div class="srow"><span>Paiement</span><b class="with-ic"><x-payment-icon :method="$order->payment_method" :size="22" /> {{ $order->payment_method->label() }}</b></div>
            <div class="srow"><span>Livraison à</span><b>{{ $order->shipping_city }}</b></div>
            <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="btn btn-ghost block"><x-icon name="package" :size="18" /> Imprimer mon reçu</a>
            <a href="{{ route('shop') }}" class="btn btn-primary block">Continuer mes achats</a>
        </aside>
    </div>
</section>
@endsection
