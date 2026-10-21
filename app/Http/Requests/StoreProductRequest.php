<?php

namespace App\Http\Requests;

use App\Support\WatchSpecs;
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
        return array_merge([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategoria' => ['required', Rule::in(array_keys(config('tagq.subcategories', [])))],
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'reference')
                    ->where(fn ($query) => $query->where('brand_id', $this->input('brand_id'))),
            ],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],

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

            'is_active' => ['sometimes', 'boolean'],
            'imagenes' => ['nullable', 'array', 'max:5'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], WatchSpecs::rules());
    }
}
