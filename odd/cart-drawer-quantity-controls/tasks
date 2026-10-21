# Cart drawer quantity controls

## Goal
Add immediate quantity controls and a black text-only removal action to populated cart drawer lines.

## Decisions
- Quantity uses minus and plus controls around the current number.
- Each increment/decrement persists immediately.
- Remove is black text and removes the whole line.
- Removing the last line keeps the drawer open and renders the empty state.

## Tasks
- [x] Add drawer Livewire actions/state for quantity increment, decrement, and removal.
- [x] Render quantity controls and black remove action in the drawer.
- [x] Add/update feature coverage for controls, persistence, limits, and empty transition.
- [x] Run focused verification.

## Evidence
- `php artisan test tests/Feature/CartDrawerTest.php` — 17 tests, 95 assertions passing.
- Independent read-only verification confirmed immediate actions, stock boundaries, black text-only removal, and last-item empty-state behavior.
- No commit created; user authorization for commits was not provided.
