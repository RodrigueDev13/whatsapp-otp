<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(WhatsAppEngineClient $engine): View
    {
        $status = $engine->status();
        $qr = $status['status'] === 'QR_READY' ? $engine->qr() : null;

        return view('dashboard', [
            'status' => $status,
            'qr' => $qr,
        ]);
    }

    public function disconnect(WhatsAppEngineClient $engine): RedirectResponse
    {
        $result = $engine->disconnect();

        return redirect()
            ->route('dashboard')
            ->with($result['success'] ? 'status_message' : 'status_error',
                $result['success']
                    ? 'Compte déconnecté — scannez un nouveau QR code pour lier un autre numéro.'
                    : ($result['error'] ?? 'Échec de la déconnexion.')
            );
    }
}
