<?php

namespace App\Exceptions\BookingBridge;

class PaidOrderSlotUnavailableException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            'PAID_ORDER_SLOT_UNAVAILABLE',
            409,
            'Il pagamento risulta acquisito, ma lo slot non è più disponibile',
            $internalMessage,
            $previous
        );
    }
}
