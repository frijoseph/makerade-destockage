@props(['p', 'hot' => false, 'flash' => false])
<article class="pcard reveal" data-id="{{ $p['slug'] }}" data-name="{{ $p['name'] }}" data-price="{{ $p['price'] }}" data-variant="{{ $p['variant'] }}">
    @if ($hot || !empty($p['hot']))<span class="tag-hot">Promo choc</span>@endif
    <button class="wish" data-wish="{{ $p['slug'] }}" aria-label="Ajouter aux favoris"><x-icon name="heart" :size="17" /></button>
    <a href="{{ route('product', $p['slug']) }}" class="pimg">
        @if ($p['off'] > 0)<span class="off">-{{ $p['off'] }} %</span>@endif
        <x-product-art :kind="$p['kind']" :c1="$p['c1']" :c2="$p['c2']" :label="$p['name']" :image="$p['image'] ?? null" />
    </a>
    <div class="pbody">
        <h3><a href="{{ route('product', $p['slug']) }}">{{ $p['name'] }}</a></h3>
        <p class="pvar">{{ $p['variant'] }}</p>
        <div class="stars" aria-label="Note {{ $p['rating'] }} sur 5">
            @for ($i = 1; $i <= 5; $i++)<x-icon name="star" :size="12" class="{{ $i <= floor($p['rating']) ? 'on' : ($i - $p['rating'] < 1 ? 'half' : '') }}" />@endfor
            <small>({{ number_format($p['reviews']) }})</small>
        </div>
        <div class="price"><b>${{ number_format($p['price'], 2) }}</b>@if ($p['old_price'])<s>${{ number_format($p['old_price'], 2) }}</s>@endif</div>
        @if ($flash)
            @php $left = 4 + crc32($p['slug']) % 38; $sold = 45 + crc32($p['slug'].'x') % 50; @endphp
            <div class="stock"><i style="--w: {{ $sold }}%"></i></div>
            <small class="stock-t">{{ $left }} articles restants</small>
        @endif
        <div class="stepper" data-stepper="{{ $p['slug'] }}"><button type="button" data-s="-1" aria-label="Moins">−</button><b>1</b><button type="button" data-s="1" aria-label="Plus">+</button></div>
        <button class="btn-add" data-add="{{ $p['slug'] }}">Ajouter au panier <x-icon name="cart" :size="17" :stroke="1.8" /></button>
    </div>
</article>
