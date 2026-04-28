<?php

namespace App\Interfaces;

use App\Models\Order;

interface ShippingProviderInterface
{
    /**
     * Create a shipment for an order
     */
    public function createShipment(Order $order, array $senderInfo): ShipmentResult;

    /**
     * Cancel a shipment
     */
    public function cancelShipment(Order $order): ShipmentResult;

    /**
     * Track a shipment
     */
    public function trackShipment(Order $order): TrackingResult;

    /**
     * Get shipping label (PDF/barcode)
     */
    public function getLabel(Order $order): ?string;

    /**
     * Get provider name
     */
    public function getName(): string;

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool;
}

/**
 * Shipment operation result
 */
class ShipmentResult
{
    public function __construct(
        public bool $success,
        public ?string $trackingNumber = null,
        public ?string $labelUrl = null,
        public ?string $message = null,
        public ?string $error = null,
        public ?int $responseCode = null,
    ) {
    }

    public static function success(string $trackingNumber, ?string $labelUrl = null, ?string $message = null): self
    {
        return new self(
            success: true,
            trackingNumber: $trackingNumber,
            labelUrl: $labelUrl,
            message: $message ?? 'Kargo başarıyla oluşturuldu.',
        );
    }

    public static function failure(string $error, ?int $responseCode = null): self
    {
        return new self(
            success: false,
            error: $error,
            responseCode: $responseCode,
        );
    }
}

/**
 * Tracking result
 */
class TrackingResult
{
    public function __construct(
        public bool $success,
        public string $status, // pending, shipped, in_transit, out_for_delivery, delivered, returned
        public string $statusLabel, // Turkish label
        public ?string $trackingNumber = null,
        public ?string $trackingUrl = null,
        public ?string $currentLocation = null,
        public ?string $lastUpdate = null,
        public array $history = [],
        public ?string $error = null,
    ) {
    }

    public static function fromStatus(string $status, string $statusLabel, ?string $trackingNumber = null, ?string $trackingUrl = null): self
    {
        return new self(
            success: true,
            status: $status,
            statusLabel: $statusLabel,
            trackingNumber: $trackingNumber,
            trackingUrl: $trackingUrl,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            status: 'unknown',
            statusLabel: 'Bilinmiyor',
            error: $error,
        );
    }
}
