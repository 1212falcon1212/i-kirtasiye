<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingRate;
use App\Models\FreeShippingRule;
use App\Models\Product;
use App\Models\Setting;

class ShippingDesiSeeder extends Seeder
{
    /**
     * Demo kargo ücretleri ve desi değerleri ekle
     */
    public function run(): void
    {
        // Komisyon ve ücret ayarları
        $this->seedFeeSettings();

        // Kargo desi fiyatları
        $this->seedShippingRates();

        // Ücretsiz kargo kuralları
        $this->seedFreeShippingRules();

        // Ürünlere desi değerleri ekle
        $this->seedProductDesi();
    }

    protected function seedFeeSettings(): void
    {
        Setting::setValue('commission.marketplace_fee_rate', 0.89);
        Setting::setValue('commission.withholding_tax_rate', 1.00);
        Setting::setValue('commission.enabled', true);

        $this->command->info('Komisyon ayarları eklendi: Hizmet Bedeli %0.89, Stopaj %1');
    }

    protected function seedShippingRates(): void
    {
        // Mevcut kayıtları temizle
        ShippingRate::truncate();

        $providers = ['aras', 'yurtici', 'kolaygelsin'];
        $desiRanges = [
            ['min' => 0, 'max' => 1, 'base' => 25],
            ['min' => 1, 'max' => 3, 'base' => 35],
            ['min' => 3, 'max' => 5, 'base' => 45],
            ['min' => 5, 'max' => 10, 'base' => 60],
            ['min' => 10, 'max' => 15, 'base' => 80],
            ['min' => 15, 'max' => 20, 'base' => 100],
            ['min' => 20, 'max' => 30, 'base' => 130],
            ['min' => 30, 'max' => 50, 'base' => 180],
        ];

        $providerMultipliers = [
            'aras' => 1.0,
            'yurtici' => 1.05,
            'kolaygelsin' => 0.95,
        ];

        foreach ($providers as $provider) {
            foreach ($desiRanges as $range) {
                $price = $range['base'] * $providerMultipliers[$provider];

                ShippingRate::create([
                    'provider' => $provider,
                    'min_desi' => $range['min'],
                    'max_desi' => $range['max'],
                    'price' => round($price, 2),
                    'is_active' => true,
                ]);
            }
        }

        $totalRates = count($providers) * count($desiRanges);
        $this->command->info("Kargo ücretleri eklendi: {$totalRates} kayıt (3 firma x 8 desi aralığı)");
    }

    protected function seedFreeShippingRules(): void
    {
        // Mevcut kuralları temizle
        FreeShippingRule::truncate();

        // Genel ücretsiz kargo kuralı (tüm firmalar için)
        FreeShippingRule::create([
            'provider' => null,
            'min_order_amount' => 500,
            'max_desi' => 30,
            'is_active' => true,
        ]);

        // Firma bazlı özel kurallar
        FreeShippingRule::create([
            'provider' => 'kolaygelsin',
            'min_order_amount' => 300,
            'max_desi' => 20,
            'is_active' => true,
        ]);

        $this->command->info('Ücretsiz kargo kuralları eklendi: 500 TL üzeri (genel), 300 TL (Kolaygelsin)');
    }

    protected function seedProductDesi(): void
    {
        // Desi değeri olmayan ürünleri güncelle
        $products = Product::whereNull('desi')->orWhere('desi', 0)->get();
        $updated = 0;

        foreach ($products as $product) {
            // İlaç kategorilerine göre desi tahmini
            $desi = $this->estimateDesi($product->name);

            $product->update([
                'desi' => $desi,
                'weight' => $desi * 0.3, // Ortalama ağırlık
            ]);

            $updated++;
        }

        $this->command->info("Ürün desi değerleri güncellendi: {$updated} ürün");
    }

    protected function estimateDesi(string $productName): float
    {
        $name = strtolower($productName);

        // Büyük kutular
        if (str_contains($name, 'şurup') || str_contains($name, 'solüsyon')) {
            return round(mt_rand(15, 30) / 10, 2); // 1.5-3.0 desi
        }

        // Orta boy
        if (str_contains($name, '100 tablet') || str_contains($name, '90 kapsül') || str_contains($name, '60 tablet')) {
            return round(mt_rand(8, 15) / 10, 2); // 0.8-1.5 desi
        }

        // Küçük kutular
        if (str_contains($name, '20 tablet') || str_contains($name, '30 tablet') || str_contains($name, 'film')) {
            return round(mt_rand(3, 8) / 10, 2); // 0.3-0.8 desi
        }

        // Varsayılan
        return round(mt_rand(5, 12) / 10, 2); // 0.5-1.2 desi
    }
}
