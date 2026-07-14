<?php
namespace App\Exceptions\BookingBridge;

class BookingAlreadyConfirmedException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('BOOKING_ALREADY_CONFIRMED', 409, 'Prenotazione già confermata', $internalMessage, $previous);
    }
}