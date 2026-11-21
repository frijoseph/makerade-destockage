@extends('layouts.app')
@section('title', 'Blog')

@section('content')
@php $title = 'Notre blog'; $sub = 'Conseils, guides et idées pour mieux vivre au quotidien.'; @endphp
@include('partials.page-head')
<section class="container section tight">
    @if ($posts->isEmpty())
        <div class="empty"><x-icon name="leaf" :size="44" :stroke="1.3" /><h3>Aucun article pour l’instant</h3><p>Revenez bientôt pour nos conseils.</p></div>
    @else
        <div class="posts">
            @foreach ($posts as $i => $post)
                <article class="post reveal" style="--d: {{ $i * 70 }}ms">
                    <a href="{{ $post->url() }}" class="post-img" style="background: {{ $post->cover_color ?? '#f1f1f1' }}">
                        @if ($post->cover)<img src="{{ $shop->imageUrl($post->cover) }}" alt="" loading="lazy">@else<x-icon :name="$post->icon ?? 'leaf'" :size="64" :stroke="1.2" />@endif
                    </a>
                    <div class="post-b">
                        @if ($post->tag)<span class="pill">{{ $post->tag }}</span>@endif
                        <h3><a href="{{ $post->url() }}">{{ $shop->interpolate($post->title) }}</a></h3>
                        <p>{{ $shop->interpolate($post->excerpt) }}</p>
                        <a href="{{ $post->url() }}" class="link-all">Lire la suite <x-icon name="arrow-right" :size="16" /></a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
