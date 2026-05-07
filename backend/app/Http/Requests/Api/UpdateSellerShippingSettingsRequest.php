<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerShippingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isSeller() || $user->isRetailer());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'default_shipping_fee' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_shipping_fee.numeric' => 'Kargo ücreti sayı olmalıdır.',
            'default_shipping_fee.min' => 'Kargo ücreti negatif olamaz.',
            'free_shipping_threshold.numeric' => 'Ücretsiz kargo eşiği sayı olmalıdır.',
            'free_shipping_threshold.min' => 'Ücretsiz kargo eşiği negatif olamaz.',
        ];
    }

    /**
     * @return array{default_shipping_fee: ?float, free_shipping_threshold: ?float}
     */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            'default_shipping_fee' => $this->normalizeNullable($data['default_shipping_fee'] ?? null),
            'free_shipping_threshold' => $this->normalizeNullable($data['free_shipping_threshold'] ?? null),
        ];
    }

    private function normalizeNullable(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (float) $value;
    }
}
