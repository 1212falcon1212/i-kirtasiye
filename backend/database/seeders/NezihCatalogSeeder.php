<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Nezih.com.tr katalog import (12,799 ürün, 467 kategori path, 1060 marka).
 * JSON kaynak: backend/database/data/nezih_products.json
 *
 * - Kategoriler: hierarchical olarak categories tablosuna (parent_id + full_slug)
 * - Markalar: brands tablosuna (slug ile)
 * - Ürünler: products tablosuna (image, psf, category_id, brand string)
 */
class NezihCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/nezih_products.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("JSON not found: {$jsonPath}");

            return;
        }

        $this->command->info('📦 Nezih kataloğu yükleniyor...');
        $raw = file_get_contents($jsonPath);
        $data = json_decode($raw, true);
        $products = $data['products'] ?? [];

        // Sadece kullanılabilir ürünleri filtrele
        $usable = array_filter($products, function ($p) {
            return ! empty($p['name'])
                && ! empty($p['price'])
                && ! empty($p['categories'])
                && ! empty($p['images'])
                && is_array($p['categories']);
        });

        $this->command->info('   Kullanılabilir ürün: '.count($usable));

        // 1) Kategori ağacını çıkar ve insert et
        $categoryMap = $this->seedCategories($usable);
        $this->command->info('   Kategori sayısı: '.count($categoryMap));

        // 2) Markaları çıkar ve insert et
        $this->seedBrands($usable);

        // 3) Ürünleri bulk insert
        $this->seedProducts($usable, $categoryMap);

        $this->command->info('✓ Nezih kataloğu yüklendi.');
    }

    /**
     * Kategori ağacını oluştur, full_slug üreterek categories tablosuna yaz.
     * Returns: ['Oyuncak > Erkek Oyuncakları > Oyuncak Arabalar' => leaf_category_id]
     */
    protected function seedCategories(iterable $products): array
    {
        // Tüm unique kategori path'lerini topla
        $allPaths = [];
        foreach ($products as $p) {
            $cats = $p['categories'];
            for ($i = 1; $i <= count($cats); $i++) {
                $partial = array_slice($cats, 0, $i);
                $key = implode(' > ', $partial);
                $allPaths[$key] = $partial;
            }
        }

        // Path uzunluğuna göre sırala (parent önce)
        uksort($allPaths, function ($a, $b) {
            return substr_count($a, '>') - substr_count($b, '>');
        });

        $now = now();
        $pathToId = [];

        foreach ($allPaths as $key => $segments) {
            $name = end($segments);
            $depth = count($segments);
            $parentId = null;
            $parentFullSlug = null;

            if ($depth > 1) {
                $parentKey = implode(' > ', array_slice($segments, 0, -1));
                $parentId = $pathToId[$parentKey] ?? null;
                $parentRow = DB::table('categories')->where('id', $parentId)->first();
                $parentFullSlug = $parentRow?->full_slug;
            }

            $slug = Str::slug($name, '-', 'tr');
            $fullSlug = $parentFullSlug ? "{$parentFullSlug}/{$slug}" : $slug;

            // full_slug çakışmasını önle (örn. "kalem" iki dalda olabilir)
            $base = $fullSlug;
            $i = 2;
            while (DB::table('categories')->where('full_slug', $fullSlug)->exists()) {
                $fullSlug = "{$base}-{$i}";
                $i++;
            }
            // slug da unique
            $baseSlug = $slug;
            $j = 2;
            while (DB::table('categories')->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$j}";
                $j++;
            }

            $id = DB::table('categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'full_slug' => $fullSlug,
                'parent_id' => $parentId,
                'description' => null,
                'commission_rate' => 5.00,
                'is_active' => true,
                'show_on_homepage' => $depth === 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $pathToId[$key] = $id;
        }

        return $pathToId;
    }

    /**
     * Tüm unique markaları brands tablosuna ekle.
     */
    protected function seedBrands(iterable $products): void
    {
        $brands = [];
        foreach ($products as $p) {
            $brand = trim($p['brand'] ?? '');
            if ($brand !== '') {
                $brands[$brand] = true;
            }
        }
        $brandNames = array_keys($brands);
        $this->command->info('   Marka sayısı: '.count($brandNames));

        // Mevcut DB'deki markaları çek (name ve slug bazında)
        $existingNames = array_flip(
            DB::table('brands')->pluck('name')->all()
        );
        $existingSlugs = array_flip(
            DB::table('brands')->pluck('slug')->all()
        );

        $now = now();
        $rows = [];
        $usedSlugs = $existingSlugs;

        foreach ($brandNames as $name) {
            // Aynı isimli marka varsa atla
            if (isset($existingNames[$name])) {
                continue;
            }

            $slug = Str::slug($name, '-', 'tr');
            if ($slug === '') {
                $slug = 'marka-'.md5($name);
            }
            $base = $slug;
            $i = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $usedSlugs[$slug] = true;

            $rows[] = [
                'name' => $name,
                'slug' => $slug,
                'logo_url' => null,
                'description' => null,
                'website_url' => null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('brands')->insertOrIgnore($chunk);
        }
    }

    /**
     * Ürünleri bulk insert (chunk 500).
     */
    protected function seedProducts(iterable $products, array $categoryMap): void
    {
        $now = now();
        $rows = [];
        $skippedNoCategory = 0;
        $skippedDupeBarcode = 0;
        $skippedBadBarcode = 0;
        $seenBarcodes = [];
        $autoBarcodeCounter = 1;

        // Mevcut barkodları çek (collision varsa skip)
        $existingBarcodes = array_flip(
            DB::table('products')->pluck('barcode')->all()
        );

        foreach ($products as $p) {
            $categoryKey = implode(' > ', $p['categories']);
            $categoryId = $categoryMap[$categoryKey] ?? null;
            if (! $categoryId) {
                $skippedNoCategory++;

                continue;
            }

            $barcode = trim($p['barcode'] ?? '');
            // 14 karakterden uzunsa skip (DB column limit)
            if ($barcode !== '' && strlen($barcode) > 14) {
                $skippedBadBarcode++;

                continue;
            }
            // Boşsa product_id'den auto-generate
            if ($barcode === '') {
                $pid = $p['product_id'] ?? ('AUTO'.$autoBarcodeCounter++);
                $barcode = 'PID-'.substr((string) $pid, 0, 10);
            }
            // Duplicate guard (JSON'da olmaması beklenir ama emniyet için)
            if (isset($seenBarcodes[$barcode])) {
                $skippedDupeBarcode++;

                continue;
            }
            // DB'de zaten varsa atla (idempotent re-run)
            if (isset($existingBarcodes[$barcode])) {
                $skippedDupeBarcode++;

                continue;
            }
            $seenBarcodes[$barcode] = true;

            $price = (float) str_replace(',', '.', (string) ($p['price'] ?? '0'));
            if ($price <= 0) {
                continue;
            }

            $image = $p['images'][0] ?? null;
            $brand = trim($p['brand'] ?? '');
            $description = $this->cleanDescription($p['description'] ?? null);

            $rows[] = [
                'barcode' => $barcode,
                'name' => mb_substr($p['name'], 0, 250),
                'brand' => $brand !== '' ? mb_substr($brand, 0, 250) : null,
                'manufacturer' => $brand !== '' ? mb_substr($brand, 0, 250) : null,
                'description' => $description,
                'image' => $image,
                'category_id' => $categoryId,
                'psf' => $price,
                'is_active' => true,
                'approval_status' => 'approved',
                'source' => 'nezih',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= 500) {
                DB::table('products')->insertOrIgnore($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            DB::table('products')->insertOrIgnore($rows);
        }

        $this->command->info('   Eklenen ürün: '.(count($seenBarcodes) - $skippedDupeBarcode));
        if ($skippedNoCategory) {
            $this->command->warn("   Kategori bulunamadı (skip): {$skippedNoCategory}");
        }
        if ($skippedDupeBarcode) {
            $this->command->warn("   Tekrarlı barcode (skip): {$skippedDupeBarcode}");
        }
        if ($skippedBadBarcode) {
            $this->command->warn("   Çok uzun barcode (skip): {$skippedBadBarcode}");
        }
    }

    protected function cleanDescription(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $clean = trim(preg_replace('/\s+/', ' ', $raw));

        return $clean !== '' ? mb_substr($clean, 0, 5000) : null;
    }
}
