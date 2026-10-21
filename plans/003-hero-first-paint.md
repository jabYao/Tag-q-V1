# 003 — Apply the hero's initial state before first paint

- **Status**: TODO
- **Commit**: 2e628f3
- **Severity**: MEDIUM
- **Category**: Performance
- **Estimated scope**: 3 files, ~30 lines

## Problem

The hero copy is server-rendered visible. GSAP hides it only after the deferred
`app.js` module loads and `enterActive()` runs. On a slow connection the browser
can paint the title, body and buttons, then GSAP applies `opacity: 0` and the
copy re-enters — a visible flash on the most important surface of the home.

The first image has the same problem in a milder form: it paints at full
opacity, then GSAP sets `autoAlpha: 0.72` and `scale: 1.04` before animating
back to full.

Relevant code:

```blade
{{-- resources/views/layouts/storefront.blade.php:12 — current --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

```blade
{{-- resources/views/storefront/home.blade.php:102-109 — current --}}
<div
    data-home-hero-overlay
    class="pointer-events-none absolute inset-0 z-[3] flex items-center pl-14 pr-6 pt-10 pb-16 sm:pl-20 sm:pr-10 sm:pt-14 sm:pb-16"
>
    @foreach($heroSlides as $index => $slide)
        <div
            data-home-hero-copy="{{ $index }}"
            @if($index !== 0) hidden @endif
            class="pointer-events-auto w-full max-w-xl"
        >
```

```js
// resources/js/app.js:180-210 — current
const timeline = gsap.timeline();

if (media) {
    timeline.fromTo(
        media,
        HERO_MEDIA_ENTRY_FROM,
        { ...HERO_MEDIA_ENTRY_TO, clearProps: 'transform,opacity,visibility' },
        0,
    );
}

layers.forEach((layer, order) => {
    timeline.fromTo(
        layer,
        HERO_COPY_ENTRY_FROM,
        { ...HERO_COPY_ENTRY_TO, clearProps: 'transform,opacity,visibility' },
        HERO_COPY_STAGGER[order] ?? order * 0.1,
    );
});
```

The constants at `resources/js/app.js:70-76` are:

```js
const HERO_MEDIA_ZOOM_START = 1.04;
const HERO_MEDIA_ZOOM_END = 1;
const HERO_COPY_STAGGER = [0, 0.1, 0.18];
const HERO_MEDIA_ENTRY_FROM = { scale: HERO_MEDIA_ZOOM_START, autoAlpha: 0.72 };
const HERO_MEDIA_ENTRY_TO = { scale: HERO_MEDIA_ZOOM_END, autoAlpha: 1, duration: 1.1, ease: 'power2.out' };
const HERO_COPY_ENTRY_FROM = { y: 20, autoAlpha: 0 };
const HERO_COPY_ENTRY_TO = { y: 0, autoAlpha: 1, duration: 0.6, ease: 'power3.out' };
```

## Target

Add a pre-paint initial state that matches the GSAP `from` values, and remove it
as soon as GSAP has created the first timeline. Keep the no-JS fallback intact:
if JavaScript is disabled or the module never runs, the copy must remain
visible.

### 1. Add the pre-paint script to the home view

In `resources/views/storefront/home.blade.php`, after
`@section('main-class', 'w-full')` at line 11 and before `@section('content')`,
add:

```blade
@push('head')
    <script>
        document.documentElement.classList.add('js');

        window.setTimeout(function () {
            if (! document.documentElement.hasAttribute('data-home-hero-ready')) {
                document.documentElement.classList.remove('js');
            }
        }, 3000);
    </script>
@endpush
```

The layout renders `@stack('head')` at line 11, immediately before `@vite` at
line 12, so the `js` class is set before the stylesheet loads.

### 2. Add the initial-state CSS

In `resources/css/app.css`, after the `[data-home-hero-viewport] .swiper` rule
at line 292, add:

```css
@media (prefers-reduced-motion: no-preference) {
    html.js:not([data-home-hero-ready]) [data-home-hero-copy]:not([hidden]) [data-home-hero-title],
    html.js:not([data-home-hero-ready]) [data-home-hero-copy]:not([hidden]) [data-home-hero-body],
    html.js:not([data-home-hero-ready]) [data-home-hero-copy]:not([hidden]) [data-home-hero-actions] {
        opacity: 0;
        transform: translateY(20px);
    }

    html.js:not([data-home-hero-ready]) [data-home-hero-slide='1'] [data-home-hero-photo] {
        opacity: 0.72;
        transform: scale(1.04);
    }
}
```

These values match `HERO_COPY_ENTRY_FROM` (`y: 20`, `autoAlpha: 0`) and
`HERO_MEDIA_ENTRY_FROM` (`scale: 1.04`, `autoAlpha: 0.72`). The
`prefers-reduced-motion: no-preference` wrapper means reduced-motion visitors
never see a hidden hero.

### 3. Mark the hero ready after the first timeline is created

In `resources/js/app.js`, after the initial call at line 210:

```js
    // La carga inicial también entra: el hero arranca con la primera diapositiva.
    showCopy(swiper.realIndex);
    enterActive(swiper.realIndex);
    document.documentElement.dataset.homeHeroReady = 'true';
```

`enterActive()` calls `timeline.fromTo(...)`, which applies the `from` values
immediately (GSAP's default `immediateRender`). Setting the attribute after that
call removes the CSS initial state without a frame where the copy is visible.
If `prefers-reduced-motion` is active, `enterActive()` returns early; the CSS
initial state never applied because of the media query, and setting the
attribute is harmless.

## Repo conventions to follow

- The layout already uses `@stack('head')` for page-specific head tags; see
  `resources/views/layouts/storefront.blade.php:8-12`.
- The home already declares the no-JS tradeoff in the comments at
  `resources/views/storefront/home.blade.php:42-49` and
  `resources/js/app.js:61-68`. This plan preserves it: no `js` class means no
  hidden state.
- The hero hooks are `data-home-hero-copy`, `data-home-hero-title`,
  `data-home-hero-body`, `data-home-hero-actions` and `data-home-hero-photo`.
  Do not rename them.

## Steps

1. Add the `@push('head')` script to `resources/views/storefront/home.blade.php`
   after line 11.
2. Add the `@media (prefers-reduced-motion: no-preference)` initial-state block
   to `resources/css/app.css` after line 292.
3. Add
   `document.documentElement.dataset.homeHeroReady = 'true';` after
   `enterActive(swiper.realIndex);` at line 210 of `resources/js/app.js`.

## Boundaries

- Do NOT change the GSAP constants, durations, easing, stagger or timeline
  structure.
- Do NOT change the hero markup or the `hidden` attribute on inactive copies.
- Do NOT hide the hero unconditionally: the `js` class is the only gate, and the
  3-second timeout must remove it if the module never runs.
- Do NOT add a `<noscript>` block that duplicates the fallback; JS disabled
  means the inline script never adds `js`, so the CSS never applies.
- If the code does not match the excerpts above, STOP and report instead of
  improvising.

## Verification

- **Mechanical**: run `npm run build` and confirm it exits 0. Run
  `php artisan test` and confirm the full suite passes.
- **Feel check**:
  - In DevTools, throttle to Slow 4G, disable cache and hard-reload the home.
    The title, body and buttons must not be visible before the entrance starts;
    there must be no flash followed by a second entrance.
  - Disable JavaScript entirely and reload. The first slide's copy must be
    visible immediately, with no missing content.
  - In DevTools, block the `app.js` request and reload. After about 3 seconds the
    copy must become visible; it must not stay hidden forever.
  - Toggle `prefers-reduced-motion` and reload. The copy must be visible
    immediately, with no zoom or stagger.
  - In DevTools, set the Animations panel playback to 10% and confirm the
    entrance still starts from `translateY(20px)` and `scale(1.04)`.
- **Done when**: the throttled reload shows no flash before the entrance, the
  no-JS and blocked-module cases both leave the copy readable, and the normal
  entrance is unchanged.
