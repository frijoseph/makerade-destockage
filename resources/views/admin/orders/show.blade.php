@extends('admin.layouts.app')
@php use App\Support\Money; use App\OrderStatus; use App\PaymentStatus; use App\PaymentMethod; @endphp

@php $paymentIcon = fn ($method) => \Illuminate\Support\Facades\Blade::render('<x-payment-icon :method="$m" :size="24" />', ['m' => $method]); @endphp
@section('title', 'Commande '.$order->number)
@section('heading', 'Commande '.$order->number)
@section('subheading')Passée le {{ $order->placed_at->translatedFormat('l j F Y à H:i') }} · {{ $order->channel->label() }}@endsection
@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Commandes</a>
@endsection

@section('content')
<div class="grid-side">
    <div class="stack">
        <section class="card">
            <header class="card-head">
                <h2>Articles</h2>
                <x-admin.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-admin.badge>
            </header>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Produit</th><th>Réf.</th><th class="num">Prix unitaire</th><th class="num">Qté</th><th class="num">Total</th></tr></thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <div class="thumb">
                                    @if ($item->product?->image_url)<img src="{{ $item->product->image_url }}" alt="">@else<span class="ph"><x-admin.icon name="box" :size="18" /></span>@endif
                                    <span>
                                        @if ($item->product)<a href="{{ route('admin.products.edit', $item->product) }}" class="strong">{{ $item->product_name }}</a>@else<b>{{ $item->product_name }}</b><small>Produit supprimé</small>@endif
                                    </span>
                                </div>
                            </td>
                            <td>{{ $item->sku }}</td>
                            <td class="num">{{ Money::format($item->unit_price) }}</td>
                            <td class="num">× {{ $item->quantity }}</td>
                            <td class="num strong">{{ Money::format($item->line_total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="totals" style="margin-top: 22px">
                <div><span>Sous-total</span><b>{{ Money::format($order->subtotal) }}</b></div>
                <div><span>Livraison</span><b>{{ (float) $order->shipping_fee ? Money::format($order->shipping_fee) : 'Offerte' }}</b></div>
                @if ((float) $order->discount)<div><span>Remise @if ($order->coupon_code)(code <b class="code-chip" style="padding:2px 8px">{{ $order->coupon_code }}</b>)@endif</span><b>− {{ Money::format($order->discount) }}</b></div>@endif
                <div class="grand"><span>Total</span><b>{{ Money::format($order->total) }}</b></div>
            </div>
        </section>

        @if ($order->notes)
            <section class="card">
                <header class="card-head"><h2>Note</h2></header>
                <p>{{ $order->notes }}</p>
            </section>
        @endif

        <section class="card">
            <header class="card-head"><h2>Historique du stock</h2></header>
            <ul class="timeline">
                @forelse ($order->stockMovements->sortBy('id') as $movement)
                    <li><span><b>{{ $movement->type->label() }}</b> · {{ $movement->product?->name ?? 'Produit supprimé' }} ({{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}) — {{ $movement->created_at->translatedFormat('j M H:i') }}</span></li>
                @empty
                    <li>Aucun mouvement de stock.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Traitement</h2></header>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="form">
                @csrf @method('PATCH')
                <label class="field">
                    <span>Statut de la commande</span>
                    <select name="status">
                        @foreach (OrderStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <small>L’annulation remet les articles en stock automatiquement.</small>
                </label>
                <button class="btn btn-primary block">Mettre à jour le statut</button>
            </form>
        </section>

        <section class="card">
            <header class="card-head">
                <h2>Paiement</h2>
                <x-admin.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-admin.badge>
            </header>
            <form method="POST" action="{{ route('admin.orders.payment', $order) }}" class="form">
                @csrf @method('PATCH')
                <label class="field">
                    <span>Moyen de paiement</span>
                    <select name="payment_method">
                        @foreach (PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}" data-icon="{{ $paymentIcon($method) }}" @selected($order->payment_method === $method)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>État du paiement</span>
                    <select name="payment_status">
                        @foreach (PaymentStatus::cases() as $payment)
                            <option value="{{ $payment->value }}" @selected($order->payment_status === $payment)>{{ $payment->label() }}</option>
                        @endforeach
                    </select>
                    @if ($order->paid_at)<small>Encaissé le {{ $order->paid_at->translatedFormat('j M Y à H:i') }}</small>@endif
                </label>
                <button class="btn block">Enregistrer le paiement</button>
            </form>
        </section>

        <section class="card">
            <header class="card-head">
                <h2>Client</h2>
                <a href="{{ route('admin.customers.show', $order->customer) }}" class="link">Fiche</a>
            </header>
            <div class="customer-head">
                <span class="avatar">{{ $order->customer->initials() }}</span>
                <div><h2>{{ $order->shipping_name ?? $order->customer->name }}</h2><small class="muted">{{ $order->customer->orders()->count() }} commande(s)</small></div>
            </div>
            <p class="contact-line"><x-admin.icon name="phone" :size="16" /> <a href="tel:{{ $order->shipping_phone }}">{{ $order->shipping_phone }}</a></p>
            @php $whatsapp = preg_replace('/\D/', '', (string) $order->shipping_phone); @endphp
            @if ($whatsapp)
                <p class="contact-line"><x-admin.icon name="whatsapp" :size="16" /> <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Bonjour, au sujet de votre commande '.$order->number.' chez '.$shop->shop_name.'…') }}" target="_blank" rel="noopener">Écrire sur WhatsApp</a></p>
            @endif
            @if ($order->customer->email)
                <p class="contact-line"><x-admin.icon name="mail" :size="16" /> <a href="mailto:{{ $order->customer->email }}">{{ $order->customer->email }}</a></p>
            @endif
            <p class="contact-line"><x-admin.icon name="pin" :size="16" /> <span>{{ collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ') ?: 'Adresse non renseignée' }}</span></p>
        </section>
    </div>
</div>
@endsection
