<?php

namespace App\Imports;

use App\Models\VergiNoWhitelist;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class VergiNoWhitelistImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private int $importedCount = 0;

    private int $skippedCount = 0;

    public function model(array $row)
    {
        $vergiNo = $row['vergi_no'] ?? $row['vergi_numarasi'] ?? $row['tax_number'] ?? null;
        $companyName = $row['firma_adi'] ?? $row['company_name'] ?? $row['firma'] ?? null;
        $city = $row['sehir'] ?? $row['il'] ?? $row['city'] ?? null;
        $district = $row['ilce'] ?? $row['district'] ?? null;
        $address = $row['adres'] ?? $row['address'] ?? null;
        $isActive = $row['aktif'] ?? $row['is_active'] ?? 'Doğru';

        if (empty($vergiNo) || ! preg_match('/^\d{10}$/', (string) $vergiNo)) {
            $this->skippedCount++;

            return null;
        }

        if (VergiNoWhitelist::where('vergi_no', $vergiNo)->exists()) {
            $this->skippedCount++;

            return null;
        }

        $this->importedCount++;

        return new VergiNoWhitelist([
            'vergi_no' => (string) $vergiNo,
            'company_name' => $companyName,
            'city' => $city,
            'district' => $district,
            'address' => $address,
            'is_active' => $this->parseBoolean($isActive),
            'is_used' => false,
        ]);
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (bool) $value;
        }

        $trueValues = ['doğru', 'dogru', 'evet', 'yes', 'true', '1', 'aktif', 'active'];

        return in_array(strtolower(trim((string) $value)), $trueValues);
    }

    public function rules(): array
    {
        return [
            '*' => 'required',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}
