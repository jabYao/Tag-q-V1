@use('App\Support\CloudinaryUrl')
@extends('layouts.storefront')

@section('title', $product->name.' — '.config('app.name', 'Tag q'))

@section('main-class', 'w-full overflow-x-clip bg-white')

@section('main-attributes', ' data-product-detail')

@section('content')
    @php
        $imagenes = array_values(array_filter($product->imagenes ?? []));
        $imagenesWebp = array_map(fn ($imagen) => CloudinaryUrl::webp($imagen), $imagenes);
        $totalImagenes = count($imagenesWebp);
        $formatMm = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');

        $diametro = filled($product->diameter_mm) ? $formatMm($product->diameter_mm).' mm' : null;

        // Resumen técnico del riel: solo datos reales y presentes, con un
        // máximo de tres celdas para que la pieza no compita con la ficha.
        $resumenTecnico = array_slice(array_filter([
            'Movimiento' => $product->movementLabel(),
            'Cristal' => $product->crystalLabel(),
            'Resistencia' => $product->waterResistanceLabel(),
            'Diámetro' => $diametro,
        ], fn ($valor) => filled($valor)), 0, 3, true);

        $referencia = filled($product->reference) ? $product->reference : null;
        $disponible = $product->stock > 0;

        $subtituloTecnico = collect([
            $product->movementLabel(),
            filled($product->case_material) ? $product->case_material : null,
            $diametro,
        ])->filter()->implode(' · ');

        $especificaciones = array_filter([
            'Marca' => $product->brandName(),
            'Referencia' => $product->reference,
            'Movimiento' => $product->movementLabel(),
            'Forma de caja' => $product->caseShapeLabel(),
            'Material de caja' => $product->case_material,
            'Color de caja' => $product->case_color,
            'Cristal' => $product->crystalLabel(),
            'Diámetro' => $diametro,
            'Grosor' => filled($product->thickness_mm) ? $formatMm($product->thickness_mm).' mm' : null,
            'Resistencia al agua' => $product->waterResistanceLabel(),
            'Tipo de correa' => $product->strapTypeLabel(),
            // Campos legados: se muestran solo mientras el producto viejo no
            // tenga el dato nuevo equivalente. El dueño los borrará a mano.
            'Material del pulso' => $product->strap_type ? null : $product->material_pulso,
            'Ancho de correa' => $product->spec('ancho_correa_mm') ? null : $product->ancho_correa,
        ], fn ($valor) => filled($valor));

        foreach (config('watch_specs.informative_groups', []) as $group) {
            foreach ($group['fields'] as $key => $field) {
                $valor = $product->spec($key);

                if (! filled($valor)) {
                    continue;
                }

                if (($field['type'] ?? null) === 'select') {
                    $valor = $field['options'][$valor] ?? $valor;
                }

                $especificaciones[$field['label']] = $valor;
            }
        }

        $categoria = $product->category?->name ?? 'Sin categoría';
        $subcategoria = $product->subcategoriaLabel();
        $categoriaCompleta = collect([$categoria, $subcategoria])->filter()->implode(' / ');
        $marca = $product->brandName() ?: $categoria;

        if (! empty($especificaciones)) {
            $especificaciones['Categoría'] = $categoriaCompleta;
        }

        // La ficha técnica dejó de ser una tabla plana: se agrupa por familias
        // reales de datos para que el comprador lea la pieza por partes. Cada
        // grupo tiene un valor principal (lead) que se retira de las filas para
        // no repetirlo; los campos sin grupo caen en "Otros datos".
        $definicionGrupos = [
            ['label' => 'Identidad', 'lead' => 'Marca', 'fields' => ['Referencia', 'Categoría']],
            ['label' => 'Movimiento', 'lead' => 'Movimiento', 'fields' => ['Calibre', 'Rubíes', 'Funciones / complicaciones']],
            ['label' => 'Caja y medidas', 'lead' => 'Diámetro', 'fields' => ['Forma de caja', 'Material de caja', 'Color de caja', 'Grosor', 'Acabado de caja', 'Bisel', 'Corona', 'Fondo', 'Distancia entre puntas de asas (mm)', 'Distancia entre asas (mm)', 'Peso (g)']],
            ['label' => 'Cristal y esfera', 'lead' => 'Cristal', 'fields' => ['Forma', 'Antirreflejo', 'Color de esfera', 'Acabado de esfera', 'Índices', 'Agujas', 'Lumen', 'Ventana de fecha', 'Segundero']],
            ['label' => 'Hermeticidad', 'lead' => 'Resistencia al agua', 'fields' => ['Válvula de helio', 'Certificación ISO 6425 (diver)', 'Resistencia a golpes', 'Rango de temperatura']],
            ['label' => 'Correa', 'lead' => 'Tipo de correa', 'fields' => ['Material del pulso', 'Material de correa', 'Ancho de correa', 'Ancho de correa (mm)', 'Largo de correa (mm)', 'Color de correa', 'Cierre', 'Extras']],
            ['label' => 'Extras', 'lead' => null, 'fields' => ['País de origen', 'Edición limitada', 'Número de edición', 'Unidades de la edición']],
        ];

        $gruposFicha = [];
        $camposUsados = [];

        foreach ($definicionGrupos as $definicion) {
            $campos = [];

            foreach ($definicion['fields'] as $etiqueta) {
                if (array_key_exists($etiqueta, $especificaciones)) {
                    $campos[$etiqueta] = $especificaciones[$etiqueta];
                    $camposUsados[$etiqueta] = true;
                }
            }

            $leadValor = null;

            if ($definicion['lead'] !== null && array_key_exists($definicion['lead'], $especificaciones)) {
                $leadValor = $especificaciones[$definicion['lead']];
                $camposUsados[$definicion['lead']] = true;
            }

            if ($leadValor === null && $campos === []) {
                continue;
            }

            $gruposFicha[] = [
                'label' => $definicion['label'],
                'leadLabel' => $definicion['lead'],
                'lead' => $leadValor,
                'fields' => $campos,
            ];
        }

        $camposRestantes = array_diff_key($especificaciones, $camposUsados);

        if ($camposRestantes !== []) {
            $gruposFicha[] = [
                'label' => 'Otros datos',
                'leadLabel' => null,
                'lead' => null,
                'fields' => $camposRestantes,
            ];
        }
    @endphp

    <div class="pd-crumbs" data-pd-utility>
        <div class="pd-shell pd-crumbs-inner">
            <nav class="pd-crumbs-nav" aria-label="Ruta de navegación">
                <a href="{{ route('home') }}">Inicio</a>
                <span class="pd-crumbs-sep" aria-hidden="true">/</span>
                <a href="{{ route('catalog.index') }}">Catálogo</a>
                @if($product->category)
                    <span class="pd-crumbs-sep" aria-hidden="true">/</span>
                    <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}">{{ $categoria }}</a>
                @endif
                <span class="pd-crumbs-sep" aria-hidden="true">/</span>
                <span class="pd-crumbs-current" aria-current="page">{{ $product->name }}</span>
            </nav>

            <div class="pd-crumbs-chips">
                @if($referencia !== null)
                    <span class="pd-crumb-chip">REF. {{ $referencia }}</span>
                @endif
                <span class="pd-crumb-chip"><i aria-hidden="true"></i>Envío a toda Colombia</span>
                <span class="pd-crumb-chip {{ $disponible ? 'pd-crumb-chip--strong' : 'pd-crumb-chip--out' }}">
                    <i aria-hidden="true"></i>{{ $disponible ? 'Disponible' : 'Agotado' }}
                </span>
            </div>
        </div>
    </div>

    <div class="pd-shell pd-main">
        <div class="pd-hero">
            <section
                class="pd-gallery @if($totalImagenes <= 1) is-single @endif"
                data-product-gallery
                data-pd-gallery
                data-gallery-count="{{ $totalImagenes }}"
                @if($totalImagenes === 1) data-gallery-single @endif
                @if($totalImagenes > 1) x-data="{ active: 0 }" @endif
            >
                @if($totalImagenes > 0)
                    <figure class="pd-frame" data-pd-media>
                        <div
                            id="product-gallery-main-{{ $product->id }}"
                            class="pd-main-photos"
                            data-gallery-main
                            role="img"
                            @if($totalImagenes > 1)
                                :aria-label="@js($product->name) + ' — vista ' + (active + 1) + ' de {{ $totalImagenes }}'"
                            @else
                                aria-label="{{ $product->name }} — fotografía del producto"
                            @endif
                        >
                            @foreach($imagenesWebp as $indice => $imagen)
                                <img
                                    class="pd-main-image @if($indice === 0) is-active @endif"
                                    data-gallery-slide
                                    src="{{ $imagen }}"
                                    alt=""
                                    width="1200"
                                    height="1200"
                                    @if($indice === 0)
                                        loading="eager"
                                        fetchpriority="high"
                                    @else
                                        loading="lazy"
                                    @endif
                                    @if($totalImagenes > 1)
                                        x-bind:class="{ 'is-active': active === {{ $indice }} }"
                                    @endif
                                >
                            @endforeach
                        </div>

                        <div class="pd-frame-hud pd-frame-hud--start">
                            <span class="pd-kicker">PIEZA <b>{{ $marca }}</b></span>
                            @if($diametro !== null)
                                <span class="pd-kicker"><i aria-hidden="true"></i>{{ $diametro }}</span>
                            @endif
                        </div>

                        @if($product->waterResistanceLabel() !== null)
                            <span class="pd-water">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3s6 6.5 6 10a6 6 0 0 1-12 0c0-3.5 6-10 6-10Z" />
                                </svg>
                                {{ $product->waterResistanceLabel() }}
                            </span>
                        @endif

                        @if($totalImagenes > 1)
                            <span class="pd-photo-index" aria-hidden="true">
                                <strong x-text="String(active + 1).padStart(2, '0')">01</strong>
                                <span>/ {{ str_pad((string) $totalImagenes, 2, '0', STR_PAD_LEFT) }}</span>
                            </span>
                        @endif

                        <span class="pd-frame-rule" aria-hidden="true"></span>
                    </figure>

                    @if($totalImagenes > 1)
                        <div class="pd-rail" data-pd-rail role="group" aria-label="Vistas de {{ $product->name }}">
                            @foreach($imagenesWebp as $indice => $imagen)
                                <button
                                    type="button"
                                    class="pd-thumb @if($indice === 0) is-active @endif"
                                    data-gallery-thumb
                                    data-gallery-thumb-index="{{ $indice }}"
                                    aria-controls="product-gallery-main-{{ $product->id }}"
                                    aria-label="Ver vista {{ $indice + 1 }} de {{ $totalImagenes }}: {{ $product->name }}"
                                    aria-pressed="{{ $indice === 0 ? 'true' : 'false' }}"
                                    x-on:click="active = {{ $indice }}"
                                    x-bind:class="{ 'is-active': active === {{ $indice }} }"
                                    x-bind:aria-pressed="active === {{ $indice }}"
                                >
                                    <img
                                        src="{{ $imagen }}"
                                        alt=""
                                        width="160"
                                        height="160"
                                        loading="lazy"
                                    >
                                    <span class="pd-thumb-index" aria-hidden="true">{{ str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <figure
                        class="pd-frame pd-frame--empty"
                        data-pd-media
                        data-gallery-empty
                        role="img"
                        aria-label="{{ $product->name }} — sin imagen disponible"
                    >
                        <div class="pd-frame-hud pd-frame-hud--start">
                            <span class="pd-kicker">PIEZA <b>{{ $marca }}</b></span>
                        </div>

                        <div class="pd-empty">
                            <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                                <circle cx="32" cy="32" r="24"></circle>
                                <path d="M32 18v15l9 5"></path>
                                <path d="M25 8h14M25 56h14"></path>
                            </svg>
                            <p>Sin imagen</p>
                            <span>Estamos preparando la fotografía de esta pieza.</span>
                        </div>

                        <span class="pd-frame-rule" aria-hidden="true"></span>
                    </figure>
                @endif

                @if($resumenTecnico !== [])
                    <div class="pd-techstrip">
                        <div class="pd-techstrip-top">
                            <div class="pd-techstrip-head">
                                <span class="pd-techstrip-mark" aria-hidden="true">
                                    <svg focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="8" />
                                        <path d="M12 7v5l3 2" />
                                    </svg>
                                </span>
                                <span class="pd-techstrip-copy">
                                    <strong>Resumen técnico</strong>
                                    <span>Lectura rápida de la pieza</span>
                                </span>
                            </div>

                            <a class="pd-techstrip-link" href="#ficha-tecnica">
                                Ver ficha técnica
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14" />
                                    <path d="m6 13 6 6 6-6" />
                                </svg>
                            </a>
                        </div>

                        <div class="pd-micro" data-pd-micro role="group" aria-label="Resumen técnico">
                            @foreach($resumenTecnico as $etiqueta => $valor)
                                <div class="pd-micro-item">
                                    <span>{{ $etiqueta }}</span>
                                    <strong>{{ $valor }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <aside class="pd-buy" data-pd-buy aria-label="Compra de {{ $product->name }}">
                <div class="pd-buy-head">
                    <span class="pd-chip pd-chip--primary">{{ $categoriaCompleta }}</span>
                    @if($referencia !== null)
                        <span class="pd-buy-ref">REF. {{ $referencia }}</span>
                    @endif
                </div>

                <h1 class="pd-title">{{ $product->name }}</h1>

                @if($subtituloTecnico !== '')
                    <p class="pd-subtitle">{{ $subtituloTecnico }}</p>
                @endif

                @if(filled($product->descripcion))
                    <p class="pd-copy">{{ $product->descripcion }}</p>
                @endif

                <div class="pd-pricebox">
                    <div class="pd-pricebox-top">
                        <span>Precio de tienda</span>
                        <span class="pd-pricebox-status {{ $disponible ? '' : 'pd-pricebox-status--out' }}">
                            <i aria-hidden="true"></i>{{ $disponible ? 'En stock' : 'Agotado' }}
                        </span>
                    </div>

                    <p class="pd-price">
                        $ {{ number_format($product->precio, 0, ',', '.') }}
                        <small>COP</small>
                    </p>

                    <p class="pd-pricebox-note">Tarjeta · PSE · Nequi · Contraentrega</p>
                </div>

                @if($disponible)
                    <p class="pd-stock"><i aria-hidden="true"></i>Disponible: {{ $product->stock }}<span>Máx. {{ $product->stock }} por pedido</span></p>
                @else
                    <p class="pd-stock pd-stock--out"><i aria-hidden="true"></i>Agotado</p>
                @endif

                <div class="pd-purchase">
                    @livewire('cart.add-to-cart', ['product' => $product, 'variant' => 'editorial'], key('detail-'.$product->id))
                </div>

                <a class="pd-secondary" href="{{ route('cart.index') }}">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <path d="M3 6h18" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                    Ver carrito
                </a>

                <div class="pd-meta">
                    <div class="pd-meta-item">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21 8-9-5-9 5v8l9 5 9-5Z" />
                            <path d="M3.3 7 12 12l8.7-5" />
                            <path d="M12 22V12" />
                        </svg>
                        <span><b>Envío nacional</b><span>A toda Colombia</span></span>
                    </div>
                    <div class="pd-meta-item">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span><b>Contraentrega</b><span>Disponible</span></span>
                    </div>
                    <div class="pd-meta-item">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2" />
                            <path d="M2 10h20" />
                        </svg>
                        <span><b>Pago</b><span>Tarjeta · PSE · Nequi</span></span>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <section class="pd-details-band" aria-labelledby="ficha-tecnica">
        <div class="pd-shell">
            <div class="pd-details" data-pd-details>
                <div class="pd-detail-intro" data-pd-details-intro>
                    <div>
                        <h2 id="ficha-tecnica">Especificaciones de la pieza</h2>
                        <p>Los datos registrados de esta pieza: caja, movimiento, cristal y medidas.</p>
                    </div>

                    @if(! empty($especificaciones))
                        <span class="pd-detail-count">{{ count($especificaciones) }} {{ count($especificaciones) === 1 ? 'dato registrado' : 'datos registrados' }}</span>
                    @endif
                </div>

                <div class="pd-specs" data-pd-specs>
                    @if(empty($especificaciones))
                        <p class="pd-spec-empty">Sin especificaciones</p>
                    @else
                        <div class="pd-spec-grid">
                            @foreach($gruposFicha as $grupo)
                                <article class="pd-spec-card" data-pd-spec-group>
                                    <h3>{{ $grupo['label'] }}</h3>

                                    @if($grupo['lead'] !== null)
                                        <p class="pd-spec-lead">
                                            <span>{{ $grupo['leadLabel'] }}</span>
                                            <strong>{{ $grupo['lead'] }}</strong>
                                        </p>
                                    @endif

                                    @if($grupo['fields'] !== [])
                                        <dl>
                                            @foreach($grupo['fields'] as $etiqueta => $valor)
                                                <div data-spec data-pd-spec class="pd-spec">
                                                    <dt>{{ $etiqueta }}</dt>
                                                    <dd>{{ $valor }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($relacionados->isNotEmpty())
        <div class="pd-shell">
            <section class="pd-related" data-pd-related aria-labelledby="otras-piezas">
                <div class="pd-related-head">
                    <h2 id="otras-piezas">Otras piezas</h2>
                    <a href="{{ route('catalog.index') }}">
                        Ver todo el catálogo
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </div>

                <div class="pd-related-grid">
                    @foreach($relacionados as $relacionado)
                        @php
                            $relacionadoImagen = $relacionado->imagenes[0] ?? null;
                            $relacionadoFicha = collect([
                                $relacionado->movementLabel(),
                                $relacionado->waterResistanceLabel(),
                            ])->filter()->implode(' · ');
                            $relacionadoEtiqueta = $relacionado->subcategoriaLabel()
                                ?? $relacionado->brandName()
                                ?? $relacionado->category?->name
                                ?? 'Reloj';
                        @endphp

                        <a
                            class="pd-related-card"
                            data-pd-related-card
                            href="{{ route('catalog.show', $relacionado) }}"
                        >
                            <span class="pd-related-media">
                                @if($relacionadoImagen !== null)
                                    <img
                                        src="{{ CloudinaryUrl::webp($relacionadoImagen) }}"
                                        alt=""
                                        width="640"
                                        height="480"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @else
                                    <span class="pd-related-empty">Sin imagen</span>
                                @endif

                                @if($relacionado->stock <= 0)
                                    <span class="pd-related-status">Agotado</span>
                                @endif
                            </span>

                            <span class="pd-related-body">
                                <span class="pd-related-meta">
                                    <span>{{ $relacionadoEtiqueta }}</span>
                                    <strong>$ {{ number_format($relacionado->precio, 0, ',', '.') }} COP</strong>
                                </span>

                                <span class="pd-related-body-title">{{ $relacionado->name }}</span>

                                @if($relacionadoFicha !== '')
                                    <span class="pd-related-spec">{{ $relacionadoFicha }}</span>
                                @endif

                                <span class="pd-related-link">
                                    Ver pieza
                                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14" />
                                        <path d="m13 6 6 6-6 6" />
                                    </svg>
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    @endif
@endsection
