# Feature: Customer Storefront Foundation and Navigation UI

## Objective
Build the customer-facing storefront foundation, catalog-driven home, and coherent shared top bar/navigation. Add the explicitly requested transitional home visual treatment without animation or fabricated business facts.

## Scope and constraints
- Preserve the confirmed Laravel SSR monolith: Blade + Livewire, with existing Alpine/Vite behavior.
- Reuse existing data, routes, typography, COP formatting, stock labels, and catalog card behavior.
- Top bar shows exactly `Envíos a todo el país` and `Envíos contraentrega`.
- Navbar categories are flat: Damas, Caballeros, and Tienda with Ver todo/Niños; use existing catalog filters.
- Preserve authentication/access rules, account/order/cart actions, and use no new backend API or dependency.
- T4 is an explicitly user-approved transitional visual home design. It supersedes earlier home-only non-goals about a hero/redesign; keep product data real and copy neutral. Do not invent discounts, stock, warranties, shipping promises, or external images.
- The home has no animations, transitions, added JS, or dependencies.
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

## Forecast and delivery
- Earlier forecast: T1 ~300, T2 ~150, T3 ~220, T4 ~200 authored changed lines; generated files excluded.
- The user selected two work-unit commits on one feature branch.
- Base is `origin/main`; the unpublished local mail commit is excluded.

## Current status
T1, T2, and T4 are completed and committed in `7e7f5b9`; T3 is implemented with focused/full PHPUnit checks passing, but dedicated browser/Dusk navigation coverage remains pending. T5 is completed: branch `feat/storefront-experience` was pushed to `origin` with commits `7e7f5b9` and `5fa6daf`. Unrelated local edits/artifacts remain uncommitted and were not pushed. Native Gentle review could not be assessed because facade validation rejected inspect-only `intendedUntracked`; no risk tier/review result is asserted. The dev server remains on port 8000.