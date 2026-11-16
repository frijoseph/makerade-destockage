@extends('layouts.app')
@section('title', 'Mon compte — Makerade Destockage')

@section('content')
@php $title = 'Mon compte'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="auth reveal">
        <div class="tabs" role="tablist"><button class="on" data-tab="login">Connexion</button><button data-tab="register">Créer un compte</button></div>
        <form class="cform authform" id="loginForm" data-pane="login" novalidate>
            <label>E-mail<input type="email" required placeholder="vous@exemple.com"></label>
            <label>Mot de passe<input type="password" required placeholder="••••••••"></label>
            <button class="btn btn-primary btn-lg block">Connexion</button>
        </form>
        <form class="cform authform" data-pane="register" hidden novalidate>
            <label>Nom complet<input required placeholder="Jean Dupont"></label>
            <label>E-mail<input type="email" required placeholder="vous@exemple.com"></label>
            <label>Mot de passe<input type="password" required placeholder="8 caractères minimum"></label>
            <button class="btn btn-primary btn-lg block">Créer un compte</button>
        </form>
    </div>
</section>
@endsection
