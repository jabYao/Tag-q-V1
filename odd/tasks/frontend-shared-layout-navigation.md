# Feature: Customer Storefront Foundation and Navigation UI

## Objective
Build the customer-facing storefront foundation, a simple catalog-driven home, and a coherent shared top bar/navigation using existing data and design conventions without inventing a new brand direction.

## Problem and rationale
The shared shell, navigation baseline, and simple home are present, but the navbar is still plain text and the top bar promotions are absent. The user wants to style these shared elements incrementally and approve their content before expanding visual design to each page.

## Scope and constraints
- Preserve the confirmed Laravel SSR monolith: Blade + Livewire, with existing Alpine/Vite behavior.
- Reuse existing typography, colors, spacing conventions, icon assets, COP formatting, stock labels, and catalog card patterns.
- Top bar shows exactly `Envíos a todo el país` and `Envíos contraentrega`.
- Navbar uses the existing flat categories: `Tienda` dropdown has `Ver todo` plus Dama, Caballero, Niños; Dama and Caballero are also direct links. Do not invent nested subcategories because the data model is flat.
- Use the documented standard navbar icons: search, user/account, shopping bag. Search goes to the existing catalog/search flow; bag goes to the existing cart.
- Preserve existing auth entry/exit access: guests can reach login/register; authenticated customers can reach Mis pedidos and logout; admin/employee retain Panel and logout. Reuse existing route authorization and Spanish UI conventions.
- Keep dropdown/menu behavior accessible and use existing Alpine/Vite; do not add JavaScript packages.
- Explicit non-goals: new hero/campaign content, new marketing claims, new images, new visual identity, deeper category taxonomy, backend/API changes, Wompi behavior, or broad page redesign.
- Preserve pre-existing unrelated work: `.gitignore`, `MEMORY.md`, `.codegraph/`, Dusk console/screenshot files, and the user's uncommitted T1 storefront work.
- Storefront changes remained uncommitted until the user's latest explicit authorization to commit and push on a new branch. The user chose `origin/main` as the new branch base to exclude the unrelated unpublished local `feat(mail)` commit.

## Task checklist

### T1 — Shared storefront shell and baseline navigation
- Status: pending (implemented and verified; user chose to keep it uncommitted, so paused/unreviewed).
- Route: delegated direct to `gentle-ai-worker`.
- Evidence: focused Feature 14/14 (137 assertions); full PHPUnit 327/327 (1,659 assertions); local Dusk checkout/catalog/admin-cancel 3/3 (54 assertions). Existing logs/screenshots preserved.

### T2 — Simple catalog-driven customer home
- Status: pending (implementation and Feature tests passed; remains uncommitted with T1 by user choice).
- Route: delegated direct to `gentle-ai-worker`.
- Scope: `/` uses the shared storefront shell with categories and up to four latest active products, using existing catalog conventions and a neutral empty state.
- Evidence: RED observed; focused `StorefrontHomeTest` 12/12 (74 assertions); full `php artisan test` 339/339 (1,733 assertions).
- Follow-up: auth access controls from the old welcome view are to be surfaced through the user/account menu per the user's confirmed choice.

### T3 — Style the shared top bar and navbar with approved content
- Status: pending (focused implementation/tests exist; full-suite and browser checks remain unverified; paused while the user requested the home-page visual slice)
- Current slice: shipping-message top bar only (`Envíos a todo el país` → `Envíos contraentrega`, four seconds each). Navbar styling remains outside this slice until explicitly requested.
- Route: delegated direct to `gentle-ai-worker` (shared multi-view UI, tests, and responsive behavior).
- Allowed edit surfaces:
  - `resources/views/layouts/storefront.blade.php`
  - `resources/views/storefront/home.blade.php`
  - `tests/Feature/StorefrontNavigationTest.php`
  - `tests/Feature/StorefrontHomeTest.php`
  - `tests/Browser/StorefrontNavigationTest.php`
- Acceptance criteria:
  1. The top bar renders the two exact user-approved shipping messages.
  2. Tienda dropdown links to all catalog plus current Dama/Caballero/Niños filters; Dama/Caballero remain direct shortcuts without invented children.
  3. The navbar uses the documented search, user/account, and shopping-bag icons, with actions routed to the current catalog, role-appropriate account/order/panel links, logout, and cart.
  4. Guest login/register and authenticated logout links from the old welcome behavior are preserved via the account control; staff Panel access remains available.
  5. Desktop/mobile menu interaction is accessible and works through existing Alpine without adding dependencies.
  6. Feature tests cover slogans, menu targets, auth visibility, and role-gated staff link; a browser test covers opening/navigating dropdowns.
  7. Preserve the currently implemented home/catalog/checkout flows and pass focused tests plus the full Unit/Feature suite.
- Checks: test-first Feature coverage; `php artisan test --filter=StorefrontNavigationTest`; `php artisan test --filter=StorefrontHomeTest`; full `php artisan test`; Dusk navigation smoke on a separate temporary port (not the user's Laravel server on 8000) with `MAIL_MAILER=array`, `SESSION_DRIVER=cookie`, test-only queue/cache, `tagq_testing`, and console/screenshot preservation. Do not execute the real Wompi Sandbox refund E2E.
- Progress/evidence: the shipping top-bar slice and requested storefront navigation are implemented in `resources/views/layouts/storefront.blade.php`. The nav has exactly Damas, Caballeros, and Tienda, with confirmed catalog filters, a Tienda submenu for Ver todo/Niños, accessible search/account/bag icons, auth-aware account actions, and an Alpine mobile menu. Focused tests pass: `StorefrontNavigationTest` 23/23 (212 assertions) and `StorefrontHomeTest` 12/12 (74 assertions).

### T4 — Create a visually styled transitional watch-store home
- Status: completed
- Authorization: user explicitly requested multiple tailored home sections and styled product cards, as a transitional design, with no animations.
- Route: delegated direct to `gentle-ai-worker`; multi-file UI and regression-test write trigger fired.
- Allowed edit surfaces:
  - `resources/views/storefront/home.blade.php`
  - `tests/Feature/StorefrontHomeTest.php`
- Acceptance criteria:
  1. Give the existing home a cohesive, responsive editorial storefront look suited to wristwatches, with a designed hero/introduction and several distinct, well-spaced sections.
  2. Style product cards visibly beyond the basic catalog list, preserving real product names, images/fallbacks, category, COP price, stock state, and existing product/cart routes.
  3. Use existing categories/products only; do not fabricate inventory, discount, warranty, shipping, or other business claims. Copy stays neutral and suitable for a transitional presentation.
  4. Preserve the shared header, layout, navigation, auth rules, and controller contract; no shared-shell or backend changes.
  5. No animation, transitions, new JS, dependencies, or external image assets; use existing Blade/Tailwind conventions and static layout effects only.
  6. Update Feature coverage to assert the new home sections and card presentation while retaining existing catalog, empty-state, and route behavior tests.
- Verification: observe test-first RED on focused UI assertions, implement until GREEN, then run `php artisan test --filter=StorefrontHomeTest`. Full-suite checks remain subject to the inherited storefront task and will be reported separately; no Dusk, commit, push, or native RDD review is authorized by this request.
- Progress/evidence: test-first RED observed — the new focused suite had 16 tests, 13 passed and 3 expected failures against the old basic home (hero/sections, editorial cards, and empty-state sections); the static-only invariant passed before implementation. GREEN after implementation was 16/16 (113 assertions); an added no-optional-attributes card case then passed in the final focused run: 17/17, 121 assertions. Parent spot-check and an independent read-only verifier both reran `php artisan test --filter=StorefrontHomeTest` and observed 17/17, 121 assertions. No browser/Dusk visual check or full suite was run for this styling slice. Changes remain uncommitted; no delivery or review lifecycle started. The required ASSESS facade call was blocked by validation rejecting its inspect-only `intendedUntracked` field, so no risk tier is claimed.

### T5 — Publish the storefront work on a clean feature branch
- Status: in_progress
- Authorization: the user explicitly requested commit and push on a new branch, then chose `origin/main` as the branch base to exclude the unrelated, unpublished local `feat(mail)` commit.
- Planned branch: `feat/storefront-experience`.
- Candidate paths: `app/Http/Controllers/StorefrontHomeController.php`; `routes/web.php`; `resources/views/layouts/storefront.blade.php`; `resources/views/layouts/app.blade.php`; `resources/views/storefront/home.blade.php`; `resources/views/cart/index.blade.php`; `resources/views/catalog/index.blade.php`; `resources/views/catalog/show.blade.php`; `resources/views/checkout/index.blade.php`; `resources/views/orders/index.blade.php`; `resources/views/orders/show.blade.php`; `tests/Feature/StorefrontHomeTest.php`; `tests/Feature/StorefrontNavigationTest.php`; `odd/tasks/frontend-shared-layout-navigation.md`.
- Exclude user-owned `.gitignore` and `MEMORY.md`, generated `.codegraph/` and screenshots/console artifacts, and unrelated agent-model diagnostic notes or old subtask ledgers.
- Required check: run the complete `php artisan test` suite and require green before committing, per `AGENTS.md`. Do not run the real Wompi Sandbox refund E2E. The dedicated browser/Dusk coverage for T3 navigation is absent/unverified; report it accurately and do not mark that check complete.
- Delivery: commit the verified storefront candidate on `feat/storefront-experience` and push that branch only to `origin`; no PR or merge is authorized. Record the final commit ID and push result here.

## Forecast and delivery
- T1 forecast ~300 authored changed lines; T2 ~150; T3 ~220; T4 ~200, generated files excluded.
- RDD is on; no assessment tier is currently available because the prior ASSESS facade call failed validation. Follow the native assessment/review contract if it becomes available; do not infer a risk tier.
- Base: `origin/main`. Exclude the unrelated unpublished `3f59a05 feat(mail): add transactional order emails` commit.

## Current status
T1 and T2 are implemented and tested but remain uncommitted. T3 focused tests pass, while the full suite and dedicated navigation browser/Dusk check were previously pending. T4 is implemented and focused-verified at 17/17 (121 assertions), confirmed by parent and independent verifier; no browser/Dusk or full-suite check was run specifically for that styling slice. T5 is in progress under fresh explicit commit/push authorization: create `feat/storefront-experience` from `origin/main`, run the full PHPUnit suite, commit and push only the storefront candidate, excluding unrelated local edits/artifacts and the unpublished mail commit. Keep the current dev server on port 8000 running.
