# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Admin/dueño**: manages the store end to end: catalog, orders, collections, users, and roles.
- **Empleado**: manages products and orders; cannot manage users or collections.
- Both use the admin daily, and no single screen is prioritized over the others. The shell and dashboard must serve both roles, with admin-only data hidden from employees.
- **Cliente / Invitado**: storefront customers, not admin users.

## Product Purpose

Tag q is a Colombian e-commerce store for wristwatches. The admin panel lets the team operate the store: manage the catalog and per-unit inventory, process orders through their lifecycle, manage collections, and administer users and roles. Success means the team can run daily operations quickly, see what needs attention, and never oversell.

## Positioning

No unique market positioning is confirmed. Do not invent one. The durable product facts are the Colombian market, COP prices, a wristwatch catalog, per-unit inventory, and the Wompi payment and order workflow.

## Operating Context

- Order lifecycle (Spanish labels): Pendiente de pago → Pagado → En preparación → Enviado → Entregado; Cancelado and Reembolsado are terminal. Reuse the existing status map rather than inventing new values.
- Payment methods: card, PSE, Nequi, and cash on delivery, processed through Wompi.
- Inventory is per unit; the system must not oversell.
- Roles: Admin (all areas), Empleado (products and orders), Cliente, Invitado (checkout without an account).
- The admin is a Laravel monolith rendered with Blade; forms submit to controllers. There is no separate admin JSON API.
- Existing admin areas: Products, Orders, Users, and Collections. The current admin home is the Products list.
- The redesign adds a dashboard as the new admin home. It is computed server-side from the existing Eloquent models and rendered in Blade; no JSON endpoint is required.
- Dashboard scope: sales for the current month, orders by status, low-stock products, and active users (the users card is Admin-only).

## Capabilities and Constraints

- Laravel 11 monolith, Blade + Livewire, Tailwind CSS v4, Alpine.js, PostgreSQL, PHPUnit. No React, no headless API, no separate frontend.
- Current admin routes live in `routes/web.php`: Products and Orders for Admin + Empleado; Collections and Users for Admin only. The redesign may restructure screens, routes, and navigation, but the capabilities and the server-rendered workflow remain.
- Today there is no admin layout, no admin Livewire component, no dashboard, and no admin JSON endpoint.
- The only JSON endpoint is the inbound Wompi webhook (`POST /webhooks/wompi`), which the admin does not consume.
- The product capabilities (catalog and per-unit inventory, orders, users and roles, collections) and their data must remain. The UI, routes, and form flows may be restructured, with tests updated to the new structure. New functionality ships with tests, and the full suite must pass before commit. No commit or push without explicit authorization.
- Keep the server-rendered workflow; no new API is required.
- The redesign is scoped to the admin; the storefront remains a separate surface.
- Low-stock threshold is an open implementation detail; use a documented default (for example, stock ≤ 3) and make it visible rather than implying a precise business rule that was not confirmed.

## Brand Commitments

- Name: Tag q. The store sells wristwatches in Colombia.
- The existing storefront uses Outfit/Inter/Bebas/Jost and a black-and-white retail palette. This is evidence of the brand, not a binding visual constraint for the admin.
- The admin must read as a daily operating tool: not a marketing landing, not a sparse layout, and not a generic SaaS dashboard.
- No admin-specific brand book, logo usage rule, or voice guide is confirmed.

## Evidence on Hand

- Business facts in `AGENTS.md`; admin code in `app/Http/Controllers/Admin/`, `resources/views/admin/`, and `routes/web.php`; admin tests in `tests/Feature/Admin*Test.php`.
- Model fields confirmed: `Order.total` (integer COP), `Order.status`, `Order.created_at`, `Order.user`; `Product.precio` (integer COP), `Product.stock`, `Product.is_active`; `User.role` and `User.is_active`.
- No analytics, sales reports, or historical dashboard exist. Do not fabricate metrics, testimonials, or product claims.
- The catalog redesign is the latest visual precedent, but its changes are uncommitted.

## Product Principles

1. Operate first: speed, scanability, and clear state matter more than decoration.
2. Inventory integrity: never allow overselling, and make low stock visible.
3. Role-appropriate access: employees see products and orders; users and collections stay Admin-only.
4. Order state is the source of truth: transitions, cancellation, and refund must be obvious and auditable.
5. Keep the monolith: server-rendered Blade and existing controllers, no separate API.

## Accessibility & Inclusion

No product-specific accessibility standard was confirmed. Use semantic HTML, keyboard-operable controls, visible focus, and sufficient contrast as the baseline.
