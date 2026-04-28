<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\VergiNoWhitelist;
use Illuminate\Database\Seeder;

/**
 * i-kirtasiye onaylı vergi numaraları whitelist seeder'ı.
 * Hem kırtasiyeciler hem tedarikçiler için demo girdiler.
 */
class VergiNoWhitelistSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['1234567890', 'Mavi Kırtasiye Ltd.', 'İstanbul', 'Kadıköy', 'Bağdat Cad. No:123 Kadıköy/İstanbul'],
            ['2345678901', 'Defter Dünyası A.Ş.', 'İstanbul', 'Beşiktaş', 'Barbaros Bul. No:45 Beşiktaş/İstanbul'],
            ['3456789012', 'Kalemci Mehmet Tic.', 'Ankara', 'Çankaya', 'Tunalı Hilmi Cad. No:78 Çankaya/Ankara'],
            ['4567890123', 'Eğitim Kırtasiye Ltd.', 'İzmir', 'Konak', 'Cumhuriyet Bul. No:90 Konak/İzmir'],
            ['5678901234', 'Toptan Kırtasiye A.Ş.', 'İstanbul', 'Bayrampaşa', 'Ortaköy Mah. Yeni Yol Cad. No:12'],
            ['6789012345', 'Eğitim Malzemeleri Ltd.', 'İstanbul', 'Ümraniye', 'Site Yolu Cad. No:34'],
            ['7890123456', 'Ofis Tedarik Co.', 'Ankara', 'Yenimahalle', 'Mevlana Bul. No:56'],
            ['8901234567', 'Sanat Malzemeleri Distribütör', 'İzmir', 'Bornova', 'Ergene Sk. No:78'],
            ['9012345678', 'Karton Pazar Ticaret', 'Bursa', 'Nilüfer', 'Beşevler Sk. No:101'],
            ['0123456789', 'Renkli Kağıt Ltd.', 'Antalya', 'Muratpaşa', 'Atatürk Cad. No:202'],
        ];

        $count = 0;
        foreach ($entries as [$vergiNo, $companyName, $city, $district, $address]) {
            VergiNoWhitelist::updateOrCreate(
                ['vergi_no' => $vergiNo],
                [
                    'company_name' => $companyName,
                    'city' => $city,
                    'district' => $district,
                    'address' => $address,
                    'is_active' => true,
                    'is_used' => false,
                ]
            );
            $count++;
        }

        $this->command->info("✓ {$count} vergi no whitelist kaydı eklendi.");
    }
}
