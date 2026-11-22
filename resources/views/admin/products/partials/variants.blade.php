@php
    $rows = old('variants');
    if ($rows === null) {
        $rows = $product->exists
            ? $product->variants->map(fn ($variant) => [
                'id' => $variant->id, 'option1' => $variant->option1, 'option2' => $variant->option2, 'option3' => $variant->option3,
                'sku' => $variant->sku, 'price' => $variant->price, 'old_price' => $variant->old_price, 'stock' => $variant->stock,
                'is_active' => $variant->is_active, 'image_url' => $variant->image_url,
            ])->all()
            : [];
    }
    $axes = old('option_names', $product->option_names ?? []);
    $axes = array_pad(array_values((array) $axes), 3, '');
@endphp

<section class="card" data-variants data-base-price="{{ $product->price }}" data-base-sku="{{ $product->sku }}">
    <header class="card-head">
        <div>
            <h2>Variantes</h2>
            <p class="muted" style="margin: 2px 0 0">Tailles, parfums, couleurs… Chaque variante a son stock, sa référence et, si besoin, son prix et sa photo.</p>
        </div>
    </header>

    <div class="var-axes">
        @foreach ([0, 1, 2] as $i)
            <div class="var-axis" data-axis="{{ $i }}">
                <label class="field"><span>Option {{ $i + 1 }} <em>(nom)</em></span><input type="text" name="option_names[{{ $i }}]" value="{{ $axes[$i] }}" maxlength="40" placeholder="{{ ['Ex. Taille', 'Ex. Parfum', 'Ex. Couleur'][$i] }}" data-axis-name></label>
                <label class="field"><span>Valeurs <em>(séparées par des virgules)</em></span><input type="text" data-axis-values placeholder="{{ ['500 ml, 1 L, 5 L', 'Citron, Lavande', 'Bleu, Vert'][$i] }}" autocomplete="off"></label>
            </div>
        @endforeach
    </div>
    <div class="var-tools">
        <button type="button" class="btn btn-sm" data-generate><x-admin.icon name="sparkles" :size="16" /> Générer les combinaisons</button>
        <button type="button" class="btn btn-sm" data-add-variant><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Ajouter une variante</button>
        <span class="muted" data-var-count></span>
    </div>

    <div class="table-wrap var-wrap">
        <table class="table var-table" data-var-table>
            <thead>
                <tr>
                    <th data-col="o0">Option 1</th><th data-col="o1">Option 2</th><th data-col="o2">Option 3</th>
                    <th>Référence</th><th class="num">Prix <small>(vide = prix du produit)</small></th><th class="num">Stock</th><th>Photo</th><th>Active</th><th class="actions"><span class="sr-only">Retirer</span></th>
                </tr>
            </thead>
            <tbody data-var-rows>
                @foreach ($rows as $i => $row)
                    @include('admin.products.partials.variant-row', ['i' => $i, 'row' => $row])
                @endforeach
            </tbody>
        </table>
        <p class="empty-note" data-var-empty @if (count($rows)) hidden @endif>Aucune variante : le produit est vendu tel quel, avec son prix et son stock.</p>
    </div>
    <template id="variantRowTemplate">
        @include('admin.products.partials.variant-row', ['i' => '__i__', 'row' => ['id' => null, 'option1' => '', 'option2' => '', 'option3' => '', 'sku' => '', 'price' => '', 'old_price' => '', 'stock' => 0, 'is_active' => true, 'image_url' => null]])
    </template>
    @error('variants')<small class="fe">{{ $message }}</small>@enderror
    @foreach ($errors->get('variants.*') as $messages)<small class="fe">{{ $messages[0] }}</small>@endforeach
</section>
