@php use App\Support\Money; @endphp
<div class="oq">
    <div class="oq-head">
        <span><x-admin.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-admin.badge> <x-admin.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-admin.badge></span>
        <small>Passée le {{ $order->placed_at->translatedFormat('j F Y à H:i') }} · {{ $order->channel->label() }}</small>
    </div>

    <div class="oq-grid">
        <div class="oq-box"><small>Client</small><b>{{ $order->shipping_name ?? $order->customer->name }}</b><br>{{ $shop->phone($order->shipping_phone) }}</div>
        <div class="oq-box"><small>Livraison</small>{{ collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ') ?: 'Adresse non renseignée' }}</div>
        <div class="oq-box"><small>Paiement</small>{{ $order->payment_method->label() }}</div>
        @if ($order->notes)<div class="oq-box"><small>Note</small>{{ $order->notes }}</div>@endif
    </div>

    <table class="oq-lines">
        <thead><tr><th>Article</th><th class="n">Prix</th><th class="n">Qté</th><th class="n">Total</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr><td>{{ $item->product_name }}<br><small>{{ $item->sku }}</small></td><td class="n">{{ Money::format($item->unit_price) }}</td><td class="n">× {{ $item->quantity }}</td><td class="n"><b>{{ Money::format($item->line_total) }}</b></td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="oq-total">
        <div><span>Sous-total</span><span>{{ Money::format($order->subtotal) }}</span></div>
        <div><span>Livraison</span><span>{{ (float) $order->shipping_fee ? Money::format($order->shipping_fee) : 'Offerte' }}</span></div>
        @if ((float) $order->discount)<div><span>Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span><span>− {{ Money::format($order->discount) }}</span></div>@endif
        <div class="g"><span>Total</span><span>{{ Money::format($order->total) }}</span></div>
    </div>

    <div class="oq-actions">
        <a class="p" href="{{ route('admin.orders.show', $order) }}">Ouvrir la commande</a>
        <a href="{{ route('admin.orders.print', [$order, 'delivery', 'auto' => 1]) }}" target="_blank" rel="noopener">Bon de livraison</a>
        <a href="{{ route('admin.orders.print', [$order, 'receipt', 'auto' => 1]) }}" target="_blank" rel="noopener">Reçu</a>
        <a href="{{ route('admin.orders.print', [$order, 'ticket', 'auto' => 1]) }}" target="_blank" rel="noopener">Ticket</a>
    </div>
</div>
