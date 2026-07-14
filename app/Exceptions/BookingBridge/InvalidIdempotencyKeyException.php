<?php
namespace App\Exceptions\BookingBridge;

class InvalidIdempotencyKeyException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('INVALID_IDEMPOTENCY_KEY', 422, 'Chiave di idempotenza non valida', $internalMessage, $previous);
    }
}