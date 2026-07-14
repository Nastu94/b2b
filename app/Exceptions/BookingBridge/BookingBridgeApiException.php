<?php
namespace App\Exceptions\BookingBridge;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class BookingBridgeApiException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected string $publicMessage;

    public function __construct(string $errorCode, int $statusCode, string $publicMessage, string $internalMessage = '', ?\Throwable $previous = null)
    {
        parent::__construct($internalMessage ?: $publicMessage, $statusCode, $previous);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->publicMessage = $publicMessage;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $this->errorCode,
            'message' => $this->publicMessage,
        ], $this->statusCode);
    }
}