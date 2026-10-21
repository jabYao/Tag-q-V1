# Feature: Shipping message top bar

## Objective
Add a storefront top bar that rotates between the approved shipping messages every four seconds.

## Acceptance criteria
- Shows `Envíos a todo el país` with the Colombia flag.
- Rotates to `Envíos contraentrega` every four seconds and cycles back.
- Uses existing Blade + Alpine/Vite stack without new dependencies.
- Includes accessible live-region semantics and focused feature coverage.
- Preserves the user's existing uncommitted storefront work; no commit or push without explicit authorization.

## Tasks
- [x] Add the rotating top bar to the shared storefront layout.
- [x] Add focused feature assertions for messages and rotation hooks.
- [ ] Run focused and applicable checks; report any unavailable checks.

## Evidence
- Worker observed RED first: the new focused test failed because the messages were absent.
- Worker observed GREEN: `php artisan test --filter=test_el_top_bar_de_envios_muestra_la_bandera_y_rota_los_mensajes_aprobados` passed (1 test, 11 assertions).
- Worker ran `php artisan test --filter=StorefrontNavigationTest`: 15/15 passed, 148 assertions.
- Worker ran `php artisan test --filter=StorefrontHomeTest`: 12/12 passed, 74 assertions.
- No commit was made, preserving the existing user-directed uncommitted feature branch state.

## Delivery (2026-10-08)

Commit `1974957 feat(storefront): rebuild shipping topbar and navbar shell` en `feat/storefront-experience` (T6/T7). Verificado como árbol aislado: 356/356 tests, 2,036 assertions.
