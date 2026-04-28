<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * i-kirtasiye için marka seed'i.
 */
class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Faber-Castell', 'description' => 'Alman köklü kırtasiye markası', 'is_featured' => true],
            ['name' => 'Pilot', 'description' => 'Japon menşeli kalem markası', 'is_featured' => true],
            ['name' => 'Stabilo', 'description' => 'Renkli kalem ve fosforlu kalem markası', 'is_featured' => true],
            ['name' => 'Bic', 'description' => 'Tükenmez kalem ve günlük yazım ürünleri', 'is_featured' => true],
            ['name' => 'Pelikan', 'description' => 'Klasik dolma kalem ve mürekkep markası', 'is_featured' => true],
            ['name' => 'Mas', 'description' => 'Türkiye\'nin lider defter markası', 'is_featured' => true],
            ['name' => 'Adel', 'description' => 'Türk kırtasiye markası', 'is_featured' => true],
            ['name' => 'Maped', 'description' => 'Fransız okul ürünleri markası', 'is_featured' => true],
            ['name' => 'Schneider', 'description' => 'Alman premium kalem markası', 'is_featured' => false],
            ['name' => 'Edding', 'description' => 'Marker ve permanent kalem markası', 'is_featured' => false],
            ['name' => 'Casio', 'description' => 'Hesap makineleri', 'is_featured' => true],
            ['name' => 'Canson', 'description' => 'Fransız sanat kağıtları markası', 'is_featured' => false],
            ['name' => 'Mikro', 'description' => 'Türk ofis ve okul malzemeleri', 'is_featured' => false],
            ['name' => 'Serve', 'description' => 'Türk kırtasiye markası', 'is_featured' => false],
            ['name' => 'Gıpta', 'description' => 'Türk defter ve okul ürünleri', 'is_featured' => false],
        ];

        $sortOrder = 1;
        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'slug' => \Illuminate\Support\Str::slug($brand['name']),
                    'description' => $brand['description'],
                    'is_active' => true,
                    'is_featured' => $brand['is_featured'],
                    'sort_order' => $sortOrder++,
                ]
            );
        }

        $this->command->info('✓ ' . count($brands) . ' marka eklendi.');
    }
}
