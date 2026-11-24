@extends('layouts.app')
@section('title', $shop->interpolate($page->title))

@section('content')
@php $title = $shop->interpolate($page->title); $sub = $page->excerpt ? $shop->interpolate($page->excerpt) : null; @endphp
@include('partials.page-head')
<section class="container section tight">
    <div class="prose rich reveal">
        {{ $page->renderedBody() }}
    </div>
</section>
@endsection
