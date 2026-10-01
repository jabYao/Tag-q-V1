<?php

namespace App\Services;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notificaciones por correo de un pedido.
 *
 * Resuelve el destinatario en este orden: el override transaccional configurado
 * (`services.transactional_mail.override`) cuando es un correo válido, el email
 * del usuario registrado y, por último, el email de `order.shipping_address`.
 * Así un cliente registrado prioriza su propia cuenta y el invitado cae al
 * correo de envío. Un pedido sin destinatario (invitado sin correo) no envía
 * nada y no falla.
 *
 * El envío nunca puede romper ni revertir el pedido: cualquier fallo del
 * proveedor de correo se captura y se registra con un mensaje genérico, sin
 * correo, dirección, código de pedido ni secretos. Los llamadores solo invocan
 * estos métodos cuando la transición ya se aplicó de forma efectiva, así que un
 * evento duplicado o una transición inválida no reenvían correo.
 */
class OrderNotificationService
{
    /** Se acaba de crear el pedido. */
    public function notifyCreated(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_CREATED);
    }

    /** Wompi confirmó el pago del pedido. */
    public function notifyPaymentApproved(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_PAYMENT_APPROVED);
    }

    /** El pedido pasó a preparación. */
    public function notifyInPreparation(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_IN_PREPARATION);
    }

    /** El pedido quedó cancelado. */
    public function notifyCancelled(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_CANCELLED);
    }

    /** El reembolso quedó aprobado y el pedido pasó a reembolsado. */
    public function notifyRefundApproved(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_REFUND_APPROVED);
    }

    /** El pedido salió a despacho. */
    public function notifyShipped(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_SHIPPED);
    }

    /** El pedido fue entregado. */
    public function notifyDelivered(Order $order): void
    {
        $this->send($order, OrderStatusMail::EVENT_DELIVERED);
    }

    /**
     * Destinatario del pedido, o null si no hay un correo válido.
     *
     * El override transaccional (si está definido y es un email válido) tiene
     * prioridad sobre cualquier destinatario del pedido. Sin override, un pedido
     * de un cliente registrado prioriza `user.email` y cae al email de envío del
     * checkout; un invitado usa el email de envío. Solo devuelve correos válidos
     * para no intentar un envío que el proveedor rechazaría.
     */
    public function recipient(Order $order): ?string
    {
        $candidates = [
            config('services.transactional_mail.override'),
            $order->user?->email,
            $this->shippingEmail($order),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var(trim($candidate), FILTER_VALIDATE_EMAIL)) {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * Envía el correo del evento sin propagar fallos.
     *
     * Un fallo del proveedor se registra con un mensaje genérico y el contexto
     * mínimo (evento y clase de la excepción): nunca se registra el correo, la
     * dirección ni ningún secreto.
     */
    private function send(Order $order, string $event): void
    {
        try {
            $recipient = $this->recipient($order);

            if ($recipient === null) {
                return;
            }

            Mail::to($recipient)->send(new OrderStatusMail($order, $event));
        } catch (\Throwable $exception) {
            Log::error('No se pudo enviar el correo transaccional del pedido.', [
                'event' => $event,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * Email de la dirección de envío del pedido, o null si falta.
     */
    private function shippingEmail(Order $order): mixed
    {
        $shipping = $order->shipping_address;

        return is_array($shipping) ? ($shipping['email'] ?? null) : null;
    }
}
