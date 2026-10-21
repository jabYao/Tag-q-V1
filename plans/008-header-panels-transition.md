# 008 — Animate the header panels that currently snap

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: MEDIUM
- **Category**: Missed opportunities
- **Estimated scope**: 1 file, 3 blocks

## Problem

The account dropdown and the two mobile panels appear and disappear with no
transition. The account menu is anchored to its trigger and should scale from
it; the mobile menu and the mobile Tienda panel are full-width disclosures and
should fade and slide a few pixels. Today all three snap, so the user gets no
spatial cue about where the panel came from.

Current account menu at `resources/views/layouts/storefront.blade.php:511-517`:

```blade
                    <div
                        data-nav-account-menu
                        x-show="open"
                        x-cloak
                        @click.outside="open = false"
                        class="absolute right-0 z-20 mt-1 w-56 rounded border border-gray-200 bg-white py-1 shadow-lg"
                    >
```

Current mobile menu at `:566-572`:

```blade
        <div
            id="storefront-mobile-menu"
            data-nav-mobile-panel
            x-show="mobileOpen"
            x-cloak
            class="mt-3 flex flex-col gap-1 border-t border-gray-200 pt-3 lg:hidden"
        >
```

Current mobile Tienda panel at `:592-598`:

```blade
            <div
                id="storefront-tienda-mega-menu-mobile"
                data-nav-mobile-mega-panel
                x-show="megaOpen"
                x-cloak
                class="mt-1 rounded border border-gray-200 bg-white p-4 shadow-sm"
            >
```

## Target

Use the same Alpine `x-transition` pattern already used by the desktop mega
menu and the search panel.

Account menu — 150 ms, opacity + 4 px lift + scale 0.97 from the top-right:

```blade
                    <div
                        data-nav-account-menu
                        x-show="open"
                        x-cloak
                        @click.outside="open = false"
                        x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.97] motion-reduce:translate-y-0 motion-reduce:scale-100"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-out duration-150 motion-reduce:transition-none"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.97] motion-reduce:translate-y-0 motion-reduce:scale-100"
                        class="absolute right-0 z-20 mt-1 w-56 origin-top-right rounded border border-gray-200 bg-white py-1 shadow-lg"
                    >
```

Mobile menu — 150 ms, opacity + 4 px slide, no scale:

```blade
        <div
            id="storefront-mobile-menu"
            data-nav-mobile-panel
            x-show="mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0 -translate-y-1 motion-reduce:translate-y-0"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-out duration-150 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1 motion-reduce:translate-y-0"
            class="mt-3 flex flex-col gap-1 border-t border-gray-200 pt-3 lg:hidden"
        >
```

Mobile Tienda panel — same 150 ms fade + 4 px slide:

```blade
            <div
                id="storefront-tienda-mega-menu-mobile"
                data-nav-mobile-mega-panel
                x-show="megaOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0 -translate-y-1 motion-reduce:translate-y-0"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-out duration-150 motion-reduce:transition-none"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1 motion-reduce:translate-y-0"
                class="mt-1 rounded border border-gray-200 bg-white p-4 shadow-sm"
            >
```

## Repo conventions to follow

- The desktop mega menu at `:354-359` and the search panel at `:647-652` use
  the same `x-transition` pattern with `motion-reduce:transition-none` and
  reduced-motion start/end values. Follow that shape.
- The account menu is a popover anchored to its trigger: scale it from
  `origin-top-right`, not from the center.
- The mobile menu and the mobile Tienda panel are full-width disclosures: do
  not scale them, and do not animate their height. Fade and a 4 px slide are
  enough.
- The 150 ms duration is inside the audit's 150–250 ms dropdown range and the
  125–200 ms small-popover range.

## Steps

1. Add the six `x-transition:*` attributes and `origin-top-right` to the account
   menu block at lines 511-517.
2. Add the six `x-transition:*` attributes to the mobile menu block at lines
   566-572.
3. Add the six `x-transition:*` attributes to the mobile Tienda block at lines
   592-598.
4. Leave `x-show`, `x-cloak`, `@click.outside`, IDs, data attributes and
   classes otherwise unchanged.

## Boundaries

- Do NOT change the existing desktop mega menu or search panel.
- Do NOT add a height or `max-height` transition to the mobile panels.
- Do NOT add `scale` to the full-width mobile panels.
- Do NOT change `x-show`, `x-cloak` or the outside-click handlers.
- If a block does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Click the account button. The menu should grow from the top-right over
    150 ms and disappear with the same ease-out.
  - At mobile width, tap the hamburger. The mobile menu should fade and slide
    4 px, without the whole panel scaling or the page height animating.
  - Tap Tienda inside the mobile menu. The Tienda panel should fade and slide
    the same way.
  - Click outside the account menu; it must still close.
  - Toggle `prefers-reduced-motion`; all three panels must appear instantly,
    without opacity or position transitions.
- **Done when**: the three panels no longer snap, the account menu scales from
  its trigger, the mobile panels only fade/slide, and reduced motion is
  respected.
