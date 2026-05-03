<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->input('role', 'retailer');

        $rules = [
            'role' => ['required', 'string', 'in:retailer,seller'],
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.,#^()_+=\-\[\]{}|\\:";\'<>,\/]).+$/',
            ],
            'business_name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'min:3', 'max:100', 'unique:users,nickname'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
        ];

        // Vergi numarası retailer için zorunlu, seller için opsiyonel; her durumda 10 hane ve unique.
        if ($role === 'retailer') {
            $rules['vergi_no'] = ['required', 'string', 'digits:10', 'unique:users,vergi_no'];
        } else {
            $rules['vergi_no'] = ['nullable', 'string', 'digits:10', 'unique:users,vergi_no'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Hesap tipi seçimi gereklidir.',
            'role.in' => 'Geçersiz hesap tipi. Kırtasiyeci veya Tedarikçi seçmelisiniz.',
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'password.required' => 'Şifre gereklidir.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
            'password.regex' => 'Şifre en az bir büyük harf, bir küçük harf, bir rakam ve bir özel karakter içermelidir.',
            'business_name.required' => 'Firma adı gereklidir.',
            'nickname.required' => 'Rumuz gereklidir.',
            'nickname.min' => 'Rumuz en az 3 karakter olmalıdır.',
            'nickname.max' => 'Rumuz en fazla 100 karakter olmalıdır.',
            'nickname.unique' => 'Bu rumuz zaten kullanılmaktadır.',
            'vergi_no.required' => 'Vergi numarası gereklidir.',
            'vergi_no.digits' => 'Vergi numarası 10 haneli olmalıdır.',
            'vergi_no.unique' => 'Bu vergi numarası zaten kayıtlı.',
        ];
    }
}
