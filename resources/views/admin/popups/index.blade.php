@extends('admin.layouts.app')
@php use App\Models\Popup; @endphp

@section('title', 'Popups & roue')
@section('heading', 'Popups & roue de la chance')
@section('subheading', 'Messages promotionnels, inscriptions newsletter et jeux, affichés sur la boutique selon vos règles.')
@section('actions')
    <a href="{{ route('admin.popups.create') }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Nouveau popup</a>
@endsection

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="green" icon="gift" label="Popups actifs" :value="$popups->where('is_active', true)->count()" />
    <x-admin.stat tone="violet" icon="eye" label="Affichages" :value="number_format($popups->sum('impressions'), 0, ',', ' ')" />
    <x-admin.stat tone="orange" icon="target" label="Clics" :value="number_format($popups->sum('clicks'), 0, ',', ' ')" :hint="$popups->sum('impressions') ? round($popups->sum('clicks') / max(1, $popups->sum('impressions')) * 100).' % de conversion' : null" />
    <x-admin.stat tone="pink" icon="sparkles" label="Tours de roue" :value="number_format($spinCount, 0, ',', ' ')" />
</section>

<section class="card">
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Popup</th><th data-filter>Type</th><th>Déclenchement</th><th>Pages</th><th class="num" data-sort>Affichages</th><th class="num" data-sort>Clics</th><th data-filter>État</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($popups as $popup)
                @php
                    $live = $popup->is_active && (! $popup->ends_at || $popup->ends_at->isFuture()) && (! $popup->starts_at || $popup->starts_at->isPast());
                    $pages = (array) $popup->pages;
                @endphp
                <tr data-href="{{ route('admin.popups.edit', $popup) }}">
                    <td><a href="{{ route('admin.popups.edit', $popup) }}" class="strong">{{ $popup->name }}</a><small>{{ \Illuminate\Support\Str::limit($popup->title, 56) }}</small></td>
                    <td><x-admin.badge :tone="['promo' => 'green', 'newsletter' => 'blue', 'wheel' => 'pink'][$popup->kind] ?? 'slate'">{{ Popup::KINDS[$popup->kind] ?? $popup->kind }}</x-admin.badge></td>
                    <td>
                        {{ ['delay' => 'Après '.$popup->trigger_value.' s', 'scroll' => 'À '.$popup->trigger_value.' % de scroll', 'exit' => 'À la sortie'][$popup->trigger] ?? $popup->trigger }}
                        <small>{{ Popup::FREQUENCIES[$popup->frequency] ?? '' }}</small>
                    </td>
                    <td>{{ $pages === [] || in_array('all', $pages, true) ? 'Toutes' : collect($pages)->map(fn ($p) => Popup::PAGES[$p] ?? $p)->implode(', ') }}<small>{{ ['all' => 'Mobile et ordinateur', 'mobile' => 'Mobile seulement', 'desktop' => 'Ordinateur seulement'][$popup->devices] ?? '' }}</small></td>
                    <td class="num">{{ number_format($popup->impressions, 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($popup->clicks, 0, ',', ' ') }}@if ($popup->impressions)<small>{{ round($popup->clicks / $popup->impressions * 100) }} %</small>@endif @if ($popup->kind === 'wheel')<small>{{ $popup->spins_count }} tour(s)</small>@endif</td>
                    <td>@if ($live)<x-admin.badge tone="green">En ligne</x-admin.badge>@elseif ($popup->is_active)<x-admin.badge tone="amber">Programmé / expiré</x-admin.badge>@else<x-admin.badge tone="slate">Désactivé</x-admin.badge>@endif</td>
                    <td class="actions">
                        <a href="{{ route('admin.popups.edit', $popup) }}" class="icon-btn" aria-label="Modifier {{ $popup->name }}"><x-admin.icon name="edit" :size="17" /></a>
                        <form method="POST" action="{{ route('admin.popups.destroy', $popup) }}" data-confirm="« {{ $popup->name }} » ne sera plus affiché sur la boutique.">
                            @csrf @method('DELETE')
                            <button class="icon-btn" aria-label="Supprimer {{ $popup->name }}"><x-admin.icon name="trash" :size="17" /></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><span><x-admin.icon name="gift" :size="26" :stroke="1.6" /></span><b>Aucun popup</b><p>Créez une roue de la chance, un popup de bienvenue ou une annonce promotionnelle.</p><a href="{{ route('admin.popups.create') }}" class="btn btn-primary btn-sm"><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Créer un popup</a></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="muted" style="font-size: 12.5px; margin-top: 14px">Un seul popup s’affiche par page : celui de plus forte priorité qui respecte ses règles (page, appareil, fréquence, dates).</p>
</section>
@endsection
