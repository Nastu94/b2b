<?php
namespace App\Exceptions\BookingBridge;

class CommissionRateMissingException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('COMMISSION_RATE_MISSING', 400, 'Commissione non configurata', $internalMessage, $previous);
    }
}