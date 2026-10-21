# 002 — Animate the nav utilities once, not twice

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: MEDIUM
- **Category**: Cohesion & tokens
- **Estimated scope**: 1 file, ~15 lines

## Problem

The navbar utilities (search, account, bag) run two entrance animations at the
same time. The container `[data-nav-utilities]` fades and lifts over 420 ms
starting at 240 ms, and each child also fades and lifts over 360 ms starting at
280, 340 and 400 ms. Nested opacity multiplies and the transforms stack: the
bag is effectively dimmer than intended and only settles around 760 ms after
every page load.

Current code in `resources/css/app.css:90-125`:

```css
@media (prefers-reduced-motion: no-preference) {
    [data-storefront-nav] [data-nav-primary],
    [data-storefront-nav] [data-nav-brand],
    [data-storefront-nav] [data-nav-utilities],
    [data-storefront-nav] [data-nav-mobile-toggle] {
        animation: storefront-nav-content-entry 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    [data-storefront-nav] [data-nav-primary] {
        animation-delay: 80ms;
    }

    [data-storefront-nav] [data-nav-brand] {
        animation-delay: 160ms;
    }

    [data-storefront-nav] [data-nav-utilities] {
        animation-delay: 240ms;
    }

    [data-storefront-nav] [data-nav-utilities] > * {
        animation: storefront-nav-content-entry 360ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    [data-storefront-nav] [data-nav-utilities] > :nth-child(1) {
        animation-delay: 280ms;
    }

    [data-storefront-nav] [data-nav-utilities] > :nth-child(2) {
        animation-delay: 340ms;
    }

    [data-storefront-nav] [data-nav-utilities] > :nth-child(3) {
        animation-delay: 400ms;
    }
}
```

## Target

Keep the child stagger and remove the container animation. Shorten the child
duration to 300 ms and move the delays to 240, 280 and 320 ms so the utilities
settle with the rest of the navbar instead of half a second later:

```css
@media (prefers-reduced-motion: no-preference) {
    [data-storefront-nav] [data-nav-primary],
    [data-storefront-nav] [data-nav-brand],
    [data-storefront-nav] [data-nav-mobile-toggle] {
        animation: storefront-nav-content-entry 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    [data-storefront-nav] [data-nav-primary] {
        animation-delay: 80ms;
    }

    [data-storefront-nav] [data-nav-brand] {
        animation-delay: 160ms;
    }

    [data-storefront-nav] [data-nav-utilities] > * {
        animation: storefront-nav-content-entry 300ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    [data-storefront-nav] [data-nav-utilities] > :nth-child(1) {
        animation-delay: 240ms;
    }

    [data-storefront-nav] [data-nav-utilities] > :nth-child(2) {
        animation-delay: 280ms;
    }

    [data-storefront-nav] [data-nav-utilities] > :nth-child(3) {
        animation-delay: 320ms;
    }
}
```

Also remove the now-redundant container selector from the reduced-motion block
at `resources/css/app.css:158-164`, leaving the children and the other groups:

```css
    [data-storefront-nav] [data-nav-primary],
    [data-storefront-nav] [data-nav-brand],
    [data-storefront-nav] [data-nav-utilities] > *,
    [data-storefront-nav] [data-nav-mobile-toggle] {
        animation: none;
    }
```

## Repo conventions to follow

- The entry keyframes and the strong curve already exist in this file:
  `@keyframes storefront-nav-content-entry` at line 78 and
  `cubic-bezier(0.22, 1, 0.36, 1)` at line 95. Do not add a new keyframe or a
  new curve.
- The stagger values are deliberately small (40 ms); do not replace them with
  the 90 ms used by the home content reveals.
- The reduced-motion block must keep disabling all nav entrance motion.

## Steps

1. In `resources/css/app.css`, delete
   `[data-storefront-nav] [data-nav-utilities],` from the group selector at
   lines 91-96.
2. Delete the `[data-storefront-nav] [data-nav-utilities] { animation-delay:
   240ms; }` block at lines 106-108.
3. Change the child animation duration from `360ms` to `300ms` and the three
   child delays from `280ms`, `340ms`, `400ms` to `240ms`, `280ms`, `320ms`.
4. In the reduced-motion block, delete the
   `[data-storefront-nav] [data-nav-utilities],` line, leaving the child
   selector in place.

## Boundaries

- Do NOT change `[data-nav-primary]`, `[data-nav-brand]` or
  `[data-nav-mobile-toggle]` delays or durations.
- Do NOT change the keyframes or the curve.
- Do NOT add `animation` to the utilities container again.
- Do NOT touch the search results animation at lines 127-146.
- If the block does not match the excerpt above, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Hard-reload the home several times. The search, account and bag icons should
    appear as one short stagger; the bag should be fully opaque well before
    700 ms, not around 760 ms.
  - In DevTools, set the Animations panel playback to 10% and confirm no element
    receives two simultaneous opacity or transform animations.
  - Toggle `prefers-reduced-motion` and confirm the utilities appear instantly,
    with no fade and no lift.
- **Done when**: the utilities container has no `animation` declaration, the
  three children animate for 300 ms from 240/280/320 ms, and the utilities no
  longer look doubly faded during load.
