<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\BookingBridgeSignatureService;

class BookingBridgeAuth
{
    public function handle(Request $request, Closure $next)
    {
        $rawHmacMode = strtolower(trim((string) config('booking_bridge.hmac_mode', 'off')));
        $hmacMode = $rawHmacMode === 'legacy' ? 'off' : $rawHmacMode;

        if (! in_array($hmacMode, ['off', 'optional', 'required'], true)) {
            return response()->json(['success' => false, 'code' => 'SERVER_CONFIGURATION_ERROR', 'message' => 'Authentication mode non valido', 'error' => 'Authentication mode non valido'], 500);
        }

        $signature = $request->header('X-Booking-Bridge-Signature');
        $timestamp = $request->header('X-Booking-Bridge-Timestamp');
        $nonce = $request->header('X-Booking-Bridge-Nonce');
        $version = $request->header('X-Booking-Bridge-Version');

        $expectedLegacy = config('booking_bridge.inbound_key') ?: config('booking_bridge.key');

        $providedKey = $request->header('X-Booking-Bridge-Key')
            ?: $request->bearerToken()
            ?: '';

        $isLegacyValid = is_string($expectedLegacy)
            && is_string($providedKey)
            && $providedKey !== ''
            && hash_equals($expectedLegacy, $providedKey);

        if ($hmacMode === 'off') {
            if (!$isLegacyValid) {
                return response()->json(['success' => false, 'code' => 'UNAUTHORIZED', 'message' => 'Unauthorized', 'error' => 'Unauthorized'], 401);
            }
            return $next($request);
        }

        $hasAnyHmacHeader = $signature !== null
            || $timestamp !== null
            || $nonce !== null
            || $version !== null;

        if ($hasAnyHmacHeader) {
            if (! $signature || ! $timestamp || ! $nonce || (string) $version !== '2') {
                return response()->json(['success' => false, 'code' => 'INVALID_SIGNATURE', 'message' => 'Invalid signature headers', 'error' => 'Invalid signature headers'], 401);
            }

            $signatureService = app(BookingBridgeSignatureService::class);
            $isValid = $signatureService->verifyInbound(
                $request->method(),
                $request->getRequestUri(),
                $request->getContent(),
                $timestamp,
                $nonce,
                $signature
            );
            
            if ($isValid) {
                return $next($request);
            }
            
            return response()->json(['success' => false, 'code' => 'INVALID_SIGNATURE', 'message' => 'Invalid signature', 'error' => 'Invalid signature'], 401);
        }

        if ($hmacMode === 'optional') {
            if ($isLegacyValid) {
                return $next($request);
            }
        }

        return response()->json(['success' => false, 'code' => 'UNAUTHORIZED', 'message' => 'Unauthorized', 'error' => 'Unauthorized'], 401);
    }
}
