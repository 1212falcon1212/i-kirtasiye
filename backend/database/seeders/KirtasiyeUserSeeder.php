<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\VergiNoWhitelist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * i-kirtasiye demo kullanıcılar:
 *  - 1 super-admin
 *  - 3 kırtasiyeci (retailer)
 *  - 4 tedarikçi (seller)
 */
class KirtasiyeUserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('Password123!');

        // 1. Super admin
        User::updateOrCreate(
            ['email' => 'admin@i-kirtasiye.com'],
            [
                'password' => $defaultPassword,
                'business_name' => 'i-kirtasiye Admin',
                'nickname' => 'admin',
                'phone' => '0212 555 00 00',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_verified' => true,
                'verified_at' => now(),
                'verification_status' => 'approved',
                'approved_at' => now(),
                'email_verified_at' => now(),
                'city' => 'İstanbul',
                'district' => 'Şişli',
                'address' => 'Mecidiyeköy Yolu Cad. No:1',
            ]
        );

        // 3 kırtasiyeci (retailer) — vergi numaraları whitelist'ten
        $retailers = [
            [
                'email' => 'mavi@i-kirtasiye.com',
                'vergi_no' => '1234567890',
                'business_name' => 'Mavi Kırtasiye Ltd.',
                'nickname' => 'mavikirtasiye',
                'phone' => '0216 555 11 11',
            ],
            [
                'email' => 'defter@i-kirtasiye.com',
                'vergi_no' => '2345678901',
                'business_name' => 'Defter Dünyası A.Ş.',
                'nickname' => 'defterdunyasi',
                'phone' => '0212 555 22 22',
            ],
            [
                'email' => 'kalemci@i-kirtasiye.com',
                'vergi_no' => '3456789012',
                'business_name' => 'Kalemci Mehmet Tic.',
                'nickname' => 'kalemcimehmet',
                'phone' => '0312 555 33 33',
            ],
        ];

        foreach ($retailers as $retailer) {
            $entry = VergiNoWhitelist::findByVergiNo($retailer['vergi_no']);

            $user = User::updateOrCreate(
                ['email' => $retailer['email']],
                [
                    'password' => $defaultPassword,
                    'vergi_no' => $retailer['vergi_no'],
                    'business_name' => $retailer['business_name'],
                    'nickname' => $retailer['nickname'],
                    'phone' => $retailer['phone'],
                    'role' => User::ROLE_RETAILER,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'verification_status' => 'approved',
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                    'city' => $entry?->city,
                    'district' => $entry?->district,
                    'address' => $entry?->address,
                ]
            );

            if ($entry) {
                $entry->markAsUsed($user->id);
            }
        }

        // 4 tedarikçi (seller)
        $sellers = [
            [
                'email' => 'toptan@i-kirtasiye.com',
                'vergi_no' => '5678901234',
                'business_name' => 'Toptan Kırtasiye A.Ş.',
                'nickname' => 'toptankirtasiye',
                'phone' => '0212 555 44 44',
            ],
            [
                'email' => 'egitim@i-kirtasiye.com',
                'vergi_no' => '6789012345',
                'business_name' => 'Eğitim Malzemeleri Ltd.',
                'nickname' => 'egitimmalzeme',
                'phone' => '0216 555 55 55',
            ],
            [
                'email' => 'ofis@i-kirtasiye.com',
                'vergi_no' => '7890123456',
                'business_name' => 'Ofis Tedarik Co.',
                'nickname' => 'ofistedarik',
                'phone' => '0312 555 66 66',
            ],
            [
                'email' => 'sanat@i-kirtasiye.com',
                'vergi_no' => '8901234567',
                'business_name' => 'Sanat Malzemeleri Distribütör',
                'nickname' => 'sanatdistributor',
                'phone' => '0232 555 77 77',
            ],
        ];

        foreach ($sellers as $seller) {
            $entry = VergiNoWhitelist::findByVergiNo($seller['vergi_no']);

            $user = User::updateOrCreate(
                ['email' => $seller['email']],
                [
                    'password' => $defaultPassword,
                    'vergi_no' => $seller['vergi_no'],
                    'business_name' => $seller['business_name'],
                    'nickname' => $seller['nickname'],
                    'phone' => $seller['phone'],
                    'role' => User::ROLE_SELLER,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'verification_status' => 'approved',
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                    'city' => $entry?->city,
                    'district' => $entry?->district,
                    'address' => $entry?->address,
                ]
            );

            if ($entry) {
                $entry->markAsUsed($user->id);
            }
        }

        $this->command->info('✓ 1 super-admin, 3 kırtasiyeci, 4 tedarikçi eklendi.');
    }
}
