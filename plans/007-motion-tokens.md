# 007 — Consolidate the repeated motion curve into theme tokens

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: LOW
- **Category**: Cohesion & tokens
- **Estimated scope**: 1 file, 5 tokens + every literal curve

## Problem

The strong ease-out `cubic-bezier(0.22, 1, 0.36, 1)` is hand-typed in seven
places in `resources/css/app.css`. Changing the storefront's motion character
means finding every copy, and the `@theme` block exposes no motion scale for
future work.

The current theme ends with the font tokens:

```css
/* resources/css/app.css:38-50 — current */
    --font-outfit: 'Outfit', ui-sans-serif, system-ui, sans-serif;

    /* Tipografía de títulos de secciones posteriores al hero. */
    --font-bebas: 'Bebas Neue', ui-sans-serif, system-ui, sans-serif;

    /*
     * Tipografía del wordmark de la marca (`font-jost`), en el navbar y en el
     * footer. Jost 500 medium se autoaloja con `laravel-vite-plugin/fonts`
     * desde Bunny Fonts (`vite.config.js`), igual que Inter. El token del tema
     * es lo que hace que Tailwind genere la utilidad reutilizable `font-jost`.
     */
    --font-jost: 'Jost', ui-sans-serif, system-ui, sans-serif;
}
```

At commit `2e628f3` the literal appears at lines 70, 95, 111, 141, 313, 314 and
324: the nav underline, the nav entry animation, the nav utilities child
animation, the search results animation, the hover transform, the hover shadow
and the hover image transition. Plans 002, 004, 005 and 009 may remove or move
some of these; the replacement below is value-based on purpose.

## Target

Add a named curve and the duration scale to `@theme`:

```css
@theme {
    /* ... existing font tokens ... */

    /*
     * Escala de movimiento del storefront: una sola curva fuerte para las
     * entradas, salidas y realces. Los tokens de duración documentan los
     * presupuestos de interacción; las reglas conservan sus valores actuales.
     */
    --ease-out-strong: cubic-bezier(0.22, 1, 0.36, 1);
    --duration-fast: 150ms;
    --duration-ui: 200ms;
    --duration-entrance: 300ms;
    --duration-underline: 260ms;
}
```

Replace every literal curve in the rules with the variable. Examples of the
post-004/005 state:

```css
[data-nav-primary-item]::after {
    transition: transform 260ms var(--ease-out-strong);
}

[data-home-hover] {
    transition:
        transform 200ms var(--ease-out-strong),
        border-color 200ms ease,
        background-color 200ms ease;
}

[data-home-hover] img {
    transition: transform 300ms var(--ease-out-strong);
}
```

If plans 002, 004 or 005 have not run yet, keep the property and duration you
find and replace only the timing function. The visual result must not change.

## Repo conventions to follow

- `@theme` is the single source for design tokens in this project; see the font
  tokens at lines 24-50.
- Tailwind 4 exposes `--ease-*` as a theme namespace, and every custom property
  in `@theme` is emitted as a CSS variable. The duration tokens are plain
  project variables (`var(--duration-*)`), not generated utilities.
- Do not rename or remove the existing font tokens.
- The token value must be the exact curve already used: no visual change.

## Steps

1. In `resources/css/app.css`, add the five token declarations immediately
   before the closing `}` of `@theme` at line 50.
2. Find every occurrence of the exact string with:
   `grep -nF 'cubic-bezier(0.22, 1, 0.36, 1)' resources/css/app.css`
3. Replace each occurrence with `var(--ease-out-strong)`, except the
   `--ease-out-strong` declaration itself.
4. If a rule already uses `var(--ease-out-strong)` because an earlier plan
   changed it, leave it. The literal count may be lower than seven; that is
   expected.
5. Do not change any property, duration, delay or selector.

## Boundaries

- Do NOT replace the literal inside the `--ease-out-strong` declaration; that
  would create a self-reference.
- Do NOT change any duration in this plan. The duration tokens are the scale;
  the per-component durations stay as they are.
- Do NOT add an `--ease-in-out-strong` token unless a rule actually uses it.
- Do NOT edit the Blade x-transition classes; they use Tailwind's built-in
  `ease-out` and are handled by plan 001.
- If a rule's value differs from the expected literal, STOP and report instead
  of guessing.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Token check**:
  `grep -nF 'cubic-bezier(0.22, 1, 0.36, 1)' resources/css/app.css`
  must return exactly one line: the `--ease-out-strong` declaration.
- **Feel check**: hover a nav item and a product card, open the search panel and
  reload the nav. The motion must look identical to before; the computed
  `transition-timing-function` and `animation-timing-function` must resolve to
  the same cubic-bezier.
- **Done when**: the theme exposes `--ease-out-strong` and the duration scale,
  every rule uses the token, and the page looks and moves exactly as before.
