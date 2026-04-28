<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => ['sometimes', 'numeric', 'min:0.01'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'expiry_date' => ['sometimes', 'date', 'after:today'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'sold_out'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.numeric' => 'Fiyat sayısal olmalıdır.',
            'price.min' => 'Fiyat 0\'dan büyük olmalıdır.',
            'stock.integer' => 'Stok miktarı tam sayı olmalıdır.',
            'stock.min' => 'Stok miktarı negatif olamaz.',
            'expiry_date.date' => 'Geçerli bir tarih giriniz.',
            'expiry_date.after' => 'Son kullanma tarihi bugünden sonra olmalıdır.',
            'status.in' => 'Geçersiz durum değeri.',
        ];
    }
}

