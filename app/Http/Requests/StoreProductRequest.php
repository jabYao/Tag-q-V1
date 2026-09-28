<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Crear productos es exclusivo del admin; el empleado no puede.
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'marca' => ['nullable', 'string', 'max:255'],
            'material_pulso' => ['nullable', 'string', 'max:255'],
            'cristal' => ['nullable', 'string', 'max:255'],
            'ancho_correa' => ['nullable', 'string', 'max:255'],
            'movimiento' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'imagenes' => ['nullable', 'array', 'max:5'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
