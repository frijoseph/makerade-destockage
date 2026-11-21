@extends('admin.layouts.app')

@section('title', 'Message de '.$message->name)
@section('heading', $message->subject ?: 'Message de '.$message->name)
@section('subheading')Reçu le {{ $message->created_at->translatedFormat('l j F Y à H:i') }}@endsection
@section('actions')
    <a href="{{ route('admin.messages.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Messages</a>
    <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Votre message')) }}" class="btn btn-primary"><x-admin.icon name="mail" :size="18" /> Répondre</a>
@endsection

@section('content')
<section class="card" style="max-width: 820px">
    <div class="customer-head">
        <span class="avatar">{{ mb_strtoupper(mb_substr($message->name, 0, 1)) }}</span>
        <div><h2>{{ $message->name }}</h2><small class="muted">{{ $message->email }}</small></div>
    </div>
    <p style="white-space: pre-line; margin-top: 12px">{{ $message->message }}</p>
    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Supprimer ce message ?" style="margin-top: 22px">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm"><x-admin.icon name="trash" :size="16" /> Supprimer</button>
    </form>
</section>
@endsection
