@extends('layouts.app')
@section('title', $shop->interpolate($post->title))

@section('content')
@php $title = $shop->interpolate($post->title); $sub = $post->published_at?->translatedFormat('j F Y').($post->tag ? ' · '.$post->tag : ''); @endphp
@include('partials.page-head')
<section class="container section tight">
    @if ($post->cover)
        <img class="article-cover reveal" src="{{ $shop->imageUrl($post->cover) }}" alt="">
    @endif
    <div class="prose rich reveal">
        {{ $post->renderedBody() }}
    </div>

    @if ($others->isNotEmpty())
        <div class="sec-head reveal" style="margin-top: 56px"><h2>À lire aussi</h2></div>
        <div class="posts">
            @foreach ($others as $other)
                <article class="post reveal">
                    <a href="{{ $other->url() }}" class="post-img" style="background: {{ $other->cover_color ?? '#f1f1f1' }}">
                        @if ($other->cover)<img src="{{ $shop->imageUrl($other->cover) }}" alt="" loading="lazy">@else<x-icon :name="$other->icon ?? 'leaf'" :size="64" :stroke="1.2" />@endif
                    </a>
                    <div class="post-b">
                        @if ($other->tag)<span class="pill">{{ $other->tag }}</span>@endif
                        <h3><a href="{{ $other->url() }}">{{ $shop->interpolate($other->title) }}</a></h3>
                        <p>{{ $shop->interpolate($other->excerpt) }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
