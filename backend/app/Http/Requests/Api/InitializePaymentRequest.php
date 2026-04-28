<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitializePaymentRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'gateway' => ['required', 'string', 'in:iyzico,paytr,stripe'],
            'return_url' => ['nullable', 'url', 'max:500'],
            'card_holder_name' => ['required_if:gateway,iyzico,paytr', 'nullable', 'string', 'max:100'],
            'card_number' => ['required_if:gateway,iyzico,paytr', 'nullable', 'string', 'digits:16'],
            'expire_month' => ['required_if:gateway,iyzico,paytr', 'nullable', 'string', 'digits:2'],
            'expire_year' => ['required_if:gateway,iyzico,paytr', 'nullable', 'string', 'digits:2'],
            'cvc' => ['required_if:gateway,iyzico,paytr', 'nullable', 'string', 'digits_between:3,4'],
            'installment' => ['nullable', 'integer', 'min:1', 'max:12'],
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
            'order_id.required' => 'Sipariş ID gereklidir.',
            'order_id.exists' => 'Sipariş bulunamadı.',
            'gateway.required' => 'Ödeme yöntemi seçilmelidir.',
            'gateway.in' => 'Geçersiz ödeme yöntemi.',
            'return_url.url' => 'Dönüş URL\'i geçerli bir URL olmalıdır.',
            'card_holder_name.required_if' => 'Kart sahibi adı gereklidir.',
            'card_holder_name.max' => 'Kart sahibi adı en fazla 100 karakter olabilir.',
            'card_number.required_if' => 'Kart numarası gereklidir.',
            'card_number.digits' => 'Kart numarası 16 haneli olmalıdır.',
            'expire_month.required_if' => 'Son kullanma ayı gereklidir.',
            'expire_month.digits' => 'Son kullanma ayı 2 haneli olmalıdır.',
            'expire_year.required_if' => 'Son kullanma yılı gereklidir.',
            'expire_year.digits' => 'Son kullanma yılı 2 haneli olmalıdır.',
            'cvc.required_if' => 'CVC gereklidir.',
            'cvc.digits_between' => 'CVC 3 veya 4 haneli olmalıdır.',
            'installment.min' => 'Taksit sayısı en az 1 olmalıdır.',
            'installment.max' => 'Taksit sayısı en fazla 12 olabilir.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('card_number')) {
            // Remove spaces and dashes from card number
            $this->merge([
                'card_number' => preg_replace('/[\s-]/', '', $this->card_number),
            ]);
        }
    }
}
