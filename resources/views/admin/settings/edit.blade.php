@extends('admin.layouts.app')

@section('title', 'Paramètres')
@section('heading', 'Paramètres')
@section('subheading', 'Informations de la boutique, monnaie et livraison.')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="grid-2" style="align-items: start">
    @csrf @method('PUT')
    <section class="card">
        <header class="card-head"><h2>Boutique</h2></header>
        <div class="form">
            <label class="field"><span>Nom de la boutique</span><input type="text" name="shop_name" value="{{ old('shop_name', $settings['shop_name']) }}" required></label>
            <label class="field"><span>E-mail de contact</span><input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}"></label>
            <label class="field"><span>Téléphone</span><input type="tel" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}"></label>
            <label class="field"><span>Numéro WhatsApp <em>(format international, chiffres uniquement)</em></span><input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number']) }}" placeholder="2250700000000" @error('whatsapp_number') aria-invalid="true" @enderror></label>
        </div>
    </section>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Monnaie</h2></header>
            <div class="form-grid cols-3">
                <label class="field"><span>Symbole</span><input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required maxlength="8"></label>
                <label class="field"><span>Position</span>
                    <select name="currency_position">
                        <option value="before" @selected(old('currency_position', $settings['currency_position']) === 'before')>Avant ($10)</option>
                        <option value="after" @selected(old('currency_position', $settings['currency_position']) === 'after')>Après (10 FCFA)</option>
                    </select>
                </label>
                <label class="field"><span>Décimales</span>
                    <select name="currency_decimals">
                        <option value="2" @selected((int) old('currency_decimals', $settings['currency_decimals']) === 2)>2 (10,50)</option>
                        <option value="0" @selected((int) old('currency_decimals', $settings['currency_decimals']) === 0)>0 (1 050)</option>
                    </select>
                </label>
            </div>
            <p class="muted" style="margin-top: 12px; font-size: 13px">Ce réglage concerne l’administration. Les prix enregistrés ne sont pas convertis.</p>
        </section>

        <section class="card">
            <header class="card-head"><h2>Livraison</h2></header>
            <div class="form-grid">
                <label class="field"><span>Frais de livraison</span><input type="number" name="shipping_fee" value="{{ old('shipping_fee', $settings['shipping_fee']) }}" required min="0" step="0.01"></label>
                <label class="field"><span>Livraison offerte dès</span><input type="number" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}" required min="0" step="0.01"><small>0 = jamais offerte</small></label>
            </div>
        </section>

        <div class="form-actions"><button class="btn btn-primary">Enregistrer les paramètres</button></div>
    </div>
</form>
@endsection
