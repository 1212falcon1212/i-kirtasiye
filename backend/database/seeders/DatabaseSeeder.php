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
            // Şehir/ilçe verileri (sabit referans, korunur)
            TurkeyLocationsSeeder::class,
            // Kargo desi tablosu (sabit referans, korunur)
            ShippingDesiSeeder::class,
            // Kategoriler (12 ana + alt kategori)
            CategorySeeder::class,
            // Markalar
            BrandSeeder::class,
            // Onaylı vergi numaraları
            VergiNoWhitelistSeeder::class,
            // Demo kullanıcılar (admin + 3 kırtasiyeci + 4 tedarikçi)
            KirtasiyeUserSeeder::class,
            // Demo ürünler (~40 adet)
            KirtasiyeProductSeeder::class,
            // Her ürün için 2-4 teklif
            KirtasiyeOfferSeeder::class,
            // Demo siparişler (farklı durumlarda)
            KirtasiyeOrderSeeder::class,
            // CMS sayfaları
            PageSeeder::class,
            // CMS bannerlar / menüler
            CmsSeeder::class,
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
