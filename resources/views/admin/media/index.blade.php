@extends('admin.layouts.app')
@php
    $bytes = fn (int $value): string => \App\Http\Controllers\Admin\MediaController::bytes($value);
    $title = match (true) {
        $folder !== null => $folder->name,
        $scope === 'none' => 'Sans dossier',
        default => 'Tous les fichiers',
    };
    $folderParam = $folder?->id ?? ($scope === 'none' ? 'none' : null);
    $urls = [
        'folders' => route('admin.media.folders.store'),
        'folder' => route('admin.media.folders.update', ['folder' => '__id__']),
        'upload' => route('admin.media.upload'),
        'file' => route('admin.media.files.update', ['file' => '__id__']),
        'optimize' => route('admin.media.optimize'),
        'index' => route('admin.media.index'),
    ];
@endphp

@section('title', 'Médiathèque')
@section('heading', 'Médiathèque')
@section('subheading', 'Vos images et documents, rangés par dossier et compressés automatiquement.')
@section('actions')
    <button type="button" class="btn" data-media-new-folder><x-admin.icon name="folder" :size="18" /> Nouveau dossier</button>
    <button type="button" class="btn btn-primary" data-media-upload-btn><x-admin.icon name="upload" :size="18" :stroke="2.2" /> Ajouter des fichiers</button>
@endsection

@section('content')
<div class="mm" data-media
     data-folder="{{ $folder?->id }}"
     data-urls="{{ json_encode($urls) }}"
     data-max-mb="{{ $maxMb }}"
     data-compression="{{ json_encode($compression) }}">

    <section class="stat-grid">
        <x-admin.stat tone="violet" icon="image" label="Fichiers" :value="$totals['files']" />
        <x-admin.stat tone="blue" icon="stock" label="Espace utilisé" :value="$bytes($totals['bytes'])" />
        <x-admin.stat tone="green" icon="bolt" label="Économisé par la compression" :value="$bytes($totals['saved'])" hint="images réduites à l’envoi" />
    </section>

    <div class="mm-layout">
        <aside class="card mm-folders" aria-label="Dossiers">
            <nav>
                <a href="{{ route('admin.media.index') }}" @class(['is-active' => $scope === 'all' || (! $folder && $scope !== 'none')])><x-admin.icon name="image" :size="18" /><span>Tous les fichiers</span><em>{{ $folders['all'] }}</em></a>
                <a href="{{ route('admin.media.index', ['folder' => 'none']) }}" @class(['is-active' => $scope === 'none'])><x-admin.icon name="folder" :size="18" /><span>Sans dossier</span><em>{{ $folders['none'] }}</em></a>
                @if ($folders['folders'])<p class="nav-title">Dossiers</p>@endif
                @foreach ($folders['folders'] as $item)
                    <a href="{{ route('admin.media.index', ['folder' => $item['id']]) }}" @class(['is-active' => $folder?->id === $item['id']])><x-admin.icon name="folder" :size="18" /><span>{{ $item['name'] }}</span><em>{{ $item['count'] }}</em></a>
                @endforeach
            </nav>
            <button type="button" class="btn block" data-media-new-folder><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Nouveau dossier</button>
        </aside>

        <section class="card mm-main">
            <header class="mm-head">
                <div>
                    <h2>{{ $title }}</h2>
                    <small class="muted">{{ $files->total() }} fichier(s)@if ($folder && $folder->product_id) · dossier d’un produit @endif</small>
                </div>
                <div class="mm-tools">
                    <form method="GET" class="search" role="search">
                        @if ($folderParam)<input type="hidden" name="folder" value="{{ $folderParam }}">@endif
                        <x-admin.icon name="search" :size="18" /><span class="sr-only">Rechercher</span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom du fichier" autocomplete="off">
                    </form>
                    <button type="button" class="btn" data-media-optimize data-folder="{{ $folderParam }}" title="Recompresse les images avec les réglages actuels"><x-admin.icon name="bolt" :size="17" /> Optimiser</button>
                    @if ($folder || $scope === 'none')
                        <a class="btn" href="{{ route('admin.media.zip', ['folder' => $folder?->id ?? 'none']) }}"><x-admin.icon name="download" :size="17" /> ZIP</a>
                    @endif
                    @if ($folder)
                        <button type="button" class="btn" data-media-rename-folder data-id="{{ $folder->id }}" data-name="{{ $folder->name }}"><x-admin.icon name="edit" :size="17" /> Renommer</button>
                        <button type="button" class="btn btn-danger-ghost" data-media-delete-folder data-id="{{ $folder->id }}" data-name="{{ $folder->name }}" data-count="{{ $files->total() }}"><x-admin.icon name="trash" :size="17" /> Supprimer</button>
                    @endif
                </div>
            </header>

            <div class="mm-drop" data-media-drop tabindex="0" role="button" aria-label="Déposer des fichiers ou cliquer pour en choisir">
                <x-admin.icon name="upload" :size="26" :stroke="1.8" />
                <b>Glissez vos fichiers ici</b>
                <span class="muted">ou cliquez pour parcourir · {{ $maxMb }} Mo max. par fichier · images compressées automatiquement</span>
                <input type="file" multiple accept="{{ $accept }}" hidden data-media-input>
            </div>

            @if ($files->isEmpty())
                <p class="mm-empty muted">Aucun fichier ici pour le moment.</p>
            @else
                <ul class="mm-grid">
                    @foreach ($files as $file)
                        @php $used = $usage[$file->path] ?? 0; @endphp
                        <li class="mm-card" data-file="{{ json_encode(['id' => $file->id, 'name' => $file->name, 'url' => $file->url, 'folder' => $file->media_folder_id, 'used' => $used]) }}">
                            <a href="{{ $file->url }}" target="_blank" rel="noopener" class="mm-thumb" aria-label="Ouvrir {{ $file->name }}">
                                @if ($file->isImage())<img src="{{ $file->url }}" alt="" loading="lazy">@else<span class="mm-doc"><x-admin.icon name="folder" :size="30" :stroke="1.4" /><b>{{ strtoupper(pathinfo($file->name, PATHINFO_EXTENSION)) }}</b></span>@endif
                            </a>
                            <div class="mm-info">
                                <b title="{{ $file->name }}">{{ $file->name }}</b>
                                <small class="muted">{{ $bytes($file->size) }}@if ($file->width) · {{ $file->width }}×{{ $file->height }}@endif</small>
                                @if ($file->savedBytes() > 0)<small class="mm-saved">−{{ round($file->savedBytes() / max(1, $file->original_size) * 100) }} % compressé</small>@endif
                                @if ($used)<small class="mm-used">Utilisé par {{ $used }} produit(s)</small>@endif
                            </div>
                            <div class="mm-actions">
                                <button type="button" class="icon-btn" data-media-copy aria-label="Copier le lien" title="Copier le lien"><x-admin.icon name="copy" :size="16" /></button>
                                <button type="button" class="icon-btn" data-media-rename aria-label="Renommer" title="Renommer"><x-admin.icon name="edit" :size="16" /></button>
                                <button type="button" class="icon-btn" data-media-move aria-label="Déplacer" title="Déplacer vers un dossier"><x-admin.icon name="folder" :size="16" /></button>
                                <button type="button" class="icon-btn" data-media-delete aria-label="Supprimer" title="Supprimer"><x-admin.icon name="trash" :size="16" /></button>
                            </div>
                        </li>
                    @endforeach
                </ul>
                {{ $files->links() }}
            @endif
            <p class="muted mm-foot">Réglages de compression (taille maximale, qualité, WebP) : <a href="{{ route('admin.settings.edit', 'media') }}">Paramètres › Médiathèque</a>.</p>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-media.js') }}?v={{ filemtime(public_path('js/admin-media.js')) }}" defer></script>
@endpush
