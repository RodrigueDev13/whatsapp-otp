<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextKey = $request->header('X-Api-Key');

        if (! is_string($plainTextKey) || $plainTextKey === '') {
            return response()->json(['error' => 'Missing X-Api-Key header'], 401);
        }

        $apiKey = ApiKey::findByPlainTextKey($plainTextKey);

        if (! $apiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
