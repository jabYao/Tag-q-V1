# Hero editorial animation

## Goal
Apply the proposed subtle editorial hero animation: image zoom-out, staggered copy/buttons, and smooth slide copy replacement without color curtain.

## Decisions
- Preserve Swiper carousel and current no-color-curtain behavior.
- Use existing GSAP/Web Animations and no new package.
- Animate image from scale 1.04 to 1 and copy from translateY/opacity with a small stagger.
- Respect prefers-reduced-motion.

## Tasks
- [x] Add hero animation hooks/styles.
- [x] Animate initial and slide-change image/copy states.
- [x] Add regression tests and verify build.

## Evidence
- `php artisan test --filter=StorefrontHomeTest` — 44 tests, 510 assertions passing.
- `npm run build` — Vite build passed.
- No color curtain was reintroduced; Swiper behavior remains covered.
- No commit created; user authorization for commits was not provided.
