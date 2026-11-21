<div class="repeater-row" data-row>
    <div class="form-grid cols-3">
        @foreach ($field['fields'] as $subKey => $subField)
            @include('admin.settings.field', [
                'name' => "{$name}[{$index}][{$subKey}]",
                'fileName' => "{$fileName}[{$index}][{$subKey}]",
                'errorKey' => "{$errorKey}.{$index}.{$subKey}",
                'field' => $subField,
                'value' => $row[$subKey] ?? null,
                'wide' => $subField['type'] === 'textarea',
            ])
        @endforeach
    </div>
    <div class="repeater-tools">
        <button type="button" class="icon-btn" data-row-up aria-label="Monter"><x-admin.icon name="arrow-up" :size="16" /></button>
        <button type="button" class="icon-btn" data-row-down aria-label="Descendre"><x-admin.icon name="arrow-down" :size="16" /></button>
        <button type="button" class="icon-btn" data-row-remove aria-label="Supprimer"><x-admin.icon name="trash" :size="16" /></button>
    </div>
</div>
