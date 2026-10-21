# 005 — Grow the nav underline with a transform

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: MEDIUM
- **Category**: Performance
- **Estimated scope**: 1 file, 3 declarations

## Problem

The primary nav underline animates `width` from `0` to `100%` over 260 ms.
Width is a layout property: every frame invalidates layout for the nav item and
its siblings, and the browser cannot keep the underline on the compositor. The
visual result is identical to a scale, so the layout work is unnecessary.

Current code in `resources/css/app.css:61-76`:

```css
[data-nav-primary-item]::after {
    position: absolute;
    right: 0;
    bottom: 0.25rem;
    left: 0;
    width: 0;
    height: 1px;
    background-color: currentColor;
    content: '';
    transition: width 260ms cubic-bezier(0.22, 1, 0.36, 1);
}

[data-nav-primary-item]:hover::after,
[data-nav-primary-item]:focus-visible::after {
    width: 100%;
}
```

## Target

Keep the pseudo-element full width at all times and scale it horizontally from
the left edge:

```css
[data-nav-primary-item]::after {
    position: absolute;
    right: 0;
    bottom: 0.25rem;
    left: 0;
    width: 100%;
    height: 1px;
    background-color: currentColor;
    content: '';
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 260ms cubic-bezier(0.22, 1, 0.36, 1);
}

[data-nav-primary-item]:hover::after,
[data-nav-primary-item]:focus-visible::after {
    transform: scaleX(1);
}
```

The reduced-motion block that sets `transition: none` on this pseudo-element
stays exactly as it is; the transform still flips instantly for those users.

## Repo conventions to follow

- The 260 ms duration and the strong curve are intentional; only the animated
  property changes.
- `transform-origin: left` is required so the line grows from the left, matching
  the current visual.
- The underline is a pseudo-element on the item; do not add a wrapper or a new
  element.

## Steps

1. In `resources/css/app.css:66`, change `width: 0;` to `width: 100%;`.
2. After the `content: '';` line at line 69, add `transform: scaleX(0);` and
   `transform-origin: left;`.
3. Replace the transition at line 70 with
   `transition: transform 260ms cubic-bezier(0.22, 1, 0.36, 1);`.
4. In the hover/focus rule at lines 73-76, replace `width: 100%;` with
   `transform: scaleX(1);`.

## Boundaries

- Do NOT change the 260 ms duration or the curve.
- Do NOT change `bottom: 0.25rem`, the height, or the color.
- Do NOT remove the `:focus-visible` selector.
- Do NOT add `will-change: transform`; the one-off hover does not need it.
- If the block does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Hover each primary nav item. The line must still grow from left to right
    over 260 ms and look identical to before.
  - Tab through the nav with the keyboard; the focus-visible line must still
    appear.
  - In DevTools, record a Performance trace while hovering. There must be no
    Layout rows for the nav item; the underline should appear as a composite.
  - Toggle `prefers-reduced-motion`; the line must appear instantly, without a
    transition.
- **Done when**: the pseudo-element uses `transform: scaleX(...)` with
  `transform-origin: left`, the width no longer changes on hover, and the visual
  is unchanged.
