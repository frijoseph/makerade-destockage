@props(['tone' => 'violet', 'icon', 'label', 'value', 'change' => null, 'caption' => null, 'spark' => null, 'progress' => null, 'invert' => false])
@php
    $isUp = $change !== null && $change >= 0;
    $isGood = $invert ? ! $isUp : $isUp;
@endphp
<article {{ $attributes->merge(['class' => 'kpi tone-'.$tone]) }}>
    <span class="kpi-ic"><x-admin.icon :name="$icon" :size="22" :stroke="2.2" /></span>
    <p class="kpi-label">{{ $label }}</p>
    <p class="kpi-value">{{ $value }}</p>
    @if ($progress !== null)
        <div class="bar"><i style="width: {{ max(0, min(100, $progress)) }}%"></i></div>
    @endif
    @if ($change !== null)
        <p class="kpi-change {{ $isGood ? 'is-good' : 'is-bad' }}">
            <x-admin.icon :name="$isUp ? 'trend-up' : 'trend-down'" :size="15" :stroke="2.2" />
            <b>{{ number_format(abs($change), 1, ',', ' ') }} %</b> <span>{{ $caption }}</span>
        </p>
    @elseif ($caption)
        <p class="kpi-caption">{{ $caption }}</p>
    @endif
    @if ($spark)
        <x-admin.sparkline :values="$spark" color="var(--tone)" />
    @endif
</article>
