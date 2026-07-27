<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BookingBridgeSignatureService
{
    private const WINDOW_SECONDS = 300;
    
    public function verifyInbound(string $method, string $path, string $body, string $timestamp, string $nonce, string $signature): bool
    {
        $secret = config('booking_bridge.hmac_secret_inbound');
        if (empty($secret)) {
            Log::error('BookingBridgeSignatureService: Missing inbound HMAC secret');
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::WINDOW_SECONDS) {
            Log::warning('BookingBridgeSignatureService: Timestamp out of window', ['timestamp' => $timestamp]);
            return false;
        }
        
        $cacheKey = "bb_nonce_{$nonce}";
        if (Cache::has($cacheKey)) {
            Log::warning('BookingBridgeSignatureService: Replay detected', ['nonce' => $nonce]);
            return false;
        }
        Cache::put($cacheKey, true, now()->addMinutes(10));
        
        $canonical = $this->buildCanonicalString($timestamp, $nonce, $method, $path, $body);
        $expected = hash_hmac('sha256', $canonical, $secret);
        
        $isValid = hash_equals($expected, strtolower($signature));
        
        if (!$isValid) {
            Log::warning('BookingBridgeSignatureService: Invalid signature');
        }
        
        return $isValid;
    }
    
    public function signOutbound(string $method, string $path, string $body): array
    {
        $secret = config('booking_bridge.hmac_secret_outbound');
        $timestamp = (string) time();
        $nonce = (string) \Illuminate\Support\Str::uuid();
        
        $canonical = $this->buildCanonicalString($timestamp, $nonce, $method, $path, $body);
        $signature = hash_hmac('sha256', $canonical, $secret ?? '');
        
        return [
            'X-Booking-Bridge-Timestamp' => $timestamp,
            'X-Booking-Bridge-Nonce' => $nonce,
            'X-Booking-Bridge-Signature' => $signature,
            'X-Booking-Bridge-Version' => '2',
        ];
    }
    
    private function buildCanonicalString(string $timestamp, string $nonce, string $method, string $path, string $body): string
    {
        return implode("\n", [
            $timestamp,
            $nonce,
            strtoupper($method),
            $path,
            hash('sha256', $body)
        ]);
    }
}
