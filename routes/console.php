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
