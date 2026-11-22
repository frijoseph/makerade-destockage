@extends('admin.layouts.app')
@php use App\Support\Money; @endphp

@section('title', 'Produits')
@section('heading', 'Produits')
@section('subheading', 'Votre catalogue : prix, remises, visibilité et niveau de stock.')
@section('actions')
    <a href="{{ route('admin.products.export') }}" class="btn"><x-admin.icon name="copy" :size="17" /> Exporter (Excel)</a>
    <a href="{{ route('admin.products.import') }}" class="btn"><x-admin.icon name="plus" :size="17" /> Importer (Excel)</a>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Nouveau produit</a>
@endsection

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="green" icon="box" label="En ligne" :value="$stats['active']" />
    <x-admin.stat tone="slate" icon="eye" label="Masqués" :value="$stats['hidden']" :href="route('admin.products.index', ['status' => 'inactive'])" />
    <x-admin.stat tone="orange" icon="alert" label="Stock faible" :value="$stats['low']" :href="route('admin.products.index', ['stock' => 'low'])" />
    <x-admin.stat tone="red" icon="x" label="En rupture" :value="$stats['out']" />
</section>

<section class="card">
    <form method="GET" class="toolbar" data-filters style="margin-bottom: 18px">
        <label class="search grow">
            <x-admin.icon name="search" :size="18" />
            <span class="sr-only">Rechercher</span>
            <input type="search" name="q" value="{{ request('q') }}" data-label="Recherche" placeholder="Nom, format ou référence" autocomplete="off">
        </label>
        <select name="category" aria-label="Catégorie" data-label="Catégorie">
            <option value="">Toutes les catégories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="brand" aria-label="Marque" data-label="Marque">
            <option value="">Toutes les marques</option>
            <option value="none" @selected(request('brand') === 'none')>Sans marque</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((string) request('brand') === (string) $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
        <select name="stock" aria-label="Stock" data-label="Stock">
            <option value="">Tout le stock</option>
            <option value="low" @selected(request('stock') === 'low')>Stock faible</option>
        </select>
        <select name="status" aria-label="Visibilité" data-label="Visibilité">
            <option value="">Actifs et masqués</option>
            <option value="active" @selected(request('status') === 'active')>Actifs</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Masqués</option>
        </select>
        <select name="promo" aria-label="Promotion" data-label="Promotion">
            <option value="">Avec ou sans promo</option>
            <option value="1" @selected(request('promo') === '1')>En promotion</option>
        </select>
        <select name="sort" aria-label="Tri" data-label="Tri">
            <option value="">Nom (A-Z)</option>
            @foreach (['stock' => 'Stock croissant', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'recent' => 'Plus récents'] as $value => $label)
                <option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn">Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Produit</th><th>Catégorie</th><th>Marque</th><th class="num">Prix</th><th class="num">Stock</th><th>Visibilité</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr data-href="{{ route('admin.products.edit', $product) }}">
                    <td>
                        <div class="thumb">
                            @if ($product->image_url)<img src="{{ $product->image_url }}" alt="" loading="lazy">@else<span class="ph" style="background: {{ $product->c1 }}22"><x-admin.icon name="box" :size="18" /></span>@endif
                            <span><a href="{{ route('admin.products.edit', $product) }}" class="strong">{{ $product->name }}</a><small>{{ $product->variant }} · {{ $product->sku }}@if ($product->variants_count) · {{ $product->variants_count }} variante(s)@endif</small></span>
                        </div>
                    </td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ $product->brand?->name ?? '—' }}</td>
                    <td class="num">
                        <b>{{ Money::format($product->price) }}</b>
                        @if ($product->discount_percent)<small><s>{{ Money::format($product->old_price) }}</s> −{{ $product->discount_percent }} %</small>@endif
                    </td>
                    <td class="num">
                        <span @class(['stock-pill', 'is-out' => $product->stock === 0, 'is-low' => $product->stock > 0 && $product->isLowStock()])>{{ $product->stock }}</span>
                        <small>seuil {{ $product->low_stock_threshold }}</small>
                    </td>
                    <td>
                        @if ($product->is_active)<x-admin.badge tone="green">En ligne</x-admin.badge>@else<x-admin.badge tone="slate">Masqué</x-admin.badge>@endif
                        @if ($product->is_new)<x-admin.badge tone="blue">Nouveau</x-admin.badge>@endif
                        @if ($product->is_hot)<x-admin.badge tone="pink">Promo choc</x-admin.badge>@endif
                    </td>
                    <td class="actions">
                        <a href="{{ route('admin.stock.index', ['product' => $product->id]) }}" class="icon-btn" title="Mouvements de stock" aria-label="Stock de {{ $product->name }}"><x-admin.icon name="stock" :size="17" /></a>
                        <a href="{{ route('admin.products.edit', $product) }}" class="icon-btn" title="Modifier" aria-label="Modifier {{ $product->name }}"><x-admin.icon name="edit" :size="17" /></a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="Supprimer « {{ $product->name }} » ? S’il a déjà été commandé, il sera seulement masqué.">
                            @csrf @method('DELETE')
                            <button class="icon-btn" title="Supprimer" aria-label="Supprimer {{ $product->name }}"><x-admin.icon name="trash" :size="17" /></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Aucun produit ne correspond à ces filtres.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</section>
@endsection
