@php
    $p = $product ?? null;
    $isAdmin = auth()->user()?->isAdmin() ?? false;
@endphp

@if($errors->any())
    <div class="bg-red-50 text-red-700 p-3 rounded text-sm">
        <ul class="list-disc ml-4">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-2 gap-4">
    <label class="block">Categoría
        <select name="category_id" class="w-full border rounded px-3 py-2">
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $p?->category_id) == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">Subcategoría
        <select name="subcategoria" class="w-full border rounded px-3 py-2" required>
            @foreach(config('tagq.subcategories', []) as $slug => $label)
                <option value="{{ $slug }}" @selected(old('subcategoria', $p?->subcategoria) === $slug)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">Nombre
        <input name="name" value="{{ old('name', $p?->name) }}" class="w-full border rounded px-3 py-2" required>
    </label>
    <label class="block">Slug
        <input name="slug" value="{{ old('slug', $p?->slug) }}" class="w-full border rounded px-3 py-2" required>
    </label>
    <label class="block">Precio COP (sin puntos)
        <input name="precio" type="number" value="{{ old('precio', $p?->precio) }}" class="w-full border rounded px-3 py-2" required @disabled(!$isAdmin)>
        @unless($isAdmin)<span class="text-xs text-gray-400">Solo el administrador puede modificar el precio.</span>@endunless
    </label>
    <label class="block">Stock
        <input name="stock" type="number" value="{{ old('stock', $p?->stock ?? 0) }}" class="w-full border rounded px-3 py-2" required @disabled(!$isAdmin)>
        @unless($isAdmin)<span class="text-xs text-gray-400">Solo el administrador puede modificar el stock.</span>@endunless
    </label>
    <label class="block">Marca
        <input name="marca" value="{{ old('marca', $p?->marca) }}" class="w-full border rounded px-3 py-2">
    </label>
    <label class="block">Material pulso
        <input name="material_pulso" value="{{ old('material_pulso', $p?->material_pulso) }}" class="w-full border rounded px-3 py-2">
    </label>
    <label class="block">Cristal
        <input name="cristal" value="{{ old('cristal', $p?->cristal) }}" class="w-full border rounded px-3 py-2">
    </label>
    <label class="block">Ancho correa
        <input name="ancho_correa" value="{{ old('ancho_correa', $p?->ancho_correa) }}" class="w-full border rounded px-3 py-2">
    </label>
    <label class="block">Movimiento
        <input name="movimiento" value="{{ old('movimiento', $p?->movimiento) }}" class="w-full border rounded px-3 py-2">
    </label>
</div>

<label class="block">Descripción
    <textarea name="descripcion" rows="3" class="w-full border rounded px-3 py-2">{{ old('descripcion', $p?->descripcion) }}</textarea>
</label>

<label class="block">
    <span>Imágenes (jpg/png/webp, máx 5, 5MB c/u)</span>
    <input type="file" name="imagenes[]" multiple accept="image/*" class="w-full border rounded px-3 py-2">
</label>

@if($isAdmin)
<label class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true))> Activo
</label>
@endif
