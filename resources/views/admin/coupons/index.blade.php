@extends('admin.layouts.app')
@php
    use App\CouponType;

    $crud = [
        'nameKey' => 'code',
        'addTitle' => 'Nouveau code promo',
        'editTitle' => 'Modifier le code « :name »',
        'storeUrl' => route('admin.coupons.store'),
        'updateUrl' => route('admin.coupons.update', ['coupon' => '__id__']),
        'deleteUrl' => route('admin.coupons.destroy', ['coupon' => '__id__']),
        'deleteText' => 'Les clients ne pourront plus l’utiliser. Les commandes passées gardent leur réduction.',
        'width' => 700,
        'defaults' => ['type' => 'percent', 'value' => 10, 'min_subtotal' => 0, 'is_active' => true, 'once_per_customer' => true],
        'fields' => [
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true, 'placeholder' => 'BIENVENUE10', 'help' => 'Lettres, chiffres, tirets. Le client le saisit au panier.'],
            ['name' => 'label', 'label' => 'Description interne', 'type' => 'text', 'placeholder' => 'Offre de bienvenue'],
            ['name' => 'type', 'label' => 'Type de réduction', 'type' => 'select', 'options' => $types],
            ['name' => 'value', 'label' => 'Valeur', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'help' => '% pour un pourcentage, montant pour un montant fixe. Ignoré pour la livraison offerte.'],
            ['name' => 'min_subtotal', 'label' => 'Achat minimum', 'type' => 'number', 'step' => '0.01', 'min' => 0],
            ['name' => 'max_discount', 'label' => 'Remise maximale', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'help' => 'Plafonne un pourcentage. Vide = sans plafond.'],
            ['name' => 'usage_limit', 'label' => 'Nombre d’utilisations max.', 'type' => 'number', 'min' => 1, 'help' => 'Vide = illimité.'],
            ['name' => 'starts_at', 'label' => 'Début', 'type' => 'datetime'],
            ['name' => 'ends_at', 'label' => 'Fin', 'type' => 'datetime', 'help' => 'Vide = sans date de fin.'],
            ['name' => 'once_per_customer', 'label' => 'Une seule fois par client', 'type' => 'checkbox', 'help' => 'Reconnu par son numéro de téléphone.', 'wide' => true],
            ['name' => 'auto_apply', 'label' => 'Appliquer automatiquement', 'type' => 'checkbox', 'help' => 'Promotion sans code : appliquée dès que les conditions sont remplies.', 'wide' => true],
            ['name' => 'is_active', 'label' => 'Code actif', 'type' => 'checkbox', 'wide' => true],
        ],
    ];
@endphp

@section('title', 'Codes promo')
@section('heading', 'Codes promo')
@section('subheading', 'Réductions, livraison offerte et promotions automatiques. Les roues et popups distribuent ces codes.')
@section('actions')
    <button type="button" class="btn btn-primary" data-crud-add><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Nouveau code</button>
@endsection

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="green" icon="percent" label="Codes actifs" :value="$active" />
    <x-admin.stat tone="violet" icon="cart" label="Utilisations" :value="number_format($used, 0, ',', ' ')" hint="toutes périodes" />
    <x-admin.stat tone="orange" icon="bolt" label="Promotions automatiques" :value="$coupons->where('auto_apply', true)->where('is_active', true)->count()" />
</section>

<section class="card" data-crud>
    <script type="application/json" data-crud-config>{!! json_encode($crud, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Code</th><th data-sort>Avantage</th><th>Conditions</th><th data-sort>Utilisation</th><th>Validité</th><th data-filter>État</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($coupons as $coupon)
                @php
                    $record = array_merge($coupon->only(['id', 'code', 'label', 'value', 'min_subtotal', 'max_discount', 'usage_limit', 'once_per_customer', 'auto_apply', 'is_active']), [
                        'type' => $coupon->type->value,
                        'starts_at' => $coupon->starts_at?->format('Y-m-d\TH:i'),
                        'ends_at' => $coupon->ends_at?->format('Y-m-d\TH:i'),
                    ]);
                    $expired = $coupon->ends_at && $coupon->ends_at->isPast();
                    $exhausted = $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit;
                    $percentUsed = $coupon->usage_limit ? min(100, round($coupon->used_count / $coupon->usage_limit * 100)) : null;
                @endphp
                <tr data-record='@json($record)' class="is-clickable">
                    <td>
                        <button type="button" class="code-chip" data-copy="{{ $coupon->code }}" title="Copier le code"><x-admin.icon name="copy" :size="14" /> {{ $coupon->code }}</button>
                        @if ($coupon->label)<small>{{ $coupon->label }}</small>@endif
                    </td>
                    <td><b class="strong">{{ $coupon->benefit() }}</b>@if ($coupon->auto_apply)<small><x-admin.badge tone="orange">Automatique</x-admin.badge></small>@endif</td>
                    <td>
                        <small style="display:block;color:inherit">{{ (float) $coupon->min_subtotal > 0 ? 'Dès '.\App\Support\Money::format($coupon->min_subtotal) : 'Sans minimum' }}</small>
                        <small>{{ $coupon->once_per_customer ? '1 fois par client' : 'Réutilisable' }}@if ($coupon->max_discount) · max {{ \App\Support\Money::format($coupon->max_discount) }}@endif</small>
                    </td>
                    <td class="usage">
                        <b>{{ $coupon->used_count }}</b> <span class="muted">/ {{ $coupon->usage_limit ?? '∞' }}</span>
                        @if ($percentUsed !== null)<div class="bar" style="--tone: var(--{{ $percentUsed >= 90 ? 'red' : 'green' }})"><i style="width: {{ $percentUsed }}%"></i></div>@endif
                    </td>
                    <td class="nowrap">
                        @if ($coupon->starts_at || $coupon->ends_at)
                            <small style="display:block;color:inherit">{{ $coupon->starts_at ? 'Du '.$coupon->starts_at->translatedFormat('j M Y') : 'Dès maintenant' }}</small>
                            <small>{{ $coupon->ends_at ? 'au '.$coupon->ends_at->translatedFormat('j M Y') : 'sans fin' }}</small>
                        @else<span class="muted">Permanent</span>@endif
                    </td>
                    <td>
                        @if (! $coupon->is_active)<x-admin.badge tone="slate">Désactivé</x-admin.badge>
                        @elseif ($expired)<x-admin.badge tone="red">Expiré</x-admin.badge>
                        @elseif ($exhausted)<x-admin.badge tone="amber">Épuisé</x-admin.badge>
                        @else<x-admin.badge tone="green">Actif</x-admin.badge>@endif
                    </td>
                    <td class="actions">
                        <button type="button" class="icon-btn" data-crud-edit data-record='@json($record)' aria-label="Modifier {{ $coupon->code }}"><x-admin.icon name="edit" :size="17" /></button>
                        <button type="button" class="icon-btn" data-crud-delete data-record='@json($record)' aria-label="Supprimer {{ $coupon->code }}"><x-admin.icon name="trash" :size="17" /></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><span><x-admin.icon name="percent" :size="26" :stroke="1.6" /></span><b>Aucun code promo</b><p>Créez un code de bienvenue, une livraison offerte ou une promotion automatique.</p><button type="button" class="btn btn-primary btn-sm" data-crud-add><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Créer un code</button></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
