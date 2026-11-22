@extends('layouts.app')
@section('title', 'Commande '.$order->number)

@section('content')
@php
    $money = fn ($amount) => $shop->money($amount);
    $first = trim(explode(' ', trim((string) ($order->shipping_name ?? $order->customer?->name)))[0] ?? '');
    $isPaid = $order->payment_status === \App\PaymentStatus::Paid;
    $address = collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ');
@endphp
<section class="container section tight ok-page" data-celebrate>
    <div class="ok-hero">
        <span class="ok-badge" aria-hidden="true"><x-icon name="check" :size="38" :stroke="2.6" /></span>
        <h1>Merci{{ $first !== '' ? ' '.$first : '' }} !</h1>
        <p>Votre commande est bien enregistrée. {{ $shop->text('checkout_hint') }}</p>
        <div class="ok-number"><small>Numéro de commande</small><b>{{ $order->number }}</b></div>
    </div>

    <ol class="ok-steps" aria-label="Étapes de votre commande">
        <li class="done"><i><x-icon name="check" :size="16" :stroke="2.8" /></i><span>Commande reçue</span></li>
        <li class="cur"><i><x-icon name="phone" :size="16" /></i><span>Confirmation par appel</span></li>
        <li><i><x-icon name="truck" :size="16" /></i><span>Livraison</span></li>
    </ol>

    <div class="ok-grid">
        <div class="ok-col">
            <article class="ok-card">
                <h2><x-icon name="truck" :size="18" /> Livraison</h2>
                <p><b>{{ $order->shipping_name ?? $order->customer?->name }}</b></p>
                <p><a href="tel:{{ $order->shipping_phone }}">{{ $shop->phone($order->shipping_phone) }}</a></p>
                <p class="muted">{{ $address ?: 'Adresse non renseignée' }}</p>
            </article>
            <article class="ok-card">
                <h2><x-icon name="package" :size="18" /> Paiement</h2>
                <p class="with-ic"><x-payment-icon :method="$order->payment_method" :size="28" /> <b>{{ $order->payment_method->label() }}</b></p>
                <p class="ok-due {{ $isPaid ? 'paid' : '' }}">{{ $isPaid ? 'Déjà réglée' : 'À régler : '.$money($order->total) }}</p>
            </article>
            <article class="ok-card">
                <h2><x-icon name="package" :size="18" /> Vos articles ({{ $order->items->sum('quantity') }})</h2>
                <ul class="ok-items">
                    @foreach ($order->items as $item)
                        <li><span>{{ $item->product_name }}<small>{{ $item->quantity }} × {{ $money($item->unit_price) }}</small></span><b>{{ $money($item->line_total) }}</b></li>
                    @endforeach
                </ul>
                <div class="ok-sum">
                    <div><span>Sous-total</span><b>{{ $money($order->subtotal) }}</b></div>
                    <div><span>Livraison</span><b>{{ (float) $order->shipping_fee ? $money($order->shipping_fee) : 'Gratuite' }}</b></div>
                    @if ((float) $order->discount)<div class="disc"><span>Réduction{{ $order->coupon_code ? ' · '.$order->coupon_code : '' }}</span><b>−{{ $money($order->discount) }}</b></div>@endif
                    <div class="tot"><span>Total</span><b>{{ $money($order->total) }}</b></div>
                </div>
            </article>
        </div>

        <aside class="ok-side">
            @guest
                @if (trim((string) $shop->confirm_account_cta) !== '')
                    <article class="ok-card ok-account">
                        <h2><x-icon name="user" :size="18" /> Votre compte</h2>
                        <p>{{ $shop->text('confirm_account_cta') }}</p>
                        <a href="{{ route('account') }}" class="btn btn-ghost block">Créer mon compte</a>
                    </article>
                @endif
            @endguest
            <div class="ok-actions">
                <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="btn btn-ghost block"><x-icon name="package" :size="18" /> Imprimer mon reçu</a>
                <a href="{{ route('track') }}" class="btn btn-ghost block"><x-icon name="truck" :size="18" /> Suivre ma commande</a>
                <a href="{{ route('shop') }}" class="btn btn-primary block">Continuer mes achats</a>
            </div>
        </aside>
    </div>
</section>
@endsection
