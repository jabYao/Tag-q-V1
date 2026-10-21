@php
    $p = $product ?? null;
    $isAdmin = auth()->user()?->isAdmin() ?? false;
    $lowStockThreshold = (int) config('tagq.low_stock_threshold', 3);
@endphp

<section class="admin-form__section" aria-labelledby="producto-identidad">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="producto-identidad">Identidad</h2>
        <p class="admin-form__section-note">Lo que ve el cliente en el catálogo.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="category_id">Categoría</label>
            <select class="admin-select" id="category_id" name="category_id" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $p?->category_id) == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="subcategoria">Subcategoría</label>
            <select class="admin-select" id="subcategoria" name="subcategoria" required>
                @foreach(config('tagq.subcategories', []) as $slug => $label)
                    <option value="{{ $slug }}" @selected(old('subcategoria', $p?->subcategoria) === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="name">Nombre</label>
            <input class="admin-input" id="name" name="name" value="{{ old('name', $p?->name) }}" required autocomplete="off">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="slug">Slug</label>
            <input class="admin-input" id="slug" name="slug" value="{{ old('slug', $p?->slug) }}" required autocomplete="off">
            <p class="admin-help">Se usa en la URL pública del producto.</p>
        </div>

        <div class="admin-field admin-field--full">
            <label class="admin-label" for="descripcion">Descripción</label>
            <textarea class="admin-textarea" id="descripcion" name="descripcion" rows="4">{{ old('descripcion', $p?->descripcion) }}</textarea>
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="producto-especificaciones">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="producto-especificaciones">Especificaciones</h2>
        <p class="admin-form__section-note">Ficha técnica del reloj.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="marca">Marca</label>
            <input class="admin-input" id="marca" name="marca" value="{{ old('marca', $p?->marca) }}">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="movimiento">Movimiento</label>
            <input class="admin-input" id="movimiento" name="movimiento" value="{{ old('movimiento', $p?->movimiento) }}">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="material_pulso">Material del pulso</label>
            <input class="admin-input" id="material_pulso" name="material_pulso" value="{{ old('material_pulso', $p?->material_pulso) }}">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="cristal">Cristal</label>
            <input class="admin-input" id="cristal" name="cristal" value="{{ old('cristal', $p?->cristal) }}">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="ancho_correa">Ancho de correa</label>
            <input class="admin-input" id="ancho_correa" name="ancho_correa" value="{{ old('ancho_correa', $p?->ancho_correa) }}">
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="producto-precio">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="producto-precio">Precio y stock</h2>
        <p class="admin-form__section-note">Inventario por unidad, sin sobreventa.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="precio">Precio COP (sin puntos)</label>
            <input
                class="admin-input"
                id="precio"
                name="precio"
                type="number"
                min="0"
                step="1"
                value="{{ old('precio', $p?->precio) }}"
                required
                @disabled(! $isAdmin)
            >
            @unless($isAdmin)
                <p class="admin-help">Solo el administrador puede modificar el precio.</p>
            @endunless
        </div>

        <div class="admin-field">
            <label class="admin-label" for="stock">Stock</label>
            <input
                class="admin-input"
                id="stock"
                name="stock"
                type="number"
                min="0"
                step="1"
                value="{{ old('stock', $p?->stock ?? 0) }}"
                required
                @disabled(! $isAdmin)
            >
            @unless($isAdmin)
                <p class="admin-help">Solo el administrador puede modificar el stock.</p>
            @endunless
            @if($isAdmin)
                <p class="admin-help">La alerta de stock bajo usa un corte provisional de {{ $lowStockThreshold }} unidades.</p>
            @endif
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="producto-imagenes">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="producto-imagenes">Imágenes</h2>
        <p class="admin-form__section-note">JPG, PNG o WebP · máximo 5 archivos de 5 MB.</p>
    </div>

    <div class="admin-field">
        <label class="admin-label" for="imagenes">Agregar imágenes</label>
        <input class="admin-file" id="imagenes" name="imagenes[]" type="file" multiple accept="image/jpeg,image/png,image/webp">
    </div>

    @if($p && !empty($p->imagenes))
        <div class="admin-field admin-field--spaced">
            <p class="admin-label">Imágenes actuales</p>
            <p class="admin-help admin-help--grid">Desmarca una imagen para eliminarla al guardar.</p>
            <div class="admin-image-grid">
                @foreach($p->imagenes as $url)
                    <label class="admin-image-card">
                        <img class="admin-image-card__preview" src="{{ \App\Support\CloudinaryUrl::webp($url) }}" alt="Imagen {{ $loop->iteration }} del producto">
                        <span class="admin-image-card__meta" title="{{ $url }}">Imagen {{ $loop->iteration }}</span>
                        <span class="admin-image-card__check">
                            <input type="checkbox" name="imagenes_existentes[]" value="{{ $url }}" checked>
                            Conservar
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif
</section>

@if($isAdmin)
    <section class="admin-form__section" aria-labelledby="producto-publicacion">
        <div class="admin-form__section-head">
            <h2 class="admin-form__section-title" id="producto-publicacion">Publicación</h2>
            <p class="admin-form__section-note">Los productos inactivos no se muestran en la tienda.</p>
        </div>

        <label class="admin-check" for="is_active">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true))>
            <span class="admin-check__text">
                Producto activo
                <span class="admin-check__help">Visible en el catálogo público y disponible para compra.</span>
            </span>
        </label>
    </section>
@endif
