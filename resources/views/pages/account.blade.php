@extends('layouts.app')
@section('title', 'Mon compte')

@section('content')
@php
    $title = $member ? 'Mon compte' : 'Connexion & inscription';
    $sub = $member ? null : 'Suivez vos commandes, retrouvez vos infos et profitez d’offres réservées aux membres.';
@endphp
@include('partials.page-head')

<section class="container section tight">
@if (! $member)
    @php $registerOpen = ! $errors->login->any() && ($errors->any() || request('tab') === 'register'); @endphp
    <div class="acc-guest">
        <aside class="acc-perks reveal">
            <h2>Pourquoi créer un compte ?</h2>
            <ul>
                <li><x-icon name="package" :size="22" :stroke="1.6" /><div><b>Suivi de vos commandes</b><span>Statut en temps réel, depuis votre espace.</span></div></li>
                <li><x-icon name="bolt" :size="22" :stroke="1.6" /><div><b>Commande en 1 clic</b><span>Vos coordonnées de livraison sont déjà remplies.</span></div></li>
                <li><x-icon name="gift" :size="22" :stroke="1.6" /><div><b>Offres réservées aux membres</b><span>Codes promo et avant-premières.</span></div></li>
            </ul>
        </aside>

        <div class="auth reveal" style="--d: 80ms">
            <div class="tabs" role="tablist">
                <button type="button" @class(['on' => ! $registerOpen]) data-tab="login">Connexion</button>
                <button type="button" @class(['on' => $registerOpen]) data-tab="register">Créer un compte</button>
            </div>

            <form method="POST" action="{{ route('account.login') }}" class="cform authform" data-pane="login" data-validate novalidate @if ($registerOpen) hidden @endif>
                @csrf
                @if ($errors->login->any())<p class="form-error" role="alert">{{ $errors->login->first() }}</p>@endif
                <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="vous@exemple.com"></label>
                <label>Mot de passe
                    <span class="pass"><input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"><button type="button" data-toggle-pass aria-label="Afficher le mot de passe"><x-icon name="eye" :size="18" /></button></span>
                </label>
                <div class="auth-row">
                    <label class="chk"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
                    <a href="{{ route('password.request') }}" class="link-all">Mot de passe oublié ?</a>
                </div>
                <button class="btn btn-primary btn-lg block">Connexion</button>
            </form>

            <form method="POST" action="{{ route('account.register') }}" class="cform authform" data-pane="register" data-validate novalidate @unless ($registerOpen) hidden @endunless>
                @csrf
                <label>Nom complet<input name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Prénom et nom" @error('name') class="invalid" @enderror>@error('name')<small class="fe">{{ $message }}</small>@enderror</label>
                <div class="row2">
                    <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" @error('email') class="invalid" @enderror>@error('email')<small class="fe">{{ $message }}</small>@enderror</label>
                    <label>Téléphone<input type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="{{ $shop->contact_phone }}" @error('phone') class="invalid" @enderror>@error('phone')<small class="fe">{{ $message }}</small>@enderror</label>
                </div>
                <div class="row2">
                    <label>Mot de passe
                        <span class="pass"><input type="password" name="password" required autocomplete="new-password" placeholder="8 caractères minimum" @error('password') class="invalid" @enderror><button type="button" data-toggle-pass aria-label="Afficher le mot de passe"><x-icon name="eye" :size="18" /></button></span>
                        @error('password')<small class="fe">{{ $message }}</small>@enderror
                    </label>
                    <label>Confirmation<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
                </div>
                <label class="chk"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required> J’accepte les <a href="{{ route('info', 'terms') }}" target="_blank" rel="noopener" class="link-all">conditions générales</a></label>
                @error('terms')<small class="fe">{{ $message }}</small>@enderror
                @if ($shop->welcome_coupon_code)<p class="auth-gift"><x-icon name="gift" :size="18" /> Un cadeau de bienvenue vous attend à l’inscription.</p>@endif
                <button class="btn btn-primary btn-lg block">Créer mon compte</button>
            </form>
        </div>
    </div>
@else
    @php $firstName = \Illuminate\Support\Str::before($member->name, ' '); @endphp
    <div class="acc-head reveal">
        <span class="acc-avatar">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
        <div><h2>Bonjour, {{ $firstName }} 👋</h2><p>{{ $member->email }}</p></div>
        <form method="POST" action="{{ route('account.logout') }}" data-confirm-logout>
            @csrf
            <button class="btn btn-ghost"><x-icon name="logout" :size="18" /> Déconnexion</button>
        </form>
    </div>

    <div class="acc-stats reveal">
        <div><small>Commandes</small><b>{{ $orders->count() }}</b></div>
        <div><small>Total dépensé</small><b>{{ $shop->money($spent) }}</b></div>
        <div><small>Dernière commande</small><b>{{ $orders->first()?->placed_at->translatedFormat('j M Y') ?? '—' }}</b></div>
    </div>

    <div class="tabs acc-tabs three" role="tablist">
        <button type="button" class="on" data-tab="orders">Mes commandes</button>
        <button type="button" data-tab="profile">Mes informations</button>
        <button type="button" data-tab="security">Mot de passe</button>
    </div>

    <div data-pane="orders" class="acc-pane">
        @forelse ($orders as $order)
            <article class="acc-order reveal">
                <div class="ao-top">
                    <div><b>{{ $order->number }}</b><small>{{ $order->placed_at->translatedFormat('j F Y') }} · {{ (int) $order->units }} article(s)</small></div>
                    <span class="st st-{{ $order->status->tone() }}">{{ $order->status->label() }}</span>
                </div>
                <div class="ao-bottom">
                    <b>{{ $shop->money($order->total) }}</b>
                    <span class="with-ic"><x-payment-icon :method="$order->payment_method" :size="20" /> {{ $order->payment_status->label() }} · {{ $order->payment_method->label() }}</span>
                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->number]) }}" class="link-all">Voir le détail <x-icon name="arrow-right" :size="15" /></a>
                </div>
            </article>
        @empty
            <div class="empty"><x-icon name="package" :size="44" :stroke="1.3" /><h3>Aucune commande pour l’instant</h3><p>Vos prochains achats apparaîtront ici.</p><a class="btn btn-primary" href="{{ route('shop') }}">Découvrir la boutique</a></div>
        @endforelse
    </div>

    <form method="POST" action="{{ route('account.profile') }}" class="cform acc-pane" data-pane="profile" data-validate novalidate hidden>
        @csrf @method('PUT')
        <div class="row2">
            <label>Nom complet<input name="name" value="{{ old('name', $member->name) }}" required @error('name') class="invalid" @enderror>@error('name')<small class="fe">{{ $message }}</small>@enderror</label>
            <label>Téléphone<input type="tel" name="phone" value="{{ old('phone', $member->phone) }}" required @error('phone') class="invalid" @enderror>@error('phone')<small class="fe">{{ $message }}</small>@enderror</label>
        </div>
        <label>E-mail<input type="email" name="email" value="{{ old('email', $member->email) }}" required @error('email') class="invalid" @enderror>@error('email')<small class="fe">{{ $message }}</small>@enderror</label>
        <div class="row2">
            <label>Ville<input name="city" value="{{ old('city', $profile?->city) }}" list="accCities"></label>
            <label>Adresse de livraison<input name="address" value="{{ old('address', $profile?->address) }}" placeholder="Quartier, rue, repère"></label>
        </div>
        <datalist id="accCities">@foreach ($shop->zones() as $zone)<option value="{{ $zone['name'] }}">@endforeach</datalist>
        <button class="btn btn-primary btn-lg">Enregistrer</button>
    </form>

    <form method="POST" action="{{ route('account.password') }}" class="cform acc-pane" data-pane="security" data-validate novalidate hidden>
        @csrf @method('PUT')
        <label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password" @error('current_password') class="invalid" @enderror>@error('current_password')<small class="fe">{{ $message }}</small>@enderror</label>
        <div class="row2">
            <label>Nouveau mot de passe<input type="password" name="password" required autocomplete="new-password" placeholder="8 caractères minimum" @error('password') class="invalid" @enderror>@error('password')<small class="fe">{{ $message }}</small>@enderror</label>
            <label>Confirmation<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        </div>
        <button class="btn btn-primary btn-lg">Modifier le mot de passe</button>
    </form>
@endif
</section>
@endsection
