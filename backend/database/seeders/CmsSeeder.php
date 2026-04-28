<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\NavigationMenu;
use App\Models\HomepageSection;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // Hero Bannerlar
        $heroBanners = [
            [
                'title' => 'Yeni Yıl Kampanyası',
                'subtitle' => 'Tüm vitaminlerde %30\'a varan indirimler',
                'image_path' => 'banners/hero-newyear.jpg',
                'link_url' => '/market/category/vitaminler',
                'button_text' => 'Hemen Alışverişe Başla',
                'location' => 'home_hero',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'B2B Kırtasiye Pazaryeri',
                'subtitle' => 'Binlerce ürün, yüzlerce satıcı, en uygun fiyatlar',
                'image_path' => 'banners/hero-b2b.jpg',
                'link_url' => '/market',
                'button_text' => 'Ürünleri Keşfet',
                'location' => 'home_hero',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Kış Sezonu Ürünleri',
                'subtitle' => 'Grip, öksürük ve bağışıklık ürünlerinde özel fiyatlar',
                'image_path' => 'banners/hero-winter.jpg',
                'link_url' => '/market/category/solunum-sistemi',
                'button_text' => 'Kampanyayı Gör',
                'location' => 'home_hero',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($heroBanners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title'], 'location' => $banner['location']],
                $banner
            );
        }

        $this->command->info('✅ 3 hero banner oluşturuldu.');

        // Orta Bannerlar
        $middleBanners = [
            [
                'title' => 'Vitamin Haftası',
                'subtitle' => 'Seçili vitaminlerde %20 indirim',
                'image_path' => 'banners/promo-vitamins.jpg',
                'link_url' => '/market/category/vitaminler',
                'button_text' => 'İncele',
                'location' => 'home_middle',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Bebek Ürünleri',
                'subtitle' => 'Anne ve bebek bakım ürünlerinde fırsatlar',
                'image_path' => 'banners/promo-baby.jpg',
                'link_url' => '/market/category/bebek-cocuk',
                'button_text' => 'Ürünleri Gör',
                'location' => 'home_middle',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($middleBanners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title'], 'location' => $banner['location']],
                $banner
            );
        }

        $this->command->info('✅ 2 promo banner oluşturuldu.');

        // Header Menüleri
        $headerMenus = [
            ['title' => 'Ana Sayfa', 'url' => '/market', 'sort_order' => 1],
            ['title' => 'Ürünler', 'url' => '/products', 'sort_order' => 2],
            ['title' => 'Kategoriler', 'url' => '/market', 'sort_order' => 3],
            ['title' => 'Satıcı Ol', 'url' => '/seller/dashboard', 'sort_order' => 4],
            ['title' => 'Yardım', 'url' => '/yardim', 'sort_order' => 5],
        ];

        foreach ($headerMenus as $menu) {
            NavigationMenu::updateOrCreate(
                ['title' => $menu['title'], 'location' => 'header'],
                array_merge($menu, [
                    'location' => 'header',
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('✅ Header menüleri oluşturuldu.');

        // Footer Menüleri (parent-child yapıda)
        $footerGroups = [
            [
                'title' => 'Kurumsal',
                'sort_order' => 1,
                'children' => [
                    ['title' => 'Hakkımızda', 'url' => '/hakkimizda', 'sort_order' => 1],
                    ['title' => 'İletişim', 'url' => '/iletisim', 'sort_order' => 2],
                    ['title' => 'Yardım Merkezi', 'url' => '/yardim', 'sort_order' => 3],
                    ['title' => 'Satıcı Ol', 'url' => '/register', 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Yardım',
                'sort_order' => 2,
                'children' => [
                    ['title' => 'Sipariş Takibi', 'url' => '/yardim/alici-rehberi/siparis-takibi', 'sort_order' => 1],
                    ['title' => 'Sepet ve Ödeme', 'url' => '/yardim/alici-rehberi/sepet-odeme', 'sort_order' => 2],
                    ['title' => 'Başlarken', 'url' => '/yardim/baslarken', 'sort_order' => 3],
                    ['title' => 'Fiyat Karşılaştırma', 'url' => '/yardim/alici-rehberi/fiyat-karsilastirma', 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Yasal',
                'sort_order' => 3,
                'children' => [
                    ['title' => 'KVKK Aydınlatma', 'url' => '/legal/kvkk', 'sort_order' => 1],
                    ['title' => 'Kullanım Koşulları', 'url' => '/legal/terms', 'sort_order' => 2],
                    ['title' => 'Gizlilik Politikası', 'url' => '/legal/privacy', 'sort_order' => 3],
                    ['title' => 'Çerez Politikası', 'url' => '/legal/cookies', 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Kategoriler',
                'sort_order' => 4,
                'children' => [
                    ['title' => 'Güneş Ürünleri', 'url' => '/market/category/gunes-urunleri', 'sort_order' => 1],
                    ['title' => 'Cilt Bakımı', 'url' => '/market/category/cilt-bakimi', 'sort_order' => 2],
                    ['title' => 'Saç Bakımı', 'url' => '/market/category/sac-bakimi', 'sort_order' => 3],
                    ['title' => 'Vücut Bakım', 'url' => '/market/category/vucut-bakim', 'sort_order' => 4],
                    ['title' => 'Anne Bebek', 'url' => '/market/category/anne-bebek', 'sort_order' => 5],
                    ['title' => 'Makyaj', 'url' => '/market/category/makyaj', 'sort_order' => 6],
                    ['title' => 'Vitaminler', 'url' => '/market/category/vitaminler', 'sort_order' => 7],
                    ['title' => 'Besin Takviyeleri', 'url' => '/market/category/besin-takviyeleri', 'sort_order' => 8],
                ],
            ],
            [
                'title' => 'Markalar',
                'sort_order' => 5,
                'children' => [
                    ['title' => 'Nuxe', 'url' => '/market/marka/nuxe', 'sort_order' => 1],
                    ['title' => 'Clinique', 'url' => '/market/marka/clinique', 'sort_order' => 2],
                    ['title' => 'La Roche Posay', 'url' => '/market/marka/la-roche-posay', 'sort_order' => 3],
                    ['title' => 'Bioderma', 'url' => '/market/marka/bioderma', 'sort_order' => 4],
                    ['title' => 'Avene', 'url' => '/market/marka/avene', 'sort_order' => 5],
                    ['title' => 'Tab', 'url' => '/market/marka/tab', 'sort_order' => 6],
                    ['title' => 'Orzax', 'url' => '/market/marka/orzax', 'sort_order' => 7],
                    ['title' => 'Darphin', 'url' => '/market/marka/darphin', 'sort_order' => 8],
                ],
            ],
        ];

        foreach ($footerGroups as $group) {
            $parent = NavigationMenu::updateOrCreate(
                ['title' => $group['title'], 'location' => 'footer', 'parent_id' => null],
                [
                    'location' => 'footer',
                    'sort_order' => $group['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($group['children'] as $child) {
                NavigationMenu::updateOrCreate(
                    ['title' => $child['title'], 'location' => 'footer', 'parent_id' => $parent->id],
                    array_merge($child, [
                        'location' => 'footer',
                        'parent_id' => $parent->id,
                        'is_active' => true,
                    ])
                );
            }
        }

        $this->command->info('✅ Footer menüleri oluşturuldu (parent-child).');

        // Ana Sayfa Bölümleri
        $sections = [
            [
                'title' => 'Çok Satanlar',
                'subtitle' => 'En popüler ürünlerimiz',
                'type' => 'best_sellers',
                'settings' => ['limit' => 8],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Yeni Gelenler',
                'subtitle' => 'Son eklenen ürünler',
                'type' => 'new_arrivals',
                'settings' => ['limit' => 8],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Fırsat Ürünleri',
                'subtitle' => 'Kaçırılmayacak teklifler',
                'type' => 'deals',
                'settings' => ['limit' => 8],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Vitaminler',
                'subtitle' => 'Vitamin ve takviye gıdalar',
                'type' => 'product_carousel',
                'settings' => ['limit' => 8, 'category_slug' => 'vitaminler'],
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($sections as $section) {
            HomepageSection::updateOrCreate(
                ['title' => $section['title']],
                $section
            );
        }

        $this->command->info('✅ Ana sayfa bölümleri oluşturuldu.');
    }
}
