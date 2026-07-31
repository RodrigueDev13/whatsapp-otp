@extends('layouts.app')

@section('title', 'Documentation API — WhatsApp OTP')
@section('body-class', 'docs-page')

@section('content')
<div class="docs-shell">
    <aside class="docs-sidebar" id="docs-sidebar">
        <div class="group-title">Démarrage</div>
        <ul>
            <li><a href="#apercu">Aperçu</a></li>
            <li><a href="#auth">Authentification</a></li>
            <li><a href="#endpoint">Référence de l'endpoint</a></li>
            <li><a href="#limites">Limites &amp; bonnes pratiques</a></li>
        </ul>

        <div class="group-title">Implémentation Laravel</div>
        <ul>
            <li><a href="#implementation">Vue d'ensemble</a></li>
            <li class="sub"><a href="#etape-1">1. Configuration</a></li>
            <li class="sub"><a href="#etape-2">2. Table des codes OTP</a></li>
            <li class="sub"><a href="#etape-3">3. Modèle OtpCode</a></li>
            <li class="sub"><a href="#etape-4">4. Service WhatsAppOtpService</a></li>
            <li class="sub"><a href="#etape-5">5. Contrôleur et routes</a></li>
            <li class="sub"><a href="#etape-6">6. Formulaire Blade</a></li>
            <li class="sub"><a href="#etape-7">7. Tests</a></li>
        </ul>

        <div class="group-title">Aide</div>
        <ul>
            <li><a href="#depannage">Dépannage</a></li>
        </ul>

        <div class="group-title">Liens rapides</div>
        <ul>
            <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li><a href="{{ route('api-keys.index') }}">Clés API</a></li>
        </ul>
    </aside>

    <main class="docs-main">
        <h1>Documentation API</h1>
        <p class="lead">
            Comment appeler l'API d'envoi WhatsApp de cette instance, et comment
            l'intégrer de bout en bout dans une application Laravel cliente.
        </p>

        <h2 id="apercu">Aperçu<a href="#apercu" class="heading-anchor">#</a></h2>
        <p>
            Ce service expose un seul endpoint transactionnel : envoyer un message
            WhatsApp (typiquement un code OTP) à un numéro donné, via la session
            WhatsApp connectée sur le <a href="{{ route('dashboard') }}">dashboard</a>.
            Il ne génère ni ne vérifie de code lui-même — c'est votre application (le
            « client ») qui génère le code, le stocke, et vérifie ce que l'utilisateur
            saisit. Cette page documente l'API elle-même, puis montre comment
            l'intégrer entièrement dans une application Laravel cliente.
        </p>

        <h2 id="auth">Authentification<a href="#auth" class="heading-anchor">#</a></h2>
        <p>
            Chaque requête doit porter un header <code>X-Api-Key</code> avec une clé
            générée depuis le <a href="{{ route('dashboard') }}">dashboard</a>
            (section « Clés API »). La clé n'est affichée qu'une seule fois à sa
            création — notez-la immédiatement.
        </p>
        <div class="note">
            Une clé donne accès à l'envoi de messages sur le numéro WhatsApp
            actuellement lié à cette instance. Traitez-la comme un secret : ne la
            commitez jamais dans un dépôt, passez-la par une variable d'environnement.
        </div>

        <h2 id="endpoint">Référence de l'endpoint<a href="#endpoint" class="heading-anchor">#</a></h2>
        <p><code>POST /api/otp/send</code></p>

        <x-code-block lang="http">Headers:
  X-Api-Key: &lt;votre clé&gt;
  Content-Type: application/json

Body (JSON):
{
  "phone":   "33612345678",           // requis, chiffres uniquement, "+" optionnel, 8 à 15 chiffres
  "message": "Votre code est 123456"  // requis, texte libre, 4096 caractères max
}</x-code-block>

        <table class="status-table">
            <tr><th>Code</th><th>Signification</th></tr>
            <tr><td><code>200</code></td><td>Message envoyé — réponse <code>{"success": true, "id": "..."}</code></td></tr>
            <tr><td><code>401</code></td><td>Clé API manquante ou invalide</td></tr>
            <tr><td><code>422</code></td><td>Numéro ou message invalide (voir <code>errors</code> dans la réponse)</td></tr>
            <tr><td><code>429</code></td><td>Trop de requêtes (limite : 30/minute, voir la section limites)</td></tr>
            <tr><td><code>503</code></td><td>Session WhatsApp non connectée (QR à scanner sur le dashboard)</td></tr>
        </table>

        <p>Exemple avec curl :</p>
        <x-code-block lang="bash">curl -X POST http://localhost:8090/api/otp/send \
  -H "X-Api-Key: wotp_xxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{"phone": "33612345678", "message": "Votre code est 123456"}'</x-code-block>

        <h2 id="limites">Limites &amp; bonnes pratiques<a href="#limites" class="heading-anchor">#</a></h2>
        <ul>
            <li><strong>Rate limit</strong> : 30 requêtes/minute par défaut sur cet endpoint (<code>routes/api.php</code> de ce projet).</li>
            <li><strong>Génération du code côté client</strong> : cette API transporte un texte libre — c'est à votre application de générer un code aléatoire, de le stocker (haché, jamais en clair) et de le faire expirer.</li>
            <li><strong>Expiration courte</strong> : 5 à 10 minutes est une durée raisonnable pour un OTP.</li>
            <li><strong>Limiter les tentatives de vérification</strong> : bloquez après quelques essais incorrects (ex. 5) pour empêcher le brute-force du code.</li>
            <li><strong>Un seul canal WhatsApp non officiel</strong> : gardez un canal de secours (email/SMS) pour les utilisateurs critiques — voir les avertissements du README du projet.</li>
        </ul>

        <h2 id="implementation">Implémentation côté Laravel (de A à Z)<a href="#implementation" class="heading-anchor">#</a></h2>
        <p>
            Exemple complet, dans une application Laravel <em>consommatrice</em> de
            cette API (une autre application que celle-ci), pour un flux « envoyer un
            code → vérifier le code saisi par l'utilisateur ».
        </p>

        <h3 id="etape-1">1. Configuration<a href="#etape-1" class="heading-anchor">#</a></h3>
        <p>Dans le <code>.env</code> de l'application cliente :</p>
        <x-code-block lang="env">WHATSAPP_OTP_URL=http://localhost:8090/api/otp/send
WHATSAPP_OTP_KEY=wotp_xxxxxxxx</x-code-block>

        <p>Dans <code>config/services.php</code> :</p>
        <x-code-block lang="php">'whatsapp_otp' => [
    'url' => env('WHATSAPP_OTP_URL'),
    'key' => env('WHATSAPP_OTP_KEY'),
],</x-code-block>

        <h3 id="etape-2">2. Table des codes OTP<a href="#etape-2" class="heading-anchor">#</a></h3>
        <x-code-block lang="bash">php artisan make:migration create_otp_codes_table</x-code-block>
        <x-code-block lang="php">Schema::create('otp_codes', function (Blueprint $table) {
    $table->id();
    $table->string('phone');
    $table->string('code_hash');
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->timestamp('expires_at');
    $table->timestamp('consumed_at')->nullable();
    $table->timestamps();

    $table->index('phone');
});</x-code-block>

        <h3 id="etape-3">3. Modèle OtpCode<a href="#etape-3" class="heading-anchor">#</a></h3>
        <x-code-block lang="bash">php artisan make:model OtpCode</x-code-block>
        <x-code-block lang="php">namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = ['phone', 'code_hash', 'attempts', 'expires_at', 'consumed_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'consumed_at' => 'datetime',
    ];
}</x-code-block>

        <h3 id="etape-4">4. Service WhatsAppOtpService<a href="#etape-4" class="heading-anchor">#</a></h3>
        <p>
            Génère un code, l'envoie via l'API, et vérifie ce que l'utilisateur saisit
            ensuite. Centraliser cette logique dans un service évite de la dupliquer
            dans plusieurs contrôleurs (inscription, connexion, changement de numéro,
            etc.).
        </p>
        <x-code-block lang="php">namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppOtpService
{
    private const CODE_LENGTH = 6;
    private const EXPIRES_IN_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function generateAndSend(string $phone): void
    {
        $code = (string) random_int(100000, 999999); // 6 chiffres

        OtpCode::create([
            'phone'      => $phone,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
        ]);

        $response = Http::withHeaders([
            'X-Api-Key' => config('services.whatsapp_otp.key'),
        ])->post(config('services.whatsapp_otp.url'), [
            'phone'   => $phone,
            'message' => "Votre code de vérification est : {$code}. Il expire dans " . self::EXPIRES_IN_MINUTES . ' minutes.',
        ]);

        if ($response->failed()) {
            Log::warning('Échec envoi OTP WhatsApp', [
                'phone'  => $phone,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            throw new RuntimeException(
                $response->json('error') ?? 'Impossible d\'envoyer le code WhatsApp pour le moment.'
            );
        }
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}</x-code-block>
        <div class="note">
            <code>random_int()</code> est cryptographiquement sûr — ne remplacez pas
            par <code>rand()</code>/<code>mt_rand()</code>. Le code est haché avec
            <code>Hash::make()</code> (bcrypt) : même un accès à la base ne révèle pas
            le code en clair.
        </div>

        <h3 id="etape-5">5. Contrôleur et routes<a href="#etape-5" class="heading-anchor">#</a></h3>
        <x-code-block lang="php">namespace App\Http\Controllers;

use App\Services\WhatsAppOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OtpController extends Controller
{
    public function request(Request $request, WhatsAppOtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
        ]);

        try {
            $otp->generateAndSend($validated['phone']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 503);
        }

        return response()->json(['success' => true]);
    }

    public function verify(Request $request, WhatsAppOtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'code'  => ['required', 'string', 'size:6'],
        ]);

        if (! $otp->verify($validated['phone'], $validated['code'])) {
            return response()->json(['success' => false, 'error' => 'Code invalide ou expiré.'], 422);
        }

        // Ici : marquer le numéro comme vérifié, connecter l'utilisateur, etc.

        return response()->json(['success' => true]);
    }
}</x-code-block>
        <x-code-block lang="php">// routes/web.php ou routes/api.php de l'application cliente
use App\Http\Controllers\OtpController;

Route::post('/otp/request', [OtpController::class, 'request'])->middleware('throttle:5,1');
Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:10,1');</x-code-block>
        <div class="note">
            Le <code>throttle</code> ici protège <em>votre</em> application (contre le
            spam d'envoi ou le brute-force de vérification) — il s'ajoute à la limite
            de 30/min déjà appliquée par ce service WhatsApp OTP lui-même.
        </div>

        <h3 id="etape-6">6. Formulaire Blade<a href="#etape-6" class="heading-anchor">#</a></h3>
        <p>Exemple minimal en deux étapes (téléphone → code), sans JavaScript de build :</p>
        <x-code-block lang="blade">&lt;form id="request-form"&gt;
    &lt;input type="text" name="phone" placeholder="+33612345678" required&gt;
    &lt;button type="submit"&gt;Recevoir le code&lt;/button&gt;
&lt;/form&gt;

&lt;form id="verify-form" style="display:none"&gt;
    &lt;input type="text" name="code" placeholder="123456" maxlength="6" required&gt;
    &lt;button type="submit"&gt;Vérifier&lt;/button&gt;
&lt;/form&gt;

&lt;script&gt;
const phoneForm = document.getElementById('request-form');
const codeForm = document.getElementById('verify-form');
let currentPhone = null;

phoneForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    currentPhone = new FormData(phoneForm).get('phone');

    const res = await fetch('/otp/request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ phone: currentPhone }),
    });

    if (res.ok) {
        phoneForm.style.display = 'none';
        codeForm.style.display = 'block';
    }
});

codeForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = new FormData(codeForm).get('code');

    const res = await fetch('/otp/verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ phone: currentPhone, code }),
    });

    if (res.ok) {
        window.location = '/dashboard'; // ou la suite de votre flux
    }
});
&lt;/script&gt;</x-code-block>

        <h3 id="etape-7">7. Tests<a href="#etape-7" class="heading-anchor">#</a></h3>
        <p>Ne testez jamais contre le vrai service WhatsApp — mockez la façade <code>Http</code> :</p>
        <x-code-block lang="php">use Illuminate\Support\Facades\Http;

public function test_it_sends_an_otp_code(): void
{
    Http::fake([
        '*/api/otp/send' => Http::response(['success' => true, 'id' => 'msg-1'], 200),
    ]);

    $this->postJson('/otp/request', ['phone' => '33612345678'])
        ->assertOk();

    $this->assertDatabaseCount('otp_codes', 1);
}

public function test_it_rejects_an_invalid_code(): void
{
    Http::fake(['*/api/otp/send' => Http::response(['success' => true], 200)]);
    $this->postJson('/otp/request', ['phone' => '33612345678']);

    $this->postJson('/otp/verify', ['phone' => '33612345678', 'code' => '000000'])
        ->assertStatus(422);
}</x-code-block>

        <h2 id="depannage">Dépannage<a href="#depannage" class="heading-anchor">#</a></h2>
        <table>
            <tr><th>Symptôme</th><th>Cause probable</th></tr>
            <tr><td><code>503</code> permanent</td><td>Session WhatsApp déconnectée — retournez sur le <a href="{{ route('dashboard') }}">dashboard</a>, un QR peut être en attente de scan.</td></tr>
            <tr><td><code>401</code></td><td>Clé API absente du header, révoquée, ou mal copiée (espace, retour à la ligne).</td></tr>
            <tr><td><code>422</code> sur <code>phone</code></td><td>Le numéro doit être uniquement des chiffres (8 à 15), avec un <code>+</code> optionnel en préfixe — pas d'espaces ni de tirets.</td></tr>
            <tr><td>Le message n'arrive jamais côté destinataire</td><td>Limitation connue des clients WhatsApp non officiels sur un tout premier contact — voir le README du projet.</td></tr>
        </table>
    </main>
</div>

<x-scroll-spy sidebar="docs-sidebar" />
@endsection
