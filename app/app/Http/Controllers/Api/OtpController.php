<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppEngineClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function send(Request $request, WhatsAppEngineClient $engine): JsonResponse
    {
        $validated = $request->validate([
            // Digits only, optionally prefixed with "+", 8-15 digits (loose E.164 shape).
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $status = $engine->status();

        if (($status['status'] ?? null) !== 'READY') {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp session is not connected. Check the dashboard and scan the QR code.',
                'engine_status' => $status['status'] ?? 'UNKNOWN',
            ], 503);
        }

        $result = $engine->sendText($validated['phone'], $validated['message']);

        if (empty($result['success'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send the message.',
            ], $result['httpStatus'] >= 400 ? $result['httpStatus'] : 502);
        }

        return response()->json([
            'success' => true,
            'id' => $result['id'],
        ]);
    }
}
