<section class="phead">
    <div class="container">
        <nav class="crumbs" aria-label="Fil d'Ariane"><a href="{{ route('home') }}">Accueil</a><x-icon name="chev-right" :size="14" /><span>{{ $title }}</span></nav>
        <h1 class="reveal">{{ $title }}</h1>
        @isset($sub)<p class="reveal">{{ $sub }}</p>@endisset
    </div>
</section>
