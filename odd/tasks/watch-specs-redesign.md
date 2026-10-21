# Watch Specs Redesign

Objective: extend the product data model with the full watch specification taxonomy, brand management, and multiple images without deleting existing products or changing the existing Cloudinary flow.

Branch: `feat/watch-specs-redesign` (local only; no push/PR until the owner authorizes). Delivery strategy: ask-on-risk. Test runner: `php artisan test` against PostgreSQL `tagq_testing`.

## Specs

- S1. The product model must cover every field in `especificaciones-reloj.md`. Fields marked `(true)` are always active and cannot be disabled.
- S2. Labels, option values, descriptions, UI copy, and seed data are in Spanish. Technical table and column names stay in English.
- S3. Avoid duplicate values through canonical slugs and a single source of truth.
- S4. `material_caja`, `color_caja`, and `funciones` stay free text. There is no base/variant split and no separate "complicaciones visibles" field.
- S5. Add `Unisex` to categories. Expand subcategories to casual, formal, deportivo, buceo, and cronógrafo.
- S6. Brands need create, deactivate, and delete management. Collections already have CRUD and `is_active`.
- S7. A product supports multiple images. Cloudinary remains the image service.
- S8. Existing products are not deleted by the implementation. The owner deletes them manually.
- S9. No demo products are created. The owner creates products manually.
- S10. New required columns are nullable in the database until the owner deletes the legacy products. Required fields are enforced in validation; a later migration may set `NOT NULL`.
- S11. `reference` is unique per brand. Brand, model, and reference are required.
- S12. Required structured fields: brand, name/model, reference, category (gender), subcategory, movement, case shape, crystal; `case_material` and `case_color` are required free text. Filterable nullable fields: diameter, thickness, water resistance, strap type.
- S13. Informative optional specs are stored in `specs` JSONB and are activated per product. Only activated/filled values render on the product page.
- S14. `DatabaseSeeder` must stop creating the four demo products; keep only base users and categories.

## Tasks

- T1. Add migrations: `brands` table, product spec columns and `specs` JSONB, `Unisex`, subcategory remap, category FK `restrict`, legacy brand backfill. (S1, S5, S8, S9, S10, S11, S12) — done (`6647047`).
- T2. Add `config/watch_specs.php`; add `Brand` model; update `Product` model. (S1, S2, S3, S13) — done (`6647047`).
- T3. Add brand admin CRUD: requests, controller, routes, views, navigation. (S6) — done (`7fbd806`).
- T4. Update product requests, controller, and admin form for the new fields and JSONB specs. (S1, S2, S10, S12, S13) — done (`f9f013b`).
- T5. Update catalog detail display and filters. (S1, S2, S12, S13) — done (`7aedcf4`).
- T6. Update seeders, add/update tests, run the full suite. (S8, S9, S14) — done (`6647047`, `7fbd806`, `f9f013b`, `7aedcf4`, `1f341e1`, `58e3121`); full suite 532 passed.

## Log

- L1 (recovered objective): Redesign the product data model to support the full watch spec taxonomy in `especificaciones-reloj.md`, brand/collection management, and multiple product images. Implement in Spanish without deleting existing products or creating demo products.
- L2 (verbatim, latest owner instruction): "no vas a borrar nada, yo hago un borrado manual agrega eso y yo borro manualmente"
- L3 (verbatim, owner constraint): "no crees productos yo los creo"
- L4 (implementation): Added the four migrations, the canonical taxonomy in `config/watch_specs.php`, the `Brand` and `Product` model changes, brand admin CRUD, product admin spec fields, and catalog filters/detail rendering. Existing products are preserved; new required columns remain nullable until manual cleanup.
- L5 (taxonomy completion): Added the `resistencia` informative group (`valvula_helio`, `iso_6425`, `resistencia_golpes`, `rango_temperatura`), added `integrado` to strap types, and translated the `joyas` and `lug_to_lug_mm` labels to Spanish.
- L6 (verification): `php artisan test --compact --stop-on-failure` -> 532 passed. Local commits `6647047`, `7fbd806`, `f9f013b`, `7aedcf4`, `1f341e1`, `58e3121`; no push or PR.
- L7 (pending): The `NOT NULL` migration for required spec columns waits for the owner's manual product deletion.
- L8 (coverage): Added catalog search coverage for the new brand relation, so searching by brand name finds products without a legacy `marca` value.
- L9 (regression fix): The flat detail table keyed informative specs by label, so duplicate labels (`Color`, `Acabado`) overwrote each other. Renamed them to `Color de esfera`, `Color de correa`, `Acabado de caja`, `Acabado de esfera`, and `Material de correa`, with a regression test covering the four values together.
