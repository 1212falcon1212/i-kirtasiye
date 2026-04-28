<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
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
            'offer_id' => ['required', 'integer', 'exists:offers,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
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
            'offer_id.required' => 'Teklif ID gereklidir.',
            'offer_id.integer' => 'Geçersiz teklif ID.',
            'offer_id.exists' => 'Teklif bulunamadı.',
            'quantity.required' => 'Miktar gereklidir.',
            'quantity.integer' => 'Miktar tam sayı olmalıdır.',
            'quantity.min' => 'Miktar en az 1 olmalıdır.',
            'quantity.max' => 'Miktar en fazla 9999 olabilir.',
        ];
    }
}
