# 006 — Add a pressed state to the home calls to action

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: LOW
- **Category**: Physicality & origin
- **Estimated scope**: 2 files, 7 class values

## Problem

The home CTAs and the compact add-to-cart button have hover states but no
pressed state. On a mouse the click has no physical confirmation; on touch the
tap can feel like nothing happened until the next page paints. The audit's
target for press feedback is a 0.95–0.97 scale over 100–160 ms.

The excerpts below show only the value of the `class` attribute; replace that
value, not the whole element.

```blade
{{-- resources/views/storefront/home.blade.php:131 — current class value --}}
inline-flex items-center bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white

{{-- :135 — current class value --}}
inline-flex items-center border border-gray-600 px-6 py-3 text-sm font-semibold text-white hover:border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white

{{-- :189 and :248 — current class value --}}
group inline-flex items-center gap-2 bg-gray-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2

{{-- :388 — current class value --}}
inline-flex items-center bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white

{{-- :393 — current class value --}}
inline-flex items-center border border-white/70 px-6 py-3 text-sm font-semibold text-white hover:border-white hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white
```

```blade
{{-- resources/views/livewire/cart/add-to-cart.blade.php:31 — current class value --}}
inline-flex h-8 w-8 items-center justify-center bg-white text-xl leading-none text-black shadow-sm hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400
```

## Target

Add `active:scale-[0.97]` and `motion-reduce:active:scale-100` to every button,
plus a transform transition where the class list does not already have one.
The 3 % scale and 150 ms duration are the audit's press-feedback values.

White CTA at lines 131 and 388:

```blade
inline-flex items-center bg-white px-6 py-3 text-sm font-semibold text-gray-900 transition-transform duration-150 ease-out hover:bg-gray-200 active:scale-[0.97] motion-reduce:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white
```

Outline CTA at line 135:

```blade
inline-flex items-center border border-gray-600 px-6 py-3 text-sm font-semibold text-white transition-transform duration-150 ease-out hover:border-gray-300 active:scale-[0.97] motion-reduce:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white
```

Black `Ver todo` / `Ver todos` CTAs at lines 189 and 248:

```blade
group inline-flex items-center gap-2 bg-gray-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 hover:shadow-md active:scale-[0.97] motion-reduce:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-950 focus-visible:ring-offset-2
```

Outline CTA at line 393:

```blade
inline-flex items-center border border-white/70 px-6 py-3 text-sm font-semibold text-white transition-transform duration-150 ease-out hover:border-white hover:bg-white/10 active:scale-[0.97] motion-reduce:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white
```

Compact add-to-cart at `add-to-cart.blade.php:31`:

```blade
inline-flex h-8 w-8 items-center justify-center bg-white text-xl leading-none text-black shadow-sm transition-transform duration-150 ease-out hover:bg-gray-100 active:scale-[0.97] motion-reduce:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400
```

## Repo conventions to follow

- The project uses Tailwind CSS 4. `scale-*` sets the individual `scale` CSS
  property, so `active:scale-[0.97]` composes with the existing
  `[data-home-hover]:hover { transform: translateY(-4px); }` instead of
  replacing it.
- Tailwind 4's `transition` utility already includes `scale`; the two `Ver todo`
  links therefore do not need `transition-transform`. The other links get
  `transition-transform` because they have no transition class today.
- `motion-reduce:active:scale-100` keeps the pressed state from moving for
  visitors who asked for reduced motion; the color changes remain.

## Steps

1. In `resources/views/storefront/home.blade.php`, replace the class value at
   lines 131 and 388 with the white CTA target.
2. Replace the class value at line 135 with the outline CTA target.
3. Replace the class values at lines 189 and 248 with the black CTA target.
4. Replace the class value at line 393 with the closing outline CTA target.
5. In `resources/views/livewire/cart/add-to-cart.blade.php`, replace the class
   value at line 31 with the compact target.

## Boundaries

- Do NOT add press feedback to the cards, gallery tiles or category links; this
  plan is only for the seven buttons listed above.
- Do NOT change hover colors, padding, text, destinations or disabled styles.
- Do NOT add a global `button:active` or `a:active` rule.
- Do NOT use `active:scale-95`; the target is 0.97.
- If a class value does not match the excerpt, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - Press and hold each CTA with the mouse. The button should scale to 0.97
    within 150 ms and return on release; the page must not shift.
  - On the home, tap a product's compact `+` button. The press should be visible
    before the cart drawer opens.
  - Use DevTools device emulation to confirm the tap state on a touch viewport.
  - Toggle `prefers-reduced-motion`; the buttons must not scale, but the hover
    colors must still change.
  - Tab to each CTA and press Enter or Space; the action must still fire and the
    focus ring must remain visible.
- **Done when**: all seven buttons have `active:scale-[0.97]` and
  `motion-reduce:active:scale-100`, the pressed scale is visible, and no other
  visual or behavioral change is present.
