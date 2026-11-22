@php
    $type = $field['type'];
    $id = 'f_'.preg_replace('/[^A-Za-z0-9_]/', '_', $name);
    $hasError = $errors->has($errorKey);
@endphp
@if ($type === 'list')
    <div class="field span-2">
        <span>{{ $field['label'] }}</span>
        @isset($field['help'])<small>{{ $field['help'] }}</small>@endisset
        <div class="repeater" data-repeater>
            <div class="repeater-rows" data-rows>
                @foreach (array_values((array) $value) as $index => $row)
                    @include('admin.settings.list-row', ['index' => $index, 'row' => (array) $row])
                @endforeach
            </div>
            <template data-row-template>
                @include('admin.settings.list-row', ['index' => '__i__', 'row' => []])
            </template>
            <button type="button" class="btn btn-sm" data-add-row><x-admin.icon name="plus" :size="16" :stroke="2.4" /> Ajouter</button>
        </div>
    </div>
@elseif ($type === 'boolean')
    <div class="field {{ ($wide ?? false) ? 'span-2' : '' }}">
        <span>{{ $field['label'] }}</span>
        <input type="hidden" name="{{ $name }}" value="0">
        <label class="check"><input type="checkbox" name="{{ $name }}" value="1" @checked($value)> Activé</label>
        @isset($field['help'])<small>{{ $field['help'] }}</small>@endisset
    </div>
@elseif ($type === 'checkboxes')
    <div class="field span-2">
        <span>{{ $field['label'] }}</span>
        <div class="checks">
            @foreach ($field['options'] as $optionValue => $optionLabel)
                <label class="check"><input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}" @checked(in_array($optionValue, (array) $value, true))> @if (str_ends_with($name, '[payment_methods]'))<x-payment-icon :method="$optionValue" :size="24" />@endif {{ $optionLabel }}</label>
            @endforeach
        </div>
        @isset($field['help'])<small>{{ $field['help'] }}</small>@endisset
    </div>
@elseif ($type === 'image')
    <div class="field {{ ($wide ?? false) ? 'span-2' : '' }}">
        <span>{{ $field['label'] }}</span>
        <div class="image-field">
            <span class="image-thumb" id="{{ $id }}p">@if ($url = \App\Models\Setting::imageUrl($value))<img src="{{ $url }}" alt="">@else<x-admin.icon name="image" :size="22" />@endif</span>
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            <input type="file" name="{{ $fileName }}" accept="image/*" data-image-input="#{{ $id }}p" aria-label="{{ $field['label'] }}">
        </div>
        @isset($field['help'])<small>{{ $field['help'] }}</small>@endisset
    </div>
@else
    <label class="field {{ ($wide ?? false) ? 'span-2' : '' }}" for="{{ $id }}">
        <span>{{ $field['label'] }}</span>
        @switch($type)
            @case('textarea')
            @case('markdown')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $type === 'markdown' ? 4 : 3 }}" @if ($hasError) aria-invalid="true" @endif>{{ $value }}</textarea>
                @if ($type === 'markdown')<small>Mise en forme Markdown : [texte](https://lien), **gras**.</small>@endif
                @break
            @case('select')
                <select id="{{ $id }}" name="{{ $name }}">
                    @foreach ($field['options'] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @break
            @case('color')
                <span class="color-field" data-color><input type="color" value="{{ $value }}" aria-label="{{ $field['label'] }}"><input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" pattern="#[0-9a-fA-F]{6}"></span>
                @break
            @case('datetime')
                <input type="datetime-local" id="{{ $id }}" name="{{ $name }}" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">
                @break
            @case('secret')
                <input type="password" id="{{ $id }}" name="{{ $name }}" value="" autocomplete="new-password" placeholder="{{ $value ? '•••••••••••• enregistrée — laissez vide pour la conserver' : 'Collez votre clé ici' }}" spellcheck="false">
                @if ($value)<span class="check"><input type="checkbox" name="clear_secrets[{{ preg_replace('/^settings\[(.+)\]$/', '$1', $name) }}]" value="1" id="{{ $id }}c"><label for="{{ $id }}c">Supprimer la clé enregistrée</label></span>@endif
                @break
            @case('number')
                <input type="number" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" step="any" @if ($hasError) aria-invalid="true" @endif>
                @break
            @default
                <input type="{{ in_array($type, ['email', 'url', 'tel'], true) ? $type : 'text' }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @if ($hasError) aria-invalid="true" @endif>
        @endswitch
        @isset($field['help'])<small>{{ $field['help'] }}</small>@endisset
        @error($errorKey)<small class="err">{{ $message }}</small>@enderror
    </label>
@endif
