@props(['kind' => 'bottle', 'c1' => '#2358b8', 'c2' => '#e9302f', 'label' => '', 'image' => null])
@if ($image)<img {{ $attributes->merge(['class' => 'art photo']) }} src="{{ $image }}" alt="{{ $label }}" loading="lazy" decoding="async">@else
@php $u = 'g'.substr(md5($kind.$c1.$c2.$label), 0, 6); @endphp
<svg {{ $attributes->merge(['class' => 'art']) }} viewBox="0 0 120 140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $label }}">
  <defs>
    <linearGradient id="{{ $u }}a" x1="0" x2="1"><stop offset="0" stop-color="#000" stop-opacity=".18"/><stop offset=".35" stop-color="#fff" stop-opacity=".28"/><stop offset=".7" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".22"/></linearGradient>
  </defs>
  <ellipse cx="60" cy="132" rx="38" ry="5" fill="#000" opacity=".12"/>
  @switch($kind)
    @case('bottle')
      <rect x="48" y="6" width="24" height="14" rx="3" fill="{{ $c2 }}"/>
      <rect x="52" y="20" width="16" height="8" fill="{{ $c1 }}"/>
      <path d="M40 40q0-12 12-12h16q12 0 12 12v78q0 10-10 10H50q-10 0-10-10z" fill="{{ $c1 }}"/>
      <rect x="40" y="58" width="40" height="46" fill="#fff" opacity=".94"/>
      <rect x="40" y="58" width="40" height="7" fill="{{ $c2 }}"/>
      <text x="60" y="86" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="{{ strlen($label) > 7 ? 8 : 10 }}" fill="{{ $c1 }}">{{ $label }}</text>
      <path d="M40 40q0-12 12-12h16q12 0 12 12v78q0 10-10 10H50q-10 0-10-10z" fill="url(#{{ $u }}a)"/>
      @break
    @case('pack')
      <path d="M26 24q34-10 68 0l4 100q-38 10-76 0z" fill="{{ $c1 }}"/>
      <path d="M26 24q34-10 68 0l1 14q-35-8-70 0z" fill="{{ $c2 }}"/>
      <ellipse cx="60" cy="74" rx="30" ry="22" fill="#fff" opacity=".95"/>
      <text x="60" y="78" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="{{ strlen($label) > 8 ? 9 : 11 }}" fill="{{ $c1 }}">{{ $label }}</text>
      <path d="M26 24q34-10 68 0l4 100q-38 10-76 0z" fill="url(#{{ $u }}a)"/>
      @break
    @case('tube')
      <g transform="rotate(-18 60 70)">
        <rect x="44" y="14" width="32" height="14" rx="3" fill="#fff"/>
        <path d="M40 28h40l-3 88H43z" fill="{{ $c1 }}"/>
        <rect x="40" y="62" width="40" height="30" fill="{{ $c2 }}" opacity=".95"/>
        <text x="60" y="82" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-style="italic" font-size="9" fill="#fff">{{ $label }}</text>
        <path d="M40 28h40l-3 88H43z" fill="url(#{{ $u }}a)"/>
        <rect x="42" y="116" width="36" height="6" rx="2" fill="{{ $c1 }}"/>
      </g>
      @break
    @case('box')
      <rect x="14" y="48" width="92" height="64" rx="8" fill="{{ $c1 }}"/>
      <rect x="22" y="56" width="76" height="48" rx="5" fill="{{ $c2 }}"/>
      <text x="60" y="86" text-anchor="middle" font-family="Georgia,serif" font-weight="700" font-size="17" fill="{{ $c1 }}">{{ $label }}</text>
      <rect x="14" y="48" width="92" height="64" rx="8" fill="url(#{{ $u }}a)"/>
      @break
    @case('soap')
      <rect x="14" y="40" width="92" height="64" rx="12" fill="{{ $c1 }}"/>
      <rect x="22" y="48" width="76" height="48" rx="8" fill="{{ $c2 }}" opacity=".55"/>
      <text x="60" y="78" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="11" fill="#fff">{{ $label }}</text>
      <rect x="14" y="40" width="92" height="64" rx="12" fill="url(#{{ $u }}a)"/>
      @break
    @default
      <rect x="30" y="30" width="60" height="90" rx="10" fill="{{ $c1 }}"/>
  @endswitch
</svg>
@endif
