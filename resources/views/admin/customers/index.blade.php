@extends('admin.layouts.app')
@php use App\Support\Money; @endphp

@section('title', 'Clients')
@section('heading', 'Clients')
@section('subheading', 'Créés automatiquement à chaque commande, reconnus par leur numéro de téléphone.')

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="violet" icon="users" label="Clients" :value="$stats['total']" />
    <x-admin.stat tone="green" icon="users-plus" label="Nouveaux (30 j)" :value="$stats['recent']" />
    <x-admin.stat tone="orange" icon="refresh" label="Clients fidèles" :value="$stats['repeat']" hint="2 commandes ou plus" />
    <x-admin.stat tone="blue" icon="star" label="Avec un compte" :value="$stats['accounts']" />
</section>

<section class="card">
    <form method="GET" class="toolbar" data-filters style="margin-bottom: 18px">
        <label class="search grow">
            <x-admin.icon name="search" :size="18" />
            <span class="sr-only">Rechercher</span>
            <input type="search" name="q" value="{{ request('q') }}" data-label="Recherche" placeholder="Nom, téléphone ou e-mail" autocomplete="off">
        </label>
        <select name="segment" aria-label="Segment" data-label="Segment">
            <option value="">Tous les clients</option>
            @foreach (['new' => 'Nouveaux (30 jours)', 'repeat' => 'Fidèles (2 commandes ou +)', 'inactive' => 'Inactifs (90 jours)', 'account' => 'Avec un compte', 'guest' => 'Sans compte'] as $value => $label)
                <option value="{{ $value }}" @selected(request('segment') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="city" aria-label="Ville" data-label="Ville">
            <option value="">Toutes les villes</option>
            @foreach ($cities as $city)
                <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
            @endforeach
        </select>
        <select name="sort" aria-label="Trier" data-label="Tri">
            <option value="">Dernière commande</option>
            <option value="spent" @selected(request('sort') === 'spent')>Montant dépensé</option>
        </select>
        <button class="btn">Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Client</th><th>Téléphone</th><th>Ville</th><th class="num">Commandes</th><th class="num">Dépensé</th><th>Dernière commande</th></tr></thead>
            <tbody>
            @forelse ($customers as $customer)
                <tr data-href="{{ route('admin.customers.show', $customer) }}">
                    <td><div class="thumb" style="min-width: 0"><span class="avatar">{{ $customer->initials() }}</span><span><a href="{{ route('admin.customers.show', $customer) }}" class="strong">{{ $customer->name }}</a><small>{{ $customer->email ?? '—' }}</small></span></div></td>
                    <td>{{ $shop->phone($customer->phone) }}</td>
                    <td>{{ $customer->city ?? '—' }}</td>
                    <td class="num">{{ $customer->orders_count }}</td>
                    <td class="num strong">{{ Money::format($customer->spent ?? 0) }}</td>
                    <td>{{ $customer->last_order_at ? \Illuminate\Support\Carbon::parse($customer->last_order_at)->translatedFormat('j M Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aucun client trouvé.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</section>
@endsection
