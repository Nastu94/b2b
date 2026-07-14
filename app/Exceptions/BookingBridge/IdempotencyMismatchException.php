<?php
namespace App\Exceptions\BookingBridge;

class IdempotencyMismatchException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('IDEMPOTENCY_MISMATCH', 409, 'La chiave di idempotenza non corrisponde alla richiesta precedente', $internalMessage, $previous);
    }
}