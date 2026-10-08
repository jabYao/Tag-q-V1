@php
    $c = $collection ?? null;
    $selectedIds = old('products', $selected ?? []);
    $selectedIds = array_map('intval', (array) $selectedIds);
    $checked = old('is_active', $c?->is_active ?? true);
@endphp

@if($errors->any())
    <div class="bg-red-50 text-red-700 p-3 rounded text-sm">
        <ul class="list-disc ml-4">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-2 gap-4">
    <label class="block">Nombre
        <input name="name" value="{{ old('name', $c?->name) }}" class="w-full border rounded px-3 py-2" required>
    </label>
    <label class="block">Slug
        <input name="slug" value="{{ old('slug', $c?->slug) }}" class="w-full border rounded px-3 py-2" required>
    </label>
</div>

<label class="block">Descripción
    <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description', $c?->description) }}</textarea>
</label>

<div class="border rounded p-3">
    <p class="text-sm font-medium mb-2">Productos de la colección</p>
    @forelse($products as $p)
        <label class="flex items-center gap-2 text-sm py-0.5">
            <input type="checkbox" name="products[]" value="{{ $p->id }}" @checked(in_array($p->id, $selectedIds, true))>
            {{ $p->name }} <span class="text-gray-400 text-xs">{{ $p->slug }}</span>
        </label>
    @empty
        <p class="text-gray-400 text-sm">No hay productos disponibles para asignar.</p>
    @endforelse
</div>

<label class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" @checked($checked)> Publicada
</label>
