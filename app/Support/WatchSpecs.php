<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class WatchSpecs
{
    /**
     * Campos informativos definidos en config/watch_specs.php.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        return collect(config('watch_specs.informative_groups', []))
            ->flatMap(fn (array $group) => $group['fields'] ?? [])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::fields());
    }

    /**
     * Reglas compartidas por StoreProductRequest y UpdateProductRequest.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = [
            'specs' => ['nullable', 'array:'.implode(',', self::keys())],
            'specs_activos' => ['nullable', 'array:'.implode(',', self::keys())],
            'specs_activos.*' => ['sometimes', 'boolean'],
        ];

        foreach (self::fields() as $key => $field) {
            $rules["specs.$key"] = match ($field['type'] ?? 'text') {
                'number' => array_values(array_filter([
                    'nullable',
                    'numeric',
                    isset($field['min']) ? 'min:'.$field['min'] : null,
                    isset($field['max']) ? 'max:'.$field['max'] : null,
                ])),
                'select' => ['nullable', Rule::in(array_keys($field['options'] ?? []))],
                'textarea' => ['nullable', 'string', 'max:2000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }
}
