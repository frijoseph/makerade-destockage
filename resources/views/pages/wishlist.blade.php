@extends('layouts.app')
@section('title', 'Favoris — Makerade Destockage')

@section('content')
@php $title = 'Mes favoris'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="pgrid" id="wishGrid">
        @foreach ($catalog as $p)
            <div class="grid-item" data-wish-item="{{ $p['slug'] }}" hidden><x-product-card :p="$p" /></div>
        @endforeach
    </div>
    <div class="empty" id="wishEmpty" hidden><x-icon name="heart" :size="44" :stroke="1.3" /><h3>Votre liste de favoris est vide</h3><p>Touchez le cœur sur un produit pour l'enregistrer ici.</p><a class="btn btn-primary" href="{{ route('shop') }}">Découvrir les produits</a></div>
</section>
@endsection
