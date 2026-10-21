# 004 — Bring the home hover timing inside the UI budget

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: MEDIUM
- **Category**: Easing & duration
- **Estimated scope**: 1 file, 4 declarations

## Problem

Every home card, tile and button marked `data-home-hover` lifts over 320 ms,
and the inner image zooms over 500 ms. The image continues moving for half a
second after the pointer stops, well past the 300 ms UI budget, and the
`box-shadow` transition animates a paint-only property on every hovered card.

Current code in `resources/css/app.css:310-330`:

```css
@media (hover: hover) and (prefers-reduced-motion: no-preference) {
    [data-home-hover] {
        transition:
            transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
            box-shadow 320ms cubic-bezier(0.22, 1, 0.36, 1),
            border-color 320ms ease,
            background-color 320ms ease;
    }

    [data-home-hover]:hover {
        transform: translateY(-4px);
    }

    [data-home-hover] img {
        transition: transform 500ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    [data-home-hover]:hover img {
        transform: scale(1.06);
    }
}
```

## Target

Keep the same movement and the same strong ease-out, but shorten it and drop
the paint-only shadow transition:

```css
@media (hover: hover) and (prefers-reduced-motion: no-preference) {
    [data-home-hover] {
        transition:
            transform 200ms cubic-bezier(0.22, 1, 0.36, 1),
            border-color 200ms ease,
            background-color 200ms ease;
    }

    [data-home-hover]:hover {
        transform: translateY(-4px);
    }

    [data-home-hover] img {
        transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    [data-home-hover]:hover img {
        transform: scale(1.06);
    }
}
```

The card's `hover:shadow-md` / `hover:shadow-lg` now changes instantly. That is
intentional: the lift and the image zoom already carry the hover feedback, and
the shadow no longer keeps a paint animation running.

## Repo conventions to follow

- The strong curve is the same one used by the nav underline and the nav entry:
  `cubic-bezier(0.22, 1, 0.36, 1)`.
- The whole block is already gated by `(hover: hover)` and
  `(prefers-reduced-motion: no-preference)`; do not add a second guard.
- The image zoom is the only nested transform; do not add a transition to the
  card's `img` element elsewhere.

## Steps

1. In `resources/css/app.css:311-317`, replace the four-entry transition list
   with the three-entry target list: `transform 200ms ...`, `border-color
   200ms ease`, `background-color 200ms ease`.
2. In the same block, change the image transition at line 324 from `500ms` to
   `300ms`.
3. Do not change the `:hover` transforms or the `scale(1.06)` end state.

## Boundaries

- Do NOT change the `translateY(-4px)` lift or the `scale(1.06)` zoom.
- Do NOT add `box-shadow` back to the transition list.
- Do NOT change the media query or add a separate reduced-motion rule; the
  block already excludes reduced motion.
- Do NOT touch the `.home-reveal` or hero rules above line 310.
- If the block does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Hover several product cards and category tiles. The lift should feel
    immediate; the image should finish zooming around 300 ms, not half a second
    later.
  - The shadow should switch instantly when the pointer enters; confirm there is
    no slow shadow fade.
  - In DevTools, record a Performance trace while hovering a card. There must be
    no Layout rows caused by the hover and no long paint from the shadow.
  - Toggle `prefers-reduced-motion` and confirm the cards do not move at all.
- **Done when**: the lift is 200 ms, the image is 300 ms, the color changes are
  200 ms `ease`, the shadow is no longer transitioned, and the hover feels
  immediate without losing the lift.
