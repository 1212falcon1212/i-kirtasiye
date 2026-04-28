<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * i-kirtasiye için kategori yapısı.
 * 12 ana kategori + her birine 3 alt kategori.
 */
class CategorySeeder extends Seeder
{
    private const DEFAULT_COMMISSION_RATE = 12.00;

    public function run(): void
    {
        $this->command->info('Kategori seed işlemi başlıyor...');

        Category::query()->delete();
        \DB::statement('ALTER TABLE categories AUTO_INCREMENT = 1');

        $categoriesData = [
            'Defter & Bloknot' => ['Spiralli Defter', 'Sözlük Defter', 'Bloknot'],
            'Kalem & Yazı' => ['Kurşun Kalem', 'Tükenmez Kalem', 'Roller Kalem'],
            'Ofis Malzemeleri' => ['Zımba & Delgeç', 'Cetvel Seti', 'Klasör'],
            'Okul Malzemeleri' => ['Kalem Kutusu', 'Silgi & Kalemtraş', 'Akıllı Tahta Kalemleri'],
            'Sanat & Hobi' => ['Sulu Boya', 'Akrilik Boya', 'Resim Defteri'],
            'Kağıt Ürünleri' => ['A4 Fotokopi Kağıdı', 'Renkli Kağıt', 'Karton'],
            'Dosyalama & Arşiv' => ['Telli Dosya', 'Klasör', 'Pres Cilt'],
            'Hesap Makineleri' => ['Bilimsel', 'Cep Tipi', 'Masa Tipi'],
            'Boya & Marker' => ['Fosforlu Kalem', 'Permanent Marker', 'Beyaz Tahta Kalemi'],
            'Yapıştırıcı & Bant' => ['Stick Yapıştırıcı', 'Sıvı Yapıştırıcı', 'Selobant'],
            'Çizim Aletleri' => ['Pergel', 'Gönye Seti', 'Şablon'],
            'Ofis Elektroniği' => ['Yazıcı Kartuşu', 'Toner', 'USB Bellek'],
        ];

        $totalMain = 0;
        $totalSub = 0;
        $sortOrder = 1;

        foreach ($categoriesData as $mainCategoryName => $subCategories) {
            $parent = Category::create([
                'name' => $mainCategoryName,
                'slug' => Str::slug($mainCategoryName),
                'description' => $mainCategoryName . ' kategorisi',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => $sortOrder++,
                'commission_rate' => self::DEFAULT_COMMISSION_RATE,
            ]);

            $totalMain++;

            $childSortOrder = 1;
            foreach ($subCategories as $subName) {
                $subSlug = Str::slug($subName);
                if (Category::where('slug', $subSlug)->exists()) {
                    $subSlug = $subSlug . '-' . $parent->id;
                }

                Category::create([
                    'name' => $subName,
                    'slug' => $subSlug,
                    'description' => $subName . ' ürünleri',
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => $childSortOrder++,
                    'commission_rate' => self::DEFAULT_COMMISSION_RATE,
                ]);
                $totalSub++;
            }
        }

        $this->command->info("✓ {$totalMain} ana kategori, {$totalSub} alt kategori eklendi.");
    }
}
