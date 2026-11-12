<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        // La contraseña de un cliente nunca se cambia desde el panel, ni
        // siquiera para devolver errores de validación: primero el 404.
        abort_unless($target instanceof User && $target->canAccessAdmin(), 404);

        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'confirm' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'confirm.accepted' => 'Debes confirmar el cambio de contraseña.',
        ];
    }
}
