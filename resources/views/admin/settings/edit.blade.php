@extends('admin.layouts.app')

@section('title', 'Paramètres')
@section('heading', 'Paramètres')
@section('subheading', 'Tout ce que voit le client se règle ici : textes, prix, livraison, menus, page d’accueil…')

@section('content')
<nav class="tabs" aria-label="Rubriques des paramètres">
    @foreach ($groups as $key => $item)
        <a href="{{ route('admin.settings.edit', $key) }}" @class(['is-active' => $key === $group])>{{ $item['label'] }}</a>
    @endforeach
</nav>

@if ($group === 'delivery')
    @include('admin.settings.zones')
@endif

<form method="POST" action="{{ route('admin.settings.update', $group) }}" enctype="multipart/form-data" class="stack">
    @csrf @method('PUT')
    <section class="card">
        <header class="card-head"><h2>{{ $groups[$group]['label'] }}</h2></header>
        <p class="muted" style="margin: -8px 0 18px; font-size: 13px">
            Dans les textes, vous pouvez insérer des valeurs automatiques :
            <code>{shop_name}</code> <code>{delivery_zone}</code> <code>{shipping_fee}</code> <code>{free_shipping_threshold}</code>
            <code>{return_days}</code> <code>{delivery_delay}</code> <code>{response_time}</code> <code>{contact_phone}</code> <code>{contact_email}</code>.
        </p>
        <div class="form-grid">
            @foreach ($fields as $key => $field)
                @isset($field['show_when'])<div data-show-when="{{ array_key_first($field['show_when']) }}={{ reset($field['show_when']) }}" style="display: contents">@endisset
                @include('admin.settings.field', [
                    'name' => "settings[{$key}]",
                    'fileName' => "files[{$key}]",
                    'errorKey' => "settings.{$key}",
                    'field' => $field,
                    'value' => old("settings.{$key}", $values[$key]),
                    'wide' => in_array($field['type'], ['list', 'textarea', 'markdown', 'checkboxes'], true),
                ])
                @isset($field['show_when'])</div>@endisset
            @endforeach
        </div>
    </section>
    <div class="form-actions sticky-actions">
        <button class="btn btn-primary btn-lg">Enregistrer « {{ $groups[$group]['label'] }} »</button>
    </div>
</form>

@if ($group === 'emails')
    <section class="card" style="margin-top: 18px">
        <header class="card-head"><h2>Tester l’envoi</h2></header>
        <p class="muted" style="margin: -8px 0 14px; font-size: 13px">Enregistrez d’abord vos réglages, puis envoyez un e-mail de test pour vérifier le serveur.</p>
        <form method="POST" action="{{ route('admin.settings.mail-test') }}" class="toolbar">
            @csrf
            <label class="field grow"><span class="sr-only">Adresse de test</span><input type="email" name="to" value="{{ old('to', auth()->user()->email) }}" required placeholder="vous@exemple.com"></label>
            <button class="btn btn-primary">Envoyer un e-mail de test</button>
        </form>
    </section>
@endif
@endsection
