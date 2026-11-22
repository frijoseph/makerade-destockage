@extends('admin.layouts.app')

@section('title', 'Newsletter')
@section('heading', 'Messages')
@section('subheading', 'Messages reçus depuis la page Contact et inscriptions à la newsletter.')
@section('actions')
    <a href="{{ route('admin.subscribers.export') }}" class="btn"><x-admin.icon name="arrow-down" :size="18" /> Exporter (CSV)</a>
@endsection

@section('content')
<nav class="tabs" aria-label="Messages">
    <a href="{{ route('admin.messages.index') }}">Messages</a>
    <a href="{{ route('admin.subscribers.index') }}" class="is-active">Newsletter <em>{{ $subscribers->total() }}</em></a>
</nav>

<section class="card">
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Téléphone</th><th data-sort>E-mail</th><th data-sort>Inscrit le</th></tr></thead>
            <tbody>
            @forelse ($subscribers as $subscriber)
                <tr><td>@if ($subscriber->phone)<a href="tel:{{ $subscriber->phone }}">{{ $shop->phone($subscriber->phone) }}</a>@else — @endif</td><td>@if ($subscriber->email)<a href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a>@else — @endif</td><td>{{ $subscriber->created_at->translatedFormat('j M Y') }}</td></tr>
            @empty
                <tr><td colspan="3" class="empty">Aucun abonné pour l’instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $subscribers->links() }}
</section>
@endsection
