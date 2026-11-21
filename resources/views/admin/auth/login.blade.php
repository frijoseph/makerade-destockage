<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion — Admin {{ \App\Models\Setting::get('shop_name') }}</title>
    <link rel="icon" href="{{ \App\Models\Setting::imageUrl(\App\Models\Setting::get('favicon')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-ui.css') }}?v={{ filemtime(public_path('css/admin-ui.css')) }}">
    <script>try{var t=localStorage.getItem('mk_admin_theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body class="admin auth-page">
<main class="auth-card">
    <div class="auth-brand">
        <img src="{{ \App\Models\Setting::imageUrl(\App\Models\Setting::get('logo')) }}" alt="" width="64" height="56">
        <div><b>{{ \App\Models\Setting::get('shop_name') }}</b><small>Espace d’administration</small></div>
    </div>
    <h1>Bon retour 👋</h1>
    <p class="muted">Connectez-vous pour gérer commandes, produits et stock.</p>

    <form method="POST" action="{{ route('admin.login.store') }}" class="form">
        @csrf
        <label class="field">
            <span>E-mail</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" @error('email') aria-invalid="true" @enderror>
            @error('email')<small class="err">{{ $message }}</small>@enderror
        </label>
        <label class="field">
            <span>Mot de passe</span>
            <input type="password" name="password" required autocomplete="current-password">
            @error('password')<small class="err">{{ $message }}</small>@enderror
        </label>
        <label class="check"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
        <button class="btn btn-primary btn-lg block">Se connecter</button>
    </form>
    <a href="{{ route('home') }}" class="auth-back"><x-admin.icon name="chev-left" :size="16" /> Retour à la boutique</a>
</main>
</body>
</html>
