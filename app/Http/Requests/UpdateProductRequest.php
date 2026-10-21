<?php

namespace App\Http\Requests;

use App\Support\WatchSpecs;
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
        $product = $this->route('product') ?? $this->route('producto');
        $productId = $product?->id;
        $currentBrandId = $product?->brand_id;
        $esAdmin = (bool) $this->user()?->isAdmin();

        return array_merge([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategoria' => ['required', Rule::in(array_keys(config('tagq.subcategories', [])))],
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(function ($query) use ($currentBrandId) {
                    $query->where('is_active', true);
                    if ($currentBrandId) {
                        $query->orWhere('id', $currentBrandId);
                    }
                }),
            ],
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'reference')
                    ->where(fn ($query) => $query->where('brand_id', $this->input('brand_id')))
                    ->ignore($productId),
            ],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug,'.$productId],
            'descripcion' => ['nullable', 'string'],

            // Campos sensibles: el admin los edita; el empleado tiene prohibido enviarlos.
            'precio' => $esAdmin ? ['required', 'integer', 'min:0'] : ['prohibited'],
            'stock' => $esAdmin ? ['required', 'integer', 'min:0'] : ['prohibited'],

            'case_shape' => ['required', Rule::in(array_keys(config('watch_specs.case_shapes', [])))],
            'case_material' => ['required', 'string', 'max:255'],
            'case_color' => ['required', 'string', 'max:255'],
            'movimiento' => ['required', Rule::in(array_keys(config('watch_specs.movement_types', [])))],
            'cristal' => ['required', Rule::in(array_keys(config('watch_specs.crystal_materials', [])))],
            'diameter_mm' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'water_resistance_atm' => ['nullable', 'integer', Rule::in(array_keys(config('watch_specs.water_resistance_options', [])))],
            'strap_type' => ['nullable', Rule::in(array_keys(config('watch_specs.strap_types', [])))],

            // Campos legados: se conservan para no romper filas existentes
            // mientras el dueño las borra manualmente. El formulario nuevo no
            // los envía.
            'marca' => ['nullable', 'string', 'max:255'],
            'material_pulso' => ['nullable', 'string', 'max:255'],
            'ancho_correa' => ['nullable', 'string', 'max:255'],

            'is_active' => $esAdmin ? ['sometimes', 'boolean'] : ['prohibited'],
            'imagenes' => ['nullable', 'array', 'max:5'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'imagenes_existentes' => ['nullable', 'array'],
            'imagenes_existentes.*' => ['string', 'url'],
        ], WatchSpecs::rules());
    }
}
