<?php
namespace App\Exceptions\BookingBridge;

class TemporaryUpstreamErrorException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('TEMPORARY_UPSTREAM_ERROR', 502, 'Errore temporaneo nel servizio', $internalMessage, $previous);
    }
}