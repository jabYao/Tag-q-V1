#!/usr/bin/env node
// Genera docs/preview/icons.html a partir de resources/svg/{outline,solid,brand}
// Uso: node tools/icon-preview.mjs
//
// Los SVG se incrustan en el HTML (inline) para que la página funcione
// abriéndola directamente con doble clic, sin servidor.

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const SVG_DIR = resolve(ROOT, 'resources/svg');
const OUT = resolve(ROOT, 'docs/preview/icons.html');

// Tamaños de la escala. Mapean 1:1 a utilidades de Tailwind, sin tokens nuevos.
const SIZES = [
  { px: 16, cls: 'w-4 h-4', use: 'Dentro de texto, badges, breadcrumb, campos, tablas' },
  { px: 20, cls: 'w-5 h-5', use: 'Botones, acciones, filtros, item de carrito' },
  { px: 24, cls: 'w-6 h-6', use: 'Navbar, bolsa, buscador, modales, toasts (por defecto)' },
];

const CATALOG = [
  {
    group: '1 · Navegación e interfaz',
    items: [
      { f: 'feather/menu', label: 'Menú', use: 'Navbar móvil, off-canvas' },
      { f: 'feather/x', label: 'Cerrar', use: 'Modal, off-canvas, toast' },
      { f: 'feather/search', label: 'Buscar', use: 'Buscador, página de búsqueda' },
      { f: 'feather/user', label: 'Usuario', use: 'Navbar, cuenta' },
      { f: 'feather/chevron-down', label: 'Chevron abajo', use: 'Dropdown, acordeón, select' },
      { f: 'feather/chevron-right', label: 'Chevron derecha', use: 'Breadcrumb, carrusel' },
      { f: 'feather/chevron-left', label: 'Chevron izquierda', use: 'Carrusel, volver' },
      { f: 'feather/chevron-up', label: 'Chevron arriba', use: 'Acordeón abierto' },
      { f: 'feather/arrow-right', label: 'Flecha derecha', use: 'Carrusel siguiente' },
      { f: 'feather/arrow-left', label: 'Flecha izquierda', use: 'Carrusel anterior' },
      { f: 'feather/home', label: 'Inicio', use: 'Breadcrumb, menú móvil' },
      { f: 'feather/external-link', label: 'Enlace externo', use: 'Footer, legales' },
    ],
  },
  {
    group: '2 · Carrito y compra',
    items: [
      { f: 'feather/shopping-bag', label: 'Carrito (bolsa)', use: 'Navbar, mini-carrito, botón de compra', note: 'Elegido' },
      { f: 'feather/plus', label: 'Más', use: 'Stepper, admin crear' },
      { f: 'feather/minus', label: 'Menos', use: 'Stepper' },
      { f: 'feather/trash-2', label: 'Eliminar', use: 'Item de carrito, admin' },
      { f: 'feather/tag', label: 'Etiqueta', use: 'Badge, banner promocional' },
      { f: 'mdi/ticket-outline', label: 'Cupón', use: 'Cupones, campo de cupón' },
      { f: 'feather/truck', label: 'Envío', use: 'Estado enviado, política de envíos' },
      { f: 'feather/package', label: 'Paquete', use: 'Estado en preparación' },
      { f: 'feather/file-text', label: 'Recibo', use: 'Historial y detalle de pedido' },
      { f: 'feather/shield', label: 'Garantía', use: 'Badge, especificaciones' },
      { f: 'feather/lock', label: 'Seguridad', use: 'Checkout' },
      { f: 'feather/credit-card', label: 'Tarjeta', use: 'Métodos de pago' },
      { f: 'mdi/cash', label: 'Efectivo', use: 'Contraentrega' },
      { f: 'feather/heart', label: 'Favorito', use: 'Wishlist (opcional)' },
    ],
  },
  {
    group: '3 · Cuenta de usuario',
    items: [
      { f: 'feather/log-out', label: 'Salir', use: 'Menú de usuario' },
      { f: 'feather/key', label: 'Contraseña', use: 'Recuperar contraseña' },
      { f: 'feather/eye', label: 'Mostrar', use: 'Campo de contraseña' },
      { f: 'feather/eye-off', label: 'Ocultar', use: 'Campo de contraseña' },
      { f: 'feather/mail', label: 'Email', use: 'Registro, verificación' },
      { f: 'feather/bell', label: 'Notificaciones', use: 'Navbar, cuenta' },
      { f: 'feather/map-pin', label: 'Dirección', use: 'Perfil, envío' },
      { f: 'feather/edit-2', label: 'Editar', use: 'Perfil, admin' },
      { f: 'feather/list', label: 'Lista', use: 'Pedidos, vista lista' },
      { f: 'feather/star', label: 'Reseñas', use: 'Reseñas (opcional)' },
      { f: 'feather/settings', label: 'Ajustes', use: 'Configuración, perfil' },
      { f: 'feather/users', label: 'Usuarios', use: 'Admin: usuarios y roles' },
    ],
  },
  {
    group: '4 · Catálogo y filtros',
    items: [
      { f: 'feather/filter', label: 'Filtrar', use: 'Filtros, navbar móvil' },
      { f: 'mdi/sort-variant', label: 'Ordenar', use: 'Selector de orden' },
      { f: 'feather/grid', label: 'Cuadrícula', use: 'Vista grid' },
      { f: 'feather/refresh-cw', label: 'Limpiar', use: 'Limpiar filtros' },
    ],
  },
  {
    group: '5 · Estados y feedback',
    items: [
      { f: 'feather/check', label: 'Check', use: 'Badge, alerta de éxito' },
      { f: 'feather/check-circle', label: 'Éxito', use: 'Toast, confirmación' },
      { f: 'feather/x-circle', label: 'Error', use: 'Alerta, toast' },
      { f: 'feather/alert-triangle', label: 'Advertencia', use: 'Alerta, stock bajo' },
      { f: 'feather/info', label: 'Info', use: 'Alerta informativa' },
      { f: 'feather/loader', label: 'Cargando', use: 'Spinner, botón cargando' },
      { f: 'feather/clock', label: 'Pendiente', use: 'Estado pendiente de pago' },
      { f: 'feather/inbox', label: 'Vacío', use: 'Estado vacío (carrito, sin resultados)' },
      { f: 'feather/alert-circle', label: 'No encontrado', use: 'Página 404' },
    ],
  },
  {
    group: '6 · Panel de administración',
    items: [
      { f: 'feather/folder', label: 'Categorías', use: 'Admin: categorías' },
      { f: 'mdi/warehouse', label: 'Inventario', use: 'Admin: inventario' },
      { f: 'mdi/bullhorn-outline', label: 'Promociones', use: 'Admin: promociones' },
      { f: 'feather/bar-chart-2', label: 'Reportes', use: 'Admin: dashboard y reportes' },
      { f: 'feather/calendar', label: 'Fechas', use: 'Rangos de fecha en admin' },
      { f: 'feather/download', label: 'Exportar', use: 'Admin: pedidos, reportes' },
      { f: 'feather/upload', label: 'Subir', use: 'Admin: galería Cloudinary' },
      { f: 'feather/image', label: 'Imagen', use: 'Galería, multimedia' },
      { f: 'feather/more-horizontal', label: 'Más opciones', use: 'Menús contextuales' },
      { f: 'feather/circle', label: 'Estado', use: 'Punto de estado, viñeta' },
    ],
  },
  {
    group: '7 · Relojería (especificaciones)',
    warn: 'Mezcla outline con sólido. Ver la sección de comparación más abajo.',
    items: [
      { f: 'mdi/watch', label: 'Reloj', use: 'Categoría, producto' },
      { f: 'mdi/watch-variant', label: 'Correa', use: 'Material de correa' },
      { f: 'mdi/cog-outline', label: 'Movimiento', use: 'Calibre / movimiento' },
      { f: 'feather/droplet', label: 'Resistencia al agua', use: 'Especificaciones' },
      { f: 'mdi/diamond-stone', label: 'Cristal de zafiro', use: 'Especificaciones' },
      { f: 'mdi/ruler', label: 'Tamaño de caja', use: 'Especificaciones' },
      { f: 'feather/hash', label: 'Número de serie', use: 'Edición limitada / serie' },
    ],
  },
  {
    group: '8 · Redes sociales (marca)',
    items: [
      { f: 'brand/instagram', label: 'Instagram', use: 'Footer' },
      { f: 'brand/facebook', label: 'Facebook', use: 'Footer' },
      { f: 'brand/tiktok', label: 'TikTok', use: 'Footer' },
      { f: 'brand/whatsapp', label: 'WhatsApp', use: 'Footer, contacto' },
      { f: 'brand/youtube', label: 'YouTube', use: 'Footer (si aplica)' },
    ],
  },
  {
    group: '9 · Métodos de pago (marca)',
    items: [
      { f: 'brand/visa', label: 'Visa', use: 'Checkout, footer' },
      { f: 'brand/mastercard', label: 'Mastercard', use: 'Checkout, footer' },
      { f: 'brand/americanexpress', label: 'American Express', use: 'Opcional' },
      { f: 'brand/nequi', label: 'Nequi', use: 'Checkout, footer — oficial Nequi' },
      { f: 'brand/wompi', label: 'Wompi', use: 'Checkout, footer — oficial Wompi' },
      { f: 'brand/pse', label: 'PSE', use: 'Checkout, footer — oficial PSE (JPG ACH)' },
      { f: 'brand/daviplata', label: 'Daviplata', use: 'Checkout, footer — provisional Daviplata' },
    ],
  },
];

// Logos de marca pendientes: ya no hay — PSE (JPG oficial) y Daviplata (provisional) agregados.
const PENDING_BRANDS = [];

// Logos rectangulares (no cuadrados): van por altura, no por caja cuadrada.
const WIDE_BRANDS = {
  'brand/nequi': { w: 95, h: 30 },
  'brand/wompi': { w: 316, h: 74 },
  'brand/daviplata': { w: 120, h: 40 },
};
const isWide = (f) => f in WIDE_BRANDS;
const wideWidth = (f, h) => Math.round((h * WIDE_BRANDS[f].w) / WIDE_BRANDS[f].h);

// ── Normalización ────────────────────────────────────────────────────────────
// Feather usa stroke (outline). MDI y los logos usan fill (sólido).
// Se limpian los atributos de tamaño/color del SVG original y se fijan los
// propios, para que el icono herede el color del contenedor (`currentColor`).
function normalize(raw, kind) {
  const start = raw.indexOf('<svg');
  if (start === -1) return raw;
  let svg = raw.slice(start).trim();

  // En el preview se quita el <title> para evitar tooltips duplicados.
  svg = svg.replace(/<title>[\s\S]*?<\/title>/g, '');

  svg = svg.replace(/^<svg([^>]*)>/, (_m, attrs) => {
    let a = ' ' + attrs;
    for (const k of ['fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'width', 'height', 'class', 'role', 'id']) {
      a = a.replace(new RegExp(`\\s${k}="[^"]*"`, 'g'), '');
    }
    a = a.replace(/\s+/g, ' ').trim();
    if (!/viewBox/i.test(a)) a += ' viewBox="0 0 24 24"';

    if (kind === 'outline') {
      a += ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
    } else {
      a += ' fill="currentColor"';
    }
    return `<svg ${a}>`;
  });

  return svg;
}

function kindOf(file) {
  return file.startsWith('feather/') ? 'outline' : 'solid';
}

function loadIcon(file) {
  const svgPath = resolve(SVG_DIR, file + '.svg');
  if (existsSync(svgPath)) return normalize(readFileSync(svgPath, 'utf8'), kindOf(file));
  // Raster fallback (PSE es JPG oficial)
  for (const ext of ['.jpg', '.png', '.webp']) {
    const p = resolve(SVG_DIR, file + ext);
    if (existsSync(p)) {
      const rel = `../../resources/svg/${file}${ext}`;
      return `<img src="${rel}" alt="${file.split('/').pop()}" style="width:100%;height:100%;object-fit:contain;display:block" />`;
    }
  }
  return null;
}

// ── Render ───────────────────────────────────────────────────────────────────
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

let total = 0;
let missing = [];
let rowsHtml = '';

for (const { group, items, warn } of CATALOG) {
  let rows = '';
  for (const it of items) {
    const svg = loadIcon(it.f);
    if (!svg) {
      missing.push(it.f);
      rows += `<tr class="missing"><td><em>no encontrado</em></td><td><b>${esc(it.label)}</b><br><code>${esc(it.f)}</code></td><td colspan="5">FALTA EL ARCHIVO</td></tr>`;
      continue;
    }
    total++;
    const isWideBrand = isWide(it.f);
    const sizeCells = SIZES.map((s) => {
      if (isWideBrand) {
        const w = wideWidth(it.f, s.px);
        return `<td class="sz wide"><span class="ico brand-wide" style="height:${s.px}px;width:${w}px">${svg}</span></td>`;
      }
      return `<td class="sz"><span class="ico" style="width:${s.px}px;height:${s.px}px">${svg}</span></td>`;
    }).join('');
    const bigCell = isWideBrand
      ? `<span class="ico brand-wide" style="height:32px;width:${wideWidth(it.f, 32)}px">${svg}</span>`
      : `<span class="ico" style="width:32px;height:32px">${svg}</span>`;
    rows += `<tr>
      <td class="big">${bigCell}</td>
      <td class="meta"><b>${esc(it.label)}</b>${it.note ? ` <span class="pill">${esc(it.note)}</span>` : ''}<br><code>${esc(it.f)}</code><br><span class="use">${esc(it.use)}</span></td>
      ${sizeCells}
    </tr>`;
  }
  rowsHtml += `<section>
    <h2>${esc(group)}</h2>
    ${warn ? `<p class="warn">⚠️ ${esc(warn)}</p>` : ''}
    <table>
      <thead><tr><th>Icono</th><th>Componente y uso</th>${SIZES.map((s) => `<th>${s.px}px</th>`).join('')}</tr></thead>
      <tbody>${rows}</tbody>
    </table>
  </section>`;
}

// Comparación outline vs sólido
const mix = ['feather/tag', 'mdi/watch', 'feather/truck', 'mdi/ruler', 'feather/shield', 'mdi/cog-outline', 'feather/droplet', 'mdi/diamond-stone'];
const mixHtml = mix
  .map((f) => {
    const svg = loadIcon(f);
    return svg ? `<div class="mixcell"><span class="ico" style="width:24px;height:24px">${svg}</span><code>${esc(f)}</code></div>` : '';
  })
  .join('');

// En fondo oscuro — dos comportamientos:
// - Iconos de interfaz (feather/mdi) y marcas monocromas (visa/mc) usan currentColor → blanco sobre navy.
// - Logos a color (nequi/wompi) NO se recolorean: van sobre píldora blanca, como en el footer/checkout real.
const darkInterface = ['feather/shopping-bag', 'feather/user', 'feather/search', 'feather/bell', 'brand/whatsapp', 'brand/instagram'];
const darkMono = ['brand/visa', 'brand/mastercard', 'brand/americanexpress'];
const darkColor = ['brand/nequi', 'brand/wompi', 'brand/pse', 'brand/daviplata'];
const renderDark = (list, opts = {}) =>
  list
    .map((f) => {
      const svg = loadIcon(f);
      if (!svg) return '';
      const wide = isWide(f);
      const ico = wide
        ? `<span class="ico brand-wide" style="height:24px;width:${wideWidth(f, 24)}px">${svg}</span>`
        : `<span class="ico" style="width:24px;height:24px">${svg}</span>`;
      const inner = opts.pill ? `<span class="pill-white">${ico}</span>` : ico;
      return `<div class="darkcell">${inner}<code>${esc(f.split('/')[1])}</code></div>`;
    })
    .join('');
const darkHtml = renderDark(darkInterface) + renderDark(darkMono) + renderDark(darkColor, { pill: true });
const darkPayHtml = renderDark([...darkMono, ...darkColor], { pill: false }); // solo informativo, no usado aparte

const html = `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iconos · tienda Tag q</title>
<style>
  :root { --line:#e5e5e5; --soft:#f7f7f7; --ink:#000; --ink2:#333; --ink3:#666; --blue:#2775ba; }
  * { box-sizing:border-box; }
  body { margin:0; padding:32px 20px 80px; font-family:system-ui,-apple-system,'Segoe UI',sans-serif; color:var(--ink); background:#fff; }
  header { max-width:1180px; margin:0 auto 28px; }
  h1 { font-size:24px; margin:0 0 6px; letter-spacing:.01em; }
  header p { margin:0; color:var(--ink3); font-size:14px; }
  section { max-width:1180px; margin:0 auto 40px; }
  h2 { font-size:15px; text-transform:uppercase; letter-spacing:.08em; padding-bottom:8px; border-bottom:2px solid var(--ink); margin:0 0 14px; }
  .warn { background:#fdf8ef; border-left:3px solid #bd8318; padding:8px 12px; font-size:13px; margin:0 0 12px; color:#7b530e; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th { text-align:left; font-weight:600; color:var(--ink3); text-transform:uppercase; font-size:10px; letter-spacing:.08em; padding:6px 8px; border-bottom:1px solid var(--line); }
  td { padding:10px 8px; border-bottom:1px solid var(--line); vertical-align:middle; }
  tr:hover { background:var(--soft); }
  .ico { display:inline-flex; align-items:center; justify-content:center; color:var(--ink); }
  .ico svg { width:100%; height:100%; display:block; }
  .dark .ico { color:#fff; }
  .dark .pill-white .ico { color:var(--ink); } /* la píldora es blanca, el icono dentro no hereda el blanco */
  .big { width:56px; }
  .meta { width:290px; }
  .meta b { font-weight:600; }
  .meta code, .mixcell code, .darkcell code { font-family:ui-monospace,monospace; font-size:10px; color:var(--ink3); background:var(--soft); padding:1px 5px; border-radius:4px; }
  .use { color:var(--ink3); font-size:12px; }
  .pill { background:var(--blue); color:#fff; font-size:9px; padding:1px 6px; border-radius:99px; letter-spacing:.04em; text-transform:uppercase; vertical-align:1px; }
  .sz { width:64px; text-align:center; }
  .sz .ico { width:100%; }
  .missing { background:#fdf3f2; }
  .mix { display:flex; gap:10px; flex-wrap:wrap; }
  .mixcell { display:flex; flex-direction:column; align-items:center; gap:6px; padding:14px 10px; border:1px solid var(--line); border-radius:8px; min-width:110px; }
  .dark { background:#00052c; padding:22px 16px; border-radius:8px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
  .darkcell { display:flex; flex-direction:column; align-items:center; gap:6px; color:#fff; min-width:90px; }
  .darkcell code { background:rgba(255,255,255,.12); color:#cccccc; }
  .pill-white { background:#fff; padding:6px 10px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 1px 2px rgba(0,0,0,.15); }
  .brand-wide svg { width:100%; height:100%; display:block; }
  .sz.wide { vertical-align:middle; }
  .note { font-size:13px; color:var(--ink3); margin:0 0 14px; }
  .pending { display:flex; gap:10px; flex-wrap:wrap; }
  .pending span { border:1px dashed #c9453b; color:#a8362e; padding:14px 18px; border-radius:8px; font-size:13px; }
  .sizes { width:100%; max-width:1180px; margin:0 auto 40px; font-size:13px; }
  .sizes td:first-child { font-family:ui-monospace,monospace; }
</style>
</head>
<body>
<header>
  <h1>Iconos · tienda Tag q</h1>
  <p>${total} iconos · tamaños reales (16 / 20 / 24 px) · archivos en <code>resources/svg/</code></p>
</header>

<section class="sizes">
  <h2>Escala de tamaños</h2>
  <table>
    <thead><tr><th>Clase Tailwind</th><th>Píxeles</th><th>Dónde se usa</th></tr></thead>
    <tbody>
      ${SIZES.map((s) => `<tr><td>${s.cls}</td><td>${s.px}px</td><td>${esc(s.use)}</td></tr>`).join('')}
    </tbody>
  </table>
  <p class="note" style="margin-top:12px">La escala no necesita tokens nuevos: son utilidades estándar de Tailwind.</p>
</section>

${rowsHtml}

<section>
  <h2>Comparación: outline vs sólido</h2>
  <p class="note">Todos a 24px. Feather es de trazo (outline); MDI y las marcas son sólidos. Así se ve la mezcla.</p>
  <div class="mix">${mixHtml}</div>
</section>

<section>
  <h2>Sobre fondo oscuro (navy-800)</h2>
  <p class="note">Interfaz y marcas monocromas heredan <code>currentColor</code> → blanco sobre navy. Logos a color (Nequi/Wompi) mantienen su color de marca y van sobre píldora blanca, como se usarán en footer/checkout.</p>
  <div class="dark">${darkHtml}</div>
  <p class="note" style="margin-top:12px">En el sitio real: <code>brand/visa</code>, <code>mastercard</code> y <code>amex</code> pueden ir directo en blanco; <code>nequi</code> y <code>wompi</code> siempre sobre fondo claro (píldora blanca o barra clara).</p>
</section>

<section>
  <h2>Faltantes — logos de marca oficiales</h2>
  <p class="note">No existen en ninguna familia libre. Hay que conseguirlos en los sitios oficiales.</p>
  <div class="pending">${PENDING_BRANDS.map((b) => `<span>${esc(b)}</span>`).join('')}</div>
</section>

</body>
</html>
`;

mkdirSync(dirname(OUT), { recursive: true });
writeFileSync(OUT, html, 'utf8');

console.log(`Preview: ${OUT}`);
console.log(`Iconos incluidos: ${total}`);
if (missing.length) {
  console.log(`FALTANTES (${missing.length}):`);
  for (const m of missing) console.log('  - ' + m);
  process.exitCode = 1;
}
