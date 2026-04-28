<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillUserCityIds extends Command
{
    protected $signature = 'shipping:backfill-city-ids {--dry-run : Sadece raporla, değişiklik yapma}';

    protected $description = 'users.city/district ve user_addresses.city/district stringlerinden city_id/district_id çöz.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $cities = City::with('districts')->get();

        $cityBySlug = [];
        $districtBySlug = [];
        foreach ($cities as $city) {
            $cityBySlug[$this->normalize($city->name)] = $city;
            foreach ($city->districts as $district) {
                $districtBySlug[$city->id.'|'.$this->normalize($district->name)] = $district;
            }
        }

        $usersUpdated = $this->backfill(User::query()->whereNull('city_id')->whereNotNull('city'), $cityBySlug, $districtBySlug, $dry);
        $addressesUpdated = $this->backfill(UserAddress::query()->whereNull('city_id')->whereNotNull('city'), $cityBySlug, $districtBySlug, $dry);

        $this->info(($dry ? '[DRY] ' : '')."Users updated: {$usersUpdated}");
        $this->info(($dry ? '[DRY] ' : '')."UserAddresses updated: {$addressesUpdated}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, City>  $cityBySlug
     * @param  array<string, District>  $districtBySlug
     */
    private function backfill($query, array $cityBySlug, array $districtBySlug, bool $dry): int
    {
        $count = 0;
        $unmatched = [];

        $query->chunkById(200, function ($records) use (&$count, &$unmatched, $cityBySlug, $districtBySlug, $dry) {
            foreach ($records as $record) {
                $citySlug = $this->normalize((string) $record->city);
                $city = $cityBySlug[$citySlug] ?? null;

                if (! $city) {
                    $unmatched[] = $record->city;

                    continue;
                }

                $district = null;
                if (! empty($record->district)) {
                    $districtSlug = $this->normalize((string) $record->district);
                    $district = $districtBySlug[$city->id.'|'.$districtSlug] ?? null;
                }

                if (! $dry) {
                    $record->city_id = $city->id;
                    if ($district) {
                        $record->district_id = $district->id;
                    }
                    $record->saveQuietly();
                }
                $count++;
            }
        });

        if (! empty($unmatched)) {
            $this->warn('Eşleşmeyen iller: '.implode(', ', array_unique(array_slice($unmatched, 0, 20))));
        }

        return $count;
    }

    private function normalize(string $name): string
    {
        $name = Str::lower($name);
        $name = strtr($name, ['ı' => 'i', 'i̇' => 'i']);

        return Str::slug($name);
    }
}
