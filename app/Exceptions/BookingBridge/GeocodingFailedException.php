<?php
namespace App\Exceptions\BookingBridge;

class GeocodingFailedException extends BookingBridgeApiException
{
    public function __construct(
        string $publicMessage = 'Impossibile calcolare la distanza per l\'indirizzo fornito',
        int $statusCode = 422,
        string $internalMessage = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            errorCode: 'GEOCODING_FAILED',
            statusCode: $statusCode,
            publicMessage: $publicMessage,
            internalMessage: $internalMessage,
            previous: $previous
        );
    }
}
