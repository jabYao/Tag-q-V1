# 001 — Use a strong ease-out on every exit transition

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: HIGH
- **Category**: Easing & duration
- **Estimated scope**: 1 file, 6 one-line replacements

## Problem

Six leave transitions in the storefront shell use Tailwind's `ease-in`
(`cubic-bezier(0.4, 0, 1, 1)`). An exit that starts slow feels unresponsive
exactly when the user has already decided to dismiss the layer. The affected
exits are the two rotating shipping messages, the desktop mega menu, the search
backdrop and panel, and the cart drawer:

```blade
{{-- resources/views/layouts/storefront.blade.php:65 and :93 — current --}}
x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"

{{-- :357 — current --}}
x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"

{{-- :623 — current --}}
x-transition:leave="transition-opacity ease-in duration-150 motion-reduce:transition-none"

{{-- :650 and :986 — current --}}
x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none"
```

## Target

Replace only the timing function on those six attributes with the storefront's
existing strong curve, `cubic-bezier(0.22, 1, 0.36, 1)`:

```blade
{{-- target — :65, :93, :650, :986 --}}
x-transition:leave="transition ease-[cubic-bezier(0.22,1,0.36,1)] duration-200 motion-reduce:transition-none"

{{-- target — :357 --}}
x-transition:leave="transition ease-[cubic-bezier(0.22,1,0.36,1)] duration-150 motion-reduce:transition-none"

{{-- target — :623 --}}
x-transition:leave="transition-opacity ease-[cubic-bezier(0.22,1,0.36,1)] duration-150 motion-reduce:transition-none"
```

The durations stay exactly as they are: 200 ms for the shipping messages, the
search panel and the cart drawer; 150 ms for the mega menu and the search
backdrop. Do not change the enter transitions.

## Repo conventions to follow

- The storefront already uses `cubic-bezier(0.22, 1, 0.36, 1)` as its strong
  ease-out in `resources/css/app.css:70` (nav underline) and `:95` (nav entry).
- For Tailwind arbitrary values, write the curve without spaces:
  `ease-[cubic-bezier(0.22,1,0.36,1)]`.
- `motion-reduce:transition-none` must remain on every line.

## Steps

1. In `resources/views/layouts/storefront.blade.php`, replace the leave
   attribute at lines 65 and 93 with the 200 ms target string above.
2. Replace the leave attribute at line 357 with the 150 ms target string.
3. Replace the leave attribute at line 623 with the `transition-opacity` target
   string.
4. Replace the leave attributes at lines 650 and 986 with the 200 ms target
   string.

## Boundaries

- Do NOT change any `x-transition:enter*` attribute.
- Do NOT change any duration.
- Do NOT touch the cart-drawer backdrop at line 970 (`x-transition.opacity`) or
  any other transition.
- Do NOT change markup, add dependencies, or reformat the file.
- If the lines do not match the excerpts above (drift since commit `2e628f3`),
  STOP and report instead of improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Reload the home and let the shipping messages rotate: each message should
    leave immediately, without a slow start.
  - Open and close the desktop mega menu, the search panel, and the cart drawer
    on desktop.
  - In DevTools, set the Animations panel playback to 10% and confirm the exit
    starts at full speed; there should be no dead 50–80 ms at the beginning.
  - Toggle `prefers-reduced-motion` in the Rendering panel and confirm the exits
    remain instant.
- **Done when**:
  `grep -R "ease-in\b" resources/views/layouts resources/views/storefront resources/views/livewire | grep -v "ease-in-out"`
  returns no storefront exit lines, and all six exits feel immediate.
