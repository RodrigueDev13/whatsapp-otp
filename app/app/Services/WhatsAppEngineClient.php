<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the internal Node.js sidecar that actually talks to
 * WhatsApp Web (whatsapp-web.js). The sidecar is never exposed publicly —
 * this is the only place in the Laravel app that reaches it.
 */
class WhatsAppEngineClient
{
    private string $baseUrl;

    private string $internalSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wa_engine.url'), '/');
        $this->internalSecret = (string) config('services.wa_engine.secret');
    }

    /**
     * @return array{status: string, phone: ?string, error?: string}
     */
    public function status(): array
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/status");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('WhatsApp engine is unreachable', ['message' => $e->getMessage()]);

            return ['status' => 'UNREACHABLE', 'phone' => null];
        }

        if ($response->failed()) {
            Log::warning('WhatsApp engine /status request failed', ['status' => $response->status()]);

            return ['status' => 'UNREACHABLE', 'phone' => null];
        }

        return $response->json();
    }

    /**
     * Returns a data: URL for the QR image, or null if none is available
     * right now (e.g. the session is already linked, or the engine is down).
     */
    public function qr(): ?string
    {
        try {
            $response = $this->request()->get("{$this->baseUrl}/qr");
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        return $response->json('qr');
    }

    /**
     * @return array{success: bool, id?: string, error?: string, httpStatus: int}
     */
    public function sendText(string $to, string $text): array
    {
        try {
            $response = $this->request()->post("{$this->baseUrl}/send", [
                'to' => $to,
                'text' => $text,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'error' => 'WhatsApp engine is unreachable: '.$e->getMessage(),
                'httpStatus' => 503,
            ];
        }

        return [
            ...$response->json(),
            'httpStatus' => $response->status(),
        ];
    }

    /**
     * Unlinks the currently connected WhatsApp account and boots a fresh
     * client, ready to link a different account (new QR code).
     *
     * @return array{success: bool, error?: string}
     */
    public function disconnect(): array
    {
        try {
            $response = $this->request()->post("{$this->baseUrl}/disconnect");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'error' => 'WhatsApp engine is unreachable: '.$e->getMessage(),
            ];
        }

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => $response->json('error') ?? 'Failed to disconnect the session.',
            ];
        }

        return ['success' => true];
    }

    private function request()
    {
        return Http::timeout(15)
            ->withHeaders(['X-Internal-Secret' => $this->internalSecret]);
    }
}
