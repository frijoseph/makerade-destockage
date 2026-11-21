@extends('layouts.app')
@section('title', 'Mot de passe oublié')

@section('content')
@php $title = 'Mot de passe oublié'; $sub = 'Saisissez votre e-mail : nous vous envoyons un lien pour en choisir un nouveau.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="auth reveal">
        <form method="POST" action="{{ route('password.email') }}" class="cform" data-validate novalidate>
            @csrf
            <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="vous@exemple.com" @error('email') class="invalid" @enderror>@error('email')<small class="fe">{{ $message }}</small>@enderror</label>
            <button class="btn btn-primary btn-lg block">Envoyer le lien</button>
            <a href="{{ route('account') }}" class="link-all" style="justify-self:center">← Retour à la connexion</a>
        </form>
    </div>
</section>
@endsection
