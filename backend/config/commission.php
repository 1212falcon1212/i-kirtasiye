<?php

declare(strict_types=1);

/**
 * Komisyon ve hizmet bedeli varsayılan ayarları.
 *
 * Bu dosya, Setting tablosunda bir kayıt yokken kullanılacak güvenli
 * varsayılanları ve ürün/kategori KDV bilgisi olmadığında uygulanacak
 * KDV fallback oranını içerir. Operasyonel değişiklikler Filament üzerindeki
 * "Hizmet Bedeli Ayarları" sayfasından yapılır; bu dosya yalnızca kod
 * tarafından okunan teknik varsayılanları tutar.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Varsayılan KDV Oranı
    |--------------------------------------------------------------------------
    |
    | Ürünün ve ürünün kategorisinin KDV oranı belirlenmemişse stopaj
    | hesabında bu oran kullanılır. Türkiye'deki genel KDV oranı %20'dir,
    | bu yüzden 0.20 (yani %20) olarak ayarlanmıştır. Decimal (0-1) formatı.
    */
    'default_vat_rate' => (float) env('COMMISSION_DEFAULT_VAT_RATE', 0.20),

    /*
    |--------------------------------------------------------------------------
    | Hesaplama varsayılanları
    |--------------------------------------------------------------------------
    |
    | Bu değerler Setting tablosu boş olduğunda fallback olarak kullanılır.
    | Operasyon sırasında admin paneli üzerindeki ayarlar geçerlidir.
    */
    'commission_percentage' => 10.0,   // %10 sabit komisyon
    'flat_service_fee' => 50.0,        // ₺50 sabit hizmet bedeli
    'withholding_tax_rate' => 1.0,     // %1 stopaj (KDV hariç tutar üzerinden)
    'min_order_amount' => 2000.0,      // ₺2.000 minimum sepet tutarı
];
