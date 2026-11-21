@extends('admin.layouts.app')
@php
    use App\Models\Popup;
    $isNew = ! $popup->exists;
    $pages = old('pages', (array) $popup->pages);
    $segmentsField = ['type' => 'list', 'label' => 'Secteurs de la roue', 'help' => 'Le tirage est fait côté serveur selon le poids de chaque secteur (poids 0 = ne tombe jamais). Laissez le code vide pour un secteur « perdu ».', 'fields' => [
        'label' => ['type' => 'text', 'label' => 'Libellé affiché'],
        'coupon_code' => ['type' => 'text', 'label' => 'Code promo offert'],
        'weight' => ['type' => 'number', 'label' => 'Poids (chance)'],
        'color' => ['type' => 'color', 'label' => 'Couleur'],
    ]];
@endphp

@section('title', $isNew ? 'Nouveau popup' : $popup->name)
@section('heading', $isNew ? 'Nouveau popup' : 'Modifier « '.$popup->name.' »')
@section('subheading', 'Le contenu, les règles d’affichage et l’aperçu en direct.')
@section('actions')
    <a href="{{ route('admin.popups.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Popups</a>
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data" class="grid-side" data-popup-form action="{{ $isNew ? route('admin.popups.store') : route('admin.popups.update', $popup) }}">
    @csrf
    @unless ($isNew) @method('PUT') @endunless

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Contenu</h2></header>
            <div class="form-grid">
                <label class="field"><span>Nom interne</span><input type="text" name="name" value="{{ old('name', $popup->name) }}" required placeholder="Roue de bienvenue"><small>Visible uniquement dans l’administration.</small></label>
                <label class="field"><span>Type</span>
                    <select name="kind">
                        @foreach (Popup::KINDS as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', $popup->kind) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field span-2"><span>Titre</span><input type="text" name="title" value="{{ old('title', $popup->title) }}" required maxlength="255" placeholder="Tentez votre chance !"></label>
                <label class="field span-2"><span>Texte <em>(facultatif)</em></span><textarea name="body" rows="3" maxlength="1000">{{ old('body', $popup->body) }}</textarea></label>
                <label class="field"><span>Texte du bouton <em>(facultatif)</em></span><input type="text" name="button_label" value="{{ old('button_label', $popup->button_label) }}" maxlength="60"></label>
                <label class="field" data-kind-only="promo"><span>Lien du bouton <em>(facultatif)</em></span><input type="text" name="button_url" value="{{ old('button_url', $popup->button_url) }}" placeholder="/best-deals"></label>
                <label class="field" data-kind-only="promo newsletter"><span>Code promo affiché ou offert</span>
                    <input type="text" name="coupon_code" value="{{ old('coupon_code', $popup->coupon_code) }}" list="couponList" placeholder="BIENVENUE10" autocomplete="off">
                    <datalist id="couponList">@foreach ($coupons as $code)<option value="{{ $code }}">@endforeach</datalist>
                    <small data-kind-only="promo">Affiché dans le popup, copiable en un clic.</small><small data-kind-only="newsletter">Révélé après l’inscription à la newsletter.</small>
                </label>
            </div>
        </section>

        <section class="card" data-kind-only="wheel">
            <header class="card-head"><h2>Roue de la chance</h2></header>
            <p class="muted" style="margin: -8px 0 16px; font-size: 13px">Le visiteur saisit son e-mail (ajouté à la newsletter) et ne peut jouer qu’une fois avec la même adresse.</p>
            @include('admin.settings.field', ['name' => 'segments', 'fileName' => 'segments_files', 'errorKey' => 'segments', 'field' => $segmentsField, 'value' => old('segments', $popup->segments ?? []), 'wide' => true])
        </section>

        <section class="card">
            <header class="card-head"><h2>Quand l’afficher ?</h2></header>
            <div class="form-grid">
                <label class="field"><span>Déclencheur</span>
                    <select name="trigger">
                        @foreach (Popup::TRIGGERS as $value => $label)
                            <option value="{{ $value }}" @selected(old('trigger', $popup->trigger) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Valeur du déclencheur</span><input type="number" name="trigger_value" value="{{ old('trigger_value', $popup->trigger_value) }}" min="0" max="3600" required><small>Secondes (délai) ou pourcentage (défilement). Ignorée pour « à la sortie ».</small></label>
                <label class="field"><span>Fréquence</span>
                    <select name="frequency">
                        @foreach (Popup::FREQUENCIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('frequency', $popup->frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Appareils</span>
                    <select name="devices">
                        @foreach (['all' => 'Mobile et ordinateur', 'mobile' => 'Mobile seulement', 'desktop' => 'Ordinateur seulement'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('devices', $popup->devices) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="field span-2"><span>Pages concernées</span>
                    <div class="checks">
                        <label class="check"><input type="checkbox" name="pages[]" value="all" @checked(in_array('all', $pages, true))> Toutes</label>
                        @foreach (Popup::PAGES as $value => $label)
                            <label class="check"><input type="checkbox" name="pages[]" value="{{ $value }}" @checked(in_array($value, $pages, true))> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <label class="field"><span>Début <em>(facultatif)</em></span><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $popup->starts_at?->format('Y-m-d\TH:i')) }}"></label>
                <label class="field"><span>Fin <em>(facultatif)</em></span><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $popup->ends_at?->format('Y-m-d\TH:i')) }}"></label>
                <label class="field"><span>Priorité</span><input type="number" name="priority" value="{{ old('priority', $popup->priority ?? 0) }}" min="0" max="100"><small>Le plus haut passe en premier.</small></label>
            </div>
        </section>
    </div>

    <div class="stack sticky-col">
        <section class="card">
            <header class="card-head"><h2>Aperçu</h2></header>
            <div class="pv" data-pv>
                <div class="pv-ic" data-pv-icon>🎁</div>
                <b class="pv-title" data-pv-title>Votre titre ici</b>
                <p class="pv-body" data-pv-body></p>
                <div class="pv-code" data-pv-code hidden></div>
                <div class="pv-input" data-pv-email hidden>votre@email.com</div>
                <span class="pv-btn" data-pv-btn>J’en profite</span>
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>Apparence</h2></header>
            <div class="form">
                <div class="field"><span>Couleur d’accent</span>
                    <div class="color-field" data-color><input type="color" value="{{ old('accent', $popup->accent) }}" aria-label="Couleur"><input type="text" name="accent" value="{{ old('accent', $popup->accent) }}" required pattern="#[0-9a-fA-F]{6}"></div>
                </div>
                <div data-kind-only="promo newsletter">
                    <div class="preview" id="popImg" style="aspect-ratio: 16/9">
                        @if ($popup->image)<img src="{{ \App\Models\Setting::imageUrl($popup->image) }}" alt="">@else<span class="ph"><x-admin.icon name="image" :size="28" :stroke="1.4" /><br>Image d’en-tête (facultatif)</span>@endif
                    </div>
                    <input type="file" name="image_file" accept="image/*" data-image-input="#popImg" aria-label="Image du popup" style="margin-top: 10px">
                    @if ($popup->image)<label class="check" style="margin-top: 8px"><input type="checkbox" name="remove_image" value="1"> Retirer l’image</label>@endif
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $popup->is_active))> Popup actif</label>
            </div>
        </section>

        <div class="form-actions"><button class="btn btn-primary btn-lg">{{ $isNew ? 'Créer le popup' : 'Enregistrer' }}</button></div>
    </div>
</form>
@endsection
