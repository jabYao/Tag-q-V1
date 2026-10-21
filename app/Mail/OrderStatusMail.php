<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

/**
 * Correo transaccional reutilizable de un pedido.
 *
 * Un solo Mailable sirve a todos los eventos del pedido: se elige la variante
 * con la constante `EVENT_*` y el mismo asunto/cuerpo se adapta al evento y al
 * estado real del pedido.
 *
 * El cuerpo incluye únicamente lo operativo: código del pedido, estado/evento,
 * total, snapshot de items y, cuando corresponde, el enlace al detalle del
 * pedido. El enlace solo aparece para pedidos asociados a un usuario (invitado
 * no tiene sesión que lo abra) y su URL base sale de `APP_URL` (`config('app.url')`),
 * así que se configura por entorno sin tocar código. Nunca se incluyen secretos,
 * llaves ni datos de pago.
 */
class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Se acaba de crear el pedido (contraentrega o Wompi). */
    public const EVENT_CREATED = 'created';

    /** Wompi confirmó el pago del pedido. */
    public const EVENT_PAYMENT_APPROVED = 'payment_approved';

    /** El pedido pasó a preparación. */
    public const EVENT_IN_PREPARATION = 'in_preparation';

    /** El pedido quedó cancelado. */
    public const EVENT_CANCELLED = 'cancelled';

    /** El reembolso quedó aprobado y el pedido pasó a reembolsado. */
    public const EVENT_REFUND_APPROVED = 'refund_approved';

    /** El pedido salió a despacho. */
    public const EVENT_SHIPPED = 'shipped';

    /** El pedido fue entregado. */
    public const EVENT_DELIVERED = 'delivered';

    /**
     * Texto corto del asunto y del encabezado por evento.
     *
     * @var array<string, string>
     */
    private const EVENT_LABELS = [
        self::EVENT_CREATED => 'Pedido recibido',
        self::EVENT_PAYMENT_APPROVED => 'Pago confirmado',
        self::EVENT_IN_PREPARATION => 'Pedido en preparación',
        self::EVENT_CANCELLED => 'Pedido cancelado',
        self::EVENT_REFUND_APPROVED => 'Reembolso aprobado',
        self::EVENT_SHIPPED => 'Pedido enviado',
        self::EVENT_DELIVERED => 'Pedido entregado',
    ];

    /**
     * Etiquetas en español de los estados del pedido (mismas que el panel).
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        'pendiente_de_pago' => 'Pendiente de pago',
        'pagado' => 'Pagado',
        'en_preparacion' => 'En preparación',
        'enviado' => 'Enviado',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
        'reembolsado' => 'Reembolsado',
    ];

    /**
     * Enlace al detalle del pedido resuelto al construir el correo, o null si no
     * corresponde.
     *
     * Solo un pedido asociado a un usuario tiene una sesión con la que abrir su
     * detalle; un pedido de invitado no enlaza. La URL sale de la ruta con
     * nombre `customer.orders.show` sobre `APP_URL` (`config('app.url')`), por lo
     * que se configura por entorno.
     */
    public readonly ?string $orderUrl;

    public function __construct(
        public readonly Order $order,
        public readonly string $event = self::EVENT_CREATED,
    ) {
        $this->orderUrl = $this->resolveOrderUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->eventLabel().' — Pedido '.$this->order->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status',
            with: [
                'eventLabel' => $this->eventLabel(),
                'orderCode' => (string) $this->order->code,
                'statusLabel' => $this->statusLabel(),
                'total' => (int) $this->order->total,
                'items' => $this->itemsSnapshot(),
                'orderUrl' => $this->orderUrl,
            ],
        );
    }

    /**
     * @see $orderUrl
     */
    private function resolveOrderUrl(): ?string
    {
        if ($this->order->user_id === null) {
            return null;
        }

        if (! Route::has('customer.orders.show')) {
            return null;
        }

        return route('customer.orders.show', $this->order);
    }

    /**
     * Snapshot de items del pedido: nombre, cantidad, precio unitario y subtotal.
     *
     * El precio es el guardado en el item (snapshot del momento de la compra),
     * no el precio actual del producto.
     *
     * @return list<array{name: string, quantity: int, price: int, subtotal: int}>
     */
    private function itemsSnapshot(): array
    {
        return $this->order->items()
            ->with('product')
            ->get()
            ->map(fn (OrderItem $item): array => [
                'name' => $item->product?->name ?? 'Producto eliminado',
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->price,
                'subtotal' => (int) $item->price * (int) $item->quantity,
            ])
            ->all();
    }

    private function eventLabel(): string
    {
        return self::EVENT_LABELS[$this->event] ?? 'Actualización del pedido';
    }

    private function statusLabel(): string
    {
        $status = (string) $this->order->status;

        return self::STATUS_LABELS[$status] ?? $status;
    }
}
