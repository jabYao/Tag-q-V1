# Memoria del proyecto

## Estado del historial de pedidos

Implementado:

- Historial autenticado en `/mis-pedidos`.
- Detalle en `/mis-pedidos/{order}`.
- Aislamiento por usuario y tests Feature.

Pendientes no bloqueantes:

- Agregar enlace “Mis pedidos” a la navegación pública.
- Crear un layout público compartido.
- Historial o consulta para invitados mediante código y email.
- Enlace al historial después de iniciar sesión.
- Emails con enlace al detalle del pedido.

El historial para invitados requiere otro flujo porque el invitado no tiene una cuenta; podría consultarse mediante código de pedido y correo.

## Parte 3: cierre de pedidos Wompi pendientes

Implementado:

- TTL configurable con `WOMPI_PENDING_TTL_MINUTES` y default de 30 minutos.
- Comando `php artisan orders:expire-wompi-pending`.
- Scheduler cada cinco minutos.
- Cancelación idempotente y restauración segura de stock con `lockForUpdate`.
- Exclusión de pedidos contraentrega, pagados y ya cancelados.
- Tests Feature y suite completa en verde.

Pendientes para producción:

- Configurar en Railway un proceso que ejecute `php artisan schedule:run` cada minuto.
- Agregar monitoreo/logs y alertas de pedidos expirados o fallos del comando.
- Definir qué hacer si Wompi aprueba un pago después de que el pedido expiró: reembolso automático, revisión manual o estado especial.
- Alinear opcionalmente `expirationTime` del Widget con la TTL interna.

## Parte 4: cancelaciones y reembolsos

Implementado:

- Cancelación administrativa para pedidos pendientes y contraentrega pagados.
- Reembolso Wompi V2 mediante `POST /v1/refunds` con llave privada en servidor.
- Validación de respuesta APPROVED antes de marcar `reembolsado`.
- Restauración idempotente del stock.
- Protección contra doble cancelación y doble reembolso.
- Persistencia de `wompi_transaction_id` y `wompi_refund_id`.
- Acciones protegidas para admin y empleado.
- Tests Feature y suite completa en verde.

Pendientes posteriores:

- Emails transaccionales.
- Monitoreo y alertas de reembolsos fallidos.
- Operación del scheduler en Railway.
- Reintentos o revisión manual para pagos/reembolsos en estados ambiguos.
