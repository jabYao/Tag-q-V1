<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isCliente();
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        // El formulario envía seis cuadritos como `code[]`; se unen en un solo
        // valor. Un payload anidado o no escalar se deja vacío para que falle
        // la validación normal en vez de romper con un error 500.
        if (is_array($code)) {
            $digits = [];

            foreach ($code as $value) {
                if (! is_scalar($value)) {
                    $digits = [];
                    break;
                }

                $digits[] = (string) $value;
            }

            $merged = implode('', $digits);

            $this->merge(['code' => $merged === '' ? null : $merged]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Escribe el código que enviamos a tu correo.',
            'code.digits' => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}
