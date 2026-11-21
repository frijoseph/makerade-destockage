@props(['icon', 'label', 'value', 'tone' => 'violet', 'hint' => null, 'href' => null])
<{{ $href ? 'a href='.$href : 'div' }} {{ $attributes->merge(['class' => 'stat-card tone-'.$tone]) }}>
    <span class="stat-ic"><x-admin.icon :name="$icon" :size="20" :stroke="2" /></span>
    <span class="stat-tx"><small>{{ $label }}</small><b>{{ $value }}</b>@if ($hint)<em>{{ $hint }}</em>@endif</span>
</{{ $href ? 'a' : 'div' }}>
