@extends('layouts.app')
@section('title', $title.' — Makerade Destockage')

@section('content')
@include('partials.page-head')
<section class="container section tight">
    <div class="toolbar reveal">
        <div class="chips">
            <a href="{{ url()->current() }}" class="chip {{ !$cat || $cat === 'all' ? 'on' : '' }}">Tous</a>
            @foreach ($categories as $c)
                <a href="{{ url()->current() }}?cat={{ $c['slug'] }}" class="chip {{ ($cat ?? '') === $c['slug'] ? 'on' : '' }}">{{ $c['slug'] === 'all' ? 'Tous' : $c['name'] }}</a>
            @endforeach
        </div>
        <label class="sort"><span>Trier par</span>
            <select id="sortSel">
                <option value="default">En vedette</option><option value="low">Prix croissant</option>
                <option value="high">Prix décroissant</option><option value="off">Plus grosse remise</option>
            </select>
        </label>
    </div>
    <p class="count"><b>{{ count($products) }}</b> produits trouvés</p>

    @if (count($products))
    <div class="pgrid" id="pgrid">
        @foreach ($products as $p)
            <div class="grid-item" data-price="{{ $p['price'] }}" data-off="{{ $p['off'] }}" data-idx="{{ $loop->index }}"><x-product-card :p="$p" /></div>
        @endforeach
    </div>
    @else
    <div class="empty"><x-icon name="search" :size="44" :stroke="1.3" /><h3>Aucun produit trouvé</h3><p>Essayez une autre catégorie ou un autre mot-clé.</p><a class="btn btn-primary" href="{{ route('shop') }}">Voir tous les produits</a></div>
    @endif
</section>
@endsection
