<?php
namespace App\Exceptions\BookingBridge;

class SlotUnavailableException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('SLOT_UNAVAILABLE', 409, 'Slot non disponibile', $internalMessage, $previous);
    }
}