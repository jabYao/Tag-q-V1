<?php

namespace App\Services;

use App\Models\Order;

/**
 * Transiciones de estado del pedido para el panel administrativo.
 *
 * Centraliza el flujo operativo permitido y concentra en un solo lugar la
 * decisión de qué avance es válido. En esta parte solo se permite el flujo
 * operativo:
 *
 *   pendiente_de_pago → pagado → en_preparacion → enviado → entregado
 *
 * Cada estado admite únicamente su sucesor inmediato: no hay retrocesos ni
 * saltos. Las transiciones de cancelación, reembolso y restock corresponden a
 * la parte 4 y no se implementan aquí.
 *
 * Wompi sigue siendo la fuente autoritativa de los pagos en línea a través de
 * su webhook; el panel solo puede avanzar el flujo operativo, incluida la
 * confirmación de contraentrega de `pendiente_de_pago` a `pagado`.
 */
class OrderStatusService
{
    /** Todos los estados válidos del pedido (coinciden con el enum de la tabla). */
    public const STATUSES = [
        'pendiente_de_pago',
        'pagado',
        'en_preparacion',
        'enviado',
        'entregado',
        'cancelado',
        'reembolsado',
    ];

    /** Estado inicial del flujo operativo. */
    public const STATUS_PENDING = 'pendiente_de_pago';

    /**
     * Flujo operativo permitido: cada estado apunta a su único sucesor.
     *
     * Los estados terminales y los resueltos por fuera del panel (`cancelado`,
     * `reembolsado`) no tienen salida en esta parte.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'pendiente_de_pago' => ['pagado'],
        'pagado' => ['en_preparacion'],
        'en_preparacion' => ['enviado'],
        'enviado' => ['entregado'],
        'entregado' => [],
        'cancelado' => [],
        'reembolsado' => [],
    ];

    /**
     * ¿Es válido avanzar del estado `$from` al estado `$to`?
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Estados alcanzables desde `$from` en el flujo operativo.
     *
     * @return list<string>
     */
    public function availableTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    /**
     * Aplica la transición al pedido o falla con un mensaje en español.
     *
     * @throws \RuntimeException si la transición no pertenece al flujo operativo
     */
    public function transition(Order $order, string $to): void
    {
        $from = (string) $order->status;

        if (! $this->canTransition($from, $to)) {
            throw new \RuntimeException(
                "Transición de estado inválida: no se puede pasar de «{$from}» a «{$to}»."
            );
        }

        $order->status = $to;
        $order->save();
    }
}
