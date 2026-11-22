<div class="line-row">
    <label class="sr-only" for="line-{{ $index }}">Produit</label>
    <select id="line-{{ $index }}" name="items[{{ $index }}][product_id]">
        <option value="">Choisir un produit…</option>
        @foreach ($products as $product)
            @if ($product->variants->where('is_active', true)->isNotEmpty())
                @foreach ($product->variants->where('is_active', true) as $variant)
                    @php $variantPrice = $variant->setRelation('product', $product)->effectivePrice(); @endphp
                    <option value="{{ $product->id }}:{{ $variant->id }}" data-price="{{ $variantPrice }}" data-stock="{{ $variant->stock }}"
                            @selected((string) $selected === $product->id.':'.$variant->id) @disabled($variant->stock < 1)>
                        {{ $product->name }} — {{ $product->variant }} ({{ $variant->name }}) · {{ \App\Support\Money::format($variantPrice) }} ({{ $variant->stock }} en stock)
                    </option>
                @endforeach
            @else
                <option value="{{ $product->id }}" data-price="{{ (float) $product->price }}" data-stock="{{ $product->stock }}"
                        @selected((string) $selected === (string) $product->id) @disabled($product->stock < 1)>
                    {{ $product->name }} — {{ $product->variant }} · {{ \App\Support\Money::format($product->price) }} ({{ $product->stock }} en stock)
                </option>
            @endif
        @endforeach
    </select>
    <input type="number" name="items[{{ $index }}][quantity]" value="{{ $quantity }}" min="1" max="999" aria-label="Quantité">
    <span class="line-total">—</span>
    <button type="button" class="icon-btn" data-remove-line aria-label="Retirer la ligne"><x-admin.icon name="trash" :size="18" /></button>
</div>
