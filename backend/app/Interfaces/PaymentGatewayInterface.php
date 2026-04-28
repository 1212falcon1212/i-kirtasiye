<?php

namespace App\Interfaces;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment for the given order
     * Returns payment URL or HTML for checkout form
     */
    public function initialize(Order $order): PaymentInitResult;

    /**
     * Generate checkout HTML/iframe content
     */
    public function getCheckoutHtml(Order $order): string;

    /**
     * Handle payment callback from gateway
     */
    public function handleCallback(Request $request): PaymentResult;

    /**
     * Process refund for an order
     */
    public function refund(Order $order, float $amount): RefundResult;

    /**
     * Get gateway name
     */
    public function getName(): string;
}

/**
 * Payment initialization result
 */
class PaymentInitResult
{
    public function __construct(
        public bool $success,
        public ?string $paymentUrl = null,
        public ?string $checkoutHtml = null,
        public ?string $transactionId = null,
        public ?string $error = null,
        public bool $requiresCardInput = false,
    ) {
    }

    public static function success(
        ?string $paymentUrl = null,
        ?string $checkoutHtml = null,
        ?string $transactionId = null,
        bool $requiresCardInput = false,
    ): self {
        return new self(
            success: true,
            paymentUrl: $paymentUrl,
            checkoutHtml: $checkoutHtml,
            transactionId: $transactionId,
            requiresCardInput: $requiresCardInput,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}

/**
 * Payment result after callback
 */
class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $status, // completed, pending, failed
        public ?string $transactionId = null,
        public ?float $paidAmount = null,
        public ?string $error = null,
        public array $rawData = [],
    ) {
    }

    public static function completed(string $transactionId, float $paidAmount, array $rawData = []): self
    {
        return new self(
            success: true,
            status: 'completed',
            transactionId: $transactionId,
            paidAmount: $paidAmount,
            rawData: $rawData,
        );
    }

    public static function pending(string $transactionId, array $rawData = []): self
    {
        return new self(
            success: true,
            status: 'pending',
            transactionId: $transactionId,
            rawData: $rawData,
        );
    }

    public static function failed(string $error, array $rawData = []): self
    {
        return new self(
            success: false,
            status: 'failed',
            error: $error,
            rawData: $rawData,
        );
    }
}

/**
 * Refund result
 */
class RefundResult
{
    public function __construct(
        public bool $success,
        public ?string $refundId = null,
        public ?float $refundedAmount = null,
        public ?string $error = null,
    ) {
    }

    public static function success(string $refundId, float $refundedAmount): self
    {
        return new self(
            success: true,
            refundId: $refundId,
            refundedAmount: $refundedAmount,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
