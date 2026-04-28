<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\Product;
use App\Models\UserIntegration;
use App\Services\Erp\ErpManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncErpProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1200; // 20 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected UserIntegration $integration
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(ErpManager $manager): void
    {
        Log::info("Starting ERP Sync for Integration ID: {$this->integration->id}, Type: {$this->integration->erp_type}");

        try {
            $driver = $manager->getDriver($this->integration);

            if (!$driver->testConnection()) {
                Log::error("ERP Connection failed for Integration ID: {$this->integration->id}");
                $this->integration->update(['status' => 'error', 'error_message' => 'Bağlantı başarısız']);
                return;
            }

            $page = 1;
            $limit = 100;
            $totalSynced = 0;

            do {
                $items = $driver->getProducts($page, $limit);

                if (empty($items)) {
                    break;
                }

                foreach ($items as $erpItem) {
                    $mapped = $driver->mapToSystemSchema($erpItem);

                    if (empty($mapped['barcode'])) {
                        continue;
                    }

                    $this->syncProduct($mapped);
                }

                $totalSynced += count($items);
                $page++;

                // Rate limiting protection
                sleep(1);

            } while (count($items) >= $limit);

            $this->integration->update([
                'last_sync_at' => now(),
                'status' => 'active',
                'error_message' => null
            ]);

            Log::info("ERP Sync completed. Total items: {$totalSynced}");

        } catch (\Exception $e) {
            Log::error("ERP Sync Job Error: " . $e->getMessage());
            $this->integration->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
            // Don't fail the job to avoid infinite retries immediately, just log error state
        }
    }

    protected function syncProduct(array $mapped): void
    {
        // 1. Find or Create Product
        $product = Product::where('barcode', $mapped['barcode'])->first();

        if (!$product) {
            $product = Product::create([
                'barcode' => $mapped['barcode'],
                'name' => $mapped['name'] ?? 'Bilinmeyen Ürün ' . $mapped['barcode'],
                'is_active' => false, // Requires admin approval
                'approval_status' => 'pending',
                'source' => 'erp',
                'created_by_id' => $this->integration->user_id,
            ]);
        }

        // 2. Find or Create Offer
        $offer = Offer::where('seller_id', $this->integration->user_id)
            ->where('product_id', $product->id)
            ->first();

        $price = $mapped['price'] ?? 0;
        $stock = $mapped['stock'] ?? 0;
        $vatRate = $mapped['vat_rate'] ?? 0; // Not used in offer yet but good to know

        if ($offer) {
            // Update existing offer
            $offer->update([
                'price' => $price,
                'stock' => $stock,
                // If stock became 0, status -> sold_out. If stock > 0 and was sold_out, active.
                'status' => $stock > 0
                    ? ($offer->status === 'sold_out' ? 'active' : $offer->status)
                    : 'sold_out'
            ]);

            // Eğer ürün aktif değilse offer da aktif olmamalı, ama bu kontrol Offer modelinde veya query scope'da yapılmalı.
            // Veritabanında status 'active' kalsa bile product is_active=false ise listelenmez.
        } else {
            // New Offer
            // Since we don't have expiration date, we set it 1 year from now but make the offer inactive
            Offer::create([
                'product_id' => $product->id,
                'seller_id' => $this->integration->user_id,
                'price' => $price,
                'stock' => $stock,
                'expiry_date' => now()->addYear(), // Placeholder
                'status' => 'inactive',
                'notes' => 'ERP import ile oluşturuldu. Lütfen SKT girip yayınlayın.',
            ]);
        }
    }
}
