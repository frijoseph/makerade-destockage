@extends('admin.layouts.app')

@section('title', 'Catégories')
@section('heading', 'Catégories')
@section('subheading', 'Les rayons de la boutique, dans l’ordre d’affichage du menu.')
@section('actions')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Nouvelle catégorie</a>
@endsection

@section('content')
<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ordre</th><th>Catégorie</th><th class="num">Produits</th><th class="num">Unités en stock</th><th>Visibilité</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr data-href="{{ route('admin.categories.edit', $category) }}">
                    <td>{{ $category->position }}</td>
                    <td><span class="swatch" style="background: {{ $category->bg }}"></span><a href="{{ route('admin.categories.edit', $category) }}" class="strong">{{ $category->name }}</a><small>/shop?cat={{ $category->slug }}</small></td>
                    <td class="num"><a class="link" href="{{ route('admin.products.index', ['category' => $category->id]) }}">{{ $category->products_count }}</a></td>
                    <td class="num">{{ (int) $category->stock }}</td>
                    <td>@if ($category->is_active)<x-admin.badge tone="green">En ligne</x-admin.badge>@else<x-admin.badge tone="slate">Masquée</x-admin.badge>@endif</td>
                    <td class="actions">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="icon-btn" aria-label="Modifier {{ $category->name }}"><x-admin.icon name="edit" :size="17" /></a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Supprimer la catégorie « {{ $category->name }} » ?">
                            @csrf @method('DELETE')
                            <button class="icon-btn" aria-label="Supprimer {{ $category->name }}" @disabled($category->products_count) title="{{ $category->products_count ? 'Contient des produits' : 'Supprimer' }}"><x-admin.icon name="trash" :size="17" /></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aucune catégorie.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
