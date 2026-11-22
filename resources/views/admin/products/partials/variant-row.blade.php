<tr data-var-row>
    <td data-col="o0"><input type="hidden" name="variants[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}"><input type="text" name="variants[{{ $i }}][option1]" value="{{ $row['option1'] ?? '' }}" maxlength="60" aria-label="Option 1" data-opt></td>
    <td data-col="o1"><input type="text" name="variants[{{ $i }}][option2]" value="{{ $row['option2'] ?? '' }}" maxlength="60" aria-label="Option 2" data-opt></td>
    <td data-col="o2"><input type="text" name="variants[{{ $i }}][option3]" value="{{ $row['option3'] ?? '' }}" maxlength="60" aria-label="Option 3" data-opt></td>
    <td><input type="text" name="variants[{{ $i }}][sku]" value="{{ $row['sku'] ?? '' }}" maxlength="60" aria-label="Référence" data-sku></td>
    <td class="num"><input type="number" name="variants[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" min="0" step="0.01" aria-label="Prix" placeholder="—"></td>
    <td class="num"><input type="number" name="variants[{{ $i }}][stock]" value="{{ $row['stock'] ?? 0 }}" min="0" step="1" aria-label="Stock"></td>
    <td class="var-photo">
        @if (! empty($row['image_url']))<img src="{{ $row['image_url'] }}" alt="" width="34" height="34">@endif
        <input type="file" name="variants[{{ $i }}][image]" accept="image/jpeg,image/png,image/webp" aria-label="Photo de la variante">
        @if (! empty($row['image_url']))<label class="check"><input type="checkbox" name="variants[{{ $i }}][remove_image]" value="1"> Retirer</label>@endif
    </td>
    <td><input type="hidden" name="variants[{{ $i }}][is_active]" value="0"><label class="check"><input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" @checked($row['is_active'] ?? true) aria-label="Active"></label></td>
    <td class="actions"><button type="button" class="icon-btn" data-remove-variant aria-label="Retirer la variante"><x-admin.icon name="trash" :size="17" /></button></td>
</tr>
