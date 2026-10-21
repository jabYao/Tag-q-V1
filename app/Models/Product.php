<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id','subcategoria','name','slug','descripcion','precio','stock','imagenes','marca','material_pulso','cristal','ancho_correa','movimiento','is_active'];

    protected function casts(): array
    {
        return [
            'precio' => 'integer',
            'stock' => 'integer',
            'imagenes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
