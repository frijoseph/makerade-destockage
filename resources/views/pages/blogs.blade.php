@extends('layouts.app')
@section('title', 'Blog — Makerade Destockage')

@section('content')
@php
    $title = 'Notre blog'; $sub = 'Conseils, guides et idées pour mieux vivre au quotidien.';
    $posts = [
        ['Entretien', '10 astuces pour garder votre maison impeccable', 'Des routines simples qui vous font gagner du temps chaque semaine.', '#e6f3e1', 'spray'],
        ['Soins', 'Construire une routine de soins douce au quotidien', 'Ce qu\'il faut rechercher dans vos produits d\'hygiène.', '#fdf1d6', 'pump'],
        ['Cuisine', 'Les indispensables de cuisine pour chaque foyer', 'Cuisinez et nettoyez plus facilement avec les bons outils.', '#e1eef6', 'utensils'],
        ['Économies', 'Économisez sur vos courses du mois', 'Offres combo, achats en gros et conseils malins.', '#fbe6ec', 'tag'],
        ['Maison', 'Organisez votre salle de bain en un week-end', 'Désencombrez et rafraîchissez avec quelques achats malins.', '#f1ece3', 'dispenser'],
        ['Éco', 'Des habitudes plus vertes pour la maison', 'De petits changements pour un grand impact.', '#e6f3e1', 'smile'],
    ];
@endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="posts">
        @foreach ($posts as $i => [$tag, $t, $d, $bg, $ic])
        <article class="post reveal" style="--d: {{ $i * 70 }}ms">
            <div class="post-img" style="background: {{ $bg }}"><x-icon :name="$ic" :size="64" :stroke="1.2" /></div>
            <div class="post-b"><span class="pill">{{ $tag }}</span><h3>{{ $t }}</h3><p>{{ $d }}</p><a href="#" class="link-all">Lire la suite <x-icon name="arrow-right" :size="16" /></a></div>
        </article>
        @endforeach
    </div>
</section>
@endsection
