# Storefront motion plans

Plans produced by the `improve-animations` audit on 2026-10-20. They are
read-only output: writing them did not change any source file.

- **Commit stamp**: `2e628f3`
- **Working tree**: the storefront files already had uncommitted changes when
  these plans were written, so each plan cites the working-tree version. If the
  code no longer matches a plan's excerpt, stop and report instead of
  improvising.
- **Scope**: storefront home and shell motion only. No backend, checkout or
  admin changes.
- **Status**: every plan starts as `TODO`.

## Plans

| Plan | Title | Severity | Files | Depends on |
| --- | --- | --- | --- | --- |
| [001](001-ease-out-exits.md) | Use a strong ease-out on every exit transition | HIGH | `resources/views/layouts/storefront.blade.php` | — |
| [002](002-nav-utilities-single-animation.md) | Animate the nav utilities once, not twice | MEDIUM | `resources/css/app.css` | — |
| [003](003-hero-first-paint.md) | Apply the hero's initial state before first paint | MEDIUM | `resources/views/storefront/home.blade.php`, `resources/css/app.css`, `resources/js/app.js` | — |
| [004](004-home-hover-timing.md) | Bring the home hover timing inside the UI budget | MEDIUM | `resources/css/app.css` | — |
| [005](005-nav-underline-transform.md) | Grow the nav underline with a transform | MEDIUM | `resources/css/app.css` | — |
| [006](006-press-feedback.md) | Add a pressed state to the home calls to action | LOW | `resources/views/storefront/home.blade.php`, `resources/views/livewire/cart/add-to-cart.blade.php` | — |
| [007](007-motion-tokens.md) | Consolidate the repeated motion curve into theme tokens | LOW | `resources/css/app.css` | — (run last) |
| [008](008-header-panels-transition.md) | Animate the header panels that currently snap | MEDIUM | `resources/views/layouts/storefront.blade.php` | — |
| [009](009-search-tabs-transition.md) | Transition the search tab content | LOW | `resources/views/livewire/storefront/search-panel.blade.php`, `resources/css/app.css` | — |
| [010](010-cart-indicator-pop.md) | Pop the cart indicator when the cart gains its first item | LOW | `resources/views/layouts/storefront.blade.php` | — |
| [011](011-hamburger-icon-swap.md) | Crossfade the hamburger into a close icon | LOW | `resources/views/layouts/storefront.blade.php` | — |

## Recommended order

1. **001** — independent; fixes the most visible exits.
2. **002**, **004**, **005**, **009** — independent CSS changes; any order.
3. **003**, **006**, **008**, **010**, **011** — independent; any order.
4. **007 last** — it replaces every remaining literal
   `cubic-bezier(0.22, 1, 0.36, 1)` with `var(--ease-out-strong)`. Running it
   after 002/004/005/009 also tokenizes the curves those plans leave in place.
   If 007 must run first, adapt 004/005/009 to use the token instead of the
   literal; do not add new literals after 007.

## Execution rules

- Execute one plan at a time, from its own file. Do not merge steps from
  different plans in one edit.
- Run `npm run build` after each plan and `php artisan test` before closing it.
- Every motion plan includes a `prefers-reduced-motion` check; run it.
- If a plan's current code does not match the file, stop. The line numbers are
  from commit `2e628f3` plus the working-tree changes; they will drift.
- Do not push at any point; push requires the repository owner's explicit
  authorization. Commit only after the full suite is green and the owner asks.
  This repository commits locally by default; a commit does not publish or
  deploy.

## Verification baseline

- Mechanical: `npm run build` must exit 0.
- Test suite: `php artisan test` must pass.
- Feel: use each plan's specific checks, with DevTools Animations playback at
  10% for timing and the Rendering panel for `prefers-reduced-motion`.
