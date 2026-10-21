# Writer harness smoke check

- UTC timestamp: 2026-10-05T19:03:10Z
- Laravel project: The repository is a Laravel project; `composer.json` declares `laravel/framework` with the version constraint `^13.17`.
- Testing rule: Tests live in `tests/Unit` for small isolated logic, `tests/Feature` for database/HTTP or application flows, and `tests/Browser` for end-to-end browser coverage; PHPUnit configures Unit and Feature suites, and tests use PostgreSQL (`tagq_testing`).
- The worker harness executed correctly.
