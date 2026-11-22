@extends('admin.layouts.app')

@section('title', 'Conversation avec l’assistant')
@section('heading', \Illuminate\Support\Str::limit($conversation->summary ?? 'Conversation', 60))
@section('subheading')Du {{ $conversation->created_at->translatedFormat('j F Y à H:i') }} · {{ $conversation->messages_count }} message(s) · {{ $conversation->user?->name ?? 'Visiteur non connecté' }}@endsection
@section('actions')
    <a href="{{ route('admin.assistant.index') }}" class="btn"><x-admin.icon name="chev-left" :size="18" /> Conversations</a>
@endsection

@section('content')
<section class="card" style="max-width: 820px">
    <div class="ai-thread">
        @foreach ($conversation->messages as $line)
            <div @class(['ai-line', 'is-user' => $line->role === 'user'])>
                <small>{{ $line->role === 'user' ? 'Visiteur' : 'Assistant' }} · {{ $line->created_at->format('H:i') }}@if (! empty($line->meta['fallback'])) · <b>message de secours</b>@endif</small>
                <p>{{ $line->content }}</p>
            </div>
        @endforeach
    </div>
    <form method="POST" action="{{ route('admin.assistant.destroy', $conversation) }}" data-confirm="Supprimer cette conversation ?" style="margin-top: 22px">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm"><x-admin.icon name="trash" :size="16" /> Supprimer</button>
    </form>
</section>
@endsection
