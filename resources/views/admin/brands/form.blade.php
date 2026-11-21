@extends('admin.layouts.app')
@php
    use App\Models\Brand;
    $isNew = ! $brand->exists;
    $styles = ['plain' => 'Simple', 'pill' => 'Pastille', 'script' => 'Manuscrit', 'swash' => 'Italique', 'serif' => 'Serif', 'circle' => 'Cercle'];
@endphp

@section('title', $isNew ? 'Nouvelle marque' : $brand->name)
@section('heading', $isNew ? 'Nouvelle marque' : 'Modifier « '.$brand->name.' »')
@section('actions')
    <a href="{{ route('admin.brands.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Marques</a>
@endsection

@section('content')
<section class="card" style="max-width: 760px">
    <form method="POST" action="{{ $isNew ? route('admin.brands.store') : route('admin.brands.update', $brand) }}" class="form">
        @csrf
        @unless ($isNew) @method('PUT') @endunless
        <div class="form-grid">
            <label class="field span-2"><span>Nom</span><input type="text" name="name" value="{{ old('name', $brand->name) }}" required maxlength="255" @error('name') aria-invalid="true" @enderror></label>
            <div class="field"><span>Couleur</span>
                <div class="color-field" data-color><input type="color" value="{{ old('color', $brand->color) }}" aria-label="Couleur"><input type="text" name="color" value="{{ old('color', $brand->color) }}" required pattern="#[0-9a-fA-F]{6}"></div>
            </div>
            <label class="field"><span>Style du logo</span>
                <select name="style">
                    @foreach ($styles as $value => $label)
                        <option value="{{ $value }}" @selected(old('style', $brand->style) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <input type="hidden" name="is_active" value="0">
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active))> Visible sur la boutique</label>
        <div class="form-actions">
            <a href="{{ route('admin.brands.index') }}" class="btn">Annuler</a>
            <button class="btn btn-primary">{{ $isNew ? 'Créer la marque' : 'Enregistrer' }}</button>
        </div>
    </form>
</section>
@endsection
