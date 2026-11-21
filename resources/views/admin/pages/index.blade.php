@extends('admin.layouts.app')
@php use App\PageType; @endphp

@section('title', $type->pluralLabel())
@section('heading', 'Contenus')
@section('subheading', 'Pages d’information (livraison, retours, CGV…) et articles du blog.')
@section('actions')
    <a href="{{ route('admin.pages.create', ['type' => $type->value]) }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> {{ $type === PageType::Article ? 'Nouvel article' : 'Nouvelle page' }}</a>
@endsection

@section('content')
<nav class="tabs" aria-label="Type de contenu">
    @foreach (PageType::cases() as $case)
        <a href="{{ route('admin.pages.index', ['type' => $case->value]) }}" @class(['is-active' => $case === $type])>{{ $case->pluralLabel() }} <em>{{ $counts[$case->value] ?? 0 }}</em></a>
    @endforeach
</nav>

<section class="card">
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Titre</th><th>Adresse</th>@if ($type === PageType::Article)<th data-filter>Rubrique</th><th>Publication</th>@endif<th data-filter>État</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($pages as $page)
                <tr data-href="{{ route('admin.pages.edit', $page) }}">
                    <td><a href="{{ route('admin.pages.edit', $page) }}" class="strong">{{ \App\Models\Setting::interpolate($page->title) }}</a><small>{{ \Illuminate\Support\Str::limit($page->excerpt, 80) }}</small></td>
                    <td><a class="link" href="{{ $page->url() }}" target="_blank" rel="noopener">{{ parse_url($page->url(), PHP_URL_PATH) }}</a></td>
                    @if ($type === PageType::Article)
                        <td>{{ $page->tag ?? '—' }}</td>
                        <td class="nowrap">{{ $page->published_at?->translatedFormat('j M Y') ?? '—' }}</td>
                    @endif
                    <td>@if ($page->is_published)<x-admin.badge tone="green">Publié</x-admin.badge>@else<x-admin.badge tone="slate">Brouillon</x-admin.badge>@endif</td>
                    <td class="actions">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="icon-btn" aria-label="Modifier"><x-admin.icon name="edit" :size="17" /></a>
                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="Supprimer « {{ $page->title }} » ?">
                            @csrf @method('DELETE')
                            <button class="icon-btn" aria-label="Supprimer"><x-admin.icon name="trash" :size="17" /></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aucun contenu pour l’instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
