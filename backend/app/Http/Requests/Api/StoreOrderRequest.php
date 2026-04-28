<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_address_id' => ['required', 'integer', 'exists:user_addresses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_method' => ['nullable', 'string', 'in:standard,express,free'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shipping_address_id.required' => 'Teslimat adresi seçilmelidir.',
            'shipping_address_id.integer' => 'Geçersiz teslimat adresi.',
            'shipping_address_id.exists' => 'Seçilen teslimat adresi bulunamadı.',
            'notes.max' => 'Sipariş notu en fazla 1000 karakter olabilir.',
            'shipping_method.in' => 'Geçersiz kargo yöntemi.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => strip_tags($this->notes),
            ]);
        }
    }
}
