<?php
namespace App\Exceptions\BookingBridge;

class LockTerminatedException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null, int $statusCode = 409)
    {
        parent::__construct('LOCK_TERMINATED', $statusCode, 'La selezione è scaduta o non più valida', $internalMessage, $previous);
    }
}