@props(['b'])
@php $n = strtolower($b['name']); @endphp
<a href="{{ route('shop') }}?q={{ urlencode($b['name']) }}" class="brand" aria-label="{{ $b['name'] }}">
<svg viewBox="0 0 140 60" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
@switch($n)
    @case('dettol')
        <circle cx="70" cy="30" r="25" fill="#2f9142"/><circle cx="70" cy="30" r="22" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="1"/>
        <path d="M70 8v9M66 12h8" stroke="#fff" stroke-width="2"/>
        <text x="70" y="36" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="15" fill="#fff">Dettol</text>
        <path d="M58 42q12 5 24 0" stroke="#fff" stroke-width="1.5" fill="none"/>
        @break
    @case('colgate')
        <text x="70" y="33" text-anchor="middle" font-family="Georgia,serif" font-style="italic" font-weight="700" font-size="29" fill="#e3262e" letter-spacing="-1">Colgate</text>
        <path d="M26 39q44 16 88-4" stroke="#e3262e" stroke-width="4.5" fill="none" stroke-linecap="round"/>
        @break
    @case('surf excel')
        <circle cx="38" cy="22" r="5" fill="#2b5db8"/><circle cx="28" cy="14" r="4" fill="#d6457a"/><circle cx="46" cy="10" r="3.5" fill="#d6457a"/><circle cx="26" cy="28" r="3.5" fill="#e0a030"/><circle cx="48" cy="30" r="3" fill="#2b5db8"/>
        <path d="M30 14 38 22 46 10M26 28 38 22 48 30" stroke="#d6457a" stroke-width="2" fill="none"/>
        <text x="52" y="30" font-family="Inter,Arial" font-style="italic" font-weight="900" font-size="23" fill="#1c4fa3">Surf</text>
        <text x="58" y="46" font-family="Inter,Arial" font-weight="800" font-size="15" fill="#1b2a6b">excel</text>
        @break
    @case('pears')
        <text x="70" y="40" text-anchor="middle" font-family="Georgia,'Times New Roman',serif" font-weight="500" font-size="35" fill="#1a1a1a" letter-spacing="-1">Pears</text>
        @break
    @case('dove')
        <text x="70" y="40" text-anchor="middle" font-family="Georgia,'Times New Roman',serif" font-style="italic" font-weight="500" font-size="34" fill="#2a4a8c">Dove</text>
        @break
    @case('harpic')
        <g transform="rotate(-9 70 30)"><text x="70" y="38" text-anchor="middle" font-family="Inter,Arial" font-style="italic" font-weight="900" font-size="25" fill="#d92332" stroke="#fff" stroke-width="1.2" paint-order="stroke">HARPIC</text></g>
        @break
    @case('vim')
        <path d="M70 8c16 0 26 9 26 21s-10 22-26 22-26-10-26-22 10-21 26-21z" fill="#6aae2a"/><path d="M52 18c6-8 28-8 34 0" stroke="#a6d94a" stroke-width="3" fill="none"/>
        <text x="70" y="38" text-anchor="middle" font-family="Inter,Arial" font-style="italic" font-weight="900" font-size="28" fill="#e11d2a" stroke="#fff" stroke-width="2" paint-order="stroke">Vim</text>
        @break
    @case('closeup')
        <text x="70" y="38" text-anchor="middle" font-family="Inter,Arial" font-weight="900" font-size="27" fill="#161616" letter-spacing="-1.5">closeup</text>
        @break
    @case('cinthol')
        <text x="70" y="36" text-anchor="middle" font-family="Inter,Arial" font-weight="700" font-size="16" fill="#4a4f4c" letter-spacing="3">CINTHOL</text>
        @break
    @default
        <text x="70" y="36" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="20" fill="{{ $b['color'] }}">{{ $b['name'] }}</text>
@endswitch
</svg>
</a>
