<?php

namespace App\Http\Requests\Api;

use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
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
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('offers')
                    ->where(fn ($q) => $q
                        ->where('seller_id', $this->user()?->id)
                        ->whereIn('status', [Offer::STATUS_ACTIVE, Offer::STATUS_INACTIVE, Offer::STATUS_PENDING, Offer::STATUS_SOLD_OUT])
                        ->whereNull('deleted_at')
                    ),
            ],
            'price' => ['required', 'numeric', 'min:0.01'],
            'stock' => ['required', 'integer', 'min:1'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Ürün seçimi zorunludur.',
            'product_id.exists' => 'Geçerli bir ürün seçiniz.',
            'product_id.unique' => 'Bu ürün için zaten bir ilanınız var. Mevcut ilanınızı düzenleyebilirsiniz.',
            'price.required' => 'Fiyat zorunludur.',
            'price.numeric' => 'Fiyat sayısal olmalıdır.',
            'price.min' => 'Fiyat 0\'dan büyük olmalıdır.',
            'stock.required' => 'Stok miktarı zorunludur.',
            'stock.integer' => 'Stok miktarı tam sayı olmalıdır.',
            'stock.min' => 'Stok miktarı en az 1 olmalıdır.',
            'expiry_date.date' => 'Geçerli bir tarih giriniz.',
            'expiry_date.after' => 'Son kullanma tarihi bugünden sonra olmalıdır.',
        ];
    }
}
