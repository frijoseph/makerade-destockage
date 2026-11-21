@props(['values' => [], 'color' => 'currentColor', 'width' => 150, 'height' => 36])
@php
    $points = array_values(array_map('floatval', (array) $values));
    $count = count($points);
    $max = $count ? max($points) : 0;
    $min = $count ? min($points) : 0;
    $range = ($max - $min) ?: 1;
    $path = '';
    foreach ($points as $i => $value) {
        $x = $count > 1 ? $i / ($count - 1) * $width : $width / 2;
        $y = $height - 3 - (($value - $min) / $range) * ($height - 6);
        $path .= ($i === 0 ? 'M' : ' L').round($x, 1).' '.round($y, 1);
    }
@endphp
<svg {{ $attributes->merge(['class' => 'spark']) }} viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" aria-hidden="true">
    @if ($count > 1)
        <path d="{{ $path }}" fill="none" stroke="{{ $color }}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
    @endif
</svg>
