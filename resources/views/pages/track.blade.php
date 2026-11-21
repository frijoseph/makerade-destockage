@extends('layouts.app')
@section('title', 'Suivi de commande')

@section('content')
@php $title = 'Suivre ma commande'; $sub = 'Saisissez votre numéro de commande et le téléphone utilisé lors de l’achat.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <form class="cform track reveal" id="trackForm" novalidate>
        <div class="row2">
            <label>Numéro de commande<input name="number" required placeholder="{{ \App\Models\Order::exampleNumber() }}" autocomplete="off"></label>
            <label>Téléphone<input name="phone" type="tel" required autocomplete="tel" placeholder="{{ $shop->contact_phone }}"></label>
        </div>
        <p class="checkout-err" data-form-error role="alert" hidden></p>
        <button class="btn btn-primary btn-lg">Suivre la commande</button>
    </form>
    <div class="track-result" id="trackResult" hidden>
        <p class="track-head"></p>
        <ol class="steps" id="steps"></ol>
    </div>
</section>
@endsection
