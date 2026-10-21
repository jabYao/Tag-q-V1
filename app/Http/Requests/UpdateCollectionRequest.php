<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La curaduría de colecciones es exclusiva del admin; el empleado no puede.
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $collectionId = $this->route('collection')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:collections,slug,' . $collectionId],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
        ];
    }
}
