<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class BookingBridgeSignatureService
{
    private const WINDOW_SECONDS = 300;
    
    public function verifyInbound(string $method, string $path, string $body, string $timestamp, string $nonce, string $signature): bool
    {
        $secret = trim((string) config('booking_bridge.hmac_secret_inbound'));

        if ($secret === '') {
            Log::error('BookingBridgeSignatureService: Missing inbound HMAC secret');
            return false;
        }

        if (! ctype_digit($timestamp) || strlen($timestamp) > 12) {
            Log::warning('BookingBridgeSignatureService: Invalid timestamp format');
            return false;
        }

        if (! preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $nonce)) {
            Log::warning('BookingBridgeSignatureService: Invalid nonce format');
            return false;
        }

        $signature = strtolower(trim($signature));
        if (! preg_match('/^[a-f0-9]{64}$/', $signature)) {
            Log::warning('BookingBridgeSignatureService: Invalid signature format');
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::WINDOW_SECONDS) {
            Log::warning('BookingBridgeSignatureService: Timestamp out of window', ['timestamp' => $timestamp]);
            return false;
        }

        $canonical = $this->buildCanonicalString($timestamp, $nonce, $method, $path, $body);
        $expected = hash_hmac('sha256', $canonical, $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('BookingBridgeSignatureService: Invalid signature');
            return false;
        }

        // Cache::add è atomico: due richieste concorrenti con lo stesso nonce
        // non possono entrambe superare il controllo anti-replay.
        $cacheKey = 'bb_nonce_' . hash('sha256', $nonce);
        if (! Cache::add($cacheKey, true, now()->addMinutes(10))) {
            Log::warning('BookingBridgeSignatureService: Replay detected', [
                'nonce_hash' => hash('sha256', $nonce),
            ]);
            return false;
        }

        return true;
    }
    
    public function signOutbound(string $method, string $path, string $body): array
    {
        $secret = trim((string) config('booking_bridge.hmac_secret_outbound'));
        $mode = strtolower(trim((string) config('booking_bridge.hmac_mode', 'off')));

        if ($secret === '') {
            if ($mode === 'required') {
                throw new RuntimeException('Segreto HMAC outbound BookingBridge non configurato.');
            }

            return [];
        }

        $timestamp = (string) time();
        $nonce = (string) Str::uuid();

        $canonical = $this->buildCanonicalString($timestamp, $nonce, $method, $path, $body);
        $signature = hash_hmac('sha256', $canonical, $secret);

        return [
            'X-Booking-Bridge-Timestamp' => $timestamp,
            'X-Booking-Bridge-Nonce' => $nonce,
            'X-Booking-Bridge-Signature' => $signature,
            'X-Booking-Bridge-Version' => '2',
        ];
    }
    
    public function buildCanonicalString(string $timestamp, string $nonce, string $method, string $path, string $body): string
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
