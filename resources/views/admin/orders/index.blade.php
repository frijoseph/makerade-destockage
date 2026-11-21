@extends('admin.layouts.app')
@php
    use App\Support\Money; use App\OrderStatus; use App\PaymentStatus;
    $paymentIcon = fn ($method) => \Illuminate\Support\Facades\Blade::render('<x-payment-icon :method="$m" :size="24" />', ['m' => $method]);
@endphp

@section('title', 'Commandes')
@section('heading', 'Commandes')
@section('subheading', 'Suivez et traitez les commandes du site, de WhatsApp et du téléphone.')
@section('actions')
    <a href="{{ route('admin.orders.create') }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Saisir une commande</a>
@endsection

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="violet" icon="orders" label="Commandes" :value="$statusCounts->sum()" />
    <x-admin.stat tone="orange" icon="clock" label="En attente" :value="$statusCounts['pending'] ?? 0" hint="à confirmer" :href="route('admin.orders.index', ['status' => 'pending'])" />
    <x-admin.stat tone="blue" icon="truck" label="À expédier" :value="($statusCounts['confirmed'] ?? 0) + ($statusCounts['processing'] ?? 0)" hint="confirmées / en préparation" />
    <x-admin.stat tone="green" icon="check" label="Livrées" :value="$statusCounts['delivered'] ?? 0" />
</section>

<nav class="tabs" aria-label="Filtrer par statut">
    <a href="{{ route('admin.orders.index', request()->except(['status', 'page'])) }}" @class(['is-active' => ! request('status')])>Toutes <em>{{ $statusCounts->sum() }}</em></a>
    @foreach (OrderStatus::cases() as $status)
        <a href="{{ route('admin.orders.index', ['status' => $status->value] + request()->except(['status', 'page'])) }}" @class(['is-active' => request('status') === $status->value])>
            {{ $status->label() }} <em>{{ $statusCounts[$status->value] ?? 0 }}</em>
        </a>
    @endforeach
</nav>

<section class="card">
    <form method="GET" class="toolbar" data-filters style="margin-bottom: 18px">
        @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <label class="search grow">
            <x-admin.icon name="search" :size="18" />
            <span class="sr-only">Rechercher</span>
            <input type="search" name="q" value="{{ request('q') }}" data-label="Recherche" placeholder="N° de commande, nom ou téléphone du client" autocomplete="off">
        </label>
        <select name="period" aria-label="Période" data-label="Période">
            <option value="">Toutes les périodes</option>
            @foreach (['today' => 'Aujourd’hui', '7d' => '7 derniers jours', '30d' => '30 derniers jours', 'month' => 'Ce mois-ci'] as $value => $label)
                <option value="{{ $value }}" @selected(request('period') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="payment" aria-label="Paiement" data-label="Paiement">
            <option value="">Tous les paiements</option>
            @foreach (PaymentStatus::cases() as $payment)
                <option value="{{ $payment->value }}" @selected(request('payment') === $payment->value)>{{ $payment->label() }}</option>
            @endforeach
        </select>
        <select name="method" aria-label="Moyen de paiement" data-label="Moyen">
            <option value="">Tous les moyens</option>
            @foreach (\App\PaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" data-icon="{{ $paymentIcon($method) }}" @selected(request('method') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
        <select name="channel" aria-label="Canal" data-label="Canal">
            <option value="">Tous les canaux</option>
            @foreach (\App\OrderChannel::cases() as $channel)
                <option value="{{ $channel->value }}" @selected(request('channel') === $channel->value)>{{ $channel->label() }}</option>
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
                    <td><x-admin.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-admin.badge><small class="with-ic"><x-payment-icon :method="$order->payment_method" :size="16" /> {{ $order->payment_method->label() }}</small></td>
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
