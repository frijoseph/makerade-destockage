@extends('admin.layouts.app')
@php
    use App\Support\Money;
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
    $firstName = explode(' ', auth()->user()->name)[0];
    $revenueTotal = (float) $byCategory->sum('value');
    $categoryColors = ['#6d5dfc', '#22b07d', '#f7931e', '#3b82f6', '#f2547d', '#a3a8b8'];
    $sellTones = ['violet', 'green', 'orange', 'blue', 'pink'];
    $revenueSeries = $dailyRevenue->all();
    $firstHalf = array_sum(array_slice($revenueSeries, 0, intdiv(count($revenueSeries), 2)));
    $secondHalf = array_sum(array_slice($revenueSeries, intdiv(count($revenueSeries), 2)));
@endphp

@section('title', 'Vue d’ensemble')
@section('heading'){{ $greeting }}, {{ $firstName }} 👋@endsection
@section('subheading', 'Voici ce qui se passe dans votre boutique aujourd’hui.')

@section('actions')
    <form method="GET" class="period">
        <x-admin.icon name="calendar" :size="18" />
        <label class="sr-only" for="period">Période</label>
        <select id="period" name="period" data-autosubmit>
            @foreach (\App\Support\SalesReport::PERIODS as $key => $label)
                <option value="{{ $key }}" @selected($report->period === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-sm">OK</button></noscript>
    </form>
@endsection

@section('content')
<section class="kpis">
    <x-admin.kpi tone="violet" icon="wallet" label="Chiffre d’affaires" :value="Money::format($totals['revenue'])"
                 :change="$totals['revenue_change']" caption="vs période précédente" :spark="$dailyRevenue->values()->all()" />
    <x-admin.kpi tone="green" icon="cart" label="Commandes" :value="number_format($totals['orders'], 0, ',', ' ')"
                 :change="$totals['orders_change']" caption="vs période précédente" :spark="$dailyOrders->values()->all()" />
    <x-admin.kpi tone="orange" icon="pie" label="Panier moyen" :value="Money::format($totals['average_basket'])"
                 :change="$totals['average_basket_change']" caption="vs période précédente" :spark="$dailyRevenue->values()->all()" />
    <x-admin.kpi tone="blue" icon="box" label="Stock sain" :value="$stockHealth['healthy_rate'].' %'" :progress="$stockHealth['healthy_rate']"
                 :caption="$stockHealth['low'].' en alerte · '.$stockHealth['out'].' en rupture sur '.$stockHealth['active']" />
</section>

<section class="grid-2">
    <article class="card">
        <header class="card-head">
            <h2>Ventes par catégorie</h2>
            <span class="chip">{{ $report->label() }}</span>
        </header>
        @if ($byCategory->isEmpty())
            <p class="empty">Aucune vente sur la période.</p>
        @else
            <div class="donut-wrap">
                <x-admin.donut :slices="$byCategory->all()" :center-value="Money::compact($revenueTotal)" center-label="Total" />
                <ul class="legend">
                    @foreach ($byCategory as $i => $slice)
                        <li>
                            <i style="background: {{ $categoryColors[$i % 6] }}"></i>
                            <span>{{ $slice['name'] }}</span>
                            <b>{{ Money::format($slice['value']) }}</b>
                            <small>{{ number_format($slice['share'], 1, ',', ' ') }} %</small>
                        </li>
                    @endforeach
                </ul>
            </div>
            <p class="note"><x-admin.icon name="star" :size="16" /> Catégorie en tête : <b>{{ $byCategory->first()['name'] }}</b> ({{ number_format($byCategory->first()['share'], 1, ',', ' ') }} %)</p>
        @endif
    </article>

    <article class="card">
        <header class="card-head">
            <h2>Évolution du chiffre d’affaires</h2>
            <span class="chip">Cumulé</span>
        </header>
        <x-admin.line-chart :series="$revenueSeries" />
        @if ($totals['revenue_change'] !== null)
            <div class="note note-trend">
                <div>
                    <span>{{ $totals['revenue_change'] >= 0 ? 'Vous vendez plus' : 'Vous vendez moins' }} que la période précédente</span>
                    <b class="{{ $totals['revenue_change'] >= 0 ? 'up' : 'down' }}">{{ $totals['revenue_change'] >= 0 ? '+' : '−' }} {{ number_format(abs($totals['revenue_change']), 1, ',', ' ') }} %</b>
                </div>
                <x-admin.sparkline :values="[$firstHalf, $secondHalf * .7, $secondHalf]" color="var(--accent)" width="80" height="26" />
            </div>
        @endif
    </article>
</section>

<section class="card">
    <header class="card-head">
        <h2>Écoulement du stock par catégorie</h2>
        <a href="{{ route('admin.categories.index') }}" class="link">Tout voir</a>
    </header>
    <div class="budgets">
        @forelse ($sellThrough as $i => $row)
            <div class="budget tone-{{ $sellTones[$i % 5] }}">
                <div class="budget-head">
                    <span class="budget-ic"><x-admin.icon name="box" :size="18" :stroke="2.2" /></span>
                    <b>{{ $row['name'] }}</b>
                </div>
                <p><b>{{ $row['sold'] }}</b> vendus / {{ $row['sold'] + $row['stock'] }}</p>
                <div class="bar"><i style="width: {{ $row['rate'] }}%"></i></div>
                <small>{{ $row['rate'] }} %</small>
            </div>
        @empty
            <p class="empty">Pas encore de catégories.</p>
        @endforelse
    </div>
</section>

<section class="card">
    <header class="card-head">
        <h2>Commandes récentes</h2>
        <a href="{{ route('admin.orders.index') }}" class="link">Tout voir</a>
    </header>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Date</th><th>Commande</th><th>Statut</th><th>Paiement</th><th class="num">Montant</th></tr></thead>
            <tbody>
            @forelse ($recentOrders as $order)
                <tr data-href="{{ route('admin.orders.show', $order) }}">
                    <td class="nowrap"><span class="cell-ic"><x-admin.icon name="calendar" :size="16" /></span>{{ $order->placed_at->translatedFormat('j M Y') }}</td>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="strong">{{ $order->number }}</a><small>{{ $order->customer->name }}</small></td>
                    <td><x-admin.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-admin.badge></td>
                    <td><span class="pay"><i class="dot tone-{{ $order->payment_method->tone() }}"></i>{{ $order->payment_method->label() }}</span></td>
                    <td class="num strong">{{ Money::format($order->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Aucune commande pour l’instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="banner">
    <span class="banner-ic"><x-admin.icon :name="$lowStockProducts->isEmpty() ? 'trend-up' : 'alert'" :size="30" :stroke="2" /></span>
    <div>
        @if ($lowStockProducts->isEmpty())
            <h2>Tout est sous contrôle ! 🚀</h2>
            <p>Aucun produit sous son seuil d’alerte. Pensez à ajouter vos prochains arrivages.</p>
            <a href="{{ route('admin.products.create') }}" class="btn btn-light btn-sm">Ajouter un produit</a>
        @else
            <h2>{{ $lowStockProducts->count() }} produit(s) à réapprovisionner</h2>
            <p>{{ $lowStockProducts->pluck('name')->take(3)->implode(', ') }}{{ $lowStockProducts->count() > 3 ? '…' : '' }} sont sous leur seuil d’alerte.</p>
            <a href="{{ route('admin.stock.index') }}" class="btn btn-light btn-sm">Gérer le stock</a>
        @endif
    </div>
    <div class="banner-art" aria-hidden="true"><i></i><i></i><i></i><span></span></div>
</section>
@endsection
