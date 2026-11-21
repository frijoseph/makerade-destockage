@extends('admin.layouts.app')

@section('title', 'Messages')
@section('heading', 'Messages')
@section('subheading', 'Messages reçus depuis la page Contact et inscriptions à la newsletter.')

@section('content')
<nav class="tabs" aria-label="Messages">
    <a href="{{ route('admin.messages.index') }}" class="is-active">Messages <em>{{ $messages->total() }}</em></a>
    <a href="{{ route('admin.subscribers.index') }}">Newsletter <em>{{ $subscriberCount }}</em></a>
</nav>

<section class="card">
    <div class="table-wrap">
        <table class="table" data-smart>
            <thead><tr><th data-sort>Reçu le</th><th>De</th><th>Sujet</th><th data-filter>État</th></tr></thead>
            <tbody>
            @forelse ($messages as $message)
                <tr data-href="{{ route('admin.messages.show', $message) }}">
                    <td class="nowrap">{{ $message->created_at->translatedFormat('j M Y H:i') }}</td>
                    <td><a href="{{ route('admin.messages.show', $message) }}" @class(['strong' => ! $message->read_at])>{{ $message->name }}</a><small>{{ $message->email }}</small></td>
                    <td>{{ $message->subject ?: \Illuminate\Support\Str::limit($message->message, 60) }}</td>
                    <td>@if ($message->read_at)<x-admin.badge tone="slate">Lu</x-admin.badge>@else<x-admin.badge tone="violet">Nouveau</x-admin.badge>@endif</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">Aucun message reçu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $messages->links() }}
</section>
@endsection
