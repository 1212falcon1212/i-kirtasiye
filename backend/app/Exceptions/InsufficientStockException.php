<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected int $availableStock;
    protected int $requestedQuantity;
    protected ?string $productName;

    public function __construct(
        int $availableStock,
        int $requestedQuantity,
        ?string $productName = null,
        string $message = ''
    ) {
        $this->availableStock = $availableStock;
        $this->requestedQuantity = $requestedQuantity;
        $this->productName = $productName;

        if (empty($message)) {
            $message = $productName
                ? "Yetersiz stok: {$productName} icin talep edilen miktar ({$requestedQuantity}) mevcut stoktan ({$availableStock}) fazla."
                : "Yetersiz stok: Talep edilen miktar ({$requestedQuantity}) mevcut stoktan ({$availableStock}) fazla.";
        }

        parent::__construct($message, 422);
    }

    /**
     * Get the available stock amount.
     */
    public function getAvailableStock(): int
    {
        return $this->availableStock;
    }

    /**
     * Get the requested quantity.
     */
    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    /**
     * Get the product name.
     */
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'insufficient_stock',
            'details' => [
                'available_stock' => $this->availableStock,
                'requested_quantity' => $this->requestedQuantity,
                'product_name' => $this->productName,
            ],
        ], 422);
    }
}
