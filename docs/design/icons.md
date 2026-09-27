# icons.md — Sistema de diseño · tienda Tag q

> Inventario de iconos necesarios, según las páginas y componentes ya definidos.
> **Estado: ✅ 81 ARCHIVOS EN EL PROYECTO (80 en preview + 1 isotipo extra).** Ver el preview en [`docs/preview/icons.html`](../preview/icons.html) (abrir en el navegador).
>
> Referencias: `C-xx` → `design/components.md` · `P-xx` / `S-xx` → `../pages.md`

---

## 0.0 Estado de los archivos

Los SVG ya están copiados en el proyecto, agrupados por familia:

| Carpeta | Familia | Iconos | Convención |
|---|---|---|---|
| `resources/svg/feather/` | Feather | 59 | trazo (`stroke`) |
| `resources/svg/mdi/` | Material Design Icons | 10 | relleno (`fill`) |
| `resources/svg/brand/` | Simple Icons | 8 | relleno (`fill`) |
| `resources/svg/brand/` | Nequi + Wompi (oficial) | 2 (+ isotipo Wompi) | relleno (`fill`) — marca registrada |
| `resources/svg/brand/` | PSE (JPG) + Daviplata (provisional) | 2 | `pse.jpg` 240×240 oficial ACH / `daviplata.svg` 120×40 provisional `#E30613` |

**Total: 81 archivos (58 Feather + 10 MDI + 13 Brand). En preview: 80 (isotipo no listado).** Licencias en `resources/svg/LICENSES.md`.

**Preview:** `docs/preview/icons.html` — todos los iconos a 16 / 20 / 24 / 32 / 48 px reales, en fondo claro y oscuro, más una comparación de outline vs sólido.

**Se regenera con:** `node tools/icon-preview.mjs`

**Sin dependencias:** no se instaló ningún paquete. Los SVG se copiaron de los repos y se versionan en el proyecto.

### Escala de tamaños (confirmada)

Tres tamaños. No hay más. Sin tokens nuevos: son utilidades estándar de Tailwind.

| Clase | Píxeles | Uso |
|---|---|---|
| `w-4 h-4` | 16 | Dentro de texto, badges, breadcrumb, campos, tablas del admin |
| `w-5 h-5` | 20 | Botones, acciones, filtros, item de carrito |
| `w-6 h-6` | 24 | Navbar, bolsa, buscador, modales, toasts **(por defecto)** |

**Descartados:** 32 y 48. Cada tamaño extra multiplica la revisión visual de cada componente, e invita a subir de tamaño cuando algo "se ve pequeño".

**Pendiente:** estado vacío (carrito vacío, sin resultados) y página 404. Con la escala actual su icono iría a 24. Falta decidir si eso basta o si esas pantallas usan una **ilustración** aparte (más grande, que no sería un icono de interfaz).

### Falta conseguir — ✅ completado

Todos los logos de pago ya están en el proyecto (PSE y Daviplata agregados el 2026-09-25):

- **Nequi ✅** — `brand/nequi.svg` (95×30, `#CA0080`/`#200020`, 2.6KB) oficial CDN Nequi
- **Wompi ✅** — `brand/wompi.svg` (316×74, `#2C2A29`, 3.5KB) + isotipo 121×121 oficial Wompi
- **PSE ✅** — `brand/pse.jpg` (240×240, 19KB) JPG oficial `pse.com.co` (Liferay) — único SVG público no existe
- **Daviplata ✅ provisional** — `brand/daviplata.svg` (120×40, `#E30613`) vector simplificado provisional; reemplazar por oficial Davivienda cuando se obtenga del brand center

---

## 0.1 Advertencias

### De dónde salen los SVG

`icons.expo.fyi` **solo sirve para navegar y copiar nombres** — es un catálogo de React Native y detrás hay fuentes TTF, no SVG. Los SVG se obtienen de la fuente web de cada familia:

| Familia | Para qué | Dónde obtener los SVG | Licencia |
|---|---|---|---|
| **Feather** | Interfaz (grupos 1–6) | `feathericons.com` (descarga individual) · repo `feathericons/feather` → carpeta `icons/` (287 SVG sueltos) | MIT |
| **Material Design Icons** | Relojería (grupo 7) | `pictogrammers.com/library/mdi/` · npm `@mdi/svg` → carpeta `svg/` · repo `Templarian/MaterialDesign` | Apache 2.0 (verificar) |
| **Heroicons** | Alternativa a Feather | `heroicons.com` · npm `heroicons` → `24/outline/*.svg` | MIT |
| **Simple Icons** | Marcas (grupos 8–9) | `simpleicons.org` · repo `simple-icons/simple-icons` → carpeta `icons/` | CC0 (los archivos) |

**Forma recomendada (sin dependencias permanentes):** clonar o descargar a `/tmp`, copiar **solo los ~82 que usamos** a `resources/svg/`, y borrar el resto. No se instala nada en el proyecto.

### Los iconos de marca tienen reglas propias

Los logos de pago (Visa, Mastercard, PSE, Nequi, Daviplata, Wompi) son **marcas registradas**. Simple Icons libera los archivos como CC0, pero eso no da permiso sobre la marca.

Se pueden usar para **indicar métodos de pago aceptados** (uso normal), pero hay que respetar las guías de cada marca y no deformarlos ni recolorearlos. Para Visa y Mastercard existen brand centers oficiales con los archivos correctos.

---

### La fuente de iconos no cubre todo

**Heroicons no tiene logos de marca.** No existen ahí Instagram, Visa, PSE, Nequi ni Wompi.

Eso significa que harán falta **dos fuentes**, no una:

| Tipo | Fuente candidata |
|---|---|
| Iconos de interfaz (grupos 1–7) | Heroicons (SVG) |
| Logos de marca (grupos 8–9) | Simple Icons, o SVG propios |

### Decisión resuelta: bolsa

Se usa **`shopping-bag` (bolsa)** en todo el sitio: navbar, mini-carrito y botón de compra. El icono de carrito se descartó y se eliminó del proyecto.

### Advertencia de estilo: outline vs sólido

Feather es **outline** (trazo, `stroke`). Material Design Icons y las marcas son **sólidos** (`fill`).

Mezclar un icono sólido entre iconos de trazo en la misma interfaz **se ve inconsistente**. Al elegir los 7 de relojería, preferir las variantes `-outline` de MDI cuando existan; si no, se dibujan a medida.

Además, cada familia trae sus propios atributos (`viewBox`, `stroke-width`, `fill`). El componente `C-01` debe **normalizarlos**, no asumir que todos vienen iguales.

---

## 1. Navegación e interfaz

| ID | Icono | Se usa en |
|---|---|---|
| I-01 | Menú (hamburguesa) | C-27 menú móvil, C-37 navbar |
| I-02 | Cerrar (X) | C-19 modal, C-27 off-canvas, C-18 toast |
| I-03 | Buscar (lupa) | C-38 buscador, P-04 búsqueda |
| I-04 | Usuario | C-37 navbar, C-20 menú de usuario |
| I-05 | Chevron abajo | C-20 dropdown, C-26 acordeón, C-12 select |
| I-06 | Chevron derecha | C-23 breadcrumb, C-36 carrusel, enlaces |
| I-07 | Chevron izquierda | C-36 carrusel, "volver" |
| I-08 | Chevron arriba | C-26 acordeón abierto |
| I-09 | Flecha derecha | C-36 carrusel (siguiente) |
| I-10 | Flecha izquierda | C-36 carrusel (anterior) |
| I-11 | Inicio (home) | C-23 breadcrumb, C-27 menú móvil |
| I-12 | Enlace externo | C-40 footer, páginas legales |

---

## 2. Carrito y compra

| ID | Icono | Se usa en |
|---|---|---|
| I-13 | Carrito | C-37 navbar, C-39 mini-carrito, C-02 botón |
| I-14 | Bolsa | *(alternativa a I-13 — elegir uno)* |
| I-15 | Más (+) | C-15 stepper, admin crear |
| I-16 | Menos (−) | C-15 stepper |
| I-17 | Basura (eliminar) | C-32 item de carrito, admin |
| I-18 | Etiqueta (tag) | C-04 badge, S-06 banner promocional |
| I-19 | Ticket / cupón | P-22 cupones, campo de cupón en P-06 |
| I-20 | Camión (envío) | P-29 política de envíos, estado "Enviado", P-14 |
| I-21 | Caja / paquete | Estado "En preparación", P-14 |
| I-22 | Recibo | P-13 historial, P-14 detalle, factura |
| I-23 | Escudo | C-04 badge garantía, S-10 garantía |
| I-24 | Candado | P-06 checkout (compra segura) |
| I-25 | Tarjeta de crédito | C-16 método de pago, P-06 |
| I-26 | Efectivo | C-16 contraentrega |
| I-27 | Corazón | Wishlist *(solo si entra en alcance)* |

---

## 3. Cuenta de usuario

| ID | Icono | Se usa en |
|---|---|---|
| I-28 | Perfil | C-37, P-15 perfil |
| I-29 | Salir | C-37 menú de usuario |
| I-30 | Llave | P-12 recuperar contraseña |
| I-31 | Ojo (mostrar) | Campo de contraseña (P-10, P-11) |
| I-32 | Ojo tachado (ocultar) | Campo de contraseña |
| I-33 | Sobre (email) | P-11 registro, verificación de email |
| I-34 | Campana | Notificaciones, C-37 |
| I-35 | Mapa / ubicación | P-15 direcciones, P-06 envío |
| I-36 | Lápiz (editar) | P-15, admin |
| I-37 | Documento / lista | P-13 pedidos |
| I-38 | Estrella | Reseñas *(solo si entra en alcance)* |
| I-39 | Engranaje | P-25 configuración, P-15 |

---

## 4. Catálogo y filtros

| ID | Icono | Se usa en |
|---|---|---|
| I-40 | Embudo | C-34 filtros, C-37 navbar (móvil) |
| I-41 | Ordenar (flechas arriba/abajo) | C-35 selector de orden |
| I-42 | Cuadrícula | Vista grid de C-29 |
| I-43 | Lista | Vista lista de C-29 |
| I-44 | Reiniciar / limpiar | Limpiar filtros en C-34 y C-22 |

---

## 5. Estados y feedback

| ID | Icono | Se usa en |
|---|---|---|
| I-45 | Check | C-04 badge, C-17 alerta (éxito) |
| I-46 | Check en círculo | C-18 toast, P-07 confirmación |
| I-47 | X en círculo | C-17 alerta (error), C-18 toast |
| I-48 | Triángulo de advertencia | C-17 alerta (warning), C-04 |
| I-49 | Info en círculo | C-17 alerta (info), C-04 |
| I-50 | Spinner (carga) | C-07, C-02 (botón cargando) |
| I-51 | Reloj / hora | Estado "Pendiente de pago", P-14 |
| I-52 | Bandeja vacía | C-22 estado vacío |
| I-53 | Documento no encontrado | P-32 (404) |

---

## 6. Panel de administración

| ID | Icono | Se usa en |
|---|---|---|
| I-54 | Dashboard (gráfico) | P-16 |
| I-55 | Carpeta / categorías | P-18 |
| I-56 | Almacén / inventario | P-19 |
| I-57 | Megáfono | P-21 promociones |
| I-58 | Grupo de personas | P-23 usuarios y roles |
| I-59 | Gráfico de barras | P-24 reportes |
| I-60 | Calendario | Rangos de fecha en P-20, P-24 |
| I-61 | Descargar / exportar | P-20, P-24 |
| I-62 | Subir / imagen | Galería Cloudinary en P-17 |
| I-63 | Filtro avanzado | P-17, P-19, P-20 |

**Reutilizan iconos anteriores:** pedidos → I-22 · cupones → I-19 · productos → I-69 · crear → I-15 · editar → I-36 · eliminar → I-17 · config → I-39 · buscar → I-03 · ordenar → I-41

---

## 7. Específicos de relojería (S-10 especificaciones)

| ID | Icono | Representa |
|---|---|---|
| I-64 | Reloj analógico | Categoría / producto |
| I-65 | Correa / pulsera | Material de correa |
| I-66 | Engranaje / movimiento | Calibre / movimiento |
| I-67 | Gota de agua | Resistencia al agua |
| I-68 | Diamante / cristal | Cristal de zafiro |
| I-69 | Regla / medida | Tamaño de caja |
| I-70 | Número de serie | Edición limitada / serie |

---

## 8. Redes sociales (logos de marca)

**No están en Heroicons.**

| ID | Icono | Se usa en |
|---|---|---|
| I-71 | Instagram | C-40 footer |
| I-72 | Facebook | C-40 footer |
| I-73 | TikTok | C-40 footer |
| I-74 | WhatsApp | C-40 footer, botón de contacto |
| I-75 | YouTube | C-40 footer *(solo si aplica)* |

---

## 9. Métodos de pago (logos de marca)

**No están en Heroicons.** Son logos oficiales: hay que usar los archivos correctos, no una aproximación.

| ID | Logo | Se usa en |
|---|---|---|
| I-76 | Visa | C-16, P-06, C-40 |
| I-77 | Mastercard | C-16, P-06, C-40 |
| I-78 | American Express | *(solo si aplica)* |
| I-79 | PSE | C-16, P-06, C-40 |
| I-80 | Nequi | C-16, P-06, C-40 |
| I-81 | Daviplata | C-16, P-06, C-40 |
| I-82 | Wompi | C-16, P-06, C-40 |

---

## Resumen

| Grupo | Cantidad |
|---|---|
| Navegación e interfaz | 12 |
| Carrito y compra | 15 |
| Cuenta de usuario | 12 |
| Catálogo y filtros | 5 |
| Estados y feedback | 9 |
| Admin | 10 (+ 10 reutilizados) |
| Relojería | 7 |
| **Subtotal interfaz** | **~70** |
| Redes sociales (marca) | 5 |
| Métodos de pago (marca) | 7 |
| **Subtotal marca** | **12** |

**Decisiones antes de conseguir los archivos:**

1. ¿Qué fuente para los ~70 de interfaz? (Heroicons / Lucide / propios)
2. ¿Qué fuente para los 12 logos de marca? (Simple Icons / SVG oficiales)
3. ¿Carrito o bolsa? (elegir uno, I-13 o I-14)
4. Marcar cuáles son opcionales: I-27 corazón, I-38 estrella, I-75 YouTube, I-78 Amex
