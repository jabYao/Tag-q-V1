<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendRegistrationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Ingresa el código de verificación.',
        ];
    }
}
