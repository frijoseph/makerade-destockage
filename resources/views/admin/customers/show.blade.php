@extends('admin.layouts.app')
@php use App\Support\Money; @endphp

@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading')Client depuis le {{ $customer->created_at->translatedFormat('j F Y') }}@endsection
@section('actions')
    <a href="{{ route('admin.customers.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Clients</a>
@endsection

@section('content')
<div class="grid-side">
    <section class="card">
        <header class="card-head"><h2>Commandes</h2></header>
        <div class="stat-row" style="margin-bottom: 18px">
            <div class="stat"><small>Commandes</small><b>{{ $customer->orders->count() }}</b></div>
            <div class="stat"><small>Total dépensé</small><b>{{ Money::format($spent) }}</b></div>
            <div class="stat"><small>Panier moyen</small><b>{{ Money::format($average) }}</b></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Commande</th><th>Date</th><th>Statut</th><th>Paiement</th><th class="num">Articles</th><th class="num">Total</th></tr></thead>
                <tbody>
                @forelse ($customer->orders as $order)
                    <tr data-href="{{ route('admin.orders.show', $order) }}">
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="strong">{{ $order->number }}</a></td>
                        <td>{{ $order->placed_at->translatedFormat('j M Y') }}</td>
                        <td><x-admin.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-admin.badge></td>
                        <td><x-admin.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-admin.badge></td>
                        <td class="num">{{ (int) $order->units }}</td>
                        <td class="num strong">{{ Money::format($order->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Aucune commande.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <header class="card-head"><h2>Coordonnées</h2></header>
        <div class="customer-head">
            <span class="avatar">{{ $customer->initials() }}</span>
            <div><h2>{{ $customer->name }}</h2><small class="muted">{{ $customer->city ?? 'Ville inconnue' }}</small></div>
        </div>
        <p class="contact-line"><x-admin.icon name="phone" :size="16" /> <a href="tel:{{ $customer->phone }}">{{ $shop->phone($customer->phone) }}</a></p>
        <p class="contact-line"><x-admin.icon name="whatsapp" :size="16" /> <a href="https://wa.me/{{ preg_replace('/\D/', '', $customer->phone) }}" target="_blank" rel="noopener">Écrire sur WhatsApp</a></p>
        @if ($customer->email)<p class="contact-line"><x-admin.icon name="mail" :size="16" /> <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></p>@endif
        <p class="contact-line"><x-admin.icon name="pin" :size="16" /> <span>{{ $customer->address ?? 'Adresse non renseignée' }}</span></p>
    </section>
</div>
@endsection
