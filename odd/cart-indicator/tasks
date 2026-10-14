# Cart indicator dot

## Goal
Show a black dot on the storefront bag icon whenever the cart contains at least one line.

## Decisions
- Dot is black and decorative, with an accessible cart label retained.
- Initial state reads the current session/database cart.
- Add/remove actions update the dot without a full page reload.

## Tasks
- [x] Add initial and reactive cart-presence state to storefront layout.
- [x] Render black dot on bag icon and update cart events.
- [x] Add regression tests and verify.

## Evidence
- Focused verification: CartDrawerTest 22 passed/103 assertions; StorefrontNavigationTest 50 passed/611 assertions; PublicCartTest 23 passed/160 assertions.
- Initial guest/auth state uses CartService active lines; drawer mutations emit count; add event turns the dot on.
- Known follow-up: checkout emits cart-updated without count, so the dot may remain visible until navigation after checkout.
- No commit created; user authorization for commits was not provided.
