@php
    $announcements = $shop->announcements();
    $announcementEnd = $shop->announcement_ends_at ? \Illuminate\Support\Carbon::parse($shop->announcement_ends_at) : null;
@endphp
@if ($announcements)
<div class="annbar" id="annbar" style="--ann-bg: {{ $shop->announcement_bg }}; --ann-fg: {{ $shop->announcement_color }}" role="region" aria-label="Annonces">
    <div class="annbar-in container">
        <div class="annbar-track" data-ann>
            @foreach ($announcements as $i => $item)
                @if ($item['url'])
                    <a href="{{ $item['url'] }}" @class(['ann-item', 'is-active' => $i === 0])><x-icon :name="$item['icon']" :size="16" :stroke="2" /> <span>{{ $item['text'] }}</span></a>
                @else
                    <span @class(['ann-item', 'is-active' => $i === 0])><x-icon :name="$item['icon']" :size="16" :stroke="2" /> <span>{{ $item['text'] }}</span></span>
                @endif
            @endforeach
        </div>
        @if ($announcementEnd && $announcementEnd->isFuture())
            <span class="ann-cd" data-countdown="{{ $announcementEnd->toIso8601String() }}" aria-label="Temps restant"><b data-h>00</b><i>:</i><b data-m>00</b><i>:</i><b data-s>00</b></span>
        @endif
        <button type="button" class="ann-x" data-ann-close aria-label="Fermer l’annonce"><x-icon name="x" :size="16" :stroke="2.4" /></button>
    </div>
</div>
@endif
