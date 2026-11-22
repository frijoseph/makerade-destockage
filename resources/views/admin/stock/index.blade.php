@extends('admin.layouts.app')
@php use App\Support\Money; use App\StockMovementType; @endphp

@section('title', 'Stock')
@section('heading', 'Stock')
@section('subheading', 'Arrivages, inventaires et pertes. Les ventes et annulations sont enregistrées automatiquement.')

@section('content')
<section class="kpis" style="grid-template-columns: repeat(3, minmax(0, 1fr))">
    <x-admin.kpi tone="blue" icon="box" label="Unités en stock" :value="number_format($units, 0, ',', ' ')" :caption="$products->count().' références'" />
    <x-admin.kpi tone="violet" icon="wallet" label="Valeur du stock" :value="Money::format($stockValue)" caption="au prix d’achat (ou de vente à défaut)" />
    <x-admin.kpi tone="orange" icon="alert" label="Sous le seuil d’alerte" :value="(string) $lowStock->count()" :caption="$lowStock->where('stock', 0)->count().' en rupture'" />
</section>

<div class="grid-side">
    <section class="card">
        <header class="card-head">
            <h2>Mouvements</h2>
            <form method="GET" class="toolbar" data-filters>
                <select name="product" aria-label="Produit" data-label="Produit" style="max-width: 240px">
                    <option value="">Tous les produits</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($selectedProduct === $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
                <select name="type" aria-label="Type" data-label="Type">
                    <option value="">Tous les types</option>
                    @foreach (StockMovementType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </form>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Produit</th><th>Type</th><th class="num">Quantité</th><th class="num">Stock après</th><th>Origine</th></tr></thead>
                <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->translatedFormat('j M Y') }}<small>{{ $movement->created_at->format('H:i') }}</small></td>
                        <td>@if ($movement->product)<a href="{{ route('admin.products.edit', $movement->product) }}" class="strong">{{ $movement->product->name }}</a><small>{{ $movement->product->variant }}@if ($movement->variant) · {{ $movement->variant->name }}@endif</small>@else — @endif</td>
                        <td><x-admin.badge :tone="$movement->type->tone()">{{ $movement->type->label() }}</x-admin.badge></td>
                        <td class="num"><span class="{{ $movement->quantity >= 0 ? 'qty-pos' : 'qty-neg' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span></td>
                        <td class="num">{{ $movement->stock_after }}</td>
                        <td>
                            @if ($movement->order)<a class="link" href="{{ route('admin.orders.show', $movement->order) }}">{{ $movement->order->number }}</a>@else{{ $movement->reason ?? '—' }}@endif
                            @if ($movement->user)<small>par {{ $movement->user->name }}</small>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Aucun mouvement.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $movements->links() }}
    </section>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Nouveau mouvement</h2></header>
            <form method="POST" action="{{ route('admin.stock.store') }}" class="form">
                @csrf
                <label class="field"><span>Produit</span>
                    <select name="product_id" required>
                        <option value="">Choisir…</option>
                        @foreach ($products as $product)
                            @if ($product->variants->isNotEmpty())
                                <optgroup label="{{ $product->name }} — {{ $product->variant }}">
                                    @foreach ($product->variants as $variant)
                                        <option value="{{ $product->id }}:{{ $variant->id }}" @selected(old('product_id') === $product->id.':'.$variant->id)>{{ $variant->name }} ({{ $variant->stock }})</option>
                                    @endforeach
                                </optgroup>
                            @else
                                <option value="{{ $product->id }}" @selected((string) old('product_id', $selectedProduct) === (string) $product->id)>{{ $product->name }} — {{ $product->variant }} ({{ $product->stock }})</option>
                            @endif
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Type</span>
                    <select name="type" required>
                        @foreach ($manualTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <small>Réapprovisionnement : ajoute · Perte : retire · Inventaire : fixe le stock compté.</small>
                </label>
                <label class="field"><span>Quantité</span><input type="number" name="quantity" value="{{ old('quantity') }}" required min="0" step="1" @error('quantity') aria-invalid="true" @enderror></label>
                <label class="field"><span>Motif <em>(facultatif)</em></span><input type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Ex. arrivage fournisseur, casse en rayon…"></label>
                <button class="btn btn-primary block">Enregistrer le mouvement</button>
            </form>
        </section>

        <section class="card">
            <header class="card-head"><h2>À réapprovisionner</h2></header>
            <ul class="timeline">
                @forelse ($lowStock->take(8) as $product)
                    <li><span><a class="strong" href="{{ route('admin.stock.index', ['product' => $product->id]) }}">{{ $product->name }}</a> — <span @class(['stock-pill', 'is-out' => $product->stock === 0, 'is-low' => $product->stock > 0])>{{ $product->stock }}</span> / seuil {{ $product->low_stock_threshold }}</span></li>
                @empty
                    <li>Aucun produit sous son seuil. 👌</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
