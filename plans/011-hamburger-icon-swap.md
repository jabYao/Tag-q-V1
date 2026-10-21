# 011 — Crossfade the hamburger into a close icon

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: LOW
- **Category**: Missed opportunities
- **Estimated scope**: 1 file, ~25 lines

## Problem

The mobile menu button keeps the same three-bar icon while the menu is open,
and its accessible name stays `Abrir menú` even when the menu is expanded. The
state change is not visible in the icon and is not announced correctly.

Current button at `resources/views/layouts/storefront.blade.php:418-432`:

```blade
                <button
                    type="button"
                    data-nav-mobile-toggle
                    aria-controls="storefront-mobile-menu"
                    :aria-expanded="mobileOpen"
                    aria-label="Abrir menú"
                    @click="mobileOpen = ! mobileOpen"
                    class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 lg:hidden"
                >
                    <svg data-nav-icon="menu" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
```

## Target

Stack a hamburger and a close SVG in the same grid cell, crossfade them with a
90° rotation, and update the accessible name with the state:

```blade
                <button
                    type="button"
                    data-nav-mobile-toggle
                    aria-controls="storefront-mobile-menu"
                    :aria-expanded="mobileOpen"
                    :aria-label="mobileOpen ? 'Cerrar menú' : 'Abrir menú'"
                    @click="mobileOpen = ! mobileOpen"
                    class="inline-flex items-center justify-center rounded p-2 text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 lg:hidden"
                >
                    <span class="grid h-6 w-6 place-items-center">
                        <svg
                            data-nav-icon="menu"
                            x-show="! mobileOpen"
                            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                            x-transition:enter-start="opacity-0 rotate-90 motion-reduce:rotate-0"
                            x-transition:enter-end="opacity-100 rotate-0"
                            x-transition:leave="transition ease-out duration-150 motion-reduce:transition-none"
                            x-transition:leave-start="opacity-100 rotate-0"
                            x-transition:leave-end="opacity-0 -rotate-90 motion-reduce:rotate-0"
                            aria-hidden="true"
                            focusable="false"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="col-start-1 row-start-1 h-6 w-6"
                        >
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="3" y1="18" x2="21" y2="18" />
                        </svg>
                        <svg
                            data-nav-icon="close"
                            x-show="mobileOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                            x-transition:enter-start="opacity-0 -rotate-90 motion-reduce:rotate-0"
                            x-transition:enter-end="opacity-100 rotate-0"
                            x-transition:leave="transition ease-out duration-150 motion-reduce:transition-none"
                            x-transition:leave-start="opacity-100 rotate-0"
                            x-transition:leave-end="opacity-0 rotate-90 motion-reduce:rotate-0"
                            aria-hidden="true"
                            focusable="false"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="col-start-1 row-start-1 h-6 w-6"
                        >
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </span>
                </button>
```

## Repo conventions to follow

- The other mobile icons are 24 × 24 and use the same stroke attributes; keep
  `stroke-width="2"`, `stroke-linecap="round"` and `stroke-linejoin="round"`.
- The button already toggles `mobileOpen`; do not add a second Alpine state.
- Grid stacking (`col-start-1 row-start-1`) keeps both icons in the same 24 × 24
  cell, so the button never grows while both are present.
- The icon swap is a state indicator: 200 ms enter / 150 ms leave is inside the
  audit's UI budget.

## Steps

1. Replace `aria-label="Abrir menú"` with
   `:aria-label="mobileOpen ? 'Cerrar menú' : 'Abrir menú'"`.
2. Wrap the existing menu SVG in
   `<span class="grid h-6 w-6 place-items-center"> ... </span>` and add the
   `x-show`, transition and grid-cell classes shown above.
3. Add the close SVG with `data-nav-icon="close"`, `x-show="mobileOpen"`,
   `x-cloak` and the reverse transitions.
4. Keep the button's classes, `aria-controls`, `:aria-expanded`, data attributes
   and `@click` unchanged.

## Boundaries

- Do NOT replace the button with a checkbox, a CSS-only toggle or a new
  component.
- Do NOT animate the menu panel here; plan 008 handles the panel.
- Do NOT use `scale(0)`; the crossfade uses opacity and rotation only.
- Do NOT remove `data-nav-icon="menu"`; other code or tests may query it.
- If the button does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - At mobile width, tap the hamburger. The bars should rotate/fade out while
    the X rotates/fades in; the button must stay the same size.
  - Tap the X. The reverse swap must run, and the menu must close.
  - Inspect the button with the accessibility tree; the name must change from
    `Abrir menú` to `Cerrar menú` and `aria-expanded` must track the state.
  - Toggle `prefers-reduced-motion`; the icons must swap instantly, without
    rotation or fading.
  - Keyboard-focus the button; the focus ring must remain visible.
- **Done when**: the icon crossfades/rotates on both open and close, the button
  size does not change, the accessible name tracks the state, and reduced
  motion is instant.
