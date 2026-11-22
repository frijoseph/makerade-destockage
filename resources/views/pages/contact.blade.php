@extends('layouts.app')
@section('title', 'Contact')

@section('content')
@php $title = 'Contact'; $sub = $shop->text('contact_intro'); @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="contact">
        <div class="contact-info reveal">
            @if ($shop->contact_phone)
                <div class="ci"><x-icon name="phone" :size="22" /><div><b>Appelez-nous</b><a href="tel:{{ preg_replace('/[^\d+]/', '', $shop->contact_phone) }}">{{ $shop->contact_phone }}</a></div></div>
            @endif
            @if ($shop->whatsapp_number)
                <div class="ci"><x-icon name="chat" :size="22" /><div><b>WhatsApp</b><a href="https://wa.me/{{ $shop->whatsapp_number }}" target="_blank" rel="noopener">Écrire sur WhatsApp</a></div></div>
            @endif
            @if ($shop->contact_email)
                <div class="ci"><x-icon name="mail" :size="22" /><div><b>E-mail</b><a href="mailto:{{ $shop->contact_email }}">{{ $shop->contact_email }}</a></div></div>
            @endif
            @if ($shop->contact_address)
                <div class="ci"><x-icon name="pin" :size="22" /><div><b>Venez nous voir</b><span>{{ $shop->contact_address }}</span></div></div>
            @endif
            @if ($shop->opening_hours)
                <div class="ci"><x-icon name="clock" :size="22" /><div><b>Horaires</b><span>{{ $shop->opening_hours }}</span></div></div>
            @endif
        </div>
        <form class="cform reveal" id="contactForm" style="--d:100ms" novalidate>
            <label>Nom complet<input name="name" required autocomplete="name" placeholder="Prénom et nom"></label>
            <div class="row2">
                <label>Téléphone <x-phone-input name="phone" required /></label>
                <label>E-mail <em>(facultatif)</em><input name="email" type="email" autocomplete="email" placeholder="vous@exemple.com"></label>
            </div>
            <label>Sujet<input name="subject" placeholder="Comment pouvons-nous vous aider ?"></label>
            <label>Message<textarea name="message" rows="5" required placeholder="Écrivez votre message..."></textarea></label>
            <p class="checkout-err" data-form-error role="alert" hidden></p>
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
            <button class="btn btn-primary btn-lg"><x-icon name="send" :size="18" /> Envoyer le message</button>
        </form>
    </div>
</section>
@endsection
