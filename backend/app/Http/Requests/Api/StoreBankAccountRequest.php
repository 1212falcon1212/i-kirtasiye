<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBankAccountRequest extends FormRequest
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
            'bank_name' => ['required', 'string', 'max:100'],
            'iban' => ['required', 'string', 'min:15', 'max:34', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/'],
            'account_holder' => ['required', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'size:8,11', 'regex:/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            'is_default' => ['nullable', 'boolean'],
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
            'bank_name.required' => 'Banka adı gereklidir.',
            'bank_name.max' => 'Banka adı en fazla 100 karakter olabilir.',
            'iban.required' => 'IBAN gereklidir.',
            'iban.min' => 'IBAN en az 15 karakter olmalıdır.',
            'iban.max' => 'IBAN en fazla 34 karakter olabilir.',
            'iban.regex' => 'Geçersiz IBAN formatı.',
            'account_holder.required' => 'Hesap sahibi adı gereklidir.',
            'account_holder.max' => 'Hesap sahibi adı en fazla 255 karakter olabilir.',
            'swift_code.size' => 'SWIFT kodu 8 veya 11 karakter olmalıdır.',
            'swift_code.regex' => 'Geçersiz SWIFT kodu formatı.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('iban')) {
            // Remove spaces and convert to uppercase
            $iban = strtoupper(str_replace([' ', '-'], '', $this->iban));
            $this->merge(['iban' => $iban]);
        }

        if ($this->has('swift_code')) {
            $this->merge(['swift_code' => strtoupper($this->swift_code)]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$validator->errors()->has('iban') && $this->iban) {
                if (!$this->validateIbanChecksum($this->iban)) {
                    $validator->errors()->add('iban', 'IBAN kontrol hatası. Lütfen IBAN\'ı kontrol ediniz.');
                }

                // Validate Turkish IBAN format (TR + 24 digits = 26 chars)
                if (str_starts_with($this->iban, 'TR') && strlen($this->iban) !== 26) {
                    $validator->errors()->add('iban', 'Türk IBAN\'ı 26 karakter olmalıdır.');
                }
            }
        });
    }

    /**
     * Validate IBAN checksum using MOD97 algorithm.
     */
    private function validateIbanChecksum(string $iban): bool
    {
        // Move the first 4 characters to the end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Convert letters to numbers (A=10, B=11, ..., Z=35)
        $numericIban = '';
        foreach (str_split($rearranged) as $char) {
            if (ctype_alpha($char)) {
                $numericIban .= (ord(strtoupper($char)) - ord('A') + 10);
            } else {
                $numericIban .= $char;
            }
        }

        // Calculate MOD97 using bcmod for large numbers
        return bcmod($numericIban, '97') === '1';
    }
}
