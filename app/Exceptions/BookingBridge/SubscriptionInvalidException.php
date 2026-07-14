<?php
namespace App\Exceptions\BookingBridge;

class SubscriptionInvalidException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('SUBSCRIPTION_INVALID', 400, 'Abbonamento vendor non valido', $internalMessage, $previous);
    }
}