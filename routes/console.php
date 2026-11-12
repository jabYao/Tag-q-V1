<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recuperación cuando el webhook de Wompi nunca llega: cierra los pedidos Wompi
// en `pendiente_de_pago` que superaron WOMPI_PENDING_TTL_MINUTES y libera su
// stock. `withoutOverlapping` evita que dos pasadas se solapen si una tarda.
Schedule::command('orders:expire-wompi-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Recuperación de reembolsos Wompi: un reembolso puede quedar `pending` si el
// webhook no llega o la respuesta fue ambigua. La conciliación consulta por id o
// reintenta con la misma idempotency key y aplica el estado final.
Schedule::command('orders:reconcile-refunds')
    ->everyTenMinutes()
    ->withoutOverlapping();
