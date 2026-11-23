@extends('admin.layouts.app')
@php
    $crud = [
        'nameKey' => 'name',
        'addTitle' => 'Nouvel utilisateur',
        'editTitle' => 'Modifier « :name »',
        'storeUrl' => route('admin.users.store'),
        'updateUrl' => route('admin.users.update', ['user' => '__id__']),
        'deleteUrl' => route('admin.users.destroy', ['user' => '__id__']),
        'deleteText' => 'Le compte et son accès seront supprimés. Un utilisateur qui a des commandes ne peut pas être supprimé : désactivez-le.',
        'width' => 700,
        'defaults' => ['role' => 'customer', 'is_active' => true],
        'fields' => [
            ['name' => 'name', 'label' => 'Nom complet', 'type' => 'text', 'required' => true],
            ['name' => 'role', 'label' => 'Rôle', 'type' => 'select', 'options' => $roles, 'help' => 'Administrateur : tout. Gestionnaire : l’administration sans les paramètres ni les utilisateurs. Client : boutique uniquement.'],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'help' => 'Obligatoire pour un administrateur ou un gestionnaire (identifiant de connexion).'],
            ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'text', 'placeholder' => '+2250700000000', 'help' => 'Obligatoire pour un client. Format international.'],
            ['name' => 'password', 'label' => 'Mot de passe', 'type' => 'password', 'help' => '8 caractères minimum. Laissez vide pour conserver le mot de passe actuel.'],
            ['name' => 'city', 'label' => 'Ville (profil)', 'type' => 'text'],
            ['name' => 'address', 'label' => 'Adresse (profil)', 'type' => 'textarea', 'wide' => true],
            ['name' => 'is_active', 'label' => 'Compte actif', 'type' => 'checkbox', 'help' => 'Un compte désactivé ne peut plus se connecter.', 'wide' => true],
        ],
    ];
    $tones = ['admin' => 'violet', 'manager' => 'blue', 'customer' => 'slate'];
@endphp

@section('title', 'Utilisateurs')
@section('heading', 'Utilisateurs')
@section('subheading', 'Créez et gérez les comptes : administrateurs, gestionnaires et clients, avec leur profil.')
@section('actions')
    <button type="button" class="btn btn-primary" data-crud-add><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Nouvel utilisateur</button>
@endsection

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="violet" icon="users" label="Administrateurs" :value="$counts['admin'] ?? 0" />
    <x-admin.stat tone="blue" icon="users" label="Gestionnaires" :value="$counts['manager'] ?? 0" />
    <x-admin.stat tone="green" icon="users-plus" label="Clients" :value="$counts['customer'] ?? 0" />
</section>

<section class="card" data-crud>
    <script type="application/json" data-crud-config>{!! json_encode($crud, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <form method="GET" class="toolbar" style="margin-bottom: 18px">
        <label class="search grow">
            <x-admin.icon name="search" :size="18" />
            <span class="sr-only">Rechercher</span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, e-mail ou téléphone" autocomplete="off">
        </label>
        <select name="role" aria-label="Rôle">
            <option value="">Tous les rôles</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn">Filtrer</button>
    </form>
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Nom</th><th>Contact</th><th data-filter>Rôle</th><th>Profil</th><th data-sort>Commandes</th><th data-sort>Dernière connexion</th><th data-filter>État</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($users as $item)
                @php
                    $profile = $profiles[$item->id] ?? null;
                    $record = [
                        'id' => $item->id, 'name' => $item->name, 'email' => $item->email, 'phone' => $item->phone,
                        'role' => $item->role->value, 'is_active' => $item->is_active,
                        'city' => $profile?->city, 'address' => $profile?->address,
                    ];
                @endphp
                <tr data-record='@json($record)' class="is-clickable">
                    <td><b class="strong">{{ $item->name }}</b>@if ($item->is(auth()->user()))<small>Vous</small>@endif</td>
                    <td><small style="display:block;color:inherit">{{ $item->email ?? '—' }}</small><small>{{ $item->phone ?? '—' }}</small></td>
                    <td><x-admin.badge :tone="$tones[$item->role->value]">{{ $item->role->label() }}</x-admin.badge></td>
                    <td><small style="color:inherit">{{ collect([$profile?->city, $profile?->address])->filter()->implode(' · ') ?: '—' }}</small></td>
                    <td>{{ $item->orders_count }}</td>
                    <td class="nowrap">{{ $item->last_login_at?->translatedFormat('j M Y, H:i') ?? 'Jamais' }}</td>
                    <td>@if ($item->is_active)<x-admin.badge tone="green">Actif</x-admin.badge>@else<x-admin.badge tone="slate">Désactivé</x-admin.badge>@endif</td>
                    <td class="actions">
                        <button type="button" class="icon-btn" data-crud-edit data-record='@json($record)' aria-label="Modifier {{ $item->name }}"><x-admin.icon name="edit" :size="17" /></button>
                        @unless ($item->is(auth()->user()))
                            <button type="button" class="icon-btn" data-crud-delete data-record='@json($record)' aria-label="Supprimer {{ $item->name }}"><x-admin.icon name="trash" :size="17" /></button>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><span><x-admin.icon name="users" :size="26" :stroke="1.6" /></span><b>Aucun utilisateur</b><p>Aucun compte ne correspond à cette recherche.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
