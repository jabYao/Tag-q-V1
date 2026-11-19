# Analítica con consentimiento (Google Analytics 4)

Medir el storefront con GA4 sin cargar analítica antes del consentimiento expreso.
Rama: `feature/legal-colombia`. Entrega: `ask-on-risk`.

La rama acumula ~3.800 líneas contra `main` (toda la feature legal). La decisión de
PR encadenado o PR único queda para cuando el dueño autorice publicar: este
trabajo no hace push ni abre PR.
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

**S8 — Embudo de negocio.** Con consentimiento, el storefront emite
`view_item` (ficha de producto), `add_to_cart` (acción del componente Livewire),
`begin_checkout` (página de checkout con carrito no vacío) y `purchase`
(confirmación del pedido). Todos llevan `currency: "COP"`, `value` e `items[]` con
`item_id` (la referencia comercial `reference`, o el id interno si falta),
`item_name`, `price` y `quantity`. El evento se declara como JSON en el HTML y lo
dispara `app.js`; sin consentimiento no se declara ninguno.

**S9 — `purchase` mide dinero cobrado y una sola vez.** Cuenta cuando el pago en
línea ya está confirmado (`pagado`, `en_preparacion`, `enviado`, `entregado`) o
cuando es contraentrega no cancelada ni reembolsada. `transaction_id` es el `code`
del pedido. Se emite una vez por pedido y navegador: `app.js` anota el código en
la cookie `tagq_purchase_tracked` y el servidor deja de declarar el evento cuando
la ve. Esa cookie también queda excluida del cifrado.

**S10 — `user_id` con sesión iniciada.** El cargador envía
`gtag('set', 'user_id', <id interno>)` cuando hay sesión, tanto si el
consentimiento venía de antes como si se acepta en la misma página. Sin sesión no
se envía. Nunca viaja correo ni nombre.

**S11 — La política de cookies inventaría la cookie propia de deduplicación.**
`tagq_purchase_tracked` aparece en el inventario, explica que solo se escribe con
consentimiento y que evita contar dos veces la misma compra. Cambiar el texto sube
la versión y la fecha de `cookies`.

## Tasks

- **T1** — Config y entorno (`config/services.php`, `.env.example`). S1. inline. ✅ hecho.
- **T2** — Exclusión del cifrado de cookies + componente del cargador. S2, S4. inline. ✅ hecho.
- **T3** — Banner de consentimiento + JS de aceptación. S3, S4. inline. ✅ hecho.
- **T4** — Inserción en `layouts/storefront`. S2, S3, S5. inline. ✅ hecho.
- **T5** — Política de cookies y de privacidad + versión legal + `LegalPagesTest`. S6. inline. ✅ hecho.
- **T6** — `tests/Feature/AnalyticsConsentTest.php`. S1–S5. inline. ✅ hecho.
- **T7** — Fecha de actualización por documento (`config/legal.php`,
  `App\Support\Legal::updatedAt`, `legal/layout`) + test. S6. inline. ✅ hecho.
- **T8** — `App\Support\Analytics` (gate, item, params, reglas de `purchase`) y
  componente `analytics.event`. S8, S9. inline. ✅ hecho.
- **T9** — Ganchos: `AddToCart`, `catalog/show`, `checkout/index`,
  `orders/public`, `scripts`/`consent`, `app.js` y exclusión de
  `tagq_purchase_tracked`. S8–S10. inline. ✅ hecho.
- **T10** — Política de cookies: inventario y deduplicación; versión y fecha de
  `cookies` suben a `2026-11-19.1` / 19 de noviembre de 2026. S11. inline. ✅ hecho.

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

**L5 (17 nov 2026) —** «si que cada documento tenga su propia fecha de actualkizacion»
(verbatim). Decisión: `cookies` y `privacy` declaran 17 de noviembre de 2026; los otros
cinco documentos conservan el 13 de noviembre de 2026 como valor por defecto.

**L6 —** RED del test nuevo antes de implementar (cookies mostraba la fecha global).
GREEN: `LegalPagesTest` 14/14; suite completa 771 tests, 0 fallos. La fecha no entra
en `documentHash`, así que el consentimiento ya registrado sigue siendo válido y no
hizo falta subir la versión otra vez.

**L7 (19 nov 2026) —** «Eventos de negocio (aquí está el valor real, no en las
visitas) … view_item / add_to_cart / begin_checkout / purchase … Deduplica por
code … gtag('set', 'user_id', …) … de esto que nos falta?» y «adelante»
(verbatim). Verificado contra el código: eventos y `user_id` no existían; el resto
de la lista ya estaba. Además el `.env` local tenía
`GOOGLE_ANALYTICS_ID=G-CMD9M44Q1W`, así que las visitas de desarrollo se estaban
mezclando con la propiedad real: se vació (sin ID no hay banner ni gtag). Se
mantiene la decisión de no usar Consent Mode v2: no se carga nada antes de
aceptar, que es más estricto que modelar.

**L8 —** RED observado: 7 de los 13 tests nuevos fallaban (no había eventos ni
`user_id`). GREEN: `AnalyticsConsentTest` 13/13, `LegalPagesTest` 15/15, suite
completa 778 tests, 0 fallos, `npm run build` OK.

**L9 —** Decisiones de medición: (a) `purchase` cuenta el pago confirmado y
también la contraentrega, porque el pedido es firme aunque el cobro sea al
recibir; un pedido pendiente de Wompi no cuenta hasta que el webhook confirma y la
propia página se recarga sola; (b) la deduplicación es por navegador, no global:
un mismo enlace abierto en otro dispositivo volvería a medir la compra; (c) un
visitante que compra sin haber decidido el consentimiento no genera `purchase` en
esa visita —no hay cola previa al consentimiento—, pero sí al volver al enlace con
la decisión ya tomada.

**L10 —** Commits locales de la feature: `8814644` (consentimiento y cargador,
T1–T6), `eb522c6` (fecha de actualización por documento, T7) y `971dc27` (embudo
completo y `user_id`, T8–T10). Ninguno se publicó: la rama no tiene upstream y el
push sigue sin autorización. El CI de GitHub nunca corrió esta rama porque
`ci.yml` solo dispara en push o PR hacia `main`. Al auditar el CI de `main` se
encontraron dos tests Dusk desactualizados —no bugs del producto— corregidos en
`fabbae2`: `AuthResponsiveTest` usaba varias sentencias en `assertScript()`, que
antepone `return`; `HomeMobileTest` buscaba el icono `compact` en favoritos, que
usa la variante `bag`. Las 39 expresiones `assertScript` de `tests/Browser` se
compilaron como cuerpo de función con Node: todas válidas. Suite 778/778.
