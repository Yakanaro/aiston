<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($header, 7);
        $hash = hash('sha256', $token);
        $record = \App\Models\ApiToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $record->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }
}
