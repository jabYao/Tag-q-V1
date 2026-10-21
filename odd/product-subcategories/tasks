# Product subcategories

## Goal
Add real product subcategories Elegante, Casual, and Deportivo to the admin product flow, catalog filters, and Tienda submenu.

## Decisions
- Existing test products are assigned `casual` by data migration/backfill.
- New products require one subcategory from the fixed values.
- Subcategory links filter independently via `?subcategoria=elegante|casual|deportivo`.
- Canonical stored values are lowercase slugs; UI labels are title-cased Spanish.

## Tasks
- [x] Add schema/model and backfill.
- [x] Add admin assignment and validation.
- [x] Add catalog filtering and display.
- [x] Add submenu links and regression tests.
- [x] Run focused and full verification.

## Evidence
- Focused feature tests: 105 passed, 924 assertions.
- Full suite: 438 tests, 436 passed, 2 pre-existing failures unrelated to subcategories:
  - `CheckoutTest::test_pagina_muestra_resumen_total_y_metodos_de_pago_disponibles` due to existing footer copy containing `Nequi`.
  - `StorefrontHomeTest::test_el_home_usa_solo_utilidades_estaticas_y_no_inventa_promesas_comerciales` due to existing `transition` classes in home changes.
- No commit created; user authorization for commits was not provided.
