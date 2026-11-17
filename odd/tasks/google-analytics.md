# Analítica con consentimiento (Google Analytics 4)

Medir el storefront con GA4 sin cargar analítica antes del consentimiento expreso.
Rama: `feature/legal-colombia`. Entrega: `ask-on-risk`.
Runner: `php artisan test` contra PostgreSQL `tagq_testing`.

## Specs

**S1 — Configuración.** `config/services.php` expone `google.analytics_id` desde
`GOOGLE_ANALYTICS_ID`. Sin valor configurado no se carga analítica ni se muestra
banner, en ningún entorno.

**S2 — Sin consentimiento no hay analítica.** Ninguna página del storefront
incluye `googletagmanager.com` ni `gtag/js` antes de que el visitante acepte.

**S3 — Banner opt-in simétrico.** Sin decisión previa, el storefront muestra un
aviso con dos acciones de igual jerarquía visual: `Aceptar` y `Rechazar`. Enlaza a
`/politica-de-cookies`. Aceptar inicia la medición sin recargar (para no perder el
pageview de la primera página). Rechazar no carga nada.

**S4 — La decisión persiste.** Cookie `tagq_analytics_consent` con `accepted` o
`rejected`, 1 año, `Path=/`, `SameSite=Lax`, escrita por el navegador. Debe quedar
excluida del cifrado de cookies de Laravel (`EncryptCookies` anula con
`DecryptException` cualquier cookie que no pueda descifrar, así que una cookie
plana se perdería y el aviso reaparecería en cada carga). Con decisión previa el
aviso no vuelve a mostrarse.

**S5 — El panel de administración nunca mide.** `layouts.admin` no incluye
analítica ni banner, con o sin ID configurado.

**S6 — La política de cookies refleja la realidad.** El inventario incluye `_ga` y
`_ga_<ID>` (Google LLC, 2 años), declara la transferencia internacional, explica
cómo revocar la decisión y elimina la afirmación de que no se usa analítica.
Cambiar el texto exige subir la versión del documento en `config/legal.php`.

**S7 — Tests.** Cobertura feature de S1–S6.

## Tasks

- **T1** — Config y entorno (`config/services.php`, `.env.example`). S1. inline. ✅ hecho.
- **T2** — Exclusión del cifrado de cookies + componente del cargador. S2, S4. inline. ✅ hecho.
- **T3** — Banner de consentimiento + JS de aceptación. S3, S4. inline. ✅ hecho.
- **T4** — Inserción en `layouts/storefront`. S2, S3, S5. inline. ✅ hecho.
- **T5** — Política de cookies y de privacidad + versión legal + `LegalPagesTest`. S6. inline. ✅ hecho.
- **T6** — `tests/Feature/AnalyticsConsentTest.php`. S1–S5. inline. ✅ hecho.

## Log

**L1 (17 nov 2026) —** «que necesitamos para añador goolge analitys al proyecto?» …
ID de medición entregado por el dueño: `G-CMD9M44Q1W`.

**L2 —** El proyecto no tenía analítica. `resources/views/layouts/app.blade.php` y
`layouts/guest.blade.php` no los usa ninguna vista; el panel usa
`layouts.admin.blade.php` y todo lo público usa `layouts.storefront.blade.php`.
Un solo punto de inserción excluye el admin por construcción.

**L3 —** `bootstrap/app.php` no configura `encryptCookies`; sin exclusión explícita
la cookie del banner se descarta en la siguiente petición.

**L4 —** RED observado el 17 nov 2026: 19 tests filtrados, falla `_ga` en
`LegalPagesTest` y toda la cobertura nueva (nada implementado). GREEN tras
implementar: 19/19. Suite completa: 770 tests, 0 fallos. `npm run build` OK.
La cookie local quedó con `GOOGLE_ANALYTICS_ID=G-CMD9M44Q1W` en `.env` (no se
versiona); el valor de producción se configura en Railway.
