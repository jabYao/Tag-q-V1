@php
    $b = $brand ?? null;
    $checked = old('is_active', $b?->is_active ?? true);
@endphp

<section class="admin-form__section" aria-labelledby="marca-identidad">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="marca-identidad">Identidad</h2>
        <p class="admin-form__section-note">Los datos que identifican al fabricante.</p>
    </div>

    <div class="admin-form__grid">
        <div class="admin-field">
            <label class="admin-label" for="name">Nombre</label>
            <input class="admin-input" id="name" name="name" value="{{ old('name', $b?->name) }}" required autocomplete="off">
        </div>

        <div class="admin-field">
            <label class="admin-label" for="slug">Slug</label>
            <input class="admin-input" id="slug" name="slug" value="{{ old('slug', $b?->slug) }}" required autocomplete="off">
            <p class="admin-help">Identificador único. Usa minúsculas, sin espacios y con guiones.</p>
        </div>
    </div>
</section>

<section class="admin-form__section" aria-labelledby="marca-publicacion">
    <div class="admin-form__section-head">
        <h2 class="admin-form__section-title" id="marca-publicacion">Disponibilidad</h2>
        <p class="admin-form__section-note">Controla si la marca se puede asignar a productos nuevos.</p>
    </div>

    <label class="admin-check" for="is_active">
        <input type="checkbox" id="is_active" name="is_active" value="1" @checked($checked)>
        <span class="admin-check__text">
            Activa
            <span class="admin-check__help">Las marcas inactivas no aparecen en el formulario de productos, pero los productos que ya las usan conservan su historial.</span>
        </span>
    </label>

    @if($b && $b->products()->exists())
        <p class="admin-help">Esta marca tiene productos asociados. Para retirarla, desactívala; no se puede eliminar mientras tenga productos.</p>
    @endif
</section>
