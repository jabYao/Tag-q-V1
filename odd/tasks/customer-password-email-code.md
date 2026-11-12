# Customer password change with email code

Objetivo: exigir un código nuevo por correo en cada cambio de contraseña del cliente; al completarlo, cerrar la sesión y redirigir al login.
Rama: árbol de trabajo actual (sin commit ni push autorizados).
Runner: `php artisan test` contra PostgreSQL `tagq_testing`.

## Specs

- S1. Iniciar cambio: `PUT /mi-cuenta/contrasena` valida contraseña actual, nueva (mín. 8, confirmada y distinta) y **no** cambia la contraseña todavía. Genera un código de 6 dígitos, guarda hasheado el código y la contraseña nueva pendiente ligados al usuario, con vencimiento de 10 minutos, máximo 5 intentos y 60 segundos de espera entre reenvíos. Envía el código al correo y redirige a la página de verificación.
- S2. Página de verificación: `GET /mi-cuenta/contrasena/verificar` muestra el mensaje de que se envió un código al correo, seis cuadritos de un dígito (`name="code[]"`), el botón de confirmar y la opción de reenviar código. Si no hay solicitud pendiente, redirige al formulario de contraseña.
- S3. Validar código: `POST /mi-cuenta/contrasena/verificar` exige 6 dígitos; código inválido, vencido, de otro usuario o con intentos agotados no cambia la contraseña; el intento fallido se contabiliza y el código correcto no se consume si la validación falla.
- S4. Éxito: aplica la contraseña nueva pendiente sin volver a hashearla, rota `remember_token`, consume el código, cierra la sesión (`Auth::logout` + invalidar sesión + regenerar token CSRF) y redirige al login con un mensaje de estado.
- S5. Reenvío y UI: `POST /mi-cuenta/contrasena/verificar/reenviar` respeta los 60 s, genera un código nuevo, invalida el anterior y conserva la contraseña pendiente. Los cuadritos aceptan solo dígitos, avanzan al escribir, retroceden con Backspace y, al pegar o autocompletar el código completo en un cuadro, lo reparten entre todos.
- S6. Protección y alcance: invitado redirigido al login, equipo (admin/empleado) con 403, throttle independiente por acción en iniciar/verificar/reenviar; sin cambios en el flujo de recuperación por enlace ni en el registro.

## Tasks

- [x] T1. Tests primero (RED observado) — `tests/Feature/CustomerPasswordChangeCodeTest.php` y ajustes en `CustomerAccountTest`. RED: 13 errores por ruta/modelo faltantes.
- [x] T2. Migración `password_change_verifications` + modelo `PasswordChangeVerification`.
- [x] T3. Mailable `PasswordChangeVerificationCode` + vista `emails.password-change-verification-code`.
- [x] T4. Rutas, `UpdateOwnPasswordRequest` y `AccountController` (enviar código, validar y cambiar contraseña, cerrar sesión).
- [x] T5. Vista `account/password.blade.php` con envío de código y campo de verificación.
- [x] T6. Suite dirigida, suite completa, `npm run build` y verificador independiente.
- [x] T7. Rediseñar el flujo: `PUT` inicia la solicitud y redirige a verificar; columna `password` pendiente; controlador y request de verificación.
- [x] T8. Nueva vista de verificación con OTP de 6 cuadritos, pegado distribuido, mensaje de envío y reenvío.
- [x] T9. Rutas, throttles y ajustes de request/vista de contraseña.
- [x] T10. Tests actualizados, suite completa y `npm run build`.
- [x] T11. Hardening del verificador: reparto al autocompletar, payload anidado sin 500 y throttle separado por acción (RED observado en copia temporal).

## Log

- L1 (2026-11-09, usuario, verbatim): "bueno dos cosas que se necesite verificacion de correo para cambiar de contrasena, y al momento de cambiar cierre la seccion actual y me redirija al login"
- L2 (2026-11-09, usuario, aclaración): "Código nuevo en cada cambio".
- L3 (2026-11-09): se reutiliza el patrón de códigos del registro (6 dígitos, hash, 10 min, 5 intentos, 60 s de reenvío) en una tabla propia ligada a `user_id`; el flujo de enlace de recuperación no se toca.
- L4 (2026-11-09, implementación): ruta `POST /mi-cuenta/contrasena/codigo` con `throttle:6,1`; el código se hashea con `Hash`, se guarda uno vigente por usuario y se consume al cambiar. El éxito rota `remember_token`, cierra la sesión (`Auth::logout` + invalidar + regenerar CSRF) y redirige al login. La vista usa `PasswordChangeVerification::CODE_TTL_MINUTES` para el texto de vencimiento.
- L5 (2026-11-09, verificación): dirigidos 33 tests / 270 aserciones; suite completa 688 tests / 5725 aserciones; `npm run build` OK. Verificador independiente: S1–S6 PASS, sin bloqueadores; avisos no bloqueantes: (a) un código vencido no incrementa intentos, (b) cambiar el correo desde `/mi-cuenta` redirige el factor de correo (riesgo residual ya conocido del flujo de datos, fuera de alcance), (c) TTL estaba fijo en la vista (corregido a la constante). Probes del verificador añadidos como `CustomerPasswordCodeVerificationProbeTest`.
- L6 (2026-11-09): sin commit ni push; el árbol conserva cambios ajenos. Riesgo del cambio: item 2 (autenticación, permisos y credenciales).
- L7 (2026-11-09, usuario, verbatim): "quita esta parte no habia necesidad de aclararlo" — se refiere al bloque "Verificación por correo" que mostraba el correo de la cuenta. Se elimina ese bloque y se conserva solo el botón "Enviar código"; S5 se ajusta y los tests de vista ahora verifican que el bloque no aparece.
- L8 (2026-11-09, re-verificación): dirigidos 33 tests / 270 aserciones y suite completa 688 tests / 5725 aserciones en verde. Cambio de presentación: riesgo none.
- L9 (2026-11-09, usuario, verbatim): "MIRA EL FLUJO DEBE SER ESTE: CUANDO INGRESEMOS LA CONTRESENA ACTUAL E INGRESEMOS LA NUEVA Y CUANDO LE DAMOS A CAMBIAR CONTRASENA, NOS REDIRIJE A UNA PAGINA DONDE DEBEMOS INGRESAR EL CODIGO QUE NOS DA EN EL CORREO, AHI SI DEBE ENVIAR EL MENSAJE QUE SE HA ENVIADO UN CODIGO AL CORREO, Y TAMBIEN DAR  LA OPCION DE RRENVIAR CODIGO, PARA INGRESAR EL COGIDO NO DEBE SER UN INPUT DE TEXTO, DEBE SER UN IMPUT DE ESOS DONDE SE VEN CUADRITOS EN EN CADA CUADRO VA EL NUMERO, SI PEFAMOS TODO EN EL PRIMER CUADRO DEBE REPARTIRSE LOS NUMEROS CORRECTOS EN CADA CADRITO SI QUIERES BUSCAS EN INTERNET Y VERIFICAS". Este pedido reemplaza S1-S6 del flujo anterior: la contraseña nueva queda pendiente hasta verificar el código en una página aparte.
- L10 (2026-11-09, implementación y verificación): `PUT /mi-cuenta/contrasena` deja la contraseña pendiente, envía el código y redirige a `GET /mi-cuenta/contrasena/verificar`; la verificación aplica el hash pendiente sin rehashear (cast `hashed` de Laravel), rota `remember_token`, consume el código y cierra la sesión. La página muestra el correo, 6 cuadritos OTP con avance/retroceso y pegado distribuido, y reenvío con espera. Rutas con `throttle` 6/10. Tests: dirigidos 29/206; suite completa 684/5661; `npm run build` OK. Se eliminó el probe del flujo anterior y se reescribió `CustomerPasswordChangeCodeTest`; `CustomerAccountTest` ahora comprueba que el primer paso no cambia la contraseña. Sin commit ni push.
- L11 (2026-11-09, hardening tras el verificador): Laravel usa solo el ID del usuario como clave de throttle para autenticados, así que las tres rutas compartían contador y agotar la verificación bloqueaba el reenvío; se añadió prefijo propio (`password-change-start`, `password-change-verify`, `password-change-resend`). El guardado del código ahora rechaza payloads anidados con validación normal en vez de 500 por "Array to string conversion". El manejador `input` reparte el código cuando el autocompletado móvil inserta varios dígitos de golpe, igual que el pegado. RED observado en copia temporal: sin prefijos el reenvío devolvía 429 y sin el guardado el payload anidado devolvía 500. Tests nuevos: `test_agotar_la_verificacion_no_bloquea_el_reenvio` y `test_un_payload_de_codigo_anidado_falla_la_validacion_sin_error_500`. Dirigidos 31/234; suite completa 686/5689; `node --check` del script OTP OK. Pendiente como aviso no bloqueante: el reset por enlace no invalida una solicitud pendiente; los formatos inválidos no consumen intentos (el throttle acota). Sin commit ni push.
