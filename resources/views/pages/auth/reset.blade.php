@extends('layouts.app')
@section('title', 'Nouveau mot de passe')

@section('content')
@php $title = 'Nouveau mot de passe'; $sub = 'Choisissez un mot de passe d’au moins 8 caractères.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="auth reveal">
        <form method="POST" action="{{ route('password.update') }}" class="cform" data-validate novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label>E-mail<input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username" @error('email') class="invalid" @enderror>@error('email')<small class="fe">{{ $message }}</small>@enderror</label>
            <label>Nouveau mot de passe
                <span class="pass"><input type="password" name="password" required autofocus autocomplete="new-password" @error('password') class="invalid" @enderror><button type="button" data-toggle-pass aria-label="Afficher le mot de passe"><x-icon name="eye" :size="18" /></button></span>
                @error('password')<small class="fe">{{ $message }}</small>@enderror
            </label>
            <label>Confirmation<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <button class="btn btn-primary btn-lg block">Enregistrer le mot de passe</button>
        </form>
    </div>
</section>
@endsection
