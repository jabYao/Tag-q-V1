# Feature: Search Panel Redesign (T16)

## Objective
Give the navbar search panel a noticeably better visual style, move the suggestions column to the LEFT (currently right), and prove the panel actually works in a real browser with a Dusk E2E test.

## Scope and constraints
- Layout decision requested by the user (2026-10-07): suggestions column on the left (~30%), results on the right (~70%); the rest of the styling is delegated to the agent.
- Preserve the T11/T13 contract asserted by `StorefrontNavigationTest` and `StorefrontSearchPanelTest`:
  - panel hooks `data-nav-search-panel`, `data-nav-search-backdrop`, `data-nav-search-close`, `data-nav-search-form`;
  - `role="dialog"`, `aria-modal`, `aria-label="Buscar"`, panel classes `bg-white`/`fixed`/`z-50`, `:style` with `searchPanelTop`/`searchPanelHeight`;
  - form GET to `catalog.index`, `role="search"`, single `input[name="q"]` with `type="search"`, `id="storefront-search-q"`, `x-ref="searchInput"`, visible `<label>` with exact text `Buscar`, `wire:model`, no `_token`;
  - hooks `data-search-panel-results`, `data-search-panel-suggestions`, `data-search-tab="productos|colecciones"`, `data-search-product`, `data-search-collection`, `data-search-suggestion`;
  - responsive steps `grid-cols-1`, `sm:grid-cols-2`, `lg:grid-cols-4` for product cards;
  - blank query exposes no catalog names in the server-rendered panel (hidden-SSR leak rule).
- The T4 "no `transition`/`animate-`/`duration-`/`<script` inside `<main>`" rule applies to `<main>` only; the panel lives inside `<nav>`, so Tailwind transitions are allowed there. Still no `<script>` tags anywhere in the panel.
- No new dependencies, no JSON endpoint, no API. Livewire component stays as is unless a real defect is found.
- `tests/Feature/StorefrontSearchPanelTest.php:493` currently asserts `lg:grid-cols-[7fr_3fr]` — that assertion changes to `lg:grid-cols-[3fr_7fr]` because the user reversed the columns; that is an intentional contract update, not a bypass.
- Keep the dev server on port 8000 running (it serves the dev DB `tagq`; Dusk gets its own server on port 8001 against `tagq_testing`).
- No commit/push without explicit user authorization (AGENTS.md §4).

## Test environment facts (verified 2026-10-07)
- `php artisan dusk:chrome-driver --detect` installed chromedriver 153; `/usr/bin/chromium` present.
- PostgreSQL `tagq_testing` exists; `tests/DuskTestCase.php` pins `APP_ENV=testing`, `DB_DATABASE=tagq_testing`.
- Dusk `baseUrl()` = `config('app.url')`, overridable with the `APP_URL` env var (Dotenv keeps values already in the environment).
- Server for Dusk: `APP_ENV=testing DB_DATABASE=tagq_testing DB_URL= APP_URL=http://127.0.0.1:8001 php artisan serve --port=8001`.
- Run: `APP_URL=http://127.0.0.1:8001 php artisan dusk tests/Browser/StorefrontSearchPanelTest.php`.

## Task checklist

### T16.1 — RED: Feature assertions for the new panel layout
- Status: completed.
- Evidence: RED observed — `StorefrontSearchPanelTest` 14 tests, 12 passed, 2 failed, 104 assertions (grid still `lg:grid-cols-[7fr_3fr]`, missing `data-search-field` hook).
- Update `StorefrontSearchPanelTest`: `lg:grid-cols-[3fr_7fr]` instead of `[7fr_3fr]`, assert the suggestions node precedes the results node in the DOM (left column), and add style markers for the new design (segmented pill tabs, rounded search field wrapper hook).
- Evidence target: observed failing assertions before the Blade change.

### T16.2 — GREEN: restyle the panel and move suggestions left
- Status: completed.
- Rework `resources/views/livewire/storefront/search-panel.blade.php`: rounded search field with inline magnifier, segmented pill tabs, suggestions `<aside>` first (left, ~30%), results `<section>` second (right, ~70%), richer product cards/collection rows, honest empty states kept. `layouts/storefront.blade.php` left byte-identical (not needed); `SearchPanel.php` read-only, no defect found there.
- Evidence: focused `StorefrontSearchPanelTest` 14/14 (120 assertions); `StorefrontNavigationTest` 46/46 (568 assertions). Final grid: `mx-auto mt-6 grid w-full max-w-6xl grid-cols-1 gap-6 lg:grid-cols-[3fr_7fr] lg:gap-8`, cards keep `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`. New hooks: `data-search-field`, `data-search-field-icon`, `data-search-submit`, `data-search-tabs`, `data-search-suggestions-list`.

### T16.3 — E2E: Dusk proves the panel works
- Status: completed (found a real app defect, fixed under T16.5).
- Created `tests/Browser/StorefrontSearchPanelTest.php`: open the panel from `/`, type a term, wait for a real product card and suggestion, click the suggestion and assert the input is filled, assert the suggestions column sits left of the results column, switch tabs, submit and land on `/catalogo?q=...`.
- First run FAILED at step 2 (`Waited 20 seconds for selector [[data-search-panel-results] [data-search-product]]`) — the panel's Livewire component never booted. `CheckoutTest` passed on the same server, isolating the fault to the panel.
- Final run after T16.5: PASS — 1 test, 28 assertions, 3.6s against `http://127.0.0.1:8001` (testing env, `tagq_testing`). Servers/chromedriver stopped; port 8000 untouched.

### T16.5 — Defecto: el panel Livewire nunca arranca en el navegador (Alpine duplicado)
- Status: completed.
- Found by T16.3's Dusk run: typing in the panel never issues a `POST /livewire/update`; `Livewire.all()` only contains the cart components; console shows `Detected multiple instances of Alpine running`.
- Root cause (read from `vendor/livewire/livewire/dist/livewire.js`): `resources/js/app.js` imports npm `alpinejs` and calls `Alpine.start()`, while `@livewireScripts` (Livewire 4.4.6) ships its own Alpine, sets `window.Alpine`, and starts it on `DOMContentLoaded`. Execution order is `livewire.js` (classic, end of body) → `app.js` (deferred module) → `DOMContentLoaded`, so `app.js` overwrites `window.Alpine` and initializes the nav `x-data` tree first; Livewire's instance then skips roots nested inside an already-initialized `x-data`, so the panel's `wire:id` (first-ever `wire:id` nested in the nav) never registers. Cart components live under `<main>` with no `x-data` ancestor, which is why `CheckoutTest` passes and the defect hid.
- Fix: remove the npm Alpine import/start from `resources/js/app.js` so Livewire's Alpine is the single instance (canonical Livewire setup). All three layouts include `@livewireScripts`, and no blade uses the `Alpine` global.
- Guard: a Feature test asserting `resources/js/app.js` no longer imports `alpinejs` nor calls `Alpine.start()` (the deterministic part of the contract; the behavioral proof is the Dusk test).
- Evidence target: observed RED on the guard, then GREEN, then the T16.3 Dusk test green.
- Evidence: RED — `StorefrontSearchPanelTest` 15 tests, 14 passed, 1 failed, 122 assertions (guard caught `from 'alpinejs'`). GREEN — 15/15, 124 assertions after removing the Alpine import/`window.Alpine`/`Alpine.start()` from `resources/js/app.js` (Swiper init and `--storefront-header-h` untouched). Headless chromium console capture at load: `Detected multiple instances of Alpine running` count 0. The unmodified Dusk E2E then passed.
- Guard added: `test_el_bundle_de_app_no_arranca_una_segunda_instancia_de_alpine()` (no `from 'alpinejs'`, no `Alpine.start()`, still contains `from 'swiper'`).
- Note: the defect existed since T13 — the panel had only ever been verified by Feature tests (SSR), never in a browser.

### T17 — Defecto: escribir en el buscador revienta con 500 si falta la tabla `collections`
- Status: completed (uncommitted).
- Report: user screenshot (2026-10-07) — `Illuminate\Database\QueryException`, `SQLSTATE[42P01]: Undefined table: relation "collections" does not exist`, DB `tagq`, fired while typing in the search panel.
- Layer 1 (environment, already fixed by the agent): migrations `2026_10_02_000000_create_collections_table` and `..._000001_create_collection_product_table` were **Pending** on the development database `tagq` (T12 had only ever run against `tagq_testing`, which is why the suite was green). Ran `php artisan migrate` → both DONE; the panel's query now returns `OK`.
- Layer 2 (code, this task): `SearchPanel` queries the `collections` table unconditionally on two paths whenever the term is non-empty — `collections()` (~line 122) and `suggestions()` (~line 184). A missing secondary table therefore 500s the whole panel, including the Products tab and the suggestions that do not need collections, and the panel is mounted in the shared shell so typing on any page breaks. That is disproportionate.
- Fix: degrade honestly — gate both paths on `Schema::hasTable('collections') && Schema::hasTable('collection_product')`, computed once in `render()` and passed down, returning empty results when unavailable. Products tab and non-collection suggestions keep working; the Collections tab keeps its existing honest empty-state copy. Deliberately do NOT wrap queries in try/catch (that would mask real DB errors) and do NOT touch `Admin\CollectionController` (the admin failing loudly on a pending migration is correct behaviour).
- Test-first: a Feature test that drops `collection_product` + `collections` inside the `RefreshDatabase` transaction (Postgres DDL is transactional, so it rolls back), then asserts the component still renders products and suggestions without throwing, and that the Collections tab shows the honest empty state. RED must be the `QueryException`.
- Evidence target: observed RED (QueryException), then GREEN, then full suite + build.
- Evidence (observed):
  - Layer 1: `php artisan migrate` on dev DB `tagq` → both T12 migrations DONE, now `[3] Ran`; panel query returns `OK`.
  - RED: `StorefrontSearchPanelTest` 16 tests, 15 passed, **1 error**, 124 assertions — error was exactly `SQLSTATE[42P01]: relation "collections" does not exist` raised in `collections()` via `render()`.
  - GREEN: focused **16/16, 128 assertions**; `StorefrontNavigationTest` **46/46, 568**.
  - The DROP DDL rolled back correctly: the collection-dependent tests that run after the dropping test still passed, no leakage.
  - Full suite: **388/388 tests, 2,363 assertions, 0 failures**. `npm run build` OK (only the known `fontaine` warning).
  - Both files remain untracked; nothing staged.
- Guard implemented: `render()` computes `$collectionsAvailable = Schema::hasTable('collections') && Schema::hasTable('collection_product')` once and passes it to `collections()` / `suggestions()`; no try/catch, no Blade/admin changes.

### T16.4 — Full verification + closeout
- Status: completed (uncommitted).
- `php artisan test`: **387/387 tests, 2,359 assertions, 0 failures**.
- `npm run build`: success (185ms); only the known non-blocking `fontaine` warning; built JS contains 0 occurrences of `alpinejs` and 0 of `Alpine.start` (bundle 83.39 kB).
- Git: `app.js` modified; `search-panel.blade.php`, both test files and this doc untracked; nothing staged.
- Not verified: no commit/push (no authorization); Dusk run once here, not wired into CI.

### T18 — El contenido del panel sale de sus contenedores
- Status: completed (uncommitted).
- Pedido del dueño: a la barra de búsqueda quitarle el **60% de su margen lateral** (aplicado a todo el div que la contiene) y a la div de sugerencias + div de resultados quitarle el **100% del margen**.
- Ambigüedad resuelta antes de escribir: el panel por fuera ya era full-bleed (`fixed inset-x-0`); lo contenido era el formulario (`max-w-4xl`) y la rejilla (`max-w-6xl`).
- Cambio en `resources/views/livewire/storefront/search-panel.blade.php`:
  - formulario: `max-w-4xl` → `max-w-[calc(60%_+_358.4px)]` (conserva `mx-auto`). Con W de ancho y m = (W − 896px)/2 de margen original, dejar 0.4·m por lado da W − 0.8·m = 0.6·W + 358.4px; por debajo de 896px el resultado supera W, o sea ancho completo.
  - rejilla de sugerencias + resultados: se elimina `mx-auto` y `max-w-6xl`; queda `mt-6 grid w-full grid-cols-1 gap-6 lg:grid-cols-[3fr_7fr] lg:gap-8`.
- Test-first: `test_el_panel_de_busqueda_sale_de_sus_contenedores()` en `StorefrontSearchPanelTest`.
  - RED observado: 17 tests, 16 passed, **1 failure** en la línea del `assertNotContains('max-w-4xl', ...)`.
  - GREEN observado: **17/17, 139 assertions**.
  - El resto de los hooks (`lg:grid-cols-[3fr_7fr]`, orden del DOM, tabs) sigue verde: T16 no dependía del `max-w`.
- CSS verificado en el build: Tailwind v4 emite `max-width:calc(60% + 358.4px)` (normaliza los espacios alrededor del `+`); selector `.max-w-\[calc\(60\%_\+_358\.4px\)\]`.
- Verificación: `php artisan test` **389/389, 2,374 assertions, 0 fallos**; `npm run build` OK (218ms, solo el warning conocido de `fontaine`).
- SSR comprobado contra el server corriendo (`GET /`): el formulario con `max-w-[calc(60%_+_358.4px)]` y la rejilla sin `mx-auto`/`max-w-6xl` ya se sirven; el único `max-w-6xl` que queda en la página es `data-home-body` (contenedor del home, ajeno al panel).
- No verificado: sin commit/push (requiere autorización); revisión visual en navegador ancho (>896px) no hecha en pantalla real.

### Delivery (2026-10-08)

Commit `4f954e0 feat(search): add live navbar search panel with redesign and guards` en `feat/storefront-experience`: shell T11 en `layouts/storefront.blade.php`, componente `SearchPanel` + vista, rediseño T16/T18, guard T17, y los tests Feature (`StorefrontSearchPanelTest`, `StorefrontNavigationTest`) + Dusk (`tests/Browser/StorefrontSearchPanelTest`).

Verificado como árbol aislado (checkout temporal del índice): **389/389 tests, 2,373 assertions**. El fix de Alpine (T16.5) viajó en `9052f5e` porque vive en `resources/js/app.js`; las colecciones que lee T12 viajaron en `0b163d9`. Push pendiente de autorización (AGENTS.md §4).

### T19 — El panel muestra solo la barra hasta la segunda letra
- Status: completed (uncommitted → commit de este work unit).
- Pedido del dueño: con la consulta vacía (o con menos de dos caracteres) el panel debe mostrar **únicamente** el div de la barra de búsqueda; las sugerencias, las tarjetas de productos y las colecciones quedan **invisibles** hasta que el usuario escribe la segunda letra.
- Cambio previsto: `render()` calcula `$active = mb_strlen(trim($query)) >= 2`, salta las tres consultas cuando no está activo (sin tocar la BD) y la vista solo emite la rejilla con `@if($active)`.
- Tests afectados (re-baseline): los que asumen la rejilla presente con consulta vacía (`test_el_panel_monta_un_componente_livewire_en_todo_el_shell`, `test_la_seccion_de_resultados_y_sugerencias_es_responsive_con_hooks_estables`, `test_el_panel_de_busqueda_sale_de_sus_contenedores`, y el T13 de `StorefrontNavigationTest`).

- Evidencia (observada):
  - RED (worker): `StorefrontSearchPanelTest` 18 tests, 17 passed, **1 failed** — `test_el_panel_solo_muestra_la_barra_hasta_la_segunda_letra` veía `data-search-panel-results` con consulta vacía.
  - RED (orquestador): `test_el_panel_inactivo_no_ejecuta_consultas_de_base_de_datos` falló con **6 consultas** (los dos `Schema::hasTable` de T17 × 3 renders) → el gate de esquema también corre con el panel inactivo.
  - GREEN: `StorefrontSearchPanelTest` **19/19, 157 assertions**; `StorefrontNavigationTest` **46/46, 568**.
  - Fix final: `$collectionsAvailable = $active && Schema::hasTable(...) && Schema::hasTable(...)`, de modo que el panel inactivo no emite ninguna consulta SQL.
  - Suite completa: **391/391 tests, 2,392 assertions, 0 fallos**; `npm run build` OK.
  - Dusk E2E re-corrido tras el cambio: **1/1, 30 assertions** (las 2 nuevas comprueban que la rejilla no existe con el input vacío). Servidor 8001 y chromedriver detenidos.
- Re-baseline honesto (la rejilla ya no existe con consulta vacía): `test_el_panel_monta_un_componente_livewire_en_todo_el_shell` ahora exige **0** copias de los hooks de rejilla y 1 de `data-nav-search-form`; `test_la_seccion_de_resultados_y_sugerencias_...`, `test_el_rediseno_del_panel_...`, `test_el_panel_tiene_las_pestanas_...`, `test_el_tab_de_colecciones_...` y `test_el_panel_de_busqueda_sale_de_sus_contenedores` ahora manejan el componente con `Livewire::test(...)->set('query', 'Ch')` + `html()`/`xpathDe()`; el T13 de `StorefrontNavigationTest` exige la ausencia de la rejilla con consulta vacía; el Dusk añade `assertMissing` de la rejilla en el paso 1.

### T20 — El panel encoge a la altura de la barra sin área viva
- Status: completed (commit de este work unit).
- Pedido del dueño: con T19 mostrando solo la barra, el contenedor del panel no debe dejar el espacio vacío del 80% del viewport: el div blanco tiene que medir lo que ocupa la barra y nada más; al activarse (segunda letra) vuelve al 80% de alto restante.
- Decisión de diseño: NO se divide el div. El panel sigue siendo el mismo `fixed`, pero su `:style` pasa a `height: auto` mientras `searchAreaActive` es falso, y `searchPanelHeight` (80%) cuando es verdadero. `searchAreaActive` lo publica el componente Livewire con un dispatch por render (`storefront-search-area`), que Alpine escucha en `window` desde la navbar; el estado inicial es `false`, igual que la consulta inicial.
- Quedan intactos `searchPanelTop` (top pegado al fondo de la navbar), el backdrop y las pruebas de resize/foco de T11.

- Evidencia (observada):
  - RED: `StorefrontSearchPanelTest` 20 tests, 19 passed, **1 failed** — `test_el_panel_publica_si_el_area_viva_esta_activa_para_alto_auto` (el evento `storefront-search-area` no se despachaba).
  - GREEN: `StorefrontSearchPanelTest` **20/20, 161 assertions**; `StorefrontNavigationTest` **46/46, 573**.
  - Suite completa: **392/392 tests, 2,401 assertions, 0 fallos**; `npm run build` OK.
  - Dusk E2E con las nuevas mediciones: **1/1, 35 assertions** — con el input vacío el panel mide su contenido (< 400px) y tras escribir vuelve al 80% del alto restante con Δ ≤ 2px respecto de `(window.innerHeight - nav.bottom) * 0.8`.
- Mecanismo: `SearchPanel::render()` despacha `storefront-search-area` con `active`; la navbar escucha `@storefront-search-area.window` y alterna el `:style` del panel entre `height: auto` y `searchPanelHeight`. Estado inicial `false`, coherente con la consulta inicial vacía; `searchAreaActive` no se resetea al cerrar, igual que `query`.
