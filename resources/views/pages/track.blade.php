@extends('layouts.app')
@section('title', 'Suivi de commande — Makerade Destockage')

@section('content')
@php $title = 'Suivre ma commande'; $sub = 'Saisissez votre numéro de commande pour suivre votre colis.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <form class="cform track reveal" id="trackForm" novalidate>
        <div class="row2"><label>Numéro de commande<input id="orderNo" placeholder="ex. DN-102938" required></label><label>E-mail<input type="email" placeholder="vous@exemple.com"></label></div>
        <button class="btn btn-primary btn-lg">Suivre la commande</button>
    </form>
    <ol class="steps" id="steps" hidden>
        <li class="done"><x-icon name="check" :size="18" :stroke="2.6" /><b>Commande validée</b></li>
        <li class="done"><x-icon name="package" :size="18" /><b>Préparée</b></li>
        <li class="cur"><x-icon name="truck" :size="18" /><b>En route</b></li>
        <li><x-icon name="pin" :size="18" /><b>Livrée</b></li>
    </ol>
</section>
@endsection
