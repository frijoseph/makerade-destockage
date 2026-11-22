@extends('admin.layouts.app')

@section('title', 'Message de '.$message->name)
@section('heading', $message->subject ?: 'Message de '.$message->name)
@section('subheading')Reçu le {{ $message->created_at->translatedFormat('l j F Y à H:i') }}@endsection
@section('actions')
    <a href="{{ route('admin.messages.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Messages</a>
    @if ($message->email)<a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Votre message')) }}" class="btn btn-primary"><x-admin.icon name="mail" :size="18" /> Répondre par e-mail</a>@endif
@endsection

@section('content')
<section class="card" style="max-width: 820px">
    <div class="customer-head">
        <span class="avatar">{{ mb_strtoupper(mb_substr($message->name, 0, 1)) }}</span>
        <div><h2>{{ $message->name }}</h2><small class="muted">{{ collect([$message->phone ? $shop->phone($message->phone) : null, $message->email])->filter()->implode(' · ') }}</small></div>
    </div>
    <div class="contact-actions" style="display:flex;gap:10px;flex-wrap:wrap;margin:6px 0 14px">
        @if ($message->phone)
            <a class="btn btn-sm" href="tel:{{ $message->phone }}"><x-admin.icon name="phone" :size="16" /> Appeler</a>
            <a class="btn btn-sm" href="https://wa.me/{{ preg_replace('/\D/', '', $message->phone) }}" target="_blank" rel="noopener"><x-admin.icon name="whatsapp" :size="16" /> WhatsApp</a>
        @endif
    </div>
    <p style="white-space: pre-line; margin-top: 12px">{{ $message->message }}</p>
    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Supprimer ce message ?" style="margin-top: 22px">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm"><x-admin.icon name="trash" :size="16" /> Supprimer</button>
    </form>
</section>
@endsection
