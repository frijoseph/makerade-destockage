@props(['b'])
@php
    $name = $b['name'];
    $color = $b['color'] ?? '#2a2a2a';
    $style = $b['style'] ?? 'plain';
    $size = max(13, min(28, (int) floor(190 / max(mb_strlen($name), 1))));
@endphp
<a href="{{ $b['url'] ?? route('shop', ['q' => $name]) }}" class="brand" aria-label="{{ $name }}">
@if (! empty($b['logo']))
    <img src="{{ $b['logo'] }}" alt="{{ $name }}" loading="lazy" style="max-height:52px;max-width:130px;object-fit:contain">
@else
<svg viewBox="0 0 140 60" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
@switch($style)
    @case('pill')
        <rect x="6" y="12" width="128" height="36" rx="18" fill="{{ $color }}"/>
        <text x="70" y="{{ 30 + $size * 0.35 }}" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="{{ min($size, 20) }}" fill="#fff">{{ $name }}</text>
        @break
    @case('circle')
        <ellipse cx="70" cy="30" rx="58" ry="25" fill="{{ $color }}"/>
        <text x="70" y="{{ 30 + $size * 0.35 }}" text-anchor="middle" font-family="Inter,Arial" font-style="italic" font-weight="900" font-size="{{ min($size, 22) }}" fill="#fff">{{ $name }}</text>
        @break
    @case('script')
        <text x="70" y="34" text-anchor="middle" font-family="Georgia,serif" font-style="italic" font-weight="700" font-size="{{ $size }}" fill="{{ $color }}">{{ $name }}</text>
        <path d="M26 42q44 14 88-4" stroke="{{ $color }}" stroke-width="3.5" fill="none" stroke-linecap="round"/>
        @break
    @case('swash')
        <text x="70" y="{{ 30 + $size * 0.35 }}" text-anchor="middle" font-family="Inter,Arial" font-style="italic" font-weight="900" font-size="{{ $size }}" fill="{{ $color }}" letter-spacing="-.5">{{ $name }}</text>
        @break
    @case('serif')
        <text x="70" y="{{ 30 + $size * 0.35 }}" text-anchor="middle" font-family="Georgia,'Times New Roman',serif" font-weight="500" font-size="{{ $size }}" fill="{{ $color }}">{{ $name }}</text>
        @break
    @default
        <text x="70" y="{{ 30 + $size * 0.35 }}" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="{{ $size }}" fill="{{ $color }}">{{ $name }}</text>
@endswitch
</svg>
@endif
</a>
