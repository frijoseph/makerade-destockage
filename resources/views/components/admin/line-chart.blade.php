@props(['series' => [], 'cumulative' => true])
@php
    use App\Support\Money;

    $labels = array_keys($series);
    $values = array_values(array_map('floatval', $series));
    if ($cumulative) {
        $running = 0;
        $values = array_map(function ($value) use (&$running) { return $running += $value; }, $values);
    }

    $width = 560; $height = 260; $left = 54; $right = 14; $top = 16; $bottom = 30;
    $plotWidth = $width - $left - $right; $plotHeight = $height - $top - $bottom;
    $count = count($values);
    $maxValue = $count ? max($values) : 0;
    $step = $maxValue > 0 ? pow(10, floor(log10($maxValue))) / 2 : 1;
    $ceiling = max($step * ceil(($maxValue ?: 1) / $step), 1);
    $ticks = 5;

    $coordinates = [];
    foreach ($values as $i => $value) {
        $coordinates[] = [
            round($left + ($count > 1 ? $i / ($count - 1) : 0.5) * $plotWidth, 1),
            round($top + $plotHeight - ($value / $ceiling) * $plotHeight, 1),
        ];
    }
    $line = collect($coordinates)->map(fn ($p, $i) => ($i ? 'L' : 'M').$p[0].' '.$p[1])->implode(' ');
    $area = $count ? $line.' L'.end($coordinates)[0].' '.($top + $plotHeight).' L'.$coordinates[0][0].' '.($top + $plotHeight).' Z' : '';
    $labelEvery = max(1, (int) ceil($count / 6));
    $gradientId = 'lg'.substr(md5(json_encode($series)), 0, 6);
@endphp
<div class="line-chart" data-line-chart>
    <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Évolution du chiffre d'affaires">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0" stop-color="var(--accent)" stop-opacity=".28"/>
                <stop offset="1" stop-color="var(--accent)" stop-opacity="0"/>
            </linearGradient>
        </defs>
        @for ($t = 0; $t <= $ticks; $t++)
            @php $y = $top + $plotHeight - $t / $ticks * $plotHeight; @endphp
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y }}" y2="{{ $y }}" class="grid"/>
            <text x="{{ $left - 10 }}" y="{{ $y + 4 }}" text-anchor="end" class="axis">{{ Money::compact($ceiling * $t / $ticks) }}</text>
        @endfor
        @foreach ($labels as $i => $label)
            @if ($i % $labelEvery === 0 || $i === $count - 1)
                <text x="{{ $coordinates[$i][0] }}" y="{{ $height - 8 }}" text-anchor="middle" class="axis">{{ \Illuminate\Support\Carbon::parse($label)->translatedFormat('j M') }}</text>
            @endif
        @endforeach
        @if ($count)
            <path d="{{ $area }}" fill="url(#{{ $gradientId }})"/>
            <path d="{{ $line }}" fill="none" stroke="var(--accent)" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round"/>
            @foreach ($coordinates as $i => [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $i === $count - 1 ? 5 : 3 }}" class="pt {{ $i === $count - 1 ? 'is-last' : '' }}"
                        data-label="{{ \Illuminate\Support\Carbon::parse($labels[$i])->translatedFormat('j M') }}"
                        data-value="{{ Money::format($values[$i]) }}"/>
            @endforeach
        @endif
    </svg>
    @if ($count)
        <div class="chart-tip" data-tip style="left: {{ end($coordinates)[0] / $width * 100 }}%; top: {{ end($coordinates)[1] / $height * 100 }}%">
            <b>{{ Money::format(end($values)) }}</b><small>{{ \Illuminate\Support\Carbon::parse(end($labels))->translatedFormat('j M') }}</small>
        </div>
    @endif
</div>
