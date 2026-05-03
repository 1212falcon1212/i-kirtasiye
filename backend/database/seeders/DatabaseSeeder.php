<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 i-kirtasiye veritabanı seed işlemi başlıyor...');
        $this->command->newLine();

        $this->call([
            // Şehir/ilçe verileri (sabit referans)
            TurkeyLocationsSeeder::class,
            // Kargo desi tablosu (sabit referans)
            ShippingDesiSeeder::class,
            // Onaylı vergi numaraları
            VergiNoWhitelistSeeder::class,
            // Demo kullanıcılar (admin + 3 kırtasiyeci + 4 tedarikçi)
            KirtasiyeUserSeeder::class,
            // Nezih kataloğu: 9 ana kategori + 467 alt path + 1060 marka + 12,799 ürün
            NezihCatalogSeeder::class,
            // Her ürüne 1-3 satıcıdan teklif
            KirtasiyeOfferSeeder::class,
            // Demo siparişler (farklı durumlarda)
            KirtasiyeOrderSeeder::class,
            // CMS sayfaları
            PageSeeder::class,
            // CMS bannerlar / menüler
            CmsSeeder::class,
            // Ana sayfa hero bannerlari (4 banner)
            HomeHeroBannerSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Tüm seed işlemleri tamamlandı.');
        $this->command->newLine();
        $this->command->info('📋 Demo Hesaplar:');
        $this->command->info('   Admin:        admin@i-kirtasiye.com / Password123!');
        $this->command->info('   Kırtasiyeci:  mavi@i-kirtasiye.com / Password123!');
        $this->command->info('   Tedarikçi:    toptan@i-kirtasiye.com / Password123!');
    }
}
