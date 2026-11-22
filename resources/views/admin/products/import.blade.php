@extends('admin.layouts.app')

@section('title', 'Importer des produits')
@section('heading', 'Importer des produits')
@section('subheading', 'Mettez à jour votre catalogue depuis un fichier Excel : créations, prix, stocks et variantes.')
@section('actions')
    <a href="{{ route('admin.products.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Produits</a>
@endsection

@section('content')
<div class="grid-side">
    <div class="stack">
        @if ($report)
            @php
                $errors_ = collect($report['errors']);
                $hasErrors = $errors_->isNotEmpty();
            @endphp
            <section class="stat-grid">
                <x-admin.stat tone="green" icon="plus" label="À créer" :value="$report['counts']['create']" />
                <x-admin.stat tone="blue" icon="edit" label="À mettre à jour" :value="$report['counts']['update']" />
                <x-admin.stat tone="violet" icon="box" label="Variantes" :value="$report['counts']['variants']" />
                <x-admin.stat :tone="$hasErrors ? 'red' : 'slate'" icon="alert" label="Erreurs" :value="$errors_->count()" />
            </section>

            @if ($hasErrors)
                <section class="card">
                    <header class="card-head"><h2>À corriger dans le fichier</h2></header>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Feuille</th><th class="num">Ligne</th><th>Problème</th></tr></thead>
                            <tbody>
                                @foreach ($errors_->take(100) as $error)
                                    <tr><td>{{ $error['sheet'] ?: '—' }}</td><td class="num">{{ $error['row'] ?: '—' }}</td><td>{{ $error['message'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($errors_->count() > 100)<p class="muted" style="margin-top: 10px">… et {{ $errors_->count() - 100 }} autre(s) erreur(s).</p>@endif
                </section>
            @endif

            <section class="card">
                <header class="card-head"><h2>Aperçu</h2></header>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Ligne</th><th>Action</th><th>Référence</th><th>Nom</th><th>Catégorie</th><th class="num">Prix</th><th class="num">Variantes</th></tr></thead>
                        <tbody>
                            @foreach (collect($report['products'])->take(60) as $entry)
                                <tr>
                                    <td>{{ $entry['row'] ?: '—' }}</td>
                                    <td>@if (! $entry['valid'])<x-admin.badge tone="red">Erreur</x-admin.badge>@elseif ($entry['action'] === 'create')<x-admin.badge tone="green">Création</x-admin.badge>@else<x-admin.badge tone="blue">Mise à jour</x-admin.badge>@endif</td>
                                    <td>{{ $entry['cells']['sku'] ?? '' }}</td>
                                    <td>{{ $entry['cells']['name'] ?? '—' }}</td>
                                    <td>{{ $entry['cells']['category'] ?? '—' }}</td>
                                    <td class="num">{{ $entry['cells']['price'] ?? '—' }}</td>
                                    <td class="num">{{ count($entry['variants']) ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($report['products']) > 60)<p class="muted" style="margin-top: 10px">… et {{ count($report['products']) - 60 }} autre(s) ligne(s).</p>@endif
            </section>

            <form method="POST" action="{{ route('admin.products.import.store') }}" class="form-actions"
                  @unless ($hasErrors) data-confirm-title="Importer ce fichier ?" data-confirm-button="Oui, importer" data-confirm="Appliquer l’import : {{ $report['counts']['create'] }} création(s) et {{ $report['counts']['update'] }} mise(s) à jour ? Les stocks modifiés seront tracés dans l’écran Stock." @endunless>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <a href="{{ route('admin.products.import') }}" class="btn">Annuler</a>
                <button class="btn btn-primary" @disabled($hasErrors)>Confirmer l’import</button>
            </form>
        @endif
    </div>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>1. Récupérer le modèle</h2></header>
            <p class="muted" style="margin: -6px 0 14px">Le modèle contient les colonnes attendues, des exemples et une feuille d’aide. Vous pouvez aussi exporter votre catalogue actuel, le modifier puis le réimporter.</p>
            <div class="form">
                <a href="{{ route('admin.products.template') }}" class="btn block"><x-admin.icon name="copy" :size="17" /> Télécharger le modèle (.xlsx)</a>
                <a href="{{ route('admin.products.export') }}" class="btn block"><x-admin.icon name="copy" :size="17" /> Exporter le catalogue actuel</a>
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>2. Envoyer votre fichier</h2></header>
            <form method="POST" action="{{ route('admin.products.import.preview') }}" enctype="multipart/form-data" class="form">
                @csrf
                <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required aria-label="Fichier Excel">
                @error('file')<small class="fe">{{ $message }}</small>@enderror
                <small class="muted">.xlsx · 5 Mo et 5 000 lignes au maximum. Rien n’est enregistré avant votre confirmation.</small>
                <button class="btn btn-primary block">Analyser le fichier</button>
            </form>
        </section>
    </div>
</div>
@endsection
