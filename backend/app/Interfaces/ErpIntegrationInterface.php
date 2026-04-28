<?php

namespace App\Interfaces;

interface ErpIntegrationInterface
{
    /**
     * Get products from ERP
     * Returns an array of standardized product objects/arrays
     *
     * @param int $page Pagination page
     * @param int $limit Items per page
     */
    public function getProducts(int $page = 1, int $limit = 100): array;

    /**
     * Map vendor specific product data to system schema
     */
    public function mapToSystemSchema(array $erpProduct): array;

    /**
     * Test API connection
     */
    public function testConnection(): bool;

    /**
     * Get Provider Name
     */
    public function getName(): string;

    /**
     * Sync order from system to ERP
     * Takes an Order model and sends it to the ERP system
     *
     * @param mixed $order Order model instance
     * @return array Result with success, message, and data keys
     */
    public function syncOrder($order): array;

    /**
     * Create invoice in ERP
     * Takes invoice data array or delegates to syncOrder
     *
     * @param array $invoiceData Invoice data array (may contain 'order' key with Order model)
     * @return array Result with success, message, and data keys
     */
    public function createInvoice(array $invoiceData): array;
}
