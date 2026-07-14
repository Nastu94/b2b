<?php
namespace App\Exceptions\BookingBridge;

class ConfigurationErrorException extends BookingBridgeApiException
{
    public function __construct(string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct('CONFIGURATION_ERROR', 500, 'Errore di configurazione interno', $internalMessage, $previous);
    }
}