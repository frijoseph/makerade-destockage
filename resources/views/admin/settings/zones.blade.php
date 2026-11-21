@php
    use App\Support\Money;

    $defaultFee = (float) \App\Models\Setting::get('shipping_fee');
    $defaultFree = (float) \App\Models\Setting::get('free_shipping_threshold');
    $crud = [
        'nameKey' => 'name',
        'addTitle' => 'Ajouter une ville ou une zone',
        'editTitle' => 'Modifier « :name »',
        'storeUrl' => route('admin.zones.store'),
        'updateUrl' => route('admin.zones.update', ['zone' => '__id__']),
        'deleteUrl' => route('admin.zones.destroy', ['zone' => '__id__']),
        'deleteText' => 'Les clients ne verront plus cette ville dans la liste de livraison.',
        'defaults' => ['is_active' => true],
        'fields' => [
            ['name' => 'name', 'label' => 'Ville ou zone', 'type' => 'text', 'required' => true, 'wide' => true, 'placeholder' => 'Ex. Abidjan – Cocody'],
            ['name' => 'fee', 'label' => 'Frais de livraison', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'help' => 'Vide = frais par défaut ('.Money::format($defaultFee).')'],
            ['name' => 'free_threshold', 'label' => 'Livraison offerte dès', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'help' => 'Vide = seuil par défaut. 0 = jamais offerte.'],
            ['name' => 'delay', 'label' => 'Délai annoncé', 'type' => 'text', 'placeholder' => 'Ex. 24 h, 2 à 3 jours'],
            ['name' => 'position', 'label' => 'Ordre d’affichage', 'type' => 'number', 'min' => 0],
            ['name' => 'is_active', 'label' => 'Zone desservie', 'type' => 'checkbox', 'help' => 'Décochée, la ville n’est plus proposée.', 'wide' => true],
        ],
    ];
@endphp

<section class="card" data-crud>
    <script type="application/json" data-crud-config>{!! json_encode($crud, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <header class="card-head">
        <div>
            <h2>Zones de livraison</h2>
            <p class="muted" style="font-size: 13px; margin-top: 2px">Un tarif et un délai par ville. Une ville absente du tableau utilise les valeurs par défaut ci-dessous.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-crud-add><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Ajouter une ville</button>
    </header>
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Ville / zone</th><th class="num" data-sort>Frais</th><th class="num">Offerte dès</th><th data-filter>Délai</th><th data-filter>État</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($zones as $zone)
                @php $record = $zone->only(['id', 'name', 'fee', 'free_threshold', 'delay', 'position', 'is_active']); @endphp
                <tr data-record='@json($record)' class="is-clickable">
                    <td><span class="cell-ic"><x-admin.icon name="pin" :size="16" /></span><b class="strong">{{ $zone->name }}</b></td>
                    <td class="num">@if ($zone->fee !== null){{ Money::format($zone->fee) }}@else<span class="muted">Par défaut</span>@endif</td>
                    <td class="num">@if ($zone->free_threshold !== null){{ (float) $zone->free_threshold > 0 ? Money::format($zone->free_threshold) : 'Jamais' }}@else<span class="muted">Par défaut</span>@endif</td>
                    <td>{{ $zone->delay ?: '—' }}</td>
                    <td>@if ($zone->is_active)<x-admin.badge tone="green">Desservie</x-admin.badge>@else<x-admin.badge tone="slate">Désactivée</x-admin.badge>@endif</td>
                    <td class="actions">
                        <button type="button" class="icon-btn" data-crud-edit data-record='@json($record)' aria-label="Modifier {{ $zone->name }}"><x-admin.icon name="edit" :size="17" /></button>
                        <button type="button" class="icon-btn" data-crud-delete data-record='@json($record)' aria-label="Supprimer {{ $zone->name }}"><x-admin.icon name="trash" :size="17" /></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><span><x-admin.icon name="pin" :size="26" :stroke="1.6" /></span><b>Aucune zone pour l’instant</b><p>Ajoutez vos villes pour fixer un tarif et un délai par destination.</p><button type="button" class="btn btn-primary btn-sm" data-crud-add><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Ajouter une ville</button></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
