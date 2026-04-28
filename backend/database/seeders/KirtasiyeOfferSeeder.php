<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Her ürüne 2-4 farklı tedarikçiden teklif yaratır.
 */
class KirtasiyeOfferSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', User::ROLE_SELLER)->pluck('id')->toArray();

        if (count($sellers) === 0) {
            $this->command->warn('Tedarikçi (seller) kullanıcı bulunamadı, offer seed atlandı.');
            return;
        }

        $products = Product::where('is_active', true)->get();
        $totalOffers = 0;

        foreach ($products as $product) {
            $offerCount = random_int(2, min(4, count($sellers)));
            $sellerSubset = collect($sellers)->shuffle()->take($offerCount);

            foreach ($sellerSubset as $sellerId) {
                $basePrice = (float) ($product->psf ?? 50.00);
                // 10-30% spread under PSF (i.e. discount)
                $discount = random_int(10, 30) / 100;
                $price = round($basePrice * (1 - $discount), 2);

                Offer::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'seller_id' => $sellerId,
                    ],
                    [
                        'price' => $price,
                        'stock' => random_int(50, 500),
                        'expiry_date' => null,
                        'status' => Offer::STATUS_ACTIVE,
                    ]
                );
                $totalOffers++;
            }
        }

        $this->command->info("✓ {$totalOffers} teklif eklendi ({$products->count()} ürün için).");
    }
}
