<div class="line-row">
    <label class="sr-only" for="line-{{ $index }}">Produit</label>
    <select id="line-{{ $index }}" name="items[{{ $index }}][product_id]">
        <option value="">Choisir un produit…</option>
        @foreach ($products as $product)
            <option value="{{ $product->id }}" data-price="{{ (float) $product->price }}" data-stock="{{ $product->stock }}"
                    @selected((string) $selected === (string) $product->id) @disabled($product->stock < 1)>
                {{ $product->name }} — {{ $product->variant }} · {{ \App\Support\Money::format($product->price) }} ({{ $product->stock }} en stock)
            </option>
        @endforeach
    </select>
    <input type="number" name="items[{{ $index }}][quantity]" value="{{ $quantity }}" min="1" max="999" aria-label="Quantité">
    <span class="line-total">—</span>
    <button type="button" class="icon-btn" data-remove-line aria-label="Retirer la ligne"><x-admin.icon name="trash" :size="18" /></button>
</div>
