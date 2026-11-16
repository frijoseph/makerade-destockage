@extends('layouts.app')

@section('content')
@php
    $perks = [['truck', 'Livraison rapide<br>et fiable'], ['shieldcheck', '100 % authentiques<br>et de qualité'], ['shield', 'Paiement sûr<br>et sécurisé']];
@endphp

{{-- ===== HERO : 3 slides animées ===== --}}
<section class="fhero" id="hero" aria-roledescription="carousel">
    <div class="bubbles" data-bubbles="14" aria-hidden="true"></div>
    <div class="hero-track" id="heroTrack">

        <div class="slide fs fs1 is-active">
            <div class="fhero-img">
                <img src="{{ asset('img/mock/hero.jpg') }}" alt="Produits d'entretien et de bien-être pour la maison" fetchpriority="high">
                <div class="fh-badge"><span>Une maison<br>propre,<br>une vie<br>sereine</span><x-icon name="heart" :size="14" :stroke="1.8" /></div>
                <div class="sparkles" data-sparkles="9" aria-hidden="true"></div>
            </div>
            <div class="fh-script">Maison<br>propre,<br>esprit<br>léger<br><x-icon name="heart" :size="20" :stroke="1.6" /></div>
            <div class="container fhero-in"><div class="fh-txt">
                <p class="eyebrow">Entretien &amp; bien-être</p>
                <h1>Une maison<br><span class="rot"><span class="rot-in"><em>impeccable</em><em>éclatante</em><em>saine</em><em>sereine</em><em>impeccable</em></span></span></h1>
                <p class="fh-p">Des produits d'entretien et de bien-être de qualité, à prix déstockés. Livraison rapide en Côte d'Ivoire.</p>
                <a href="{{ route('shop') }}" class="fh-btn">Découvrir nos produits <x-icon name="arrow-right" :size="16" :stroke="2.2" /></a>
                <div class="fh-perks">@foreach ($perks as [$ic, $t])<div><x-icon :name="$ic" :size="28" :stroke="1.5" /><span>{!! $t !!}</span></div>@endforeach</div>
            </div></div>
        </div>

        <div class="slide fs fs2">
            <div class="fhero-img alt">
                <img src="{{ asset('img/photos/hero-entretien.jpg') }}" alt="Produits d'entretien et microfibres" loading="lazy">
                <div class="fh-disc"><small>JUSQU'À</small><b>30 %</b><span>DE REMISE</span></div>
                <div class="sparkles" data-sparkles="7" aria-hidden="true"></div>
            </div>
            <div class="container fhero-in"><div class="fh-txt">
                <p class="eyebrow">Entretien &amp; lessives</p>
                <h1>Des surfaces<br><em>qui brillent</em></h1>
                <p class="fh-p">Nettoyants, lessives et accessoires des plus grandes marques, jusqu'à 30 % moins chers.</p>
                <a href="{{ route('shop', ['cat' => 'entretien']) }}" class="fh-btn">Voir les produits <x-icon name="arrow-right" :size="16" :stroke="2.2" /></a>
                <div class="fh-perks">@foreach ($perks as [$ic, $t])<div><x-icon :name="$ic" :size="28" :stroke="1.5" /><span>{!! $t !!}</span></div>@endforeach</div>
            </div></div>
        </div>

        <div class="slide fs fs3">
            <div class="fhero-img alt">
                <img src="{{ asset('img/photos/hero-soins.jpg') }}" alt="Savons et soins de bien-être" loading="lazy">
                <div class="fh-disc"><small>JUSQU'À</small><b>25 %</b><span>DE REMISE</span></div>
                <div class="sparkles" data-sparkles="7" aria-hidden="true"></div>
            </div>
            <div class="container fhero-in"><div class="fh-txt">
                <p class="eyebrow">Soins &amp; bien-être</p>
                <h1>Prenez soin<br><em>de vous</em></h1>
                <p class="fh-p">Savons, shampooings et soins du quotidien pour toute la famille, à prix doux.</p>
                <a href="{{ route('shop', ['cat' => 'bienetre']) }}" class="fh-btn">Découvrir les soins <x-icon name="arrow-right" :size="16" :stroke="2.2" /></a>
                <div class="fh-perks">@foreach ($perks as [$ic, $t])<div><x-icon :name="$ic" :size="28" :stroke="1.5" /><span>{!! $t !!}</span></div>@endforeach</div>
            </div></div>
        </div>
    </div>

    <button class="hero-arrow prev" id="heroPrev" aria-label="Slide précédente"><x-icon name="chev-left" :size="16" :stroke="2.6" /></button>
    <button class="hero-arrow next" id="heroNext" aria-label="Slide suivante"><x-icon name="chev-right" :size="16" :stroke="2.6" /></button>
    <div class="hero-dots" id="heroDots"><button class="on" aria-label="Slide 1"></button><button aria-label="Slide 2"></button><button aria-label="Slide 3"></button></div>
    <div class="hero-progress"><i id="heroBar"></i></div>
</section>

{{-- ===== CATÉGORIES ===== --}}
<section class="container fcats" id="categories">
    @foreach ($categories as $i => $c)
        <a href="{{ route('shop', ['cat' => $c['slug']]) }}" class="fcat reveal" style="--d: {{ $i * 60 }}ms">
            <span class="fcat-c"><img src="{{ asset('img/clean/cat-'.($i + 1).'.jpg') }}" alt="{{ $c['name'] }}" loading="lazy"></span>
            <span class="fcat-n">{{ $c['name'] }}</span>
        </a>
    @endforeach
</section>

{{-- ===== VENTES FLASH ===== --}}
<section class="container fsec flash" id="flash">
    <div class="flash-h reveal">
        <div class="fl-t"><span class="bolt">⚡</span><h2>Ventes Flash</h2></div>
        <div class="fl-time">Se termine dans <div class="cd" data-countdown><b data-h>00</b><i>:</i><b data-m>00</b><i>:</i><b data-s>00</b></div></div>
        <a href="{{ route('deals') }}" class="fl-all">Voir tout <x-icon name="arrow-right" :size="15" :stroke="2.2" /></a>
    </div>
    <div class="fgrid flashgrid">
        @foreach ($flash as $p)
            <x-product-card :p="$p" :flash="true" />
        @endforeach
    </div>
</section>

{{-- ===== BANNIÈRES 1 ===== --}}
<section class="container fban2">
    <a href="{{ route('shop', ['cat' => 'entretien']) }}" class="fb fb-peach2 reveal wipe">
        <div class="fb-t"><h3>Entretien de la maison<br>à prix déstockés</h3><p>Nettoyants, désinfectants et accessoires.<br>Une maison éclatante, sans effort.</p><span class="fb-btn green">Acheter <x-icon name="arrow-right" :size="15" :stroke="2.2" /></span></div>
        <img class="fb-img" src="{{ asset('img/photos/clean-test.jpg') }}" alt="" loading="lazy">
        <div class="fb-disc"><small>JUSQU'À</small><b>30 %</b><span>DE REMISE</span></div>
    </a>
    <a href="{{ route('shop', ['cat' => 'bienetre']) }}" class="fb fb-cream reveal wipe" style="--d:100ms">
        <div class="fb-t"><h3>Soins &amp; bien-être<br>Plus de douceur</h3><span class="fb-btn green">Acheter les soins <x-icon name="arrow-right" :size="15" :stroke="2.2" /></span></div>
        <img class="fb-img" src="{{ asset('img/photos/ban-soins.jpg') }}" alt="" loading="lazy">
        <div class="fb-script">Prenez soin<br>de vous<br><x-icon name="heart" :size="16" :stroke="1.6" /></div>
    </a>
</section>

{{-- ===== PRODUITS À LA UNE ===== --}}
<section class="container fsec" id="featured">
    <div class="fsec-h reveal"><h2>Produits à la une</h2><a href="{{ route('shop') }}" class="ul-link">Voir tous les produits <x-icon name="arrow-right" :size="15" :stroke="2" /></a></div>
    <div class="fgrid">
        @foreach ($featured as $p)
            <x-product-card :p="$p" />
        @endforeach
    </div>
</section>

{{-- ===== BANNIÈRES 2 ===== --}}
<section class="container fban3">
    <a href="{{ route('shop', ['cat' => 'lessive']) }}" class="fb fb-pink reveal wipe">
        <div class="fb-t"><h3>Linge propre<br>Parfum de frais</h3><span class="fb-btn green">Acheter les lessives <x-icon name="arrow-right" :size="15" :stroke="2.2" /></span></div>
        <img class="fb-img" src="{{ asset('img/photos/hero-linge.jpg') }}" alt="" loading="lazy">
    </a>
    <a href="{{ route('deals') }}" class="fb fb-gold reveal wipe" style="--d:100ms">
        <div class="fb-t"><h3><span>Jusqu'à</span><b>50 % de remise</b></h3><p>Sur les offres combo</p><span class="fb-btn white">Voir les offres <x-icon name="arrow-right" :size="15" :stroke="2.2" /></span></div>
        <img class="fb-img" src="{{ asset('img/mock/promo-4.jpg') }}" alt="" loading="lazy">
        <div class="fb-pct">%</div>
    </a>
    <a href="{{ route('info', 'about') }}" class="fb fb-light reveal wipe" style="--d:200ms">
        <div class="fb-t"><h3>Entretien responsable<br>Une planète plus verte</h3><p>Des choix durables<br>pour un avenir meilleur.</p><span class="fb-btn green">En savoir plus <x-icon name="arrow-right" :size="15" :stroke="2.2" /></span></div>
        <img class="fb-img" src="{{ asset('img/fresh/ban-5.jpg') }}" alt="" loading="lazy">
    </a>
</section>

{{-- ===== CHIFFRES ===== --}}
<section class="container fsec">
    <div class="stats reveal">
        <div class="bubbles" data-bubbles="10" aria-hidden="true"></div>
        <div class="stat"><b><span data-countto="10000" data-suffix="+">0</span></b><small>clients satisfaits</small></div>
        <div class="stat"><b><span data-countto="500" data-suffix="+">0</span></b><small>produits d'entretien et de bien-être</small></div>
        <div class="stat"><b><span data-countto="48" data-suffix=" h">0</span></b><small>délai de livraison express</small></div>
        <div class="stat"><b><span data-countto="100" data-suffix=" %">0</span></b><small>produits authentiques</small></div>
    </div>
</section>

{{-- ===== TEST DE PROPRETÉ ===== --}}
<section class="container fsec">
    <div class="cleantest reveal">
        <div class="ct-txt">
            <span class="pill">Essayez !</span>
            <h2>Le test de propreté <span class="spark">✨</span></h2>
            <p>Passez la souris (ou le doigt) sur la vitre sale pour la nettoyer et découvrir nos produits d'entretien.</p>
            <div class="ct-meter"><i id="ctBar"></i></div>
            <small id="ctPct">0 % nettoyé</small>
            <a href="{{ route('shop', ['cat' => 'entretien']) }}" class="fh-btn ct-cta" id="ctCta">Voir les produits d'entretien <x-icon name="arrow-right" :size="16" :stroke="2.2" /></a>
        </div>
        <div class="ct-stage" id="ctStage">
            <img src="{{ asset('img/photos/hero-entretien.jpg') }}" alt="Produits d'entretien">
            <canvas id="ctCanvas" aria-label="Zone à nettoyer"></canvas>
            <div class="ct-hint" id="ctHint">Frottez ici ✨</div>
        </div>
    </div>
</section>

{{-- ===== MARQUES (défilement continu) ===== --}}
<section class="container fsec">
    <div class="fsec-h reveal"><h2>Nos marques de confiance</h2></div>
    <div class="marquee" aria-label="Marques">
        <div class="marquee-in">
            @foreach ([1, 2] as $loop2)
                @foreach ($brands as $b)<div class="mq-item"><x-brand-logo :b="$b" /></div>@endforeach
            @endforeach
        </div>
    </div>
</section>

{{-- ===== POURQUOI NOUS CHOISIR ===== --}}
<section class="container fsec why">
    <h2 class="reveal">Pourquoi choisir Makerade Destockage ?</h2>
    <div class="why-grid">
        @foreach ([['grid', 'Large choix', "Entretien et bien-être au même endroit"], ['leaf', 'Qualité garantie', 'Des marques reconnues et authentiques'], ['truck', 'Livraison rapide', "Partout en Côte d'Ivoire"], ['refresh', 'Retours faciles', 'Des achats sans souci'], ['chat', 'Support 24h/24', 'Toujours là pour vous']] as $i => [$ic, $t, $d])
            <div class="why-i reveal" style="--d: {{ $i * 70 }}ms"><span class="why-c"><x-icon :name="$ic" :size="26" :stroke="1.5" /></span><div><b>{{ $t }}</b><span>{{ $d }}</span></div></div>
        @endforeach
    </div>
</section>
@endsection
