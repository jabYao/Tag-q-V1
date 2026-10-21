# 010 — Pop the cart indicator when the cart gains its first item

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: LOW
- **Category**: Missed opportunities
- **Estimated scope**: 1 file, 6 attributes

## Problem

The black cart dot appears and disappears with a hard `display` toggle. It is
the only persistent state indicator in the navbar, and the jump from nothing to
an 8 px dot is easy to miss. A short pop makes the state change legible without
becoming a decorative loop.

Current span at `resources/views/layouts/storefront.blade.php:554-560`:

```blade
                    <span
                        data-nav-cart-indicator
                        aria-hidden="true"
                        x-show="cartHasItems"
                        style="{{ $storefrontCartHasItems ? '' : 'display: none;' }}"
                        class="pointer-events-none absolute right-1 top-1 h-2 w-2 rounded-full bg-black"
                    ></span>
```

## Target

Add a 150 ms enter and a 100 ms leave, scaling from 0.8 to 1:

```blade
                    <span
                        data-nav-cart-indicator
                        aria-hidden="true"
                        x-show="cartHasItems"
                        x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                        x-transition:enter-start="opacity-0 scale-[0.8] motion-reduce:scale-100"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-out duration-100 motion-reduce:transition-none"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-[0.8] motion-reduce:scale-100"
                        style="{{ $storefrontCartHasItems ? '' : 'display: none;' }}"
                        class="pointer-events-none absolute right-1 top-1 h-2 w-2 rounded-full bg-black"
                    ></span>
```

## Repo conventions to follow

- The dot is a state indicator, not decoration: 150 ms enter and 100 ms leave
  are inside the audit's 100–160 ms state-feedback range.
- Keep the server-rendered `display: none` fallback; it prevents a flash of the
  dot on pages where the cart is empty before Alpine boots.
- Do not scale from zero; 0.8 keeps the dot visible throughout the pop.

## Steps

1. In `resources/views/layouts/storefront.blade.php:557`, add the six
   `x-transition:*` attributes between `x-show` and `style`.
2. Keep the `style` attribute exactly as it is.
3. Do not change the class, the size or the position.

## Boundaries

- Do NOT animate the bag icon itself; only the dot.
- Do NOT remove the inline `display: none` fallback or the `aria-hidden`.
- Do NOT use a looping pulse or a bounce; this is a one-shot state change.
- Do NOT change `x-show="cartHasItems"`.
- If the span does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - With an empty cart, add the first product from the home. The dot should
    pop in over 150 ms as the drawer opens; it must not jump into place.
  - Remove the last item and confirm the dot fades out over 100 ms.
  - Check that the dot still sits at `right-1 top-1` and remains 8 × 8 px; it
    must not move the bag icon.
  - Toggle `prefers-reduced-motion`; the dot must appear and disappear
    instantly, with no scale.
- **Done when**: the dot pops in on the first item, fades out on the last, keeps
  its position and size, and reduced motion is instant.
