@extends('layouts.app')

@section('title', 'WhatsApp OTP — service auto-hébergé')
@section('body-class', 'app-page')

@section('content')
<div class="app-shell">
    <div class="app-hero">
        <p class="eyebrow">Service auto-hébergé</p>
        <h1>Codes OTP par WhatsApp,<br>sans API Business Meta</h1>
        <p>
            Envoyez des codes de vérification par WhatsApp depuis votre application,
            via un seul endpoint transactionnel — pas de compte Business à valider,
            pas de template à faire approuver.
        </p>
        <div style="display:flex; gap:.85rem; justify-content:center; margin-top:1.75rem; flex-wrap:wrap;">
            <a href="{{ route('docs') }}" class="btn-outline" style="text-decoration:none; padding:.7rem 1.5rem; font-size:.9rem;">Documentation</a>
            <a href="{{ route('login') }}" class="btn-pill" style="text-decoration:none;">Connexion admin</a>
        </div>
    </div>

    <div class="note">
        Ce service parle à WhatsApp via <code>whatsapp-web.js</code>, une librairie
        <strong>non officielle</strong> qui simule un client WhatsApp Web — ce n'est
        pas l'API officielle Meta Cloud API. Risque de ban du numéro non nul, pas de
        garantie de délivrance contractuelle. N'utilisez jamais un numéro personnel
        ou professionnel principal. Détails dans la
        <a href="{{ route('docs') }}#limites">documentation</a>.
    </div>

    <div class="card-grid">
        <div class="feature-card" style="cursor:default;">
            <div class="icon">🔌</div>
            <h3>Un seul endpoint</h3>
            <p><code>POST /api/otp/send</code>, protégé par une clé API — envoyez un message à un numéro, c'est tout.</p>
        </div>
        <div class="feature-card" style="cursor:default;">
            <div class="icon">📱</div>
            <h3>QR code &amp; dashboard</h3>
            <p>Liez un numéro WhatsApp en scannant un QR code, suivez le statut de la session en temps réel.</p>
        </div>
        <div class="feature-card" style="cursor:default;">
            <div class="icon">🔒</div>
            <h3>Auto-hébergé</h3>
            <p>Laravel + sidecar Node.js, vos données restent chez vous — rien ne transite par un tiers.</p>
        </div>
    </div>

    <div class="panel">
        <div class="panel-titlebar">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="label">Exemple d'appel</span>
        </div>
        <div class="panel-body">
            <x-code-block lang="bash">curl -X POST {{ url('/api/otp/send') }} \
  -H "X-Api-Key: &lt;votre clé&gt;" \
  -H "Content-Type: application/json" \
  -d '{"phone": "33612345678", "message": "Votre code est 123456"}'</x-code-block>
            <p style="margin-top:1rem;">
                Guide d'intégration complet (Laravel, gestion des codes, tests) dans la
                <a href="{{ route('docs') }}">documentation</a>.
            </p>
        </div>
    </div>
</div>
@endsection
