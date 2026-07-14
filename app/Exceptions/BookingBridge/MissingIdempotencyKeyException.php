<?php
namespace App\Exceptions\BookingBridge;

class MissingIdempotencyKeyException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('MISSING_IDEMPOTENCY_KEY', 400, 'Chiave di idempotenza mancante', $internalMessage, $previous);
    }
}