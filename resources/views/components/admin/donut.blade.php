@props(['slices' => [], 'centerValue' => '', 'centerLabel' => 'Total'])
@php
    $colors = ['#6d5dfc', '#22b07d', '#f7931e', '#3b82f6', '#f2547d', '#a3a8b8'];
    $total = max(array_sum(array_column($slices, 'value')), 0.0001);
    $radius = 70;
    $circumference = 2 * M_PI * $radius;
    $offset = 0;
@endphp
<svg {{ $attributes->merge(['class' => 'donut']) }} viewBox="0 0 200 200" role="img" aria-label="Répartition par catégorie">
    <circle cx="100" cy="100" r="{{ $radius }}" fill="none" stroke="var(--line)" stroke-width="34"/>
    @foreach ($slices as $i => $slice)
        @php $length = $slice['value'] / $total * $circumference; @endphp
        <circle cx="100" cy="100" r="{{ $radius }}" fill="none" stroke="{{ $colors[$i % count($colors)] }}" stroke-width="34"
                stroke-dasharray="{{ round(max($length - 1.5, 0), 2) }} {{ round($circumference, 2) }}"
                stroke-dashoffset="{{ round(-$offset, 2) }}" transform="rotate(-90 100 100)">
            <title>{{ $slice['name'] }} : {{ $slice['share'] }} %</title>
        </circle>
        @php $offset += $length; @endphp
    @endforeach
    <text x="100" y="98" text-anchor="middle" class="donut-value">{{ $centerValue }}</text>
    <text x="100" y="120" text-anchor="middle" class="donut-label">{{ $centerLabel }}</text>
</svg>
