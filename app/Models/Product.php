<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'subcategoria',
        'name',
        'reference',
        'slug',
        'descripcion',
        'precio',
        'stock',
        'imagenes',
        'marca',
        'material_pulso',
        'cristal',
        'ancho_correa',
        'movimiento',
        'case_shape',
        'case_material',
        'case_color',
        'diameter_mm',
        'thickness_mm',
        'water_resistance_atm',
        'strap_type',
        'specs',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'brand_id' => 'integer',
            'precio' => 'integer',
            'stock' => 'integer',
            'imagenes' => 'array',
            'diameter_mm' => 'float',
            'thickness_mm' => 'float',
            'water_resistance_atm' => 'integer',
            'specs' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Nombre de marca con compatibilidad para productos legados que todavía
     * usan la columna `marca` mientras el dueño borra esos productos.
     */
    public function brandName(): ?string
    {
        return $this->brand?->name ?? $this->marca;
    }

    /**
     * Etiqueta legible de la subcategoría fija (slug -> etiqueta).
     * Devuelve null si la fila es legada y no tiene subcategoría.
     */
    public function subcategoriaLabel(): ?string
    {
        if (blank($this->subcategoria)) {
            return null;
        }

        return config('tagq.subcategories')[$this->subcategoria] ?? null;
    }

    public function movementLabel(): ?string
    {
        return $this->configLabel('watch_specs.movement_types', $this->movimiento);
    }

    public function crystalLabel(): ?string
    {
        return $this->configLabel('watch_specs.crystal_materials', $this->cristal);
    }

    public function caseShapeLabel(): ?string
    {
        return $this->configLabel('watch_specs.case_shapes', $this->case_shape);
    }

    public function strapTypeLabel(): ?string
    {
        return $this->configLabel('watch_specs.strap_types', $this->strap_type);
    }

    public function waterResistanceLabel(): ?string
    {
        if ($this->water_resistance_atm === null) {
            return null;
        }

        return config('watch_specs.water_resistance_options')[$this->water_resistance_atm]
            ?? $this->water_resistance_atm.' ATM';
    }

    public function spec(string $key): mixed
    {
        return $this->specs[$key] ?? null;
    }

    private function configLabel(string $configKey, ?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return config($configKey)[$value] ?? $value;
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withTimestamps();
    }
}
