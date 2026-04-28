<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * i-kirtasiye için ~40 demo ürün, 12 kategoriye dağıtılmış.
 */
class KirtasiyeProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Defter & Bloknot' => [
                ['Mas A4 Spiralli Kareli Defter 100 Yaprak', 'Mas', '8690000010001', 65.00, 0.6],
                ['Gıpta Kareli Defter 80 Yaprak A4', 'Gıpta', '8690000010002', 45.00, 0.5],
                ['Mas Sözlük Defter 200 Yaprak', 'Mas', '8690000010003', 95.00, 0.7],
                ['Serve Bloknot A6 50 Yaprak', 'Serve', '8690000010004', 25.00, 0.2],
            ],
            'Kalem & Yazı' => [
                ['Faber-Castell Grip 2001 Kurşun Kalem', 'Faber-Castell', '8690000020001', 18.00, 0.1],
                ['Pilot G-2 0.7mm Tükenmez Kalem Mavi', 'Pilot', '8690000020002', 35.00, 0.1],
                ['Bic Cristal Tükenmez Kalem Mavi', 'Bic', '8690000020003', 8.00, 0.1],
                ['Schneider Slider Memo Roller Kalem', 'Schneider', '8690000020004', 42.00, 0.1],
            ],
            'Ofis Malzemeleri' => [
                ['Mikro Tel Zımba 24/6 1000 Adet', 'Mikro', '8690000030001', 22.00, 0.4],
                ['Maped Cetvel Seti 4\'lü', 'Maped', '8690000030002', 65.00, 0.3],
                ['Adel Geniş Klasör Plastik', 'Adel', '8690000030003', 38.00, 0.6],
                ['Mikro Delgeç 2 Delikli', 'Mikro', '8690000030004', 95.00, 0.5],
            ],
            'Okul Malzemeleri' => [
                ['Maped Kalem Kutusu Çift Katlı', 'Maped', '8690000040001', 145.00, 0.5],
                ['Faber-Castell Silgi & Kalemtraş Set', 'Faber-Castell', '8690000040002', 32.00, 0.1],
                ['Stabilo Akıllı Tahta Kalemi 4\'lü Set', 'Stabilo', '8690000040003', 78.00, 0.2],
                ['Adel Boya Kalemi 12 Renk', 'Adel', '8690000040004', 55.00, 0.3],
            ],
            'Sanat & Hobi' => [
                ['Pelikan Sulu Boya 12 Renk Tablet', 'Pelikan', '8690000050001', 110.00, 0.3],
                ['Faber-Castell Akrilik Boya Seti 12x12ml', 'Faber-Castell', '8690000050002', 215.00, 0.5],
                ['Canson Resim Defteri A3 60 Yaprak', 'Canson', '8690000050003', 145.00, 0.7],
                ['Maped Pastel Boya 24 Renk', 'Maped', '8690000050004', 88.00, 0.4],
            ],
            'Kağıt Ürünleri' => [
                ['Mavi A4 Fotokopi Kağıdı 80gr 500\'lü', 'Mas', '8690000060001', 165.00, 2.5],
                ['Gıpta Renkli Kağıt A4 100\'lü 10 Renk', 'Gıpta', '8690000060002', 95.00, 1.0],
                ['Adel Karton 50x70 cm 10\'lu Mix', 'Adel', '8690000060003', 75.00, 1.5],
                ['Mas Bristol Karton A4 200gr 50\'li', 'Mas', '8690000060004', 58.00, 1.0],
            ],
            'Dosyalama & Arşiv' => [
                ['Mikro Telli Dosya 50\'li', 'Mikro', '8690000070001', 95.00, 0.8],
                ['Adel Geniş Klasör 8 cm', 'Adel', '8690000070002', 52.00, 0.6],
                ['Serve Pres Cilt Karton', 'Serve', '8690000070003', 18.00, 0.2],
            ],
            'Hesap Makineleri' => [
                ['Casio FX-991ES Plus Bilimsel Hesap Makinesi', 'Casio', '8690000080001', 750.00, 0.3],
                ['Casio HL-820LV Cep Hesap Makinesi', 'Casio', '8690000080002', 145.00, 0.2],
                ['Casio MS-20F Masa Tipi Hesap Makinesi', 'Casio', '8690000080003', 385.00, 0.5],
            ],
            'Boya & Marker' => [
                ['Stabilo Boss Original Fosforlu Kalem 6\'lı', 'Stabilo', '8690000090001', 165.00, 0.2],
                ['Edding 3000 Permanent Marker Siyah', 'Edding', '8690000090002', 38.00, 0.1],
                ['Pilot V Board Master Beyaz Tahta Kalemi', 'Pilot', '8690000090003', 45.00, 0.1],
            ],
            'Yapıştırıcı & Bant' => [
                ['Pritt Stick Yapıştırıcı 22gr', 'Mas', '8690000100001', 28.00, 0.1],
                ['UHU Sıvı Yapıştırıcı 30ml', 'Adel', '8690000100002', 24.00, 0.1],
                ['Mas Selobant 18mm', 'Mas', '8690000100003', 12.00, 0.1],
            ],
            'Çizim Aletleri' => [
                ['Faber-Castell Grip Pergel Seti', 'Faber-Castell', '8690000110001', 95.00, 0.3],
                ['Maped Geo Notebook Gönye Seti', 'Maped', '8690000110002', 48.00, 0.2],
                ['Adel Şablon Seti 5\'li', 'Adel', '8690000110003', 35.00, 0.2],
            ],
            'Ofis Elektroniği' => [
                ['HP 305 Siyah Yazıcı Kartuşu', 'Mikro', '8690000120001', 1450.00, 0.4],
                ['Brother TN-2456 Toner Kartuşu', 'Mikro', '8690000120002', 2150.00, 1.0],
                ['Kingston DataTraveler 32GB USB Bellek', 'Mikro', '8690000120003', 295.00, 0.1],
            ],
        ];

        $totalCount = 0;

        foreach ($catalog as $categoryName => $products) {
            $category = Category::where('name', $categoryName)->whereNull('parent_id')->first();
            if (! $category) {
                $this->command->warn("Kategori bulunamadı: {$categoryName}");
                continue;
            }

            foreach ($products as [$name, $brand, $barcode, $psf, $desi]) {
                Product::updateOrCreate(
                    ['barcode' => $barcode],
                    [
                        'name' => $name,
                        'brand' => $brand,
                        'manufacturer' => $brand,
                        'description' => $name . ' - ' . $categoryName . ' kategorisi.',
                        'category_id' => $category->id,
                        'psf' => $psf,
                        'desi' => $desi,
                        'weight' => $desi,
                        'is_active' => true,
                        'approval_status' => 'approved',
                        'source' => 'system',
                    ]
                );
                $totalCount++;
            }
        }

        $this->command->info("✓ {$totalCount} ürün eklendi.");
    }
}
