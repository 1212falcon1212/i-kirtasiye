<?php

namespace App\Http\Requests\Api;

use App\Models\SellerDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentRequest extends FormRequest
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
        $allowedTypes = array_keys(SellerDocument::TYPE_LABELS);

        return [
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240', // 10MB max
            ],
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
            'type.required' => 'Belge tipi gereklidir.',
            'type.in' => 'Geçersiz belge tipi.',
            'file.required' => 'Dosya yüklenmelidir.',
            'file.file' => 'Geçerli bir dosya yüklenmelidir.',
            'file.mimes' => 'Dosya formatı PDF, JPG, JPEG veya PNG olmalıdır.',
            'file.max' => 'Dosya boyutu en fazla 10MB olabilir.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'belge tipi',
            'file' => 'dosya',
        ];
    }
}
