@extends('admin.layouts.app')

@section('title', 'Marques')
@section('heading', 'Marques')
@section('subheading', 'Les marques affichées sur la boutique et dans le bandeau des marques.')
@section('actions')
    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary"><x-admin.icon name="plus" :size="18" :stroke="2.4" /> Nouvelle marque</a>
@endsection

@section('content')
<section class="card">
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Marque</th><th data-filter>Style du logo</th><th class="num" data-sort>Produits</th><th data-filter>Visibilité</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($brands as $brand)
                <tr data-href="{{ route('admin.brands.edit', $brand) }}">
                    <td><span class="swatch" style="background: {{ $brand->color }}"></span><a href="{{ route('admin.brands.edit', $brand) }}" class="strong">{{ $brand->name }}</a></td>
                    <td>{{ $brand->style }}</td>
                    <td class="num">{{ $brand->products_count }}</td>
                    <td>@if ($brand->is_active)<x-admin.badge tone="green">En ligne</x-admin.badge>@else<x-admin.badge tone="slate">Masquée</x-admin.badge>@endif</td>
                    <td class="actions">
                        <a href="{{ route('admin.brands.edit', $brand) }}" class="icon-btn" aria-label="Modifier {{ $brand->name }}"><x-admin.icon name="edit" :size="17" /></a>
                        <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" data-confirm="Supprimer la marque « {{ $brand->name }} » ? Ses {{ $brand->products_count }} produit(s) resteront, sans marque.">
                            @csrf @method('DELETE')
                            <button class="icon-btn" aria-label="Supprimer {{ $brand->name }}"><x-admin.icon name="trash" :size="17" /></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Aucune marque.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
