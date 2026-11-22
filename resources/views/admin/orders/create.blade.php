@extends('admin.layouts.app')
@php
    use App\Models\Setting;
    use App\OrderChannel;
    use App\PaymentMethod;
    use App\Support\Money;
    $oldItems = old('items', []);
@endphp

@php $paymentIcon = fn ($method) => \Illuminate\Support\Facades\Blade::render('<x-payment-icon :method="$m" :size="24" />', ['m' => $method]); @endphp
@section('title', 'Nouvelle commande')
@section('heading', 'Saisir une commande')
@section('subheading', 'Pour les commandes reçues par WhatsApp, téléphone ou en magasin. Le stock est décompté automatiquement.')
@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Commandes</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.orders.store') }}" class="grid-side" data-order-builder
      data-symbol="{{ Setting::get('currency_symbol') }}" data-position="{{ Setting::get('currency_position') }}"
      data-decimals="{{ (int) Setting::get('currency_decimals') }}" data-shipping="{{ (float) Setting::get('shipping_fee') }}"
      data-free-from="{{ (float) Setting::get('free_shipping_threshold') }}" data-zones='@json($shop->zones())'>
    @csrf
    <div class="stack">
        <section class="card">
            <header class="card-head">
                <h2>Articles</h2>
                <button type="button" class="btn btn-sm" data-add-line><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Ajouter une ligne</button>
            </header>
            <div class="lines" data-lines>
                @foreach ($oldItems as $i => $item)
                    @include('admin.orders.partials.line', ['index' => $i, 'selected' => $item['product_id'] ?? null, 'quantity' => $item['quantity'] ?? 1])
                @endforeach
            </div>
            <template id="line-template">
                @include('admin.orders.partials.line', ['index' => '__i__', 'selected' => null, 'quantity' => 1])
            </template>
        </section>

        <section class="card">
            <header class="card-head"><h2>Client et livraison</h2></header>
            <div class="form-grid">
                <label class="field"><span>Nom complet</span><input type="text" name="customer[name]" value="{{ old('customer.name') }}" required></label>
                <div class="field"><span>Téléphone</span><x-phone-input name="customer[phone]" country-name="customer[phone_country]" required /><small>Un client existant est retrouvé par son numéro.</small></div>
                <label class="field"><span>E-mail <em>(facultatif)</em></span><input type="email" name="customer[email]" value="{{ old('customer.email') }}"></label>
                <label class="field"><span>Ville</span>
                    <input type="text" name="customer[city]" value="{{ old('customer.city', $shop->default_city) }}" list="cities">
                    <datalist id="cities">@foreach ($shop->zones() as $zone)<option value="{{ $zone['name'] }}">@endforeach</datalist>
                </label>
                <label class="field span-2"><span>Adresse / repère de livraison</span><input type="text" name="customer[address]" value="{{ old('customer.address') }}" placeholder="Quartier, rue, point de repère"></label>
                <label class="field span-2"><span>Note interne <em>(facultatif)</em></span><textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Récapitulatif</h2></header>
            <div class="form">
                <label class="field"><span>Canal</span>
                    <select name="channel">
                        @foreach (OrderChannel::cases() as $channel)
                            <option value="{{ $channel->value }}" @selected(old('channel', 'whatsapp') === $channel->value)>{{ $channel->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Moyen de paiement</span>
                    <select name="payment_method">
                        @foreach (PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}" data-icon="{{ $paymentIcon($method) }}" @selected(old('payment_method', 'cash_on_delivery') === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Remise</span><input type="number" name="discount" value="{{ old('discount', 0) }}" min="0" step="0.01"></label>
                <div class="totals">
                    <div><span>Sous-total</span><b data-sub>{{ Money::format(0) }}</b></div>
                    <div><span>Livraison</span><b data-ship>—</b></div>
                    <div><span>Remise</span><b data-discount>—</b></div>
                    <div class="grand"><span>Total</span><b data-grand>{{ Money::format(0) }}</b></div>
                </div>
                <button class="btn btn-primary btn-lg block">Créer la commande</button>
            </div>
        </section>
    </div>
</form>
@endsection
