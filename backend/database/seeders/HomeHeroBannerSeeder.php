<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Ana sayfa hero bannerlarini seed'ler.
 *
 * Image dosyalari storage/app/public/banners/ altindadir; deploy
 * sirasinda rsync edilir. Seeder yalnizca DB kaydini olusturur.
 *
 * Mevcut home_hero kayitlari silinip yeniden olusturulur (idempotent).
 */
class HomeHeroBannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'image_path' => 'banners/01KQQ2M942N5JQJWNHEQ0KRGWV.png',
                'link_url' => '/market/products',
                'sort_order' => 0,
            ],
            [
                'image_path' => 'banners/01KQQ2M944W6SWRANZW1XHKTF3.png',
                'link_url' => '/market/category/kirtasiye',
                'sort_order' => 1,
            ],
            [
                'image_path' => 'banners/01KQQ2M946BXP3SS5QHF57AY2A.png',
                'link_url' => '/market/category/lego',
                'sort_order' => 2,
            ],
            [
                'image_path' => 'banners/01KQQ2M947Q0CY8BW9SGV1BAEM.png',
                'link_url' => '/market/category/kitap',
                'sort_order' => 3,
            ],
        ];

        Banner::where('location', 'home_hero')->delete();

        foreach ($banners as $data) {
            Banner::create([
                'location' => 'home_hero',
                'image_path' => $data['image_path'],
                'link_url' => $data['link_url'],
                'sort_order' => $data['sort_order'],
                'is_active' => true,
            ]);
        }

        Cache::forget('cms.banners.home_hero');

        $this->command->info('✓ '.count($banners).' adet hero banner eklendi.');
    }
}
