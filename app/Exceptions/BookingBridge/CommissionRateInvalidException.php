<?php
namespace App\Exceptions\BookingBridge;

class CommissionRateInvalidException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('COMMISSION_RATE_INVALID', 400, 'Commissione configurata non valida', $internalMessage, $previous);
    }
}