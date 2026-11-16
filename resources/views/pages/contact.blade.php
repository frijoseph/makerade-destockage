@extends('layouts.app')
@section('title', 'Contact — Makerade Destockage')

@section('content')
@php $title = 'Contact'; $sub = "Nous serions ravis de vous lire. Notre équipe répond sous 24 heures."; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="contact">
        <div class="contact-info reveal">
            <div class="ci"><x-icon name="phone" :size="22" /><div><b>Appelez-nous</b><span>+225 07 00 00 00 00</span></div></div>
            <div class="ci"><x-icon name="mail" :size="22" /><div><b>E-mail</b><span>contact@makerade-destockage.com</span></div></div>
            <div class="ci"><x-icon name="pin" :size="22" /><div><b>Venez nous voir</b><span>Abidjan, Côte d'Ivoire</span></div></div>
            <div class="ci"><x-icon name="clock" :size="22" /><div><b>Horaires</b><span>Lun–Sam, 8h00 – 19h00</span></div></div>
        </div>
        <form class="cform reveal" id="contactForm" style="--d:100ms" novalidate>
            <div class="row2">
                <label>Nom complet<input name="name" required placeholder="Jean Dupont"></label>
                <label>E-mail<input name="email" type="email" required placeholder="vous@exemple.com"></label>
            </div>
            <label>Sujet<input name="subject" placeholder="Comment pouvons-nous vous aider ?"></label>
            <label>Message<textarea name="message" rows="5" required placeholder="Écrivez votre message..."></textarea></label>
            <button class="btn btn-primary btn-lg"><x-icon name="send" :size="18" /> Envoyer le message</button>
        </form>
    </div>
</section>
@endsection
