@extends('layouts.app')
@section('title', 'Mot de passe oublié')

@section('content')
@php $title = 'Mot de passe oublié'; $sub = 'Saisissez votre téléphone ou votre e-mail : nous envoyons un lien à l’adresse e-mail de votre compte.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="auth reveal">
        <form method="POST" action="{{ route('password.email') }}" class="cform" data-validate novalidate>
            @csrf
            <label>Téléphone ou e-mail<input type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" placeholder="07 00 00 00 00 ou vous@exemple.com" @error('login') class="invalid" @enderror>@error('login')<small class="fe">{{ $message }}</small>@enderror</label>
            <button class="btn btn-primary btn-lg block">Envoyer le lien</button>
            <a href="{{ route('account') }}" class="link-all" style="justify-self:center">← Retour à la connexion</a>
        </form>
    </div>
</section>
@endsection
