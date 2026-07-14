<?php
namespace App\Exceptions\BookingBridge;

class OfferingNotBookableException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('OFFERING_NOT_BOOKABLE', 400, 'Il servizio non è prenotabile', $internalMessage, $previous);
    }
}