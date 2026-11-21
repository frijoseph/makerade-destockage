@extends('layouts.app')
@section('title', 'Marques')

@section('content')
@php $title = 'Nos marques'; $sub = 'Des marques de confiance, réunies dans une seule boutique.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="brand-grid">
        @foreach ($brands as $i => $b)
            <div class="brand-card reveal" style="--d: {{ $i * 50 }}ms">
                <x-brand-logo :b="$b" />
                <span>{{ collect($products)->where('brand', $b['name'])->count() }} produits</span>
            </div>
        @endforeach
    </div>
</section>
@endsection
