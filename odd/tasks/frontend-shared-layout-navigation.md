# Feature: Customer Storefront Foundation and Navigation UI

## Objective
Build the customer-facing storefront foundation, catalog-driven home, and coherent shared top bar/navigation. Add the explicitly requested transitional home visual treatment without animation or fabricated business facts.

## Scope and constraints
- Preserve the confirmed Laravel SSR monolith: Blade + Livewire, with existing Alpine/Vite behavior.
- Reuse existing data, routes, typography, COP formatting, stock labels, and catalog card behavior.
- T3 shipped the messages `Envíos a todo el país` and `Envíos contraentrega`; the user's later T6 request supersedes them with explicit uppercase copy and a fixed phone number.
- Navbar categories are flat: Damas, Caballeros, and Tienda with Ver todo/Niños; use existing catalog filters.
- Preserve authentication/access rules, account/order/cart actions, and use no new backend API or dependency.
- T4 is an explicitly user-approved transitional visual home design. It supersedes earlier home-only non-goals about a hero/redesign; keep product data real and copy neutral. Do not invent discounts, stock, warranties, shipping promises, or external images.
- The T4 home has no animations, transitions, added JS, or dependencies. T6 authorizes a restrained animated transition only in the shared shipping top bar; reuse Alpine and respect reduced-motion preferences.
- Preserve unrelated user-owned changes and generated artifacts: `.gitignore`, `MEMORY.md`, `.codegraph/`, Dusk screenshots/console, and agent-model diagnostic notes.
- User explicitly authorized commit and push on a new branch, choosing `origin/main` as the base to exclude the unrelated unpublished local `feat(mail)` commit.
- Keep the development server on port 8000 running.

## Task checklist

### T1 — Shared storefront shell and baseline navigation
- Status: completed; committed in `7e7f5b987f0fc4a545a24bdf76170a8cba2bda0f`.
- Evidence: focused Feature 14/14 (137 assertions); full PHPUnit 327/327 (1,659 assertions); local Dusk checkout/catalog/admin-cancel 3/3 (54 assertions).

### T2 — Simple catalog-driven customer home
- Status: completed; committed in `7e7f5b987f0fc4a545a24bdf76170a8cba2bda0f` with T4 visual work.
- Scope: `/` uses the shared storefront shell with real categories and up to four latest active products, using existing catalog conventions and a neutral empty state.
- Evidence: RED observed; focused `StorefrontHomeTest` 12/12 (74 assertions); full suite 339/339 (1,733 assertions) in earlier validation. Latest branch full suite: 326/326 (1,747 assertions).

### T3 — Style the shared top bar and navbar with approved content
- Status: implementation and focused/full PHPUnit checks complete; dedicated browser/Dusk navigation check remains pending.
- Implementation: shipping-message top bar plus Damas/Caballeros/Tienda nav, Ver todo/Niños submenu, accessible search/account/bag actions, role-aware links, and Alpine mobile menu.
- Focused tests: `StorefrontNavigationTest` 23/23 (212 assertions); `StorefrontHomeTest` 12/12 (74 assertions) before T4.
- Full `php artisan test` on the delivery branch: 326/326 (1,747 assertions). No dedicated Dusk navigation test exists or was run. Do not claim browser verification.

### T4 — Create a visually styled transitional watch-store home
- Status: completed; included in `7e7f5b987f0fc4a545a24bdf76170a8cba2bda0f`.
- Authorization: user requested several tailored home sections and styled product cards for a wristwatch store, with no animations.
- Result: editorial hero with static CSS watch motif, real category section, responsive product-card grid with existing Livewire cart, neutral empty state, and closing catalog/cart CTA. Shared header, controller, routes, COP, stock and image fallback behavior remain intact.
- Test-first evidence: RED was 16 tests (13 passed, 3 expected failures); GREEN 16/16 (113 assertions); final after an optional-attributes edge case 17/17 (121 assertions). Parent spot-check and independent verifier both observed the focused pass. Full branch suite passed 326/326 (1,747 assertions). No browser/Dusk visual check was run.

### T5 — Publish the storefront work on a clean feature branch
- Status: completed.
- Authorization: user explicitly requested commit and push, then chose `origin/main` as the base to exclude local unpublished commit `3f59a05 feat(mail): add transactional order emails`.
- Branch: `feat/storefront-experience`, created from `origin/main`.
- Candidate paths: `app/Http/Controllers/StorefrontHomeController.php`; `routes/web.php`; `resources/views/layouts/storefront.blade.php`; `resources/views/layouts/app.blade.php`; `resources/views/storefront/home.blade.php`; `resources/views/cart/index.blade.php`; `resources/views/catalog/index.blade.php`; `resources/views/catalog/show.blade.php`; `resources/views/checkout/index.blade.php`; `resources/views/orders/index.blade.php`; `resources/views/orders/show.blade.php`; `tests/Feature/StorefrontHomeTest.php`; `tests/Feature/StorefrontNavigationTest.php`; this task document.
- Excluded user-owned `.gitignore` and `MEMORY.md`, generated `.codegraph/` and screenshots/console artifacts, and unrelated agent-model diagnostic notes and old subtask ledgers.
- Required full gate passed independently and by parent before each of two work-unit commits: `php artisan test` — 326/326 tests, 1,747 assertions. `git diff --cached --check` passed before the second commit. No Wompi sandbox E2E was run.
- Commits: `7e7f5b987f0fc4a545a24bdf76170a8cba2bda0f` (`feat(storefront): add shared shell and styled home`); `5fa6daf167af002d47afd074559b8ee1b6af442d` (`feat(storefront): migrate public pages to shared shell`).
- Push: `feat/storefront-experience` pushed successfully to `origin` with upstream tracking. No PR or merge was created.
- Native ASSESS/INSPECT could not complete because the facade rejected its inspect-only `intendedUntracked` field; no native risk tier or review outcome is claimed.

### T6 — Update shipping top bar with fixed contact and animated copy
- Status: completed (uncommitted; no commit/push authorized for T6).
- Authorization: user requested wider letter spacing, a persistent `+573113059507` phone number on the right, two specified rotating shipping messages, and animated transitions; user also asked for a recommendation.
- Recommendation: desktop keeps the rotating announcement flexible on the left and the `tel:+573113059507` link fixed on the right; mobile wraps the phone to a separate line so long spaced copy remains legible. Use a short Alpine fade/slide transition, with reduced-motion support; do not add AOS/GSAP wiring.
- Messages: `REALIZAMOS ENVÍOS A TODA COLOMBIA` with the Colombia flag, and `REALIZAMOS TUS ENVÍOS CONTRAENTREGA`.
- Allowed edit surfaces: `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontNavigationTest.php`.
- Acceptance: phone stays visible/static while announcements rotate every four seconds; copy and flag are correct; wider tracking fills desktop space but wraps on mobile; transitions affect only message content; keep `role=status`/polite live-region accessibility; honor reduced motion; add deterministic Feature assertions.
- Test-first evidence: RED after adding focused assertions and before the Blade fix: 27 tests, 26 passed, 1 failed (missing leave-start state), 252 assertions. GREEN after explicit enter/leave states: focused `StorefrontNavigationTest` 27/27, 272 assertions; parent spot-check also passed.
- Full verification: `php artisan test` passed 330/330 tests, 1,807 assertions. `npm run build` succeeded; non-blocking warning says optional `fontaine` is absent for optimized font fallbacks. Generated `public/build/` remains gitignored.
- Browser/Dusk animation behavior was not verified. No commit or push authorized for this follow-up.

### T7 — Remove the top bar max-width container
- Status: completed locally, uncommitted; no commit/push authorized for T7.
- Authorization: user asked to remove the container restricting the top-bar text and move the phone closer to the right edge.
- Recommendation: remove the outer `mx-auto max-w-7xl` width constraint, keep the full-width semantic announcement/live-region wrapper needed for accessibility and transitions, and use `px-2` horizontal padding (about 8 px) so the phone stays right-aligned close to the viewport edge. Preserve the responsive row/stack behavior.
- Allowed edit surfaces: `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontNavigationTest.php`.
- Acceptance: top bar content spans the viewport without a max-width/auto-margin container; the phone remains outside the rotating region and sits at the right with small consistent padding on desktop and mobile; announcements remain centered/responsive; existing copy, rotation, flag, transitions, reduced-motion, and accessibility do not regress.
- Result: removed the outer `mx-auto max-w-7xl` constraint; the bar now spans full width with `px-2` (~8px) and the phone uses `ml-auto` to sit near the right edge on desktop and its own mobile row. The semantic announcement/live-region wrapper and all T6 content/animation behavior remain unchanged.
- Test-first evidence: RED after adding the layout assertion but before the Blade change: 28 tests, 1 failed because `mx-auto` remained. GREEN focused `StorefrontNavigationTest`: 28/28 tests, 285 assertions; parent spot-check passed. Full suite `php artisan test`: 331/331 tests, 1,820 assertions.
- No browser/Dusk visual check was run; no commit/push authorized for T7.

### T8 — Reorganize the storefront navbar
- Status: completed locally, uncommitted; no commit/push authorized for T8.
- Authorization: user requested uppercase primary items, three icon-only utility controls, centered logo, utility icons on the left, categories on the right, and storefront-appropriate styling.
- Layout: desktop three-column header — search/account/bag icons left, `Tag q` wordmark centered, Damas/Caballeros/Tienda right. Mobile retains the centered wordmark, icon-only utilities, right-side hamburger, and responsive menu.
- Acceptance: primary/mobile category labels display uppercase; utility icons have no visible text while retaining accessible `aria-label`/`title`; category routes, Tienda submenu, auth-aware account dropdown, and cart route remain unchanged; apply refined uppercase tracking/hover/focus styles without breaking mobile layout.
- Allowed edit surfaces: `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontNavigationTest.php`.
- Test-first evidence: RED after test-only edits: 31 tests, 26 passed, 5 expected failures for uppercase labels, layout hooks/order, icon-only controls, and grid structure (294 assertions). GREEN focused `StorefrontNavigationTest`: 31/31 tests, 348 assertions; `StorefrontHomeTest` remained green at 17/17 (121 assertions).
- Full verification: `php artisan test` passed 334/334 tests, 1,883 assertions. `npm run build` succeeded with a non-blocking warning that optional `fontaine` is absent for optimized font fallbacks. No browser/Dusk visual verification was run.
- No commit/push authorized for T8.

### T9 — Add navbar breathing room and a branded Tag Q wordmark
- Status: completed locally, uncommitted; no commit/push authorized for T9.
- Authorization: user requested more navbar padding and a logo-like `Tag Q` wordmark; changing the font is allowed.
- Recommendation: increase responsive navbar padding and style the existing text mark with Tailwind's built-in serif stack, a larger size, semibold weight, and restrained tracking. This avoids downloading or adding a font dependency.
- Allowed edit surfaces: `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontNavigationTest.php`; `tests/Feature/StorefrontHomeTest.php`.
- Acceptance: visible brand text is exactly `Tag Q`; centered logo has a distinct title-like serif style; navbar has measurably larger responsive padding; existing icon-only utilities, uppercase categories, layout positions, links, auth, mobile menu and topbar remain intact.
- Test-first evidence: RED after test-only updates: navigation had 2 expected failures (old `Tag q` case and old padding); home had 1 expected failure (brand link helper could not find `Tag Q`). GREEN: `StorefrontNavigationTest` 32/32 (365 assertions); `StorefrontHomeTest` 17/17 (121 assertions).
- Full verification: `php artisan test` passed 335/335 tests, 1,900 assertions. `npm run build` succeeded with a non-blocking warning that optional `fontaine` is absent for optimized font fallbacks.
- No browser/Dusk visual check was run; no commit/push authorized for T9.

### T10 — Place the SVG mark beside Tag Q and invert navbar columns
- Status: completed locally, uncommitted; no commit/push authorized for T10.
- Authorization: user requested primary items on the left, utility icons on the right, the logo SVG beside `Tag Q`, 24px icons, and slightly larger primary labels.
- Asset: user-provided `logo.svg` exists at the project root but Laravel serves public assets from `public/`. Copy it unchanged to `public/images/tag-q-logo.svg`; do not move or modify the original. Reference it with `asset('images/tag-q-logo.svg')`; apply a darkening CSS filter if needed for contrast on the white navbar.
- Layout: desktop primary items left; centered brand group with mark immediately before exact `Tag Q`; three utilities right. Mobile puts hamburger left, brand centered, utilities right.
- Acceptance: retain T9 padding/serif wordmark and T8 routes/auth/submenus; primary category labels remain uppercase but increase to `text-sm`; search/account/bag icons render at 24px (`h-6 w-6`) with accessible labels and no visible words; SVG is visibly rendered before Tag Q; mobile layout remains usable.
- Allowed edit surfaces: `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontNavigationTest.php`; `public/images/tag-q-logo.svg`.
- Test-first evidence: RED `StorefrontNavigationTest` 31 passed/6 failed (378 assertions) on new/updated layout and asset assertions. GREEN focused navigation 37/37 (440 assertions); `StorefrontHomeTest` 17/17 (121 assertions).
- Full verification: `php artisan test` passed 340/340 tests, 1,975 assertions. `npm run build` succeeded with the non-blocking optional `fontaine` warning. `cmp logo.svg public/images/tag-q-logo.svg` confirmed the public copy is byte-identical to the user's root source.
- SVG size follow-up: changed only the responsive desktop override from `sm:h-8` (32px) to `sm:h-14` (56px); mobile `h-7` (28px) stays unchanged. Test-first RED on the missing `sm:h-14`, then focused navigation GREEN 37/37 (443 assertions). `npm run build` confirms the `sm:h-14` utility is emitted; build succeeded with the non-blocking optional `fontaine` warning. The full suite and browser/Dusk were not rerun for this single utility update. No commit/push authorized for T10.

### T11 — Add an accessible navbar search panel
- Status: completed locally, uncommitted; no commit/push authorized for T11.
- Authorization: user wants the navbar search to open a white panel directly below the nav, disable background/body scrolling while open, and occupy 80% of the viewport height remaining below the nav.
- Recommendation: keep the panel below the visible navbar, dim the remaining page with a backdrop, lock body scroll, focus the search field on open, and restore focus/scroll on close. Use the existing `GET catalog.index?q=...` flow; no API or live-search backend.
- Acceptance: search icon opens panel; panel is white, begins immediately under the nav, and its height is `0.8 * (viewportHeight - navBottom)`; background is dimmed and body scroll is locked; close button, backdrop and Escape close it; focus moves to input and returns to the trigger; form submits the existing `q` filter; layout works on mobile; existing nav/T6–T10 behavior stays intact.
- Allowed edit surfaces: `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontNavigationTest.php`.
- Test-first evidence: RED tests-only run 46 tests, 37 passed, 9 failed (465 assertions) on missing panel/backdrop/body-lock/close/focus/resize hooks. GREEN focused `StorefrontNavigationTest` 46/46 (567 assertions); parent spot-check passed.
- Full verification: `php artisan test` 349/349 (2,102 assertions); `npm run build` succeeded with the non-blocking optional `fontaine` warning.
- No browser/Dusk verification; no commit/push authorized for T11.

### T12 — Persist admin-curated product collections
- Status: completed locally, uncommitted; no commit/push authorized.
- Authorization: user chose persisted collections curated in the admin panel rather than groups derived from categories/brands.
- Recommendation: add `collections` plus a `collection_product` many-to-many pivot, with admin-only CRUD and product assignment. Do not seed invented names or memberships; the Collections search tab remains empty until admins create and publish entries.
- Acceptance: create/edit/list/deactivate/delete collections; validate names/slugs; assign existing products through the pivot; enforce admin access; add Feature coverage using PostgreSQL/RefreshDatabase; update admin navigation only for authorized admins.
- Allowed edit surfaces: `database/migrations/*_create_collections_table.php`; `database/migrations/*_create_collection_product_table.php`; `app/Models/Collection.php`; `app/Models/Product.php`; `app/Http/Controllers/Admin/CollectionController.php`; `app/Http/Requests/StoreCollectionRequest.php`; `app/Http/Requests/UpdateCollectionRequest.php`; `routes/web.php`; `resources/views/layouts/app.blade.php`; `resources/views/admin/collections/*.blade.php`; `tests/Feature/AdminCollectionTest.php`.
- Test-first evidence: RED `AdminCollectionTest` 16 tests/0 passed/16 errors (missing Collection model/routes); GREEN focused 16/16 (59 assertions). Parent spot-check passed.
- Full verification: `php artisan test` passed 365/365 tests, 2,161 assertions.
- No collection names or product assignments were seeded; no browser/Dusk run; no commit/push authorized.

### T13 — Add live suggestions, product/collection tabs to search
- Status: completed locally, uncommitted; no commit/push authorized.
- Scope: extend the existing T11 panel through a Livewire component (no custom JSON API); 70% left content with Products/Collections tabs, 30% right suggestions; show up to four real matching product cards and matching persisted collections; suggestions derive from actual catalog terms (names/brands/categories/collections), not invented search history.
- Acceptance: suggestions update while typing; Products tab renders four or fewer active products with existing image fallback/category/COP/routes; Collections tab lists matching active DB collections; collection list has a meaningful empty state; mobile layout stacks responsively; GET `q` search remains available and T11 body-lock/modal behavior is preserved.
- Allowed edit surfaces: `app/Livewire/Storefront/SearchPanel.php`; `resources/views/livewire/storefront/search-panel.blade.php`; `resources/views/layouts/storefront.blade.php`; `tests/Feature/StorefrontSearchPanelTest.php`; `tests/Feature/StorefrontNavigationTest.php`.
- Test-first evidence: RED `StorefrontSearchPanelTest` 11 tests, 0 passed, 11 errors before the Livewire component existed. GREEN focused panel tests 13/13 (106 assertions); `StorefrontNavigationTest` 46/46 (568 assertions); T12 `AdminCollectionTest` 16/16 (59 assertions).
- Full-suite correction: first integrated `php artisan test` found 9 failures because the hidden Livewire panel rendered product names with a blank query on every storefront page. Fixed by returning empty product/collection/suggestion lists while blank, showing an idle prompt, and pushing suggestion filtering into bounded SQL queries. Final full suite passed 378/378 tests, 2,268 assertions; `npm run build` succeeded with a non-blocking optional `fontaine` warning.
- No browser/Dusk verification; no commit/push authorized.

## Forecast and delivery
- Earlier forecast: T1 ~300, T2 ~150, T3 ~220, T4 ~200 authored changed lines; generated files excluded.
- The user selected two work-unit commits on one feature branch.
- Base is `origin/main`; the unpublished local mail commit is excluded.

## Current status
T1/T2/T4 are complete and pushed in commit `7e7f5b9`; T3 implementation and focused/full PHPUnit checks pass, but its dedicated browser/Dusk navigation check remains pending. T5 pushed `feat/storefront-experience` with commits `7e7f5b9` and `5fa6daf`. T6–T8 are complete locally and remain uncommitted; no commit/push was authorized for these follow-ups. T6/T7 shipping bar has uppercase copy, Colombia flag, fixed phone near the right edge, ~8px padding, wider tracking, and Alpine transitions with reduced motion; T6 focused 27/27 and full 330/330, T7 focused 28/28 and full 331/331. T8 has icon-only search/account/bag left, centered Tag q wordmark, uppercase Damas/Caballeros/Tienda right, and responsive mobile menu; focused 31/31, Home 17/17, full 334/334, build passed with a non-blocking missing-`fontaine` warning. T9 is completed locally and uncommitted: the navbar has increased responsive padding and the exact `Tag Q` wordmark now uses a built-in serif title treatment. Test-first Navigation tests passed 32/32 (365 assertions), Home tests 17/17 (121 assertions), full suite 335/335 (1,900 assertions), and the Vite build succeeded with a non-blocking missing-`fontaine` warning. No T9 commit/push is authorized. T10 is complete locally and uncommitted: primary items are left, the centered brand group renders the public SVG immediately before Tag Q, and 24px icon-only utilities sit right; mobile keeps the hamburger left, brand centered and utilities right. The public copy matches the root SVG byte-for-byte and the original remains untouched. T11 is complete locally and uncommitted: the search button opens an accessible white panel below the nav, sized at 80% of the remaining viewport, dims the underlying page, locks body scrolling, handles Escape/close/backdrop, manages focus and submits to the existing catalog `q` filter. Focused navigation tests passed 46/46 (567 assertions), full suite 349/349 (2,102 assertions), and Vite build succeeded with the non-blocking optional-fontaine warning. No browser/Dusk verification was run. T12 is complete locally and uncommitted: persisted admin-curated collections, pivot-based product assignment, and admin CRUD are implemented with no invented seed data; focused 16/16 (59 assertions) and full suite 365/365 (2,161 assertions) pass. T13 is complete locally and uncommitted: the panel has Livewire catalog-word suggestions, the 70/30 layout, Products/Collections tabs, up to four active product cards, and matching persisted collections. The first integrated full suite exposed 9 negative-assertion failures from blank-query product leakage; the idle-query short-circuit fixed them. Final panel 13/13 (106 assertions), navigation 46/46 (568), collections admin 16/16 (59), Home 17/17 (121), full suite 378/378 (2,268), build passed with non-blocking missing-`fontaine`. No T6–T13 commit/push is authorized. Focused navigation tests 37/37 (440 assertions), Home 17/17 (121 assertions), full suite 340/340 (1,975 assertions), and build passed. A later logo-height-only follow-up keeps mobile at 28px and raises the `sm` size to 56px; its navigation test passed 37/37 (443 assertions) and the rebuilt CSS includes `sm:h-14`. No full-suite/Dusk rerun for that single utility change. No browser/Dusk visual check was run; no T6–T10 commit/push is authorized. No browser/Dusk visual checks were run. Unrelated local edits/artifacts remain untouched. Native Gentle review could not be assessed because facade validation rejected inspect-only `intendedUntracked`; no risk tier/review result is asserted. Dev server remains on port 8000.
## Delivery (2026-10-08)

Los estados "uncommitted" de arriba quedan resueltos por esta secuencia de work-unit commits en `feat/storefront-experience`. La suite completa se corrió antes de cada commit y **cada árbol candidato se verificó aparte** (exportado el índice a un checkout temporal y corrido `php artisan test` ahí):

| Commit | Alcance |
|---|---|
| `0b163d9` | T12 — colecciones admin (modelo, migraciones, CRUD, rutas, test) |
| `1974957` | T6/T7 topbar + T8/T9/T10 navbar (incluye `public/images/tag-q-logo.svg`); el control de búsqueda sigue siendo enlace al catálogo en este commit |
| `9052f5e` | T1–T5 hero del home + `app.js` con una sola instancia de Alpine (detalles en `home-hero-slider.md`) |
| `4f954e0` | T11/T13 panel de búsqueda (shell + componente) y T16/T17/T18 (detalles en `search-panel-redesign.md`) |

Evidencia por árbol: `0b163d9` 342/342 · `1974957` 356/356 · `9052f5e` 363/363 · `4f954e0` 389/389 (2,373 assertions; el árbol de trabajo suma 2,374 porque el test de logo compara también `logo.svg` de la raíz, que sigue sin trackear).

Push sigue pendiente de autorización explícita (AGENTS.md §4).
