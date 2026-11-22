@extends('admin.layouts.app')

@section('title', 'Mon profil')
@section('heading', 'Mon profil')
@section('subheading', 'Vos informations de connexion à l’administration.')

@section('content')
<div class="grid-2" style="align-items: start">
    <section class="card">
        <header class="card-head"><h2>Informations</h2></header>
        <div class="profile-id">
            <span class="avatar avatar-lg">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div><b>{{ $user->name }}</b><small class="muted" style="display: block">{{ $user->role->label() }}@if ($user->last_login_at) · dernière connexion {{ $user->last_login_at->translatedFormat('j F Y à H:i') }}@endif</small></div>
        </div>
        <form method="POST" action="{{ route('admin.profile.update') }}" class="form">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <label class="field span-2"><span>Nom</span><input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name" @error('name') aria-invalid="true" @enderror></label>
                <label class="field span-2"><span>E-mail (identifiant de connexion)</span><input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email" @error('email') aria-invalid="true" @enderror></label>
            </div>
            <div class="form-actions"><button class="btn btn-primary">Enregistrer</button></div>
        </form>
    </section>

    <section class="card">
        <header class="card-head"><h2>Mot de passe</h2></header>
        <form method="POST" action="{{ route('admin.profile.password') }}" class="form">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <label class="field span-2"><span>Mot de passe actuel</span><input type="password" name="current_password" required autocomplete="current-password" @error('current_password') aria-invalid="true" @enderror></label>
                <label class="field"><span>Nouveau mot de passe</span><input type="password" name="password" required minlength="8" autocomplete="new-password" @error('password') aria-invalid="true" @enderror><small>8 caractères minimum.</small></label>
                <label class="field"><span>Confirmer</span><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
            </div>
            <div class="form-actions"><button class="btn btn-primary">Changer le mot de passe</button></div>
        </form>
    </section>
</div>
@endsection
