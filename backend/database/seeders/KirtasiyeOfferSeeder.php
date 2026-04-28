<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Her ürüne 1-3 farklı tedarikçiden teklif yaratır (bulk insert).
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

        $products = DB::table('products')
            ->select('id', 'psf')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('Aktif ürün bulunamadı, offer seed atlandı.');
            return;
        }

        $this->command->info('   Offer üretiliyor: '.$products->count().' ürün için...');

        $now = now();
        $rows = [];
        $totalOffers = 0;

        foreach ($products as $product) {
            $offerCount = random_int(1, min(3, count($sellers)));
            $sellerSubset = collect($sellers)->shuffle()->take($offerCount)->all();
            $basePrice = (float) ($product->psf ?? 50.00);

            foreach ($sellerSubset as $sellerId) {
                $discount = random_int(10, 30) / 100;
                $price = round($basePrice * (1 - $discount), 2);

                $rows[] = [
                    'product_id' => $product->id,
                    'seller_id' => $sellerId,
                    'price' => $price,
                    'stock' => random_int(50, 500),
                    'expiry_date' => null,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalOffers++;

                if (count($rows) >= 1000) {
                    DB::table('offers')->insert($rows);
                    $rows = [];
                }
            }
        }

        if (! empty($rows)) {
            DB::table('offers')->insert($rows);
        }

        $this->command->info("✓ {$totalOffers} teklif eklendi.");
    }
}
