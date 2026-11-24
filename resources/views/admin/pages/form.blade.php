@extends('admin.layouts.app')
@php
    use App\PageType;
    use App\Support\SettingsSchema;
    $isNew = ! $page->exists;
    $isArticle = $page->type === PageType::Article;
@endphp

@section('title', $isNew ? 'Nouveau contenu' : $page->title)
@section('heading', $isNew ? ($isArticle ? 'Nouvel article' : 'Nouvelle page') : 'Modifier « '.$page->title.' »')
@section('actions')
    <a href="{{ route('admin.pages.index', ['type' => $page->type->value]) }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> {{ $page->type->pluralLabel() }}</a>
    @unless ($isNew)<a href="{{ $page->url() }}" target="_blank" rel="noopener" class="btn"><x-admin.icon name="eye" :size="18" /> Voir</a>@endunless
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-editor.js') }}?v={{ filemtime(public_path('js/admin-editor.js')) }}" defer></script>
    <script src="{{ asset('js/admin-writer.js') }}?v={{ filemtime(public_path('js/admin-writer.js')) }}" defer></script>
    <script>window.MK_MEDIA = { browse: @json(route('admin.media.browse')) };</script>
    <script src="{{ asset('js/admin-media.js') }}?v={{ filemtime(public_path('js/admin-media.js')) }}" defer></script>
@endpush

@section('content')
<form method="POST" enctype="multipart/form-data" class="grid-side" action="{{ $isNew ? route('admin.pages.store') : route('admin.pages.update', $page) }}">
    @csrf
    @unless ($isNew) @method('PUT') @endunless
    <input type="hidden" name="type" value="{{ $page->type->value }}">

    <section class="card">
        <div class="form">
            <label class="field"><span>Titre</span><input type="text" name="title" value="{{ old('title', $page->title) }}" required @error('title') aria-invalid="true" @enderror></label>
            <label class="field"><span>Adresse <em>(slug)</em></span><input type="text" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="générée depuis le titre" @error('slug') aria-invalid="true" @enderror>
                <small>{{ $isArticle ? url('/blog') : url('/page') }}/<b>{{ $page->slug ?: '…' }}</b></small></label>
            <label class="field"><span>Résumé <em>(facultatif)</em></span><textarea name="excerpt" rows="2">{{ old('excerpt', $page->excerpt) }}</textarea></label>
            @php
                $writerOn = \App\Support\Content\DescriptionWriter::enabled() && $isArticle;
                $writerConfig = ['type' => 'article', 'url' => route('admin.content.generate'), 'name' => '[name=title]', 'tag' => '[name=tag]', 'excerpt' => '[name=excerpt]',
                    'auto' => $writerOn && (bool) \App\Models\Setting::get('writer_auto'), 'ignoreId' => $page->exists ? $page->id : null];
            @endphp
            <div class="field" @if ($writerOn) data-writer="{{ json_encode($writerConfig) }}" @endif>
                <div class="rte-gen"><span>Contenu</span>
                    @if ($writerOn)<button type="button" class="btn btn-sm" data-writer-run title="Rédige un article complet à partir du titre et de la rubrique"><x-admin.icon name="sparkles" :size="16" /> Rédiger l’article</button>@endif
                </div>
                <textarea name="body" rows="18" data-rich placeholder="Écrivez votre contenu…">{{ old('body', $page->editorBody()) }}</textarea>
                @error('body')<small class="err">{{ $message }}</small>@enderror
                <small>Valeurs automatiques à écrire dans le texte : <code>{shop_name}</code>, <code>{return_days}</code>, <code>{free_shipping_threshold}</code>, <code>{contact_email}</code>…</small>
            </div>
        </div>
    </section>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Publication</h2></header>
            <div class="form">
                <input type="hidden" name="is_published" value="0">
                <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> Publié</label>
                <label class="field"><span>Date de publication</span><input type="datetime-local" name="published_at" value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}"></label>
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>Présentation</h2></header>
            <div class="form">
                @if ($isArticle)
                    <label class="field"><span>Rubrique</span><input type="text" name="tag" value="{{ old('tag', $page->tag) }}" maxlength="60" placeholder="Entretien, Soins…"></label>
                @endif
                <div class="preview" id="coverPreview" style="aspect-ratio: 16/9">
                    @if ($page->cover)<img src="{{ \App\Models\Setting::imageUrl($page->cover) }}" alt="">@else<span class="ph"><x-admin.icon name="image" :size="30" :stroke="1.4" /><br>Image de couverture</span>@endif
                </div>
                <input type="file" name="cover_file" accept="image/*" data-image-input="#coverPreview" aria-label="Image de couverture">
                @if ($page->cover)<label class="check"><input type="checkbox" name="remove_cover" value="1"> Retirer l’image</label>@endif
                <div class="field"><span>Couleur de fond (sans image)</span>
                    <div class="color-field" data-color><input type="color" value="{{ old('cover_color', $page->cover_color ?? '#e6f3e1') }}" aria-label="Couleur"><input type="text" name="cover_color" value="{{ old('cover_color', $page->cover_color) }}" pattern="#[0-9a-fA-F]{6}"></div>
                </div>
                <label class="field"><span>Icône (sans image)</span>
                    <select name="icon">
                        <option value="">—</option>
                        @foreach (SettingsSchema::ICONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('icon', $page->icon) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn btn-primary">{{ $isNew ? 'Créer' : 'Enregistrer' }}</button>
        </div>
    </div>
</form>
@endsection
