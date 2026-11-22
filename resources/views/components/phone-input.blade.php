@props(['name' => 'phone', 'value' => null, 'country' => null, 'countryName' => null, 'required' => false, 'id' => null, 'placeholder' => null])
@php
    use App\Support\Phone;

    // customer[phone] → customer.phone pour retrouver l'ancienne saisie après une erreur de validation
    $dotted = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $countryName ??= "{$name}_country";
    $dottedCountry = trim(str_replace(['[', ']'], ['.', ''], $countryName), '.');

    $given = old($dotted, $value);
    $iso = old($dottedCountry, $country);
    $national = (string) $given;

    if (is_string($given) && str_starts_with($given, '+')) {
        [$splitIso, $national] = Phone::split($given);
        $iso ??= $splitIso;
    }

    $iso = strtoupper((string) ($iso ?: Phone::defaultIso()));
    $id ??= 'ph_'.preg_replace('/[^A-Za-z0-9_]/', '_', $name);
@endphp
<div {{ $attributes->merge(['class' => 'phone-field']) }} data-phone>
    <select name="{{ $countryName }}" aria-label="Indicatif du pays" data-search-from="1">
        @foreach (Phone::countries() as $code => $info)
            <option value="{{ $code }}" data-short="{{ $info['flag'] }} +{{ $info['dial'] }}" @selected($iso === $code)>{{ $info['flag'] }} {{ $info['name'] }} (+{{ $info['dial'] }})</option>
        @endforeach
    </select>
    <input type="tel" id="{{ $id }}" name="{{ $name }}" value="{{ $national }}" inputmode="tel" autocomplete="tel-national" placeholder="{{ $placeholder ?? '07 00 00 00 00' }}" @required($required)>
</div>
