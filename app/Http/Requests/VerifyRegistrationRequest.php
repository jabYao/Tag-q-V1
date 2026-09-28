<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Ingresa el código de verificación.',
            'code.string' => 'El código no es válido.',
            'code.digits' => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}
