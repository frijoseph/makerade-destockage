@props(['method', 'size' => 28])
@php
    $value = $method instanceof \App\PaymentMethod ? $method->value : (string) $method;
    // Les logos officiels peuvent être déposés dans public/img/payments/{moyen}.svg|png|webp : ils remplacent le dessin intégré.
    $file = collect(['svg', 'png', 'webp', 'jpg'])->first(fn ($ext) => is_file(public_path("img/payments/{$value}.{$ext}")));
@endphp
@if ($file)
    <img src="{{ asset("img/payments/{$value}.{$file}") }}" alt="" width="{{ $size }}" height="{{ $size }}" {{ $attributes->merge(['class' => 'pay-ic']) }} style="object-fit:contain;border-radius:7px">
@else
    <svg {{ $attributes->merge(['class' => 'pay-ic']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" role="img" aria-hidden="true">
        @switch($value)
            @case('cash_on_delivery')
                <rect width="40" height="40" rx="9" fill="#1f7a34"/>
                <rect x="7" y="12" width="26" height="16" rx="3" fill="none" stroke="#fff" stroke-width="2"/>
                <circle cx="20" cy="20" r="4" fill="#fff"/><circle cx="11.5" cy="20" r="1.3" fill="#fff"/><circle cx="28.5" cy="20" r="1.3" fill="#fff"/>
                @break
            @case('orange_money')
                <rect width="40" height="40" rx="9" fill="#ff7900"/>
                <rect x="7" y="7" width="26" height="26" rx="5" fill="#fff"/>
                <text x="20" y="25.5" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-weight="900" font-size="13" fill="#ff7900">OM</text>
                @break
            @case('mtn_momo')
                <rect width="40" height="40" rx="9" fill="#ffcc00"/>
                <text x="20" y="17" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-weight="900" font-size="9" fill="#000">MTN</text>
                <text x="20" y="29" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-weight="900" font-size="12.5" fill="#000">MoMo</text>
                @break
            @case('wave')
                <rect width="40" height="40" rx="9" fill="#1dc8ff"/>
                <path d="M7 17c3-4 6-4 9 0s6 4 9 0 6-4 8 0" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
                <path d="M7 26c3-4 6-4 9 0s6 4 9 0 6-4 8 0" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".75"/>
                @break
            @case('moov_money')
                <rect width="40" height="40" rx="9" fill="#0a5dab"/>
                <text x="20" y="25" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-weight="800" font-style="italic" font-size="13" fill="#fff">moov</text>
                <rect x="9" y="29" width="22" height="2.4" rx="1.2" fill="#ff8a00"/>
                @break
            @default
                <rect width="40" height="40" rx="9" fill="#5b3cc4"/>
                <rect x="7" y="11" width="26" height="18" rx="3" fill="none" stroke="#fff" stroke-width="2"/>
                <rect x="7" y="16" width="26" height="4" fill="#fff"/>
        @endswitch
    </svg>
@endif
