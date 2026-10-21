@php
    $c = $collection ?? null;
    $selectedIds = old('products', $selected ?? []);
    $selectedIds = array_map('intval', (array) $selectedIds);
    $checked = old('is_active', $c?->is_active ?? true);
@endphp

<section class="admin-form__section" aria-labelledby="coleccion-identidad">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="coleccion-identidad">Identidad</h2>
        <p class="admin-form__section-note">Los datos que identifican la selección.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="name">Nombre</label>
            <input class="admin-input" id="name" name="name" value="{{ old('name', $c?->name) }}" required autocomplete="off">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="slug">Slug</label>
            <input class="admin-input" id="slug" name="slug" value="{{ old('slug', $c?->slug) }}" required autocomplete="off">
            <p class="admin-help">Identificador único. Usa minúsculas, sin espacios y con guiones.</p>
        </div>

        <div class="admin-field admin-field--full">
            <label class="admin-label" for="description">Descripción</label>
            <textarea class="admin-textarea" id="description" name="description" rows="4">{{ old('description', $c?->description) }}</textarea>
            <p class="admin-help">Opcional. Una línea editorial para describir la selección.</p>
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="coleccion-productos">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="coleccion-productos">Productos</h2>
        <p class="admin-form__section-note">Relojes que forman la selección.</p>
    </div>

    <div class="admin-check-list">
        @forelse($products as $p)
            <label class="admin-check-row">
                <input type="checkbox" name="products[]" value="{{ $p->id }}" @checked(in_array($p->id, $selectedIds, true))>
                <span class="admin-check-row__label" title="{{ $p->name }}">{{ $p->name }}</span>
                <span class="admin-check-row__meta" aria-hidden="true">{{ $p->slug }}</span>
            </label>
        @empty
            <div class="admin-empty">No hay productos en el catálogo. Guarda la colección y asigna relojes cuando existan.</div>
        @endforelse
    </div>

    @if($products->isNotEmpty())
        <p class="admin-help">
            {{ $products->count() }} {{ $products->count() === 1 ? 'producto disponible' : 'productos disponibles' }}.
            Desmarca todos para dejar la colección sin productos.
        </p>
    @endif
</section>

<section class="admin-form__section" aria-labelledby="coleccion-publicacion">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="coleccion-publicacion">Publicación</h2>
        <p class="admin-form__section-note">Visibilidad de la selección en la tienda.</p>
    </div>

    <label class="admin-check" for="is_active">
        <input type="checkbox" id="is_active" name="is_active" value="1" @checked($checked)>
        <span class="admin-check__text">
            Publicada
            <span class="admin-check__help">Las colecciones sin publicar no se muestran en la tienda.</span>
        </span>
    </label>
</section>
