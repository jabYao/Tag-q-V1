# Legal Colombia — Páginas legales y consentimiento verificable

Objective: redactar las siete páginas legales de Tag Q conforme al derecho colombiano y registrar consentimiento verificable en checkout y registro, sin inventar datos del vendedor.
Branch: current branch. Delivery: local commits only; push remains unauthorized. Test runner: `php artisan test` against PostgreSQL `tagq_testing`; asset check: `npm run build`.

## Specs

- **S1 — Páginas legales.** Las siete rutas existentes (`legal.privacy`, `legal.terms`, `legal.cookies`, `legal.returns`, `legal.shipping`, `legal.payments`, `legal.contact`) sirven textos reales en español, con fecha de actualización, versión, botón de impresión/guardado en PDF, navegación entre páginas y enlace visible a la SIC. Se conservan las URLs y el layout público del storefront.
- **S2 — Privacidad.** La política de privacidad cumple el contenido mínimo del artículo 2.2.2.25.3.1 del Decreto 1074 de 2015 y la Ley 1581 de 2012: responsable, tratamiento, finalidades, derechos del titular, consultas (10 días hábiles, prórroga de 5), reclamos (15 días hábiles, prórroga de 8), encargados, transferencias internacionales, seguridad, retención, menores y cookies.
- **S3 — Términos, cookies, devoluciones, envíos, pagos y contacto.** Los textos cubren identificación del vendedor, precios en COP, proceso de compra, aceptación expresa, acuse de recibo, retracto (5 días hábiles), garantía legal (1 año), reversión, PQRS, plazos y enlace a la SIC. La política de cookies inventaría solo las cookies reales: `tag-q-session`, `XSRF-TOKEN` y las de Wompi al abrir el widget; no hay analítica ni publicidad propias.
- **S4 — Evidencia de consentimiento.** Tabla `consent_records` con documento, versión, hash del texto, fecha de aceptación, IP, user agent, usuario, pedido, correo, origen y contexto. Modelo `ConsentRecord` y servicio `ConsentService` como única vía de registro.
- **S5 — Consentimiento en checkout.** Dos casillas obligatorias y no premarcadas (términos y privacidad) con enlaces. La validación del componente y el servicio rechazan el pedido sin ambas. Las dos evidencias se guardan dentro de la misma transacción del pedido.
- **S6 — Resumen del checkout.** Antes de pagar se muestran precio unitario, subtotal, costo de envío, total, medio de pago y plazo máximo de entrega. El resumen se puede imprimir o guardar en PDF, y el comprador puede volver al carrito o cancelar antes de confirmar.
- **S7 — Consentimiento en registro.** Dos casillas obligatorias y no premarcadas en el formulario de registro. La evidencia se registra al enviar el formulario con el correo; al verificar el código se asocia al usuario creado sin alterar la evidencia original.
- **S8 — Acuse de recibo por correo.** El correo de pedido creado incluye fecha del pedido, medio de pago, subtotal, costo de envío, total exacto y plazo máximo de entrega, además del código y los items.
- **S9 — SIC.** Enlace visible a la Superintendencia de Industria y Comercio en el pie del storefront y en las páginas legales.
- **S10 — Pruebas.** Se actualizan las pruebas de páginas legales, checkout, registro y correo; se agregan pruebas de consentimiento. `php artisan test` debe pasar completo antes de cada commit.
- **S11 — Datos no inventados.** Nombre, documento, dirección, ciudad, correos, horario, transportadora y régimen de IVA se leen de variables de entorno. Mientras falten, la página muestra marcadores visibles y un aviso de borrador; no se publica un dato falso.
- **S12 — Calidad.** Pint sin cambios pendientes y `npm run build` en verde.

## Tasks

- **T1** — `config/legal.php`, `app/Support/Legal.php`, layout legal y siete vistas. Specs: S1-S3, S11. Route: inline. Status: done. Evidence: rutas `legal.*` sirven `legal.*` con título, clave y documento; layout con aviso de borrador, impresión, navegación y SIC; `LegalPagesTest` 11/11.
- **T2** — Migración, modelo y servicio de consentimiento. Specs: S4. Route: inline. Status: done. Evidence: migración `2026_11_12_000000_create_consent_records_table.php`, `ConsentRecord`, `ConsentService`.
- **T3** — Checkout: casillas, validación, evidencia y resumen imprimible. Specs: S5-S6. Route: inline. Status: done. Evidence: `CheckoutForm`, `CheckoutService` y vista con `data-checkout-consent`, `data-checkout-unit-price`, `data-checkout-shipping`, `data-checkout-delivery` y `data-checkout-print`; `ConsentTest` y `CheckoutTest`.
- **T4** — Registro: casillas, validación y asociación de evidencia. Specs: S7. Route: inline. Status: done. Evidence: `RegisterRequest`, `RegistrationController@store/verify`, `register.blade.php`; `RegistrationTest` y `ConsentTest`.
- **T5** — Correo de acuse de recibo. Specs: S8. Route: inline. Status: done. Evidence: `OrderStatusMail` y `emails/orders/status.blade.php` con fecha, medio de pago, subtotal, envío, total y plazo; `OrderTransactionalEmailsTest`.
- **T6** — Enlace SIC en footer y pruebas. Specs: S9-S10. Route: inline. Status: done. Evidence: `data-footer-link="sic"` en el footer y `data-legal-sic` en el layout; `LegalPagesTest`.
- **T7** — Verificación completa: suite, Pint y build. Specs: S10, S12. Route: inline. Status: done. Evidence: `php artisan test` 736 tests / 6026 aserciones en verde; Pint limpio en los archivos tocados; `npm run build` en verde.

## Log

- **L1 — Solicitud original.** "no aun no tengo ningun texto legal, crealos de acuerdo a la ley colombiana te toca buscar en internet para ello". Después: "adelante". La tienda opera como persona natural; no se inventan nombre, NIT/cédula, dirección, correos, plazos ni régimen de IVA. Los textos son borradores técnicos y deben pasar por revisión de un abogado antes de publicarse.
- **L2 — Marco confirmado.** Ley 1581 de 2012; Decreto 1074 de 2015; Ley 1480 de 2011; Ley 527 de 1999; Ley 2439 de 2024 (efectos desde abril de 2025). Retracto aplica a relojes: la SIC reserva "bienes de uso personal" para higiene y cuidado personal. Garantía legal de un año para productos nuevos. Cookies: sin norma específica; las estrictamente necesarias no requieren banner y hoy no hay analítica ni publicidad.
- **L3 — Datos pendientes.** El usuario no entregó nombre legal, documento, dirección, ciudad, correos, horario, transportadora, tiempos/costos de envío ni régimen de IVA. Se implementan variables de entorno con marcadores visibles y aviso de borrador. Teléfono del sitio: +57 311 305 9507.
- **L4 — Decisión de alcance.** El formulario PQRS con radicado y seguimiento no existe hoy; las páginas publican el canal de correo/teléfono y el compromiso de radicado. Queda como siguiente tarea funcional, no se simula un formulario.
- **L5 — Implementación.** Se crean `config/legal.php`, `App\Support\Legal`, layout y siete vistas; migración/modelo/servicio `consent_records`; casillas en checkout y registro; resumen con precio unitario, envío, total y plazo; correo con acuse, medio de pago, envío y plazo; enlace SIC en footer.
- **L6 — Ajustes de verificación.** El CSS de impresión usaba el selector `[data-legal-draft]`, que hacía fallar la prueba de desaparición del borrador; se cambió a `[role="note"]`. La cadena "Ley 1480 de 2011" quedó partida por un salto de línea y se unió. El layout legal usa `site-container py-8` para conservar el contenedor global del storefront.
- **L7 — User agent en pruebas Livewire.** El harness de Livewire reemplaza los headers en cada rerender, así que la prueba de checkout verifica que la evidencia guarda un user agent no vacío en lugar de simular un valor fijo. La captura real sigue siendo `request()->userAgent()` en `ConsentService`.
- **L8 — Estilo.** Pint pasa limpio en todos los archivos tocados. El repositorio conserva archivos ajenos a esta tarea con estilo pendiente (`DashboardController`, `CatalogFilterRequest`, entre otros); no se reformatearon para no mezclar cambios fuera de alcance.
- **L9 — Verification.** Suite completa: 736 tests, 6026 aserciones, 0 fallos. `npm run build`: correcto. Sin push.
