@extends('admin.layouts.app')
@php use App\Support\Money; use App\OrderStatus; use App\PaymentStatus; @endphp

@section('title', 'Commandes')
@section('heading', 'Commandes')
@section('subheading', 'Suivez et traitez les commandes du site, de WhatsApp et du téléphone.')
@section('actions')
    <a href="{{ route('admin.orders.create') }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Saisir une commande</a>
@endsection

@section('content')
<nav class="tabs" aria-label="Filtrer par statut">
    <a href="{{ route('admin.orders.index', request()->except(['status', 'page'])) }}" @class(['is-active' => ! request('status')])>Toutes <em>{{ $statusCounts->sum() }}</em></a>
    @foreach (OrderStatus::cases() as $status)
        <a href="{{ route('admin.orders.index', ['status' => $status->value] + request()->except(['status', 'page'])) }}" @class(['is-active' => request('status') === $status->value])>
            {{ $status->label() }} <em>{{ $statusCounts[$status->value] ?? 0 }}</em>
        </a>
    @endforeach
</nav>

<section class="card">
    <form method="GET" class="toolbar" style="margin-bottom: 18px">
        @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <label class="search grow">
            <x-admin.icon name="search" :size="18" />
            <span class="sr-only">Rechercher</span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="N° de commande, nom ou téléphone du client">
        </label>
        <select name="payment" data-autosubmit aria-label="Paiement" style="width: auto">
            <option value="">Tous les paiements</option>
            @foreach (PaymentStatus::cases() as $payment)
                <option value="{{ $payment->value }}" @selected(request('payment') === $payment->value)>{{ $payment->label() }}</option>
            @endforeach
        </select>
        <button class="btn">Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Commande</th><th>Date</th><th>Client</th><th>Canal</th><th>Statut</th><th>Paiement</th><th class="num">Articles</th><th class="num">Total</th></tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr data-href="{{ route('admin.orders.show', $order) }}">
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="strong">{{ $order->number }}</a></td>
                    <td>{{ $order->placed_at->translatedFormat('j M Y') }}<small>{{ $order->placed_at->format('H:i') }}</small></td>
                    <td>{{ $order->customer->name }}<small>{{ $order->customer->phone }}{{ $order->shipping_city ? ' · '.$order->shipping_city : '' }}</small></td>
                    <td>{{ $order->channel->label() }}</td>
                    <td><x-admin.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-admin.badge></td>
                    <td><x-admin.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-admin.badge><small>{{ $order->payment_method->label() }}</small></td>
                    <td class="num">{{ (int) $order->units }}</td>
                    <td class="num strong">{{ Money::format($order->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">Aucune commande ne correspond à ces filtres.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</section>
@endsection
