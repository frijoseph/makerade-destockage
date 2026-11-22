@extends('layouts.app')

@section('content')
@php
    use App\Support\Catalog;
    $slides = (array) $shop->hero_slides;
    $perks = (array) $shop->hero_perks;
    $banners = (array) $shop->home_banners;
    $flashEnd = $shop->flash_ends_at ? \Illuminate\Support\Carbon::parse($shop->flash_ends_at) : null;
    $stats = collect((array) $shop->stats)
        ->map(fn ($stat) => $stat + ['number' => Catalog::stat($stat['source'] ?? 'fixed', $stat['value'] ?? 0)])
        ->filter(fn ($stat) => $stat['number'] > 0)
        ->values();
@endphp

{{-- ===== HERO ===== --}}
@if (count($slides))
<section class="fhero" id="hero" aria-roledescription="carousel">
    <div class="bubbles" data-bubbles="14" aria-hidden="true"></div>
    <div class="hero-track" id="heroTrack">
        @foreach ($slides as $i => $slide)
            @php
                $words = array_values(array_filter(array_map('trim', explode('|', $shop->interpolate($slide['highlight'] ?? '')))));
                $badge = Catalog::badge($slide['badge'] ?? null);
                $isFeature = ($slide['layout'] ?? 'promo') === 'feature';
            @endphp
            <div class="slide fs {{ $slide['theme'] ?? 'fs1' }} {{ $i === 0 ? 'is-active' : '' }}">
                <div class="fhero-img {{ $isFeature ? '' : 'alt' }}">
                    @if ($image = $shop->imageUrl($slide['image'] ?? null))
                        <img src="{{ $image }}" alt="{{ $shop->interpolate($slide['title'] ?? '') }}" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                    @endif
                    @if ($badge)
                        @if ($isFeature && $badge['type'] === 'text')
                            <div class="fh-badge"><span>{{ $badge['value'] }}</span><x-icon name="heart" :size="14" :stroke="1.8" /></div>
                        @else
                            <div class="fh-disc">@if ($badge['type'] === 'discount')<small>JUSQU'À</small><b>{{ $badge['value'] }}</b><span>DE REMISE</span>@else<b>{{ $badge['value'] }}</b>@endif</div>
                        @endif
                    @endif
                    <div class="sparkles" data-sparkles="{{ $isFeature ? 9 : 7 }}" aria-hidden="true"></div>
                </div>
                @if ($isFeature && ! empty($slide['script']))
                    <div class="fh-script">{!! nl2br(e(str_replace(' ', "\n", $shop->interpolate($slide['script'])))) !!}<br><x-icon name="heart" :size="20" :stroke="1.6" /></div>
                @endif
                <div class="container fhero-in"><div class="fh-txt">
                    @if (! empty($slide['eyebrow']))<p class="eyebrow">{{ $shop->interpolate($slide['eyebrow']) }}</p>@endif
                    <h1>{{ $shop->interpolate($slide['title'] ?? '') }}
                        @if (count($words) > 1)
                            <br><span class="rot"><span class="rot-in">@foreach ([...$words, $words[0]] as $word)<em>{{ $word }}</em>@endforeach</span></span>
                        @elseif (count($words) === 1)
                            <br><em>{{ $words[0] }}</em>
                        @endif
                    </h1>
                    @if (! empty($slide['text']))<p class="fh-p">{{ $shop->interpolate($slide['text']) }}</p>@endif
                    @if (! empty($slide['button_label']))
                        <a href="{{ $shop->link($slide['button_url'] ?? '/shop') }}" class="fh-btn">{{ $shop->interpolate($slide['button_label']) }} <x-icon name="arrow-right" :size="16" :stroke="2.2" /></a>
                    @endif
                    @if (count($perks))
                        <div class="fh-perks">@foreach ($perks as $perk)<div><x-icon :name="$perk['icon'] ?? 'leaf'" :size="28" :stroke="1.5" /><span>{{ $shop->interpolate($perk['text'] ?? '') }}</span></div>@endforeach</div>
                    @endif
                </div></div>
            </div>
        @endforeach
    </div>

    @if (count($slides) > 1)
        <button class="hero-arrow prev" id="heroPrev" aria-label="Slide précédente"><x-icon name="chev-left" :size="16" :stroke="2.6" /></button>
        <button class="hero-arrow next" id="heroNext" aria-label="Slide suivante"><x-icon name="chev-right" :size="16" :stroke="2.6" /></button>
        <div class="hero-dots" id="heroDots">@foreach ($slides as $i => $slide)<button @class(['on' => $i === 0]) aria-label="Slide {{ $i + 1 }}"></button>@endforeach</div>
        <div class="hero-progress"><i id="heroBar"></i></div>
    @endif
</section>
@endif

{{-- ===== CATÉGORIES ===== --}}
<section class="container fcats" id="categories">
    @foreach ($categories as $i => $c)
        <a href="{{ route('shop', ['cat' => $c['slug']]) }}" class="fcat reveal" style="--d: {{ $i * 60 }}ms">
            <span class="fcat-c" style="background: {{ $c['bg'] }}">@if ($c['image'])<img src="{{ $c['image'] }}" alt="{{ $c['name'] }}" loading="lazy">@endif</span>
            <span class="fcat-n">{{ $c['name'] }}</span>
        </a>
    @endforeach
</section>

{{-- ===== VENTES FLASH ===== --}}
@if ($shop->flash_enabled && count($flash))
<section class="container fsec flash" id="flash">
    <div class="flash-h reveal">
        <div class="fl-t"><span class="bolt">⚡</span><h2>{{ $shop->text('flash_title') }}</h2></div>
        @if ($flashEnd && $flashEnd->isFuture())
            <div class="fl-time">Se termine dans <div class="cd" data-countdown="{{ $flashEnd->toIso8601String() }}"><b data-h>00</b><i>:</i><b data-m>00</b><i>:</i><b data-s>00</b></div></div>
        @endif
        <a href="{{ route('deals') }}" class="fl-all">Voir tout <x-icon name="arrow-right" :size="15" :stroke="2.2" /></a>
    </div>
    <div class="fgrid flashgrid">
        @foreach ($flash as $p)
            <x-product-card :p="$p" :flash="true" />
        @endforeach
    </div>
</section>
@endif

{{-- ===== BANNIÈRES ===== --}}
@foreach (array_filter([array_slice($banners, 0, 2), array_slice($banners, 2)]) as $row => $group)
    @if ($row === 1 && count($featured))
        {{-- ===== PRODUITS À LA UNE (entre les deux rangées de bannières) ===== --}}
        <section class="container fsec" id="featured">
            <div class="fsec-h reveal"><h2>{{ $shop->text('featured_title') }}</h2><a href="{{ route('shop') }}" class="ul-link">Voir tous les produits <x-icon name="arrow-right" :size="15" :stroke="2" /></a></div>
            <div class="fgrid">@foreach ($featured as $p)<x-product-card :p="$p" />@endforeach</div>
        </section>
    @endif
    <section class="container {{ count($group) >= 3 ? 'fban3' : 'fban2' }}">
        @foreach ($group as $i => $banner)
            @php $badge = Catalog::badge($banner['badge'] ?? null); @endphp
            <a href="{{ $shop->link($banner['url'] ?? '/shop') }}" class="fb {{ $banner['style'] ?? 'fb-light' }} reveal wipe" style="--d: {{ $i * 100 }}ms">
                <div class="fb-t">
                    <h3>{{ $shop->interpolate($banner['title'] ?? '') }}</h3>
                    @if (! empty($banner['text']))<p>{{ $shop->interpolate($banner['text']) }}</p>@endif
                    @if (! empty($banner['button_label']))<span class="fb-btn {{ ($banner['style'] ?? '') === 'fb-gold' ? 'white' : 'green' }}">{{ $shop->interpolate($banner['button_label']) }} <x-icon name="arrow-right" :size="15" :stroke="2.2" /></span>@endif
                </div>
                @if ($image = $shop->imageUrl($banner['image'] ?? null))<img class="fb-img" src="{{ $image }}" alt="" loading="lazy">@endif
                @if ($badge)
                    <div class="fb-disc">@if ($badge['type'] === 'discount')<small>JUSQU'À</small><b>{{ $badge['value'] }}</b><span>DE REMISE</span>@else<b>{{ $badge['value'] }}</b>@endif</div>
                @endif
            </a>
        @endforeach
    </section>
@endforeach
@if (count($banners) <= 2 && count($featured))
    <section class="container fsec" id="featured">
        <div class="fsec-h reveal"><h2>{{ $shop->text('featured_title') }}</h2><a href="{{ route('shop') }}" class="ul-link">Voir tous les produits <x-icon name="arrow-right" :size="15" :stroke="2" /></a></div>
        <div class="fgrid">@foreach ($featured as $p)<x-product-card :p="$p" />@endforeach</div>
    </section>
@endif

{{-- ===== CHIFFRES (réels) ===== --}}
@if ($stats->isNotEmpty())
<section class="container fsec">
    <div class="stats reveal">
        <div class="bubbles" data-bubbles="10" aria-hidden="true"></div>
        @foreach ($stats as $stat)
            <div class="stat"><b><span data-countto="{{ $stat['number'] }}" data-suffix="{{ $stat['suffix'] ?? '' }}">0</span></b><small>{{ $shop->interpolate($stat['label'] ?? '') }}</small></div>
        @endforeach
    </div>
</section>
@endif

{{-- ===== TEST DE PROPRETÉ ===== --}}
@if ($shop->clean_test_enabled)
<section class="container fsec">
    <div class="cleantest reveal">
        <div class="ct-txt">
            <span class="pill">Essayez !</span>
            <h2>{{ $shop->text('clean_test_title') }}</h2>
            <p>{{ $shop->text('clean_test_text') }}</p>
            <div class="ct-meter"><i id="ctBar"></i></div>
            <small id="ctPct">0 % nettoyé</small>
            <a href="{{ $shop->link($shop->clean_test_url) }}" class="fh-btn ct-cta" id="ctCta">Voir les produits <x-icon name="arrow-right" :size="16" :stroke="2.2" /></a>
        </div>
        <div class="ct-stage" id="ctStage">
            <img src="{{ $shop->image('clean_test_image') }}" alt="">
            <canvas id="ctCanvas" aria-label="Zone à nettoyer"></canvas>
            <div class="ct-hint" id="ctHint">Frottez ici ✨</div>
        </div>
    </div>
</section>
@endif

{{-- ===== MARQUES ===== --}}
@if (count($brands))
<section class="container fsec">
    @php $brandSpeed = (int) $shop->brands_speed; @endphp
    <div class="fsec-h reveal"><h2>{{ $shop->text('brands_title') }}</h2>@if ($shop->brands_link_all)<a href="{{ route('brands') }}" class="ul-link">Voir toutes les marques <x-icon name="arrow-right" :size="15" :stroke="2" /></a>@endif</div>
    <div class="marquee @if ($brandSpeed === 0) marquee-static @endif" aria-label="Marques">
        <div class="marquee-in" @if ($brandSpeed > 0) style="animation-duration: {{ $brandSpeed }}s" @endif>
            @foreach ($brandSpeed > 0 ? [1, 2] : [1] as $copy)
                @foreach ($brands as $b)<div class="mq-item"><x-brand-logo :b="$b" /></div>@endforeach
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== RÉCEMMENT CONSULTÉS ===== --}}
@if ($shop->recently_viewed_enabled)
<section class="container fsec" id="recentSec" hidden>
    <div class="fsec-h reveal"><h2>Récemment consultés</h2></div>
    <div class="recent-grid" id="recentGrid"></div>
</section>
@endif

{{-- ===== POURQUOI NOUS CHOISIR ===== --}}
@if (count((array) $shop->why_items))
<section class="container fsec why">
    <h2 class="reveal">{{ $shop->text('why_title') }}</h2>
    <div class="why-grid">
        @foreach ((array) $shop->why_items as $i => $item)
            <div class="why-i reveal" style="--d: {{ $i * 70 }}ms"><span class="why-c"><x-icon :name="$item['icon'] ?? 'leaf'" :size="26" :stroke="1.5" /></span><div><b>{{ $shop->interpolate($item['title'] ?? '') }}</b><span>{{ $shop->interpolate($item['text'] ?? '') }}</span></div></div>
        @endforeach
    </div>
</section>
@endif
@endsection
