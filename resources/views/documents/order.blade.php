@php
    $isTicket = $mode === 'ticket';
    $isDelivery = $mode === 'delivery';
    $title = $isDelivery ? $shop->text('print_delivery_title') : $shop->text('print_receipt_title');
    $company = trim((string) $shop->print_company_name) !== '' ? $shop->print_company_name : $shop->shop_name;
    $details = array_filter(preg_split('/\R/', (string) $shop->print_company_details) ?: []);
    $showPrices = ! $isDelivery || $shop->print_delivery_show_prices;
    $isPaid = $order->payment_status === \App\PaymentStatus::Paid;
    $money = fn ($amount) => $shop->money($amount);
    $logo = $shop->print_logo_enabled ? $shop->image('logo') : null;
    $autoPrint = $auto && $shop->print_auto_open;
    $width = $isTicket ? $shop->print_ticket_width : null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} {{ $order->number }} — {{ $company }}</title>
    <style>
        :root { --ink: #151a17; --mut: #5f6a64; --line: #d9dfdb; --ac: {{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) $shop->theme_color) ? $shop->theme_color : '#0f6a26' }}; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1ef; color: var(--ink); font: 14px/1.45 system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: center; padding: 10px 14px; background: #fff; border-bottom: 1px solid var(--line); }
        .bar a, .bar button { font: inherit; font-weight: 700; padding: 8px 16px; border-radius: 10px; border: 1.5px solid var(--line); background: #fff; color: var(--ink); text-decoration: none; cursor: pointer; }
        .bar .on { border-color: var(--ac); color: var(--ac); }
        .bar .go { background: var(--ac); border-color: var(--ac); color: #fff; }
        .sheet { background: #fff; margin: 18px auto; padding: 14mm 14mm 12mm; max-width: 210mm; min-height: 250mm; box-shadow: 0 8px 30px rgba(0, 0, 0, .12); }
        .ticket .sheet { max-width: {{ $width === '58mm' ? '58mm' : '80mm' }}; min-height: 0; padding: 4mm 3mm; font-size: 12px; line-height: 1.35; }
        header.top { display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; border-bottom: 3px solid var(--ac); padding-bottom: 12px; margin-bottom: 16px; }
        .brand img { height: 52px; max-width: 200px; object-fit: contain; display: block; margin-bottom: 6px; }
        .brand b { font-size: 18px; display: block; }
        .brand small, .meta small { color: var(--mut); display: block; }
        .meta { text-align: right; }
        .meta h1 { margin: 0 0 4px; font-size: 24px; color: var(--ac); letter-spacing: .02em; text-transform: uppercase; }
        .meta .no { font-size: 16px; font-weight: 800; }
        .pill { display: inline-block; padding: 2px 10px; border-radius: 99px; font-weight: 700; font-size: 12px; border: 1.5px solid currentColor; }
        .pill.ok { color: #14793a; } .pill.due { color: #b45309; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .box { border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; }
        .box h2 { margin: 0 0 4px; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--mut); }
        table { width: 100%; border-collapse: collapse; }
        th { background: color-mix(in srgb, var(--ac) 10%, #fff); text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        th, td { padding: 8px 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
        .n { text-align: right; white-space: nowrap; }
        td small { color: var(--mut); display: block; }
        .totals { margin: 14px 0 0 auto; width: min(100%, 300px); display: grid; gap: 4px; }
        .totals div { display: flex; justify-content: space-between; gap: 18px; }
        .totals .g { font-size: 17px; font-weight: 800; border-top: 2px solid var(--ink); padding-top: 6px; margin-top: 2px; }
        .collect { margin: 16px 0; padding: 12px 16px; border: 2px dashed var(--ac); border-radius: 12px; display: flex; justify-content: space-between; align-items: center; gap: 12px; font-weight: 800; font-size: 16px; }
        .collect.paid { border-color: #14793a; color: #14793a; }
        .note { margin: 12px 0; color: var(--mut); }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 26px; }
        .sign div { border: 1px solid var(--line); border-radius: 10px; height: 90px; padding: 8px 10px; color: var(--mut); font-size: 12px; }
        footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid var(--line); text-align: center; color: var(--mut); font-size: 12px; }
        .ticket header.top { display: block; text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 8px; }
        .ticket .brand img { margin: 0 auto 4px; height: 34px; }
        .ticket .meta { text-align: center; margin-top: 6px; } .ticket .meta h1 { font-size: 15px; color: #000; }
        .ticket .cols { display: block; } .ticket .box { border: 0; border-bottom: 1px dashed #000; border-radius: 0; padding: 4px 0; }
        .ticket th { background: none; border-bottom: 1px dashed #000; } .ticket th, .ticket td { padding: 3px 0; border-bottom: 0; }
        .ticket .totals { width: 100%; } .ticket .totals .g { border-top: 1px dashed #000; font-size: 14px; }
        .ticket .collect { border: 1px dashed #000; padding: 6px; font-size: 13px; } .ticket .sign { display: none; }
        .ticket footer { border-top: 1px dashed #000; }
        @page { size: {{ $isTicket ? ($width === '58mm' ? '58mm' : '80mm').' auto' : 'A4' }}; margin: {{ $isTicket ? '2mm' : '10mm' }}; }
        @media print {
            body { background: #fff; } .bar { display: none; }
            .sheet { margin: 0; box-shadow: none; max-width: none; min-height: 0; padding: 0; }
            .ticket .sheet { padding: 0; max-width: none; }
        }
        @media (max-width: 640px) { .sheet { padding: 14px; margin: 0; } .cols { grid-template-columns: 1fr; } header.top { flex-direction: column; } .meta { text-align: left; } }
    </style>
</head>
<body class="{{ $isTicket ? 'ticket' : '' }}">
<div class="bar">
    @foreach ($switch as $tab)<a href="{{ $tab['url'] }}" @class(['on' => $tab['on']])>{{ $tab['label'] }}</a>@endforeach
    <button type="button" class="go" onclick="window.print()">Imprimer</button>
    @if ($back)<a href="{{ $back }}">Retour à la commande</a>@else<button type="button" onclick="window.close()">Fermer</button>@endif
</div>

<main class="sheet">
    <header class="top">
        <div class="brand">
            @if ($logo)<img src="{{ $logo }}" alt="">@endif
            <b>{{ $company }}</b>
            @foreach ($details as $line)<small>{{ $line }}</small>@endforeach
            @if ($shop->contact_address)<small>{{ $shop->contact_address }}</small>@endif
            <small>{{ collect([$shop->contact_phone, $shop->contact_email])->filter()->implode(' · ') }}</small>
        </div>
        <div class="meta">
            <h1>{{ $title }}</h1>
            <div class="no">N° {{ $order->number }}</div>
            <small>{{ $order->placed_at->translatedFormat('j F Y à H:i') }}</small>
            <span @class(['pill', 'ok' => $isPaid, 'due' => ! $isPaid])>{{ $order->payment_status->label() }}</span>
        </div>
    </header>

    <section class="cols">
        <div class="box">
            <h2>{{ $isDelivery ? 'Livrer à' : 'Client' }}</h2>
            <b>{{ $order->shipping_name ?? $order->customer->name }}</b><br>
            {{ $shop->phone($order->shipping_phone) }}<br>
            {{ collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ') ?: 'Adresse non renseignée' }}
        </div>
        <div class="box">
            <h2>Commande</h2>
            Paiement : <b>{{ $order->payment_method->label() }}</b><br>
            Canal : {{ $order->channel->label() }}@if ($order->notes)<br>Note : {{ $order->notes }}@endif
        </div>
    </section>

    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                @unless ($isTicket)<th>Réf.</th>@endunless
                <th class="n">Qté</th>
                @if ($showPrices)<th class="n">Prix</th>@unless ($isTicket)<th class="n">Total</th>@endunless @endif
            </tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}@if ($isTicket && $showPrices)<small>{{ $item->quantity }} × {{ $money($item->unit_price) }}</small>@endif</td>
                @unless ($isTicket)<td>{{ $item->sku }}</td>@endunless
                <td class="n">{{ $item->quantity }}</td>
                @if ($showPrices)
                    @if ($isTicket)<td class="n">{{ $money($item->line_total) }}</td>
                    @else<td class="n">{{ $money($item->unit_price) }}</td><td class="n"><b>{{ $money($item->line_total) }}</b></td>@endif
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>

    @if ($showPrices)
        <div class="totals">
            <div><span>Sous-total</span><span>{{ $money($order->subtotal) }}</span></div>
            <div><span>Livraison</span><span>{{ (float) $order->shipping_fee ? $money($order->shipping_fee) : 'Offerte' }}</span></div>
            @if ((float) $order->discount)<div><span>Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span><span>− {{ $money($order->discount) }}</span></div>@endif
            <div class="g"><span>Total</span><span>{{ $money($order->total) }}</span></div>
        </div>
    @endif

    @if ($isDelivery || $isTicket)
        <div @class(['collect', 'paid' => $isPaid])>
            @if ($isPaid)<span>Déjà réglée — rien à encaisser</span>@else<span>Montant à encaisser</span><span>{{ $money($order->total) }}</span>@endif
        </div>
    @endif

    @if ($isDelivery && trim((string) $shop->print_delivery_note) !== '')<p class="note">{{ $shop->text('print_delivery_note') }}</p>@endif

    @if ($shop->print_signatures_enabled && $isDelivery)
        <div class="sign"><div>{{ $shop->text('print_signature_left') }}</div><div>{{ $shop->text('print_signature_right') }}</div></div>
    @endif

    <footer>
        @if (trim((string) $shop->print_footer) !== '')<div>{!! nl2br(e($shop->text('print_footer'))) !!}</div>@endif
        <div>Suivi : {{ route('track') }} · n° {{ $order->number }}</div>
    </footer>
</main>

@if ($autoPrint)<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 350); });</script>@endif
</body>
</html>
