@extends('layouts.app')

@section('title', 'Clés API')
@section('body-class', 'app-page')

@section('content')
<div class="app-shell">
    <div class="app-hero">
        <p class="eyebrow">Accès programmatique</p>
        <h1>Clés API</h1>
        <p>
            Utilisez une clé dans le header <code>X-Api-Key</code> pour appeler
            <code>POST /api/otp/send</code> — voir la
            <a href="{{ route('docs') }}">documentation complète</a>.
        </p>
    </div>

    @if (session('new_api_key'))
        <div class="panel glow-ready">
            <div class="panel-titlebar">
                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                <span class="label">Nouvelle clé générée</span>
            </div>
            <div class="panel-body">
                <p style="margin-bottom:.75rem;">Copiez-la maintenant — elle ne sera plus jamais affichée :</p>
                <code style="font-size:1.05rem; padding:.5rem .8rem;">{{ session('new_api_key') }}</code>
            </div>
        </div>
    @endif

    <div class="panel">
        <div class="panel-titlebar">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="label">Vos clés</span>
        </div>
        <div class="panel-body">
            @if ($apiKeys->isEmpty())
                <p>Aucune clé pour l'instant.</p>
            @else
                @foreach ($apiKeys as $key)
                    <div class="key-row">
                        <div>
                            <strong>{{ $key->name }}</strong>
                            <div class="meta">
                                Créée le {{ $key->created_at->format('Y-m-d H:i') }}
                                · dernière utilisation : {{ $key->last_used_at?->diffForHumans() ?? 'jamais' }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('api-keys.destroy', $key) }}" onsubmit="return confirm('Révoquer cette clé ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-outline">Révoquer</button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-titlebar">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="label">Générer une nouvelle clé</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('api-keys.store') }}" style="display:flex; gap:.75rem;">
                @csrf
                <input type="text" name="name" placeholder="Nom de la clé (ex: app-mobile)" required style="flex:1;">
                <button type="submit" class="btn-pill">Générer</button>
            </form>
        </div>
    </div>
</div>
@endsection
