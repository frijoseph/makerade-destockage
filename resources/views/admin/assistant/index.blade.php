@extends('admin.layouts.app')

@section('title', 'Assistant IA')
@section('heading', 'Assistant IA')
@section('subheading', 'Les discussions de vos visiteurs avec l’assistant, et son bon fonctionnement.')
@section('actions')
    <a href="{{ route('admin.settings.edit', 'assistant') }}" class="btn btn-primary"><x-admin.icon name="settings" :size="17" /> Paramétrer l’assistant</a>
@endsection

@section('content')
<section class="stat-grid">
    <x-admin.stat tone="violet" icon="sparkles" label="Conversations aujourd’hui" :value="$stats['today']" />
    <x-admin.stat tone="blue" icon="mail" label="Questions aujourd’hui" :value="$stats['messages']" :hint="$limit ? 'plafond '.$limit.' / jour' : 'sans plafond'" />
    <x-admin.stat tone="green" icon="users" label="Conversations enregistrées" :value="$stats['total']" />
    <x-admin.stat :tone="$stats['failed'] ? 'red' : 'slate'" icon="alert" label="Réponses de secours (7 j)" :value="$stats['failed']" />
</section>

@if (! $enabled || ($modelChosen && ! $usesModel))
    <section class="card" style="border-left: 4px solid var(--accent)">
        <b>{{ ! $enabled ? 'L’assistant est désactivé.' : 'Mode IA choisi mais aucune clé API : le robot intégré répond à la place.' }}</b>
        <p class="muted" style="margin: 4px 0 0">{{ ! $enabled ? 'Activez-le dans les paramètres pour l’afficher aux visiteurs.' : 'Enregistrez une clé (paramètres ou AI_API_KEY dans .env), ou repassez en « Robot intégré » (sans API).' }}</p>
    </section>
@endif

<div class="grid-side">
    <section class="card">
        <header class="card-head">
            <h2>Conversations</h2>
            <form method="GET" class="toolbar" data-filters>
                <label class="search grow"><x-admin.icon name="search" :size="18" /><span class="sr-only">Rechercher</span><input type="search" name="q" value="{{ request('q') }}" data-label="Recherche" placeholder="Mot dans la conversation" autocomplete="off"></label>
            </form>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Première question</th><th>Visiteur</th><th class="num">Messages</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @forelse ($conversations as $conversation)
                    <tr data-href="{{ route('admin.assistant.show', $conversation) }}">
                        <td>{{ $conversation->last_message_at?->translatedFormat('j M Y') }}<small>{{ $conversation->last_message_at?->format('H:i') }}</small></td>
                        <td><a href="{{ route('admin.assistant.show', $conversation) }}" class="strong">{{ \Illuminate\Support\Str::limit($conversation->summary ?? '—', 70) }}</a></td>
                        <td>{{ $conversation->user?->name ?? 'Visiteur' }}</td>
                        <td class="num">{{ $conversation->messages_count }}</td>
                        <td class="actions"><a href="{{ route('admin.assistant.show', $conversation) }}" class="icon-btn" aria-label="Voir la conversation"><x-admin.icon name="eye" :size="17" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Aucune conversation pour l’instant.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $conversations->links() }}
    </section>

    <div class="stack">
        <section class="card">
            <header class="card-head"><h2>Tester l’assistant</h2></header>
            <form class="form" data-ai-test action="{{ route('admin.assistant.test') }}">
                <label class="field"><span>Question</span><textarea name="message" rows="3" required maxlength="500" placeholder="Ex. Avez-vous de l’eau de Javel ?"></textarea></label>
                <button class="btn btn-primary block">Envoyer le test</button>
                <div class="ai-test-out" data-ai-out hidden></div>
            </form>
            <small class="muted">Utilise les paramètres actuels. Rien n’est enregistré.</small>
        </section>

        <section class="card">
            <header class="card-head"><h2>Conservation</h2></header>
            <p class="muted" style="margin: -6px 0 12px">Les conversations sont conservées {{ (int) \App\Models\Setting::get('ai_retention_days') ?: 'sans limite de' }} {{ (int) \App\Models\Setting::get('ai_retention_days') ? 'jour(s)' : 'durée' }} (supprimées automatiquement chaque jour).</p>
            <form method="POST" action="{{ route('admin.assistant.purge') }}" data-confirm-title="Purger maintenant ?" data-confirm-button="Oui, purger" data-confirm="Supprimer les conversations plus anciennes que la durée de conservation ?">
                @csrf
                <button class="btn block">Purger les anciennes conversations</button>
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-ai-test]'), out = document.querySelector('[data-ai-out]');
    if (!form) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = form.querySelector('button'); btn.disabled = true; out.hidden = false; out.className = 'ai-test-out'; out.textContent = 'L’assistant réfléchit…';
        try {
            const res = await fetch(form.action, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': MKUI.csrf() }, body: JSON.stringify({ message: form.message.value }) });
            const data = await res.json();
            out.textContent = data.ok ? data.reply + '\n\n— ' + data.products + ' produit(s) et ' + data.context_chars + ' caractères de contexte transmis.' : (data.message || (data.errors && Object.values(data.errors)[0][0]) || 'Erreur.');
            out.classList.toggle('is-error', !data.ok);
        } catch (err) { out.textContent = 'Connexion impossible.'; out.classList.add('is-error'); }
        btn.disabled = false;
    });
});
</script>
@endpush
