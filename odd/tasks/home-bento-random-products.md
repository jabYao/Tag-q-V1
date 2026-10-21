# Random bento product images

## Goal
Make the bento gallery show two random category images plus three randomly selected product images from the catalog, varying the product trio on each render.

## Decisions
- Product candidates come from all active catalog products with a real first image, not only the four recent home products.
- Keep category/product image URLs unique and exclude Kairos Obama as existing business rule.
- Gallery displays up to five items: two categories plus three products, fewer only when data is insufficient.

## Tasks
- [x] Provide complete active product candidates to the home gallery.
- [x] Randomly select exactly three product images distinct from categories.
- [x] Update regression tests and verify.

## Evidence
- Bento-focused tests: 6 passed, 46 assertions.
- Broader StorefrontHomeTest: 36 passed, 1 pre-existing unrelated failure caused by existing `transition` utility assertions.
- No commit created; user authorization for commits was not provided.
