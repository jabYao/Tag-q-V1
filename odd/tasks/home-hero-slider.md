# Feature: Home Hero Slider (Cloudinary)

## Objective
Replace the static editorial home hero's decorative CSS watch with a full-width, swipeable Swiper carousel showing the three existing Cloudinary hero images, keeping the current copy, CTAs, and T4 test contract intact.

## Scope and constraints
- Layout chosen by the user: full-width slider with the text/CTA overlay on top of the images behind a dark veil.
- Behavior chosen by the user: autoplay (~5s) + manual navigation (arrows, dots, swipe); autoplay pauses on hover/focus and respects `prefers-reduced-motion`.
- Images are referenced by URL from Cloudinary (`tag-q/hero`); no binary assets are committed to the repo.
- Use the already-installed `swiper@14.2.0` (dependency present, not yet wired); import only what is needed in `resources/js/app.js` and `resources/css/app.css`.
- Preserve the T4 contract asserted by `StorefrontHomeTest`: `data-home-hero`, `data-home-hero-eyebrow`, `data-home-hero-actions`, `<h1`, `Relojes de pulso`, catalog/cart hrefs, and no `<script>`, `animate-`, `transition`, or `duration-` strings inside `<main>`.
- No new backend, routes, or API. No fabricated copy, prices, discounts, or guarantees.
- Swiper initialization lives in `app.js` (loaded via `@vite` in `<head>`, outside `<main>`), guarded so pages without a slider do nothing.
- Keep the dev server on port 8000 running.
- No commit/push unless the user explicitly authorizes it (AGENTS.md §4).

## Image inventory (Cloudinary `tag-q/hero`, JPG 1920x1072)
1. `tag-q/hero/tgcquxabd0kcnu1gpbas.jpg` (421 KB) — silver Kairos watch, racetrack scene.
2. `tag-q/hero/ykkmw54d5meuvdfxpr3m.jpg` (123 KB) — gold Q&Q watch on dark blue.
3. `tag-q/hero/ziptwdbhj0l6fwouddno.jpg` (123 KB) — same gold Q&Q image (duplicate of #2; kept because the user explicitly asked for the three).

## Task checklist

### T1 — RED: hero slider Feature assertions
- Status: completed.
- Added `HERO_SLIDE_IMAGES` const plus `test_el_home_muestra_el_carrusel_del_hero_con_las_tres_imagenes_de_cloudinary` and a triangulation test `test_el_carrusel_del_hero_no_depende_del_catalogo_y_expone_controles_accesibles` in `tests/Feature/StorefrontHomeTest.php`.
- Evidence (observed RED before Blade changes): 18 tests, 17 passed, 1 failed, 123 assertions — failure = missing `data-home-hero-slider`.

### T2 — GREEN: Blade hero slider + Swiper wiring
- Status: completed.
- `resources/views/storefront/home.blade.php`: `@php $heroSlides` with the 3 Cloudinary URLs/alts; hero rebuilt as full-width Swiper (`data-home-hero-slider`, veil + overlaid text, pagination/prev/next markers); decorative CSS watch removed; copy/eyebrow/h1/CTA byte-identical.
- `resources/js/app.js`: guarded Swiper ESM init (loop, autoplay from `data-home-hero-slider-autoplay="5000"`, clickable pagination, prev/next, mouseenter/focus pause, `prefers-reduced-motion` disables autoplay).
- `resources/css/app.css`: Swiper CSS imports (`swiper/css`, `/navigation`, `/pagination`) + `data-home-hero-slider` custom-property overrides.
- Evidence (observed GREEN): focused `StorefrontHomeTest` 19/19, 154 assertions.
- Worker note: "full width" = width of the `max-w-6xl` main container, not the viewport (layout is outside the edit surface).

### T3 — Full verification
- Status: completed.
- `php artisan test`: **380/380 tests, 2,301 assertions, 0 failures**.
- `npm run build`: success (37 modules, 207ms); only the known non-blocking optional `fontaine` warning; `public/build` regenerated and gitignored (`git status --short public/build` empty).
- Changed source files confirmed: exactly the 4 intended (plus this task doc); everything else is pre-existing T6–T13 local work.

### T5 — Hero fuera del contenedor: a sangre, pegado del navbar y alto restante de viewport
- Status: completed (uncommitted).
- Authorization: user asked (2026-10-07) to take the hero out of the container so it covers the full width, sits flush against the navbar with no gap, and fills the remaining viewport height.
- Approach (layout shell untouched — it already exposes `@yield('main-class', ...)`):
  - `resources/views/storefront/home.blade.php`: define `main-class` as `w-full` (home only); hero becomes the first element of `<main>` with no top/bottom spacing; categories/products/closing move into a new constrained wrapper (`max-w-6xl mx-auto px-6`) that reproduces the previous `py-8`/gap rhythm; drop `rounded-2xl` and `mb-12` from the hero; drop the fixed `h-[36rem] sm:h-[32rem] lg:h-[34rem]` heights.
  - `resources/css/app.css`: `min-height: max(calc(100dvh - var(--storefront-header-h, 0px)), 26rem)` on the hero (26rem floor so short/landscape viewports do not clip the overlay) and `position: absolute; inset: 0` on its `.swiper` so the carousel always fills the hero box. Rules must stay unlayered and beat Swiper's own `.swiper` rule.
  - `resources/js/app.js`: publish `--storefront-header-h` on `<html>` from the measured topbar+nav bottom, refreshed on resize/load, so the hero = `100dvh - header`.
  - `tests/Feature/StorefrontHomeTest.php`: test-first assertions that home `<main>` carries `w-full` (no `max-w-6xl`/`py-8`), the hero is the first child of `<main>`, the hero has `data-home-hero-viewport` and no `rounded-2xl`/`mb-12`, the constrained wrapper exists after the hero, and the CSS/JS wire `--storefront-header-h`.
- Contract that must keep passing: T4 static-utilities test (`no animate-/transition/duration-/<script` inside `<main>`), all existing hero markers and the three Cloudinary slides.
- Evidence (observed):
  - RED before implementation: `StorefrontHomeTest` 23 tests, 19 passed, **4 failed**, 166 assertions — main lacked `w-full`, hero lacked `data-home-hero-viewport`, `<main>` had 4 element children instead of 2, `app.css` lacked `--storefront-header-h`.
  - GREEN: `StorefrontHomeTest` **24/24, 194 assertions**; `StorefrontNavigationTest` **46/46, 568 assertions** (shared shell intact).
  - Triangulation: `test_el_contenedor_acotado_por_defecto_sigue_vigente_fuera_del_home` proves `catalog.index` still renders `main class="max-w-6xl mx-auto px-6 py-8"` — the override is home-only.
  - Full suite: **385/385 tests, 2,341 assertions, 0 failures**. `npm run build` OK (37 modules, 196ms), only the known non-blocking `fontaine` warning.
  - Built CSS contains the unlayered rules: `[data-home-hero-viewport]{min-height:max(calc(100dvh - var(--storefront-header-h,0px)),26rem)}` and `[data-home-hero-viewport] .swiper{position:absolute;inset:0}`.
  - Git: exactly the 4 intended files modified, nothing staged.
- Implementation lines: `home.blade.php:9` `@section('main-class', 'w-full')`; `:42` `data-home-hero-viewport`; `:121` `<div data-home-body class="max-w-6xl mx-auto px-6 py-8">`; `app.css:46-53` the two hero rules; `app.js:18-35` `publishStorefrontHeaderHeight()` + load/resize wiring before the Swiper init.
- Not verified: no browser/Dusk check — flush-to-navbar and the exact `100dvh - header` fit are reasoned from layout structure, not observed pixels. Stale-var risk: height is republished only on load/resize, so a nav height change without resize (mobile menu open) leaves the hero at the previous value until the next resize. JS-off fallback: `--storefront-header-h` = `0px`, hero = `100dvh` (still flush, extends past the fold).

### T4 — Closeout
- Status: completed (pending native review preflight + user decision on commit).
- This document, Engram mirror, and `todo` updated with final evidence.
- Not verified: no browser/Dusk check (autoplay cadence, swipe, bullets reasoned only); Cloudinary URLs not fetched from the test env.
- No commit/push: awaiting explicit user authorization (AGENTS.md §4).

## Delivery (2026-10-08)

Commit `9052f5e feat(home): add full-bleed swipeable hero carousel` en `feat/storefront-experience`: `home.blade.php`, `resources/css/app.css`, `resources/js/app.js` y `StorefrontHomeTest`. Incluye de paso la eliminación del segundo Alpine en `app.js` (T16.5); su guard vive en el test del panel de búsqueda (`4f954e0`).

Verificado como árbol aislado (checkout temporal del índice): **363/363 tests, 2,109 assertions**. La nota de "pending native review preflight" queda cubierta por la secuencia completa: el preflight sobre todo el workspace fue rechazado con `lens_context_budget_exceeded` y por eso se partió en commits encadenados. Push pendiente de autorización (AGENTS.md §4).
