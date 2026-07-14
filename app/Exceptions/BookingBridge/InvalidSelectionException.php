<?php
namespace App\Exceptions\BookingBridge;

class InvalidSelectionException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('INVALID_SELECTION', 400, 'Selezione non valida', $internalMessage, $previous);
    }
}