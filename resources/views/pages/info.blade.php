@extends('layouts.app')
@section('title', $title.' — Makerade Destockage')

@section('content')
@include('partials.page-head')
<section class="container section tight">
    <div class="prose reveal">
        <h3>Présentation</h3>
        <p>Cette page présente notre rubrique « {{ $title }} ». Chez Makerade Destockage, nous voulons rester simples, transparents et équitables envers chaque client.</p>
        <h3>Détails</h3>
        <p>Livraison gratuite dès 49 $ d'achat. La livraison standard prend 2 à 4 jours ouvrés et chaque article peut être retourné sous 30 jours en l'état pour un remboursement intégral.</p>
        <h3>Des questions ?</h3>
        <p>Contactez notre équipe à tout moment : <a href="mailto:contact@makerade-destockage.com">contact@makerade-destockage.com</a> ou appelez le +225 07 00 00 00 00.</p>
    </div>
</section>
@endsection
