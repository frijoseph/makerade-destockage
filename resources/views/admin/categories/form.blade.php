@extends('admin.layouts.app')
@php $isNew = ! $category->exists; @endphp

@section('title', $isNew ? 'Nouvelle catégorie' : $category->name)
@section('heading', $isNew ? 'Nouvelle catégorie' : 'Modifier « '.$category->name.' »')
@section('actions')
    <a href="{{ route('admin.categories.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Catégories</a>
@endsection

@section('content')
<section class="card" style="max-width: 760px">
    <form method="POST" action="{{ $isNew ? route('admin.categories.store') : route('admin.categories.update', $category) }}" class="form">
        @csrf
        @unless ($isNew) @method('PUT') @endunless
        <div class="form-grid">
            <label class="field"><span>Nom</span><input type="text" name="name" value="{{ old('name', $category->name) }}" required maxlength="255" @error('name') aria-invalid="true" @enderror></label>
            <label class="field"><span>Identifiant d’URL <em>(slug)</em></span><input type="text" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="généré depuis le nom" @error('slug') aria-invalid="true" @enderror></label>
            <div class="field"><span>Couleur de fond</span>
                <div class="color-field" data-color><input type="color" value="{{ old('bg', $category->bg) }}" aria-label="Couleur de fond"><input type="text" name="bg" value="{{ old('bg', $category->bg) }}" required pattern="#[0-9a-fA-F]{6}"></div>
            </div>
            <label class="field"><span>Ordre d’affichage</span><input type="number" name="position" value="{{ old('position', $category->position) }}" required min="0"></label>
            <label class="field span-2"><span>Description <em>(facultatif)</em></span><textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea></label>
        </div>
        <input type="hidden" name="is_active" value="0">
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Visible sur la boutique</label>
        <div class="form-actions">
            <a href="{{ route('admin.categories.index') }}" class="btn">Annuler</a>
            <button class="btn btn-primary">{{ $isNew ? 'Créer la catégorie' : 'Enregistrer' }}</button>
        </div>
    </form>
</section>
@endsection
