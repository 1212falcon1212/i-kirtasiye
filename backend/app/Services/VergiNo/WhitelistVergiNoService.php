<?php

namespace App\Services\VergiNo;

use App\Models\VergiNoWhitelist;

class WhitelistVergiNoService
{
    /**
     * Verify a vergi numarası against the whitelist.
     *
     * @return array{valid: bool, whitelisted: bool, company_name: ?string, city: ?string, district: ?string, address: ?string, error: ?string}
     */
    public function verify(string $vergiNo): array
    {
        if (! $this->validateFormat($vergiNo)) {
            return [
                'valid' => false,
                'whitelisted' => false,
                'company_name' => null,
                'city' => null,
                'district' => null,
                'address' => null,
                'error' => 'Geçersiz vergi numarası formatı. Vergi numarası 10 haneli olmalıdır.',
            ];
        }

        $entry = VergiNoWhitelist::findByVergiNo($vergiNo);

        if (! $entry) {
            return [
                'valid' => true,
                'whitelisted' => false,
                'company_name' => null,
                'city' => null,
                'district' => null,
                'address' => null,
                'error' => 'Bu vergi numarası onaylı listede bulunamadı.',
            ];
        }

        if (! $entry->is_active) {
            return [
                'valid' => true,
                'whitelisted' => false,
                'company_name' => $entry->company_name,
                'city' => $entry->city,
                'district' => $entry->district,
                'address' => $entry->address,
                'error' => 'Bu vergi numarası pasif durumda.',
            ];
        }

        if ($entry->is_used) {
            return [
                'valid' => true,
                'whitelisted' => false,
                'company_name' => $entry->company_name,
                'city' => $entry->city,
                'district' => $entry->district,
                'address' => $entry->address,
                'error' => 'Bu vergi numarası başka bir kullanıcı tarafından kullanılıyor.',
            ];
        }

        return [
            'valid' => true,
            'whitelisted' => true,
            'company_name' => $entry->company_name,
            'city' => $entry->city,
            'district' => $entry->district,
            'address' => $entry->address,
            'error' => null,
        ];
    }

    /**
     * Mark a vergi numarası as used by a user.
     */
    public function markAsUsed(string $vergiNo, int $userId): bool
    {
        $entry = VergiNoWhitelist::findByVergiNo($vergiNo);

        if (! $entry) {
            return false;
        }

        return $entry->markAsUsed($userId);
    }

    /**
     * Release a vergi numarası (mark as unused).
     */
    public function release(string $vergiNo): bool
    {
        $entry = VergiNoWhitelist::findByVergiNo($vergiNo);

        if (! $entry) {
            return false;
        }

        return $entry->release();
    }

    /**
     * Validate vergi numarası format.
     * Turkish vergi numarası is exactly 10 digits, all numeric.
     */
    public function validateFormat(string $vergiNo): bool
    {
        return (bool) preg_match('/^\d{10}$/', $vergiNo);
    }
}
