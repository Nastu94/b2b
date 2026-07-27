<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\BookingBridgeSignatureService;

class BookingBridgeAuth
{
    public function handle(Request $request, Closure $next)
    {
        $rawHmacMode = config('booking_bridge.hmac_mode', 'off');
        $hmacMode = $rawHmacMode === 'legacy' ? 'off' : $rawHmacMode; // legacy -> off, off, optional, required
        
        $signature = $request->header('X-Booking-Bridge-Signature');
        
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

        $timestamp = $request->header('X-Booking-Bridge-Timestamp');
        $nonce = $request->header('X-Booking-Bridge-Nonce');

        if ($signature && $timestamp && $nonce) {
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
            
            // If signature is provided but invalid, we reject even in optional mode to prevent tampering attempts
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