<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['admin', 'empleado']);
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('producto')?->id;
        $esAdmin = (bool) $this->user()?->isAdmin();

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'subcategoria' => ['required', Rule::in(array_keys(config('tagq.subcategories', [])))],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug,' . $productId],
            'descripcion' => ['nullable', 'string'],
            // Campos sensibles: el admin los edita; el empleado tiene prohibido enviarlos.
            'precio' => $esAdmin ? ['required', 'integer', 'min:0'] : ['prohibited'],
            'stock' => $esAdmin ? ['required', 'integer', 'min:0'] : ['prohibited'],
            'marca' => ['nullable', 'string', 'max:255'],
            'material_pulso' => ['nullable', 'string', 'max:255'],
            'cristal' => ['nullable', 'string', 'max:255'],
            'ancho_correa' => ['nullable', 'string', 'max:255'],
            'movimiento' => ['nullable', 'string', 'max:255'],
            'is_active' => $esAdmin ? ['sometimes', 'boolean'] : ['prohibited'],
            'imagenes' => ['nullable', 'array', 'max:5'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'imagenes_existentes' => ['nullable', 'array'],
            'imagenes_existentes.*' => ['string', 'url'],
        ];
    }
}
