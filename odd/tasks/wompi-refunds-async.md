# Wompi refunds asíncronos

Objetivo: que un reembolso Wompi funcione de punta a punta (solicitud, estado pendiente, webhook, stock y correo) sin fallar en silencio. Rama: `main` (sin commits hasta autorización explícita). Runner: `php artisan test` contra PostgreSQL. Entrega: trabajo local, push solo con autorización del dueño.

## Specs

- **S1.** `WOMPI_API_URL` apunta a la API de Wompi (`https://sandbox.wompi.co/v1` en local; `https://production.wompi.co/v1` en producción). `.env.example` documenta todas las claves `WOMPI_*`. Motivo: hoy la variable apunta al webhook de ngrok y el POST del panel devuelve 404 antes de llegar a Wompi.
- **S2.** La solicitud de reembolso envía `transaction_id`, `amount_in_cents`, `reason`, `reference` y además los campos V2 (`idempotency_key`, `merchant_id`, `region`, `currency`, `fee_in_cents`, `fee_vat_in_cents`, `total_freeze_amount`, `refund_type`, `original_amount_in_cents`, `references`). El parser acepta `refund_id`, `data.refund_id` y `data.id`; el estado desde `status` o `data.status`. Timeout 10 s.
- **S3.** La llamada HTTP a Wompi ocurre fuera de la transacción de base de datos. Antes de llamar, una transacción corta marca `refund_status = pending`, `refund_requested_at` y una `refund_idempotency_key` única.
- **S4.** Una respuesta 201/202 con `PENDING` deja el pedido en `pagado`, `refund_status = pending`, guarda `wompi_refund_id` si viene y no toca el stock. El panel informa que el reembolso está en proceso.
- **S5.** `APPROVED` (respuesta inmediata, webhook o conciliación) marca el pedido `reembolsado`, guarda `wompi_refund_id`, restaura el stock una sola vez y envía el correo una sola vez.
- **S6.** `DECLINED`/`ERROR`/`CANCELLED` deja el pedido `pagado`, guarda el motivo en `refund_error` y permite reintentar con una nueva idempotency key. Un timeout o 5xx queda `pending` (ambiguo) y lo resuelve la conciliación.
- **S7.** El webhook `refund.updated` acepta `data.refund.refund_id` y `data.refund.id`, maneja `PENDING`, `APPROVED`, `DECLINED`, `ERROR` y `CANCELLED`, y responde 200 cuando el pedido no existe para no provocar reintentos infinitos.
- **S8.** Solo el rol `admin` puede reembolsar; `empleado` recibe 403. Mientras `refund_status = pending`, el panel no permite avanzar el pedido (`en_preparacion`, `enviado`, `entregado`) ni muestra el botón de reembolso.
- **S9.** Un comando programado cada 10 minutos concilia pedidos con `refund_status = pending`: consulta el reembolso por id o reintenta con la misma idempotency key, y aplica el estado final.
- **S10.** La suite completa, Pint y `npm run build` pasan. La llave privada nunca aparece en cuerpos de petición, mensajes de error ni logs.

## Tasks

- [x] **T1** (S1) Config: corregir `.env`, documentar `.env.example`, añadir `merchant_id` y `region` a `config/services.php`. Evidencia: `config('services.wompi.api_url')` = `https://sandbox.wompi.co/v1`, merchant `2082714`, región `CO`.
- [x] **T2** (S3-S6) Migración `add_refund_tracking_to_orders_table` + `Order` fillable/casts. Evidencia: migración aplicada en `tagq_testing` y en la BD local `tagq`.
- [x] **T3** (S2) `WompiRefundResult` + `WompiService::requestRefund`/`fetchRefund`, parser híbrido. Evidencia: `tests/Unit/WompiRefundPayloadTest.php` 8/8 verde (legacy 201, V2 202, 409 idempotente, 422, red, fetch por id, refunds de transacción).
- [x] **T4** (S3-S6) `OrderCancellationService`: preparar/ejecutar reembolso asíncrono, sin HTTP dentro de transacción; `canRefund` y reintentos. Evidencia: `OrderCancellationRefundTest` 36/36 verde, incluido reintento con key nueva y bloqueo de doble intento en curso.
- [x] **T5** (S5-S7) Webhook dual-shape y estados intermedios; 200 para pedido desconocido. Evidencia: tests con `refund.id` y `refund.refund_id`, PENDING/DECLINED/APPROVED, duplicado no duplica stock ni correo, huérfano 200.
- [x] **T6** (S8) Ruta de reembolso solo admin, panel con estado/motivo, bloqueo de transiciones. Evidencia: empleado recibe 403 y no ve el botón; pedido con reembolso en curso no avanza y muestra «En proceso».
- [x] **T7** (S9) Comando `orders:reconcile-refunds` + schedule cada 10 min. Evidencia: tests de aprobación por id, reintento con la misma key y omisión de solicitudes recientes.
- [x] **T8** (S1-S9) Prueba sandbox real. Evidencia: pedido 12 `TAG-HWKRF0OH`, transacción `12082714-1794501891-47393` (APPROVED, sin reembolsos). `refund()` → `PENDING`, refund `32963`, pedido `pagado`, stock 7 intacto. El webhook real llegó: pedido `reembolsado`, `refund_status=approved`, refund `32963` APPROVED en Wompi, stock 8. La conciliación posterior procesó 0 (ya resuelto). Correo `refund_approved` intentado una vez; falló solo por el transporte local de Resend.
- [x] **T9** (S10) Suite completa, Pint, build y documento/Engram al día. Evidencia: `php artisan test` 715/715 (5856 aserciones), `vendor/bin/pint --test` verde, `npm run build` verde.

## Log

- **L1** Usuario: «revisa el sistema de reembolo haber en que estaod esta». Auditoría inicial: contrato legacy síncrono, 0 reembolsos en la BD local.
- **L2** Usuario: «si» al plan de corrección.
- **L3** Usuario: «adelante».
- **L4** Evidencia sandbox: payload legacy → HTTP 201 `{data:{id:32961,status:"PENDING"}}`, APPROVED ~4 s después. Payload V2 completo también devuelve 201 legacy. `GET /v1/transactions/{id}` expone `refunds[]` sin id. Firma de eventos es agnóstica al shape.
- **L5** Decisión: contrato híbrido, sin estado nuevo de pedido, reembolsos solo admin, reembolso total.
- **L6** Implementación T1-T7 completada. Decisión de diseño: `applyRefundStatus` ya no exige `refund_id` para APPROVED porque la conciliación por transacción no lo conoce; la idempotencia se garantiza con el estado del pedido bajo `lockForUpdate`. Un id guardado desde PENDING no bloquea la aprobación; el id que llega con APPROVED es el autoritativo.
- **L7** T8 sandbox real: refund `32963` creado en PENDING, aprobado en ~30 s; webhook real aplicó la transición y restauró stock una sola vez. La conciliación corrió después y no reprocesó nada.
- **L8** T9: suite 715/715, Pint verde, build verde. El correo de reembolso se intentó una vez y falló por el transporte local de Resend (`TransportException`), capturado por el servicio sin afectar el pedido; en producción depende de credenciales válidas de Resend.
