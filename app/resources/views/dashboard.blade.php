@extends('layouts.app')

@section('title', 'Dashboard')
@section('body-class', 'app-page')

@php
    $badgeClass = match ($status['status'] ?? null) {
        'READY' => 'ready',
        'QR_READY' => 'qr',
        'DISCONNECTED', 'FAILED', 'UNREACHABLE' => 'down',
        default => 'other',
    };
    $glowClass = match ($status['status'] ?? null) {
        'READY' => 'glow-ready',
        'QR_READY' => 'glow-qr',
        default => 'glow-down',
    };
@endphp

@if (($status['status'] ?? null) !== 'READY')
    <meta http-equiv="refresh" content="5">
@endif

@section('content')
<div class="app-shell">
    <div class="app-hero">
        <p class="eyebrow">Instance auto-hébergée</p>
        <h1>Tableau de bord</h1>
        <p>Pilotez la session WhatsApp connectée et gérez l'accès à l'API d'envoi.</p>
    </div>

    @if (session('status_message'))
        <div class="flash">{{ session('status_message') }}</div>
    @endif
    @if (session('status_error'))
        <div class="flash" style="background:#dc26261a; border-color:#dc262655;">{{ session('status_error') }}</div>
    @endif

    <div class="panel {{ $glowClass }}">
        <div class="panel-titlebar">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="label">Session WhatsApp</span>
            <span class="badge {{ $badgeClass }}">{{ $status['status'] ?? 'INCONNU' }}</span>
        </div>
        <div class="panel-body">
            <p>
                @if (!empty($status['phone']))
                    Numéro lié : <strong>+{{ $status['phone'] }}</strong>
                @else
                    Aucun numéro lié pour le moment.
                @endif
            </p>

            @if (($status['status'] ?? null) === 'QR_READY' && $qr)
                <p>Scannez ce QR code avec WhatsApp (Appareils liés → Lier un appareil) :</p>
                <img class="qr" src="{{ $qr }}" alt="QR code WhatsApp">
            @elseif (($status['status'] ?? null) === 'UNREACHABLE')
                <p class="error">Impossible de joindre le service WhatsApp interne. Vérifiez qu'il tourne bien (voir README).</p>
            @elseif (($status['status'] ?? null) !== 'READY')
                <p>En attente du QR code…</p>
            @endif

            @if (($status['status'] ?? null) === 'READY')
                <form
                    method="POST"
                    action="{{ route('dashboard.disconnect') }}"
                    style="margin-top:1rem;"
                    onsubmit="return confirm('Déconnecter ce numéro WhatsApp ? Vous devrez scanner un nouveau QR code pour en lier un autre.');"
                >
                    @csrf
                    <button type="submit" class="btn-outline">Déconnecter &amp; changer de compte</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card-grid">
        <a href="{{ route('api-keys.index') }}" class="feature-card">
            <div class="icon">🔑</div>
            <h3>Clés API</h3>
            <p>Générez et révoquez les clés utilisées pour appeler l'API d'envoi.</p>
        </a>
        <a href="{{ route('docs') }}" class="feature-card">
            <div class="icon">📘</div>
            <h3>Documentation</h3>
            <p>Référence de l'endpoint et guide d'intégration Laravel complet.</p>
        </a>
    </div>
</div>
@endsection
