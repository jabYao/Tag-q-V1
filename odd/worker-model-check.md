# Writer harness model check

- **UTC timestamp:** 2026-10-05T19:26:15Z
- **Repository/framework:** The repository root is a Laravel project: `composer.json` identifies it as a Composer project, includes the Laravel framework, and the repository contains the Laravel `artisan` entry point. The framework version constraint is `laravel/framework: ^13.17`.
- **Testing rule:** New functionality must include a passing test. Small isolated logic belongs in `tests/Unit`; database-, HTTP-, route-, service-, Livewire-, money-, or stock-related flows belong in `tests/Feature` (using `RefreshDatabase` where applicable); critical browser purchase/payment flows belong in `tests/Browser`. Tests must run against PostgreSQL database `tagq_testing`, not SQLite, and the full `php artisan test` suite must pass before a commit.
- **Model:** The model identity running this check was not reported.
