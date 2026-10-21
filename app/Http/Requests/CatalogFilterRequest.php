<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación de los filtros del catálogo público (query string).
 *
 * Todos los campos son opcionales. Un valor inválido no llega nunca a la
 * consulta: la validación estándar de Laravel responde 422 (JSON) o redirige
 * con errores (navegación normal), evitando SQL inseguro.
 */
class CatalogFilterRequest extends FormRequest
{
    /**
     * El catálogo es público: filtrar no requiere autenticación.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza cadenas en blanco a null para que `nullable` se aplique
     * también cuando el formulario GET envía selects/inputs vacíos.
     *
     * Además resuelve las etiquetas legadas de `cristal` y `movimiento`
     * (con o sin acentos y mayúsculas) al slug canónico que guarda la base.
     * El formulario nuevo envía slugs; los enlaces viejos enviaban etiquetas.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach ([
            'q',
            'categoria',
            'subcategoria',
            'marca',
            'strap_type',
            'water_resistance_atm',
            'material_pulso',
            'cristal',
            'movimiento',
            'diametro_min',
            'diametro_max',
            'grosor_min',
            'grosor_max',
            'precio_min',
            'precio_max',
            'disponibilidad',
            'orden',
        ] as $key) {
            $value = $this->query($key);

            if (is_string($value) && trim($value) === '') {
                $normalized[$key] = null;
            }
        }

        foreach ([
            'cristal' => 'watch_specs.crystal_materials',
            'movimiento' => 'watch_specs.movement_types',
        ] as $key => $configKey) {
            $value = $this->query($key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $normalized[$key] = $this->resolveTaxonomySlug($value, config($configKey, []));
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @param array<string, string> $options
     */
    private function resolveTaxonomySlug(string $value, array $options): string
    {
        if (array_key_exists($value, $options)) {
            return $value;
        }

        $needle = $this->normalizeTaxonomyValue($value);

        foreach ($options as $slug => $label) {
            if ($this->normalizeTaxonomyValue($slug) === $needle
                || $this->normalizeTaxonomyValue($label) === $needle) {
                return $slug;
            }
        }

        return $value;
    }

    private function normalizeTaxonomyValue(string $value): string
    {
        return mb_strtolower(Str::ascii(trim($value)));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Se aceptan slugs canónicos y etiquetas legadas mientras el dueño
        // termina de borrar los productos viejos. Los nuevos formularios solo
        // envían slugs.
        $crystalValues = array_merge(
            array_keys(config('watch_specs.crystal_materials', [])),
            array_values(config('watch_specs.crystal_materials', [])),
        );

        $movementValues = array_merge(
            array_keys(config('watch_specs.movement_types', [])),
            array_values(config('watch_specs.movement_types', [])),
        );

        return [
            'q' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255', 'exists:categories,slug'],
            'subcategoria' => ['nullable', 'string', Rule::in(array_keys(config('tagq.subcategories', [])))],
            'marca' => ['nullable', 'string', 'max:255'],
            'strap_type' => ['nullable', 'string', Rule::in(array_keys(config('watch_specs.strap_types', [])))],
            'water_resistance_atm' => ['nullable', 'integer', Rule::in(array_keys(config('watch_specs.water_resistance_options', [])))],
            'material_pulso' => ['nullable', 'string', 'max:255'],
            'cristal' => ['nullable', 'string', Rule::in($crystalValues)],
            'movimiento' => ['nullable', 'string', Rule::in($movementValues)],
            'diametro_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'diametro_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grosor_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grosor_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'precio_min' => ['nullable', 'integer', 'min:0'],
            'precio_max' => ['nullable', 'integer', 'min:0'],
            'disponibilidad' => ['nullable', Rule::in(['todos', 'disponible', 'agotado'])],
            'orden' => ['nullable', Rule::in(['recientes', 'precio_asc', 'precio_desc', 'nombre_asc'])],
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez.
     *
     * Un rango invertido no filtra nada útil: se rechaza con el error estándar
     * de validación en lugar de devolver un listado ambiguo.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateRange(
                    $validator,
                    'precio_min',
                    'precio_max',
                    'El precio máximo debe ser mayor o igual que el precio mínimo.',
                );

                $this->validateRange(
                    $validator,
                    'diametro_min',
                    'diametro_max',
                    'El diámetro máximo debe ser mayor o igual que el diámetro mínimo.',
                );

                $this->validateRange(
                    $validator,
                    'grosor_min',
                    'grosor_max',
                    'El grosor máximo debe ser mayor o igual que el grosor mínimo.',
                );
            },
        ];
    }

    private function validateRange(Validator $validator, string $minKey, string $maxKey, string $message): void
    {
        // Si un extremo ya es inválido, `rules()` lo reporta: no duplicamos mensajes.
        if ($validator->errors()->has($minKey) || $validator->errors()->has($maxKey)) {
            return;
        }

        $min = $this->input($minKey);
        $max = $this->input($maxKey);

        if (! is_numeric($min) || ! is_numeric($max)) {
            return;
        }

        if ((float) $min > (float) $max) {
            $validator->errors()->add($maxKey, $message);
        }
    }
}
