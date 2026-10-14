# Modern home animations

## Goal
Add expressive modern entrance and hover animations across the storefront home without disrupting the existing hero carousel.

## Decisions
- Use existing GSAP/ScrollTrigger dependency; do not add another animation package.
- Animate all home sections after the hero: headings, copy, category cards, product cards, CTA, and bento gallery.
- Include staggered reveals, image zoom/reveal, and expressive but bounded hover states.
- Respect `prefers-reduced-motion` and keep content usable without JavaScript.

## Tasks
- [x] Add animation hooks and styles to home sections/cards.
- [x] Initialize scroll/entrance and hover animations in app JS.
- [x] Add structural/accessibility regression tests.
- [x] Run build and focused home verification.

## Evidence
- `php artisan test --filter=StorefrontHomeTest` — 40 tests, 445 assertions passing.
- `npm run build` — Vite build passed.
- Existing hero carousel remains outside `data-home-body` animation root.
- No commit created; user authorization for commits was not provided.
