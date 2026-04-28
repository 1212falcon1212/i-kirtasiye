<?php

namespace App\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    protected ?string $errorCode;
    protected ?string $transactionId;
    protected ?array $gatewayResponse;

    public function __construct(
        string $message = 'Odeme islemi basarisiz oldu.',
        ?string $errorCode = null,
        ?string $transactionId = null,
        ?array $gatewayResponse = null
    ) {
        $this->errorCode = $errorCode;
        $this->transactionId = $transactionId;
        $this->gatewayResponse = $gatewayResponse;

        parent::__construct($message, 402);
    }

    /**
     * Get the payment error code.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the transaction ID.
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * Get the gateway response.
     */
    public function getGatewayResponse(): ?array
    {
        return $this->gatewayResponse;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'payment_failed',
            'details' => [
                'error_code' => $this->errorCode,
                'transaction_id' => $this->transactionId,
            ],
        ], 402);
    }
}
