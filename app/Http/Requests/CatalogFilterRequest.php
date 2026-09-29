<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['q', 'categoria', 'marca', 'material_pulso', 'cristal', 'movimiento', 'disponibilidad', 'orden'] as $key) {
            $value = $this->query($key);

            if (is_string($value) && trim($value) === '') {
                $normalized[$key] = null;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255', 'exists:categories,slug'],
            'marca' => ['nullable', 'string', 'max:255'],
            'material_pulso' => ['nullable', 'string', 'max:255'],
            'cristal' => ['nullable', 'string', 'max:255'],
            'movimiento' => ['nullable', 'string', 'max:255'],
            'precio_min' => ['nullable', 'integer', 'min:0'],
            'precio_max' => ['nullable', 'integer', 'min:0'],
            'disponibilidad' => ['nullable', Rule::in(['todos', 'disponible', 'agotado'])],
            'orden' => ['nullable', Rule::in(['recientes', 'precio_asc', 'precio_desc', 'nombre_asc'])],
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez.
     *
     * Un rango de precio invertido (precio_min > precio_max) no filtra nada
     * útil: se rechaza con el error estándar de validación en lugar de devolver
     * un listado ambiguo.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Si un extremo ya es inválido, `rules()` lo reporta: no duplicamos mensajes.
                if ($validator->errors()->has('precio_min') || $validator->errors()->has('precio_max')) {
                    return;
                }

                $min = $this->input('precio_min');
                $max = $this->input('precio_max');

                if (! is_numeric($min) || ! is_numeric($max)) {
                    return;
                }

                if ((int) $min > (int) $max) {
                    $validator->errors()->add('precio_max', 'El precio máximo debe ser mayor o igual que el precio mínimo.');
                }
            },
        ];
    }
}
