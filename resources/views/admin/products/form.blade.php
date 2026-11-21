@extends('admin.layouts.app')
@php
    use App\Models\Product;
    $isNew = ! $product->exists;
    $kinds = ['bottle' => 'Flacon', 'pack' => 'Sachet / paquet', 'tube' => 'Tube', 'box' => 'Boîte', 'soap' => 'Savon'];
@endphp

@section('title', $isNew ? 'Nouveau produit' : $product->name)
@section('heading', $isNew ? 'Nouveau produit' : 'Modifier « '.$product->name.' »')
@section('subheading', $isNew ? 'Ajoutez un article au catalogue.' : 'Référence '.$product->sku.' · '.$product->stock.' en stock')
@section('actions')
    <a href="{{ route('admin.products.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Produits</a>
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data" class="grid-side"
      action="{{ $isNew ? route('admin.products.store') : route('admin.products.update', $product) }}">
    @csrf
    @unless ($isNew) @method('PUT') @endunless

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Informations</h2></header>
            <div class="form-grid">
                <label class="field"><span>Nom</span><input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="255" @error('name') aria-invalid="true" @enderror></label>
                <label class="field"><span>Format</span><input type="text" name="variant" value="{{ old('variant', $product->variant) }}" required placeholder="Ex. Liquide vaisselle 750 ml" @error('variant') aria-invalid="true" @enderror></label>
                <label class="field"><span>Catégorie</span>
                    <select name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Marque</span>
                    <select name="brand_id">
                        <option value="">— Sans marque —</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Référence (SKU)</span><input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required maxlength="40" @error('sku') aria-invalid="true" @enderror></label>
                <label class="field"><span>Adresse de la page <em>(slug)</em></span><input type="text" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="généré depuis le nom" @error('slug') aria-invalid="true" @enderror></label>
                <label class="field span-2"><span>Description <em>(facultatif)</em></span><textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea></label>
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>Prix et stock</h2></header>
            <div class="form-grid cols-3">
                <label class="field"><span>Prix de vente</span><input type="number" name="price" value="{{ old('price', $product->price) }}" required min="0" step="0.01" @error('price') aria-invalid="true" @enderror></label>
                <label class="field"><span>Prix barré <em>(facultatif)</em></span><input type="number" name="old_price" value="{{ old('old_price', $product->old_price) }}" min="0" step="0.01" @error('old_price') aria-invalid="true" @enderror><small>Affiche la remise sur le site.</small></label>
                <label class="field"><span>Prix d’achat <em>(facultatif)</em></span><input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" min="0" step="0.01"><small>Sert à calculer la valeur du stock.</small></label>
                @if ($isNew)
                    <label class="field"><span>Stock initial</span><input type="number" name="stock" value="{{ old('stock', 0) }}" required min="0" step="1"></label>
                @else
                    <div class="field"><span>Stock actuel</span><input type="text" value="{{ $product->stock }}" disabled><small><a class="link" href="{{ route('admin.stock.index', ['product' => $product->id]) }}">Ajuster depuis l’écran Stock</a></small></div>
                @endif
                <label class="field"><span>Seuil d’alerte</span><input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" required min="0" step="1"></label>
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>Visuel de secours</h2></header>
            <p class="muted" style="margin: -8px 0 16px">Utilisé sur le site quand aucune photo n’est fournie.</p>
            <div class="form-grid cols-3">
                <label class="field"><span>Forme</span>
                    <select name="kind">
                        @foreach ($kinds as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', $product->kind) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="field"><span>Couleur principale</span>
                    <div class="color-field" data-color><input type="color" value="{{ old('c1', $product->c1) }}" aria-label="Couleur principale"><input type="text" name="c1" value="{{ old('c1', $product->c1) }}" required pattern="#[0-9a-fA-F]{6}"></div>
                </div>
                <div class="field"><span>Couleur secondaire</span>
                    <div class="color-field" data-color><input type="color" value="{{ old('c2', $product->c2) }}" aria-label="Couleur secondaire"><input type="text" name="c2" value="{{ old('c2', $product->c2) }}" required pattern="#[0-9a-fA-F]{6}"></div>
                </div>
                <label class="field span-2"><span>Texte sur l’étiquette <em>(facultatif)</em></span><input type="text" name="label" value="{{ old('label', $product->label) }}" maxlength="255"></label>
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>Avis clients</h2></header>
            <div class="form-grid">
                <label class="field"><span>Note moyenne <em>(sur 5)</em></span><input type="number" name="rating" value="{{ old('rating', $product->rating) }}" min="0" max="5" step="0.1"></label>
                <label class="field"><span>Nombre d’avis</span><input type="number" name="reviews" value="{{ old('reviews', $product->reviews ?? 0) }}" min="0" step="1"><small>0 = les étoiles ne sont pas affichées.</small></label>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Photo</h2></header>
            <div class="preview" id="imgPreview">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="">
                @elseif ($product->exists)
                    <x-product-art :kind="$product->kind" :c1="$product->c1" :c2="$product->c2" :label="$product->name" />
                @else
                    <span class="ph"><x-admin.icon name="image" :size="34" :stroke="1.4" /><br>JPG, PNG ou WebP · 3 Mo max.</span>
                @endif
            </div>
            <div class="form" style="margin-top: 14px">
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input="#imgPreview" aria-label="Photo du produit">
                @if ($product->image)
                    <label class="check"><input type="checkbox" name="remove_image" value="1"> Supprimer la photo</label>
                @endif
            </div>
        </section>

        <section class="card">
            <header class="card-head"><h2>Visibilité</h2></header>
            <div class="form">
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Visible sur la boutique</label>
                <input type="hidden" name="is_new" value="0">
                <label class="check"><input type="checkbox" name="is_new" value="1" @checked(old('is_new', $product->is_new))> Badge « Nouveauté »</label>
                <input type="hidden" name="is_hot" value="0">
                <label class="check"><input type="checkbox" name="is_hot" value="1" @checked(old('is_hot', $product->is_hot))> Badge « Promo choc »</label>
            </div>
        </section>

        <div class="form-actions">
            <a href="{{ route('admin.products.index') }}" class="btn">Annuler</a>
            <button class="btn btn-primary">{{ $isNew ? 'Créer le produit' : 'Enregistrer' }}</button>
        </div>
    </div>
</form>
@endsection
