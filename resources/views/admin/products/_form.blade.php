@php
    $p = $product ?? null;
    $isAdmin = auth()->user()?->isAdmin() ?? false;
    $lowStockThreshold = (int) config('tagq.low_stock_threshold', 3);
    $brands = $brands ?? collect();
    $specs = old('specs', $p?->specs ?? []);
    $specs = is_array($specs) ? $specs : [];
    $activeSpecs = old('specs_activos', array_fill_keys(array_keys($specs), '1'));
    $activeSpecs = is_array($activeSpecs) ? $activeSpecs : [];
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
            <label class="admin-label" for="name">Nombre / modelo</label>
            <input class="admin-input" id="name" name="name" value="{{ old('name', $p?->name) }}" required autocomplete="off">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="brand_id">Marca</label>
            <select class="admin-select" id="brand_id" name="brand_id" required>
                <option value="">Seleccioná una marca</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) old('brand_id', $p?->brand_id) === (string) $brand->id)>
                        {{ $brand->name }}{{ $brand->is_active ? '' : ' (inactiva)' }}
                    </option>
                @endforeach
            </select>
            @if($brands->isEmpty())
                <p class="admin-help">
                    No hay marcas activas.
                    <a class="admin-link" href="{{ route('admin.brands.create') }}">Creá una marca</a>
                    antes de guardar el producto.
                </p>
            @endif
        </div>

        <div class="admin-field">
            <label class="admin-label" for="reference">Referencia</label>
            <input class="admin-input" id="reference" name="reference" value="{{ old('reference', $p?->reference) }}" required autocomplete="off">
            <p class="admin-help">Única dentro de la marca.</p>
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
        <p class="admin-form__section-note">Ficha técnica obligatoria del reloj. Los campos libres aceptan cualquier valor; las listas usan slugs canónicos.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="case_shape">Forma de caja</label>
            <select class="admin-select" id="case_shape" name="case_shape" required>
                <option value="">Seleccioná una forma</option>
                @foreach(config('watch_specs.case_shapes', []) as $slug => $label)
                    <option value="{{ $slug }}" @selected(old('case_shape', $p?->case_shape) === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="case_material">Material de caja</label>
            <input class="admin-input" id="case_material" name="case_material" value="{{ old('case_material', $p?->case_material) }}" list="case-material-suggestions" required autocomplete="off">
            <datalist id="case-material-suggestions">
                @foreach(config('watch_specs.case_material_suggestions', []) as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="case_color">Color de caja</label>
            <input class="admin-input" id="case_color" name="case_color" value="{{ old('case_color', $p?->case_color) }}" list="case-color-suggestions" required autocomplete="off">
            <datalist id="case-color-suggestions">
                @foreach(config('watch_specs.case_color_suggestions', []) as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="movimiento">Movimiento</label>
            <select class="admin-select" id="movimiento" name="movimiento" required>
                <option value="">Seleccioná un movimiento</option>
                @foreach(config('watch_specs.movement_types', []) as $slug => $label)
                    <option value="{{ $slug }}" @selected(old('movimiento', $p?->movimiento) === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="cristal">Cristal</label>
            <select class="admin-select" id="cristal" name="cristal" required>
                <option value="">Seleccioná un cristal</option>
                @foreach(config('watch_specs.crystal_materials', []) as $slug => $label)
                    <option value="{{ $slug }}" @selected(old('cristal', $p?->cristal) === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="diameter_mm">Diámetro (mm)</label>
            <input class="admin-input" id="diameter_mm" name="diameter_mm" type="number" min="0" max="100" step="0.1" value="{{ old('diameter_mm', $p?->diameter_mm) }}">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="thickness_mm">Grosor (mm)</label>
            <input class="admin-input" id="thickness_mm" name="thickness_mm" type="number" min="0" max="100" step="0.1" value="{{ old('thickness_mm', $p?->thickness_mm) }}">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="water_resistance_atm">Resistencia al agua</label>
            <select class="admin-select" id="water_resistance_atm" name="water_resistance_atm">
                <option value="">Sin especificar</option>
                @foreach(config('watch_specs.water_resistance_options', []) as $atm => $label)
                    <option value="{{ $atm }}" @selected((string) old('water_resistance_atm', $p?->water_resistance_atm) === (string) $atm)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-field">
            <label class="admin-label" for="strap_type">Tipo de correa</label>
            <select class="admin-select" id="strap_type" name="strap_type">
                <option value="">Sin especificar</option>
                @foreach(config('watch_specs.strap_types', []) as $slug => $label)
                    <option value="{{ $slug }}" @selected(old('strap_type', $p?->strap_type) === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="producto-especificaciones-informativas">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="producto-especificaciones-informativas">Especificaciones informativas</h2>
        <p class="admin-form__section-note">Opcionales. Activá solo las que quieras mostrar en la ficha pública; si no tienen valor, no se publican.</p>
    </div>

    @foreach(config('watch_specs.informative_groups', []) as $groupKey => $group)
        <div class="admin-form__subsection" aria-labelledby="spec-group-{{ $groupKey }}">
            <h3 class="admin-form__section-title admin-form__section-title--small" id="spec-group-{{ $groupKey }}">{{ $group['label'] }}</h3>

            <div class="admin-form__grid">
                @foreach($group['fields'] as $key => $field)
                    @php
                        $value = $specs[$key] ?? null;
                        $isActive = array_key_exists($key, $activeSpecs);
                    @endphp
                    <div class="admin-field">
                        <label class="admin-label" for="spec_{{ $key }}">{{ $field['label'] }}</label>

                        @if($field['type'] === 'textarea')
                            <textarea
                                class="admin-textarea"
                                id="spec_{{ $key }}"
                                name="specs[{{ $key }}]"
                                rows="{{ $field['rows'] ?? 3 }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                            >{{ $value }}</textarea>
                        @elseif($field['type'] === 'select')
                            <select class="admin-select" id="spec_{{ $key }}" name="specs[{{ $key }}]">
                                <option value="">Sin especificar</option>
                                @foreach($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input
                                class="admin-input"
                                id="spec_{{ $key }}"
                                name="specs[{{ $key }}]"
                                type="{{ $field['type'] }}"
                                value="{{ $value }}"
                                @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                            >
                        @endif

                        <label class="admin-help flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="specs_activos[{{ $key }}]"
                                value="1"
                                @checked($isActive)
                            >
                            Mostrar en la ficha
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
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
