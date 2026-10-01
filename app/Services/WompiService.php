<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Integración Wompi Sandbox con Web Checkout.
 *
 * Construye el formulario de Web Checkout (public key, referencia, monto en
 * centavos, moneda, firma de integridad y redirect URL) y valida/aplica los
 * eventos que Wompi envía por webhook: firma del evento, resolución del pedido y
 * coincidencia exacta de monto antes de mover estado o stock.
 *
 *   - `transaction.updated`: el pedido se resuelve por su referencia (código
 *     TAG-) y se exige monto (`total * 100`) y moneda.
 *   - `refund.updated`: el pedido se resuelve por
 *     `data.refund.transaction_id` contra `orders.wompi_transaction_id` y solo
 *     se exige el monto (el evento de reembolso no trae moneda). Un reembolso
 *     APPROVED marca el pedido pagado como `reembolsado`, guarda
 *     `wompi_refund_id` y restaura el stock una sola vez; DECLINED/ERROR/
 *     CANCELLED y los duplicados dejan el pedido pagado y el stock intacto. El
 *     webhook nunca llama a la API de Wompi: el reembolso ya ocurrió y el evento
 *     solo lo confirma.
 *
 * También centraliza la cancelación segura de un pedido pendiente
 * (`cancelPendingOrder`), que restaura el stock una sola vez bajo
 * `lockForUpdate`: la usa el webhook ante un rechazo y el cierre de pedidos
 * vencidos (`orders:expire-wompi-pending`) cuando el webhook nunca llega.
 *
 * Nunca expone ni registra `WOMPI_PRIVATE_KEY` ni `WOMPI_EVENTS_SECRET`: solo
 * viajan al navegador la public key, la firma derivada y los datos públicos del
 * pedido. El monto viaja en centavos (`total COP * 100`).
 *
 * Referencias oficiales:
 * - Widget Checkout (integrado): se instancia en el navegador con
 *   `new WidgetCheckout({ publicKey, currency, amountInCents, reference,
 *   signature: { integrity }, redirectUrl }).open(callback)` y el script oficial
 *   se carga desde https://checkout.wompi.co/widget.js.
 * - Web Checkout clásico (respaldo manual): se envía un GET a
 *   https://checkout.wompi.co/p/ con public-key, currency, amount-in-cents,
 *   reference, signature:integrity y redirect-url.
 * - Firma de integridad: sha256(reference + amount_in_cents + currency + integrity_secret).
 * - Firma de eventos: sha256(concat(signature.properties) + timestamp + events_secret).
 */
class WompiService
{
    /** URL del Web Checkout de Wompi (misma para sandbox y producción). */
    public const WEB_CHECKOUT_URL = 'https://checkout.wompi.co/p/';

    /** Wompi recibe montos en centavos; el sitio maneja COP enteros sin decimales. */
    public const CENTS_FACTOR = 100;

    public const CURRENCY = 'COP';

    /** Estados de transacción que aprueban el pago. */
    public const STATUS_APPROVED = 'APPROVED';

    /** Estados de transacción que cancelan el pedido y liberan el stock. */
    public const STATUS_CANCELLED = ['DECLINED', 'ERROR', 'VOIDED', 'EXPIRED'];

    /** Nombre oficial del evento de transacción. */
    public const EVENT_TRANSACTION_UPDATED = 'transaction.updated';

    /** Nombre oficial del evento de reembolso. */
    public const EVENT_REFUND_UPDATED = 'refund.updated';

    /**
     * Estado de reembolso que aprueba: solo este mueve el pedido. El resto
     * (DECLINED/ERROR/CANCELLED y cualquier valor desconocido) lo deja pagado.
     */
    public const REFUND_STATUS_APPROVED = 'APPROVED';

    /** Estados de pedido usados en la transición del webhook. */
    private const ORDER_PENDING = 'pendiente_de_pago';

    private const ORDER_PAID = 'pagado';

    private const ORDER_CANCELLED = 'cancelado';

    private const ORDER_REFUNDED = 'reembolsado';

    /** Minutos de espera por defecto antes de cerrar un pedido Wompi sin pago. */
    public const PENDING_TTL_DEFAULT_MINUTES = 30;

    /** Clave de configuración de la TTL del cierre automático. */
    private const PENDING_TTL_CONFIG = 'services.wompi.pending_ttl_minutes';

    /**
     * Id de la transacción del último evento cuya firma y monto se validaron.
     *
     * El controlador del webhook (`WompiWebhookController`) invoca
     * `verifyEventAmount` inmediatamente antes de `applyTransactionStatus`, así
     * que el servicio recuerda el id aquí para persistirlo en el pedido sin
     * cambiar la firma pública que ya consume ese controlador. Una respuesta
     * distinta de 200 no llega a `applyTransactionStatus`, por lo que el id
     * recordado solo se guarda cuando el evento ya pasó todas las validaciones.
     */
    private ?string $verifiedEventTransactionId = null;

    /*
    |--------------------------------------------------------------------------
    | Configuración
    |--------------------------------------------------------------------------
    */

    public function isCheckoutConfigured(): bool
    {
        return $this->publicKey() !== '' && $this->integritySecret() !== '';
    }

    public function isEventConfigured(): bool
    {
        return $this->eventsSecret() !== '';
    }

    /**
     * @throws \RuntimeException si falta la public key o el secreto de integridad
     */
    public function assertCheckoutConfigured(): void
    {
        if (! $this->isCheckoutConfigured()) {
            throw new \RuntimeException('El pago con Wompi no está disponible en este momento.');
        }
    }

    /**
     * @throws \RuntimeException si falta el secreto de eventos
     */
    public function assertEventConfigured(): void
    {
        if (! $this->isEventConfigured()) {
            throw new \RuntimeException('La confirmación de pagos de Wompi no está disponible en este momento.');
        }
    }

    /**
     * URL del Web Checkout. Configurable por `services.wompi.checkout_url` y con
     * la URL oficial como valor por defecto.
     */
    public function checkoutUrl(): string
    {
        $configured = config('services.wompi.checkout_url');

        return is_string($configured) && $configured !== '' ? $configured : self::WEB_CHECKOUT_URL;
    }

    /**
     * URL de retorno tras pagar, o null si no hay una URL https válida.
     *
     * Wompi exige una URL de retorno absoluta https. Se respeta
     * `services.wompi.redirect_url` solo cuando es una cadena no vacía que
     * empieza por `https://`; en caso contrario se deriva de APP_URL
     * (`config('app.url')`) solo si también es https, concatenando `/checkout`.
     * Para entornos locales (http o URL relativa) devuelve null y el llamador
     * omite el parámetro: es mejor no enviarlo que enviar una URL inválida.
     */
    public function redirectUrl(): ?string
    {
        $configured = config('services.wompi.redirect_url');

        if (is_string($configured) && str_starts_with($configured, 'https://')) {
            return $configured;
        }

        $appUrl = config('app.url');

        if (is_string($appUrl) && str_starts_with($appUrl, 'https://')) {
            return rtrim($appUrl, '/').'/checkout';
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Web Checkout
    |--------------------------------------------------------------------------
    */

    /**
     * Firma de integridad SHA-256 del Web Checkout.
     *
     * @throws \RuntimeException si Wompi no está configurado
     */
    public function integritySignature(string $reference, int $amountInCents, ?string $currency = null): string
    {
        $this->assertCheckoutConfigured();

        return hash('sha256', $reference.$amountInCents.($currency ?? self::CURRENCY).$this->integritySecret());
    }

    /**
     * Parámetros del formulario de Web Checkout clásico, listos para enviar al
     * navegador. Se conserva como respaldo manual del widget.
     *
     * Solo incluye `redirect-url` cuando hay una URL de retorno https válida
     * (ver `redirectUrl()`); en entornos locales el parámetro se omite.
     *
     * No incluye private key, integrity secret ni events secret.
     *
     * @return array<string, string|int>
     *
     * @throws \RuntimeException si Wompi no está configurado
     */
    public function buildCheckoutPayload(Order $order): array
    {
        $this->assertCheckoutConfigured();

        $reference = (string) $order->code;
        $amountInCents = $this->amountInCents($order);

        $payload = [
            'public-key' => $this->publicKey(),
            'currency' => self::CURRENCY,
            'amount-in-cents' => $amountInCents,
            'reference' => $reference,
            'signature:integrity' => $this->integritySignature($reference, $amountInCents),
        ];

        $redirectUrl = $this->redirectUrl();

        if ($redirectUrl !== null) {
            $payload['redirect-url'] = $redirectUrl;
        }

        return $payload;
    }

    /**
     * Configuración pública del Widget Checkout de Wompi para el pedido.
     *
     * Devuelve exclusivamente lo que viaja al navegador, con las claves camelCase
     * que espera `new WidgetCheckout(config)`: `publicKey`, `currency`,
     * `amountInCents`, `reference`, `signature.integrity` y, cuando existe,
     * `redirectUrl`.
     *
     * Nunca incluye `private_key`, `integrity_secret` ni `events_secret`.
     *
     * `redirectUrl` solo aparece cuando hay una URL de retorno https válida
     * (ver `redirectUrl()`); en entornos locales la clave se omite.
     *
     * @return array{publicKey: string, currency: string, amountInCents: int, reference: string, signature: array{integrity: string}, redirectUrl?: string}
     *
     * @throws \RuntimeException si Wompi no está configurado
     */
    public function buildWidgetPayload(Order $order): array
    {
        $this->assertCheckoutConfigured();

        $reference = (string) $order->code;
        $amountInCents = $this->amountInCents($order);

        $payload = [
            'publicKey' => $this->publicKey(),
            'currency' => self::CURRENCY,
            'amountInCents' => $amountInCents,
            'reference' => $reference,
            'signature' => [
                'integrity' => $this->integritySignature($reference, $amountInCents),
            ],
        ];

        $redirectUrl = $this->redirectUrl();

        if ($redirectUrl !== null) {
            $payload['redirectUrl'] = $redirectUrl;
        }

        return $payload;
    }

    /**
     * URL del Web Checkout clásico con los parámetros públicos del pedido.
     *
     * Es el respaldo manual cuando el widget no puede cargarse: nunca se navega
     * a ella automáticamente, solo la abre el usuario desde "Abrir Wompi".
     *
     * @throws \RuntimeException si Wompi no está configurado
     */
    public function buildWidgetFallbackUrl(Order $order): string
    {
        return $this->checkoutUrl().'?'.http_build_query($this->buildCheckoutPayload($order));
    }

    /**
     * Monto del pedido en centavos (COP * 100).
     */
    public function amountInCents(Order $order): int
    {
        return ((int) $order->total) * self::CENTS_FACTOR;
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos (webhook)
    |--------------------------------------------------------------------------
    */

    /**
     * Valida el checksum del evento con el secreto de eventos.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyEventSignature(array $payload): bool
    {
        if (! $this->isEventConfigured()) {
            return false;
        }

        $signature = $payload['signature'] ?? null;
        $properties = is_array($signature) ? ($signature['properties'] ?? null) : null;
        $checksum = is_array($signature) ? ($signature['checksum'] ?? null) : null;
        $timestamp = $payload['timestamp'] ?? null;
        $data = $payload['data'] ?? null;

        if (! is_array($properties) || $properties === [] || ! is_string($checksum) || $checksum === '') {
            return false;
        }

        if (! is_numeric($timestamp) || ! is_array($data)) {
            return false;
        }

        $concatenated = '';

        foreach ($properties as $path) {
            if (! is_string($path) || $path === '') {
                return false;
            }

            $concatenated .= $this->valueAtPath($data, $path);
        }

        $expected = hash('sha256', $concatenated.((string) $timestamp).$this->eventsSecret());

        return hash_equals($expected, strtolower($checksum));
    }

    /**
     * Nombre del evento (`event`), o null si falta.
     *
     * @param  array<string, mixed>  $payload
     */
    public function eventType(array $payload): ?string
    {
        $event = $payload['event'] ?? null;

        return is_string($event) && $event !== '' ? $event : null;
    }

    /**
     * Referencia (código de pedido) del evento, o null si falta.
     *
     * @param  array<string, mixed>  $payload
     */
    public function eventReference(array $payload): ?string
    {
        $reference = $payload['data']['transaction']['reference'] ?? null;

        return is_string($reference) && $reference !== '' ? $reference : null;
    }

    /**
     * Estado de la transacción del evento en mayúsculas, o null si falta.
     *
     * @param  array<string, mixed>  $payload
     */
    public function eventStatus(array $payload): ?string
    {
        $status = $payload['data']['transaction']['status'] ?? null;

        return is_string($status) && $status !== '' ? strtoupper($status) : null;
    }

    /**
     * Id de la transacción del evento (`data.transaction.id`), o null si falta.
     *
     * @param  array<string, mixed>  $payload
     */
    public function eventTransactionId(array $payload): ?string
    {
        $id = $payload['data']['transaction']['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Verifica que la transacción declare exactamente el monto y la moneda del
     * pedido: `data.transaction.amount_in_cents === order.total * 100` y
     * `data.transaction.currency === 'COP'`.
     *
     * La firma del evento solo prueba que Wompi emitió el payload; no prueba que
     * el monto cobrado corresponda a este pedido. Sin esta comparación, un
     * evento firmado con un monto manipulado aprobaría un pedido pagando menos.
     * Si el monto o la moneda faltan, no son válidos o no coinciden, devuelve
     * false y el llamador no debe tocar el estado del pedido ni el stock.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyEventAmount(Order $order, array $payload): bool
    {
        $transaction = $payload['data']['transaction'] ?? null;

        if (! is_array($transaction)) {
            return false;
        }

        $amountInCents = $this->declaredAmountInCents($transaction);
        $currency = $transaction['currency'] ?? null;

        if ($amountInCents === null || ! is_string($currency)) {
            return false;
        }

        if ($amountInCents !== $this->amountInCents($order) || $currency !== self::CURRENCY) {
            return false;
        }

        // El evento ya pasó la validación de monto y moneda: se recuerda su id
        // para que applyTransactionStatus lo persista en el pedido.
        $this->verifiedEventTransactionId = $this->eventTransactionId($payload);

        return true;
    }

    /**
     * Aplica el estado de la transacción al pedido de forma idempotente.
     *
     * Guarda `data.transaction.id` en `wompi_transaction_id` cuando el evento lo
     * trae (aprobar, rechazar o cualquier estado final): es la referencia que
     * necesita un reembolso posterior. El id ya guardado no se sobrescribe, así
     * un evento duplicado o tardío no reemplaza la transacción original.
     *
     * APPROVED pasa de pendiente_de_pago a pagado (el stock ya estaba reservado).
     * DECLINED/ERROR/VOIDED/EXPIRED pasa de pendiente_de_pago a cancelado y
     * restaura el stock una sola vez. Cualquier otro estado o transición desde
     * un estado ya resuelto se ignora, así los eventos duplicados o fuera de
     * orden no vuelven a descontar ni restaurar stock.
     *
     * El tercer parámetro permite pasar el id de la transacción de forma
     * explícita; si se omite se usa el último recordado por `verifyEventAmount`.
     *
     * @return string 'approved' | 'cancelled' | 'ignored' | 'not_found'
     */
    public function applyTransactionStatus(Order $order, ?string $status, ?string $transactionId = null): string
    {
        $status = strtoupper((string) $status);
        $transactionId ??= $this->verifiedEventTransactionId;

        return DB::transaction(function () use ($order, $status, $transactionId): string {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return 'not_found';
            }

            $this->rememberTransactionId($locked, $transactionId);

            if ($status === self::STATUS_APPROVED) {
                if ($locked->status === self::ORDER_PENDING) {
                    $locked->status = self::ORDER_PAID;
                    $locked->save();

                    return 'approved';
                }

                return 'ignored';
            }

            if (in_array($status, self::STATUS_CANCELLED, true)) {
                return $this->cancelLockedOrder($locked);
            }

            return 'ignored';
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reembolsos (refund.updated)
    |--------------------------------------------------------------------------
    */

    /**
     * ¿El evento describe un reembolso?
     *
     * El nombre oficial es `refund.updated`. Además se tolera cualquier evento
     * que traiga `data.refund` y no traiga `data.transaction`: un
     * `transaction.updated` nunca se confunde con un reembolso porque siempre
     * incluye `data.transaction`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function isRefundEvent(array $payload): bool
    {
        if ($this->eventType($payload) === self::EVENT_REFUND_UPDATED) {
            return true;
        }

        return ! is_array($payload['data']['transaction'] ?? null)
            && is_array($payload['data']['refund'] ?? null);
    }

    /**
     * `data.refund.transaction_id` del evento, o null si falta.
     *
     * Es el id con el que se resuelve el pedido: se compara contra
     * `orders.wompi_transaction_id`, no contra la referencia `TAG-`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function refundTransactionId(array $payload): ?string
    {
        return $this->stringValue($payload['data']['refund']['transaction_id'] ?? null);
    }

    /**
     * `data.refund.id` del evento, o null si falta.
     *
     * @param  array<string, mixed>  $payload
     */
    public function refundId(array $payload): ?string
    {
        return $this->stringValue($payload['data']['refund']['id'] ?? null);
    }

    /**
     * `data.refund.status` en mayúsculas, o null si falta.
     *
     * @param  array<string, mixed>  $payload
     */
    public function refundStatus(array $payload): ?string
    {
        $status = $this->stringValue($payload['data']['refund']['status'] ?? null);

        return $status === null ? null : strtoupper($status);
    }

    /**
     * Verifica que el reembolso declare exactamente el monto del pedido:
     * `data.refund.amount_in_cents === order.total * 100`.
     *
     * El evento de reembolso no trae moneda, así que no se exige. La firma del
     * evento solo prueba que Wompi emitió el payload; sin esta comparación un
     * reembolso firmado por un monto distinto al del pedido pasaría. Si el monto
     * falta o no coincide, devuelve false y el llamador no debe tocar el estado
     * del pedido ni el stock.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyRefundAmount(Order $order, array $payload): bool
    {
        $refund = $payload['data']['refund'] ?? null;

        if (! is_array($refund)) {
            return false;
        }

        $amountInCents = $this->declaredAmountInCents($refund);

        return $amountInCents !== null && $amountInCents === $this->amountInCents($order);
    }

    /**
     * Aplica el estado del reembolso al pedido de forma idempotente.
     *
     * Solo APPROVED mueve el pedido: un pedido pagado pasa a `reembolsado`, se
     * guarda `wompi_refund_id` y se restaura el stock una sola vez bajo
     * `lockForUpdate`. No se llama a la API de Wompi: el reembolso ya ocurrió y
     * el evento solo lo confirma.
     *
     * DECLINED/ERROR/CANCELLED y cualquier otro estado se ignoran sin mutar: el
     * pedido sigue pagado y el stock intacto. Un evento duplicado o fuera de
     * orden de un pedido ya reembolsado, cancelado o aún pendiente también se
     * ignora, así que el stock nunca se restaura dos veces ni se sobrescribe el
     * id del primer reembolso.
     *
     * @return string 'refunded' | 'ignored' | 'not_found'
     */
    public function applyRefundStatus(Order $order, ?string $status, ?string $refundId = null): string
    {
        $status = strtoupper((string) $status);
        $refundId = is_string($refundId) && $refundId !== '' ? $refundId : null;

        if ($status !== self::REFUND_STATUS_APPROVED || $refundId === null) {
            return 'ignored';
        }

        return DB::transaction(function () use ($order, $refundId): string {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return 'not_found';
            }

            // Solo un pedido pagado se reembolsa: ya reembolsado, cancelado o
            // aún pendiente no restaura stock.
            if ($locked->status !== self::ORDER_PAID) {
                return 'ignored';
            }

            // Ya hay un reembolso registrado: un evento duplicado (mismo id) o
            // uno distinto no lo sobrescribe ni vuelve a restaurar stock.
            if (is_string($locked->wompi_refund_id) && $locked->wompi_refund_id !== '') {
                return 'ignored';
            }

            $locked->wompi_refund_id = $refundId;
            $locked->status = self::ORDER_REFUNDED;
            $locked->save();

            $this->restoreStock($locked);

            return 'refunded';
        });
    }

    /**
     * Guarda el id de la transacción en el pedido bloqueado, sin sobrescribir el
     * que ya estaba registrado.
     */
    private function rememberTransactionId(Order $locked, ?string $transactionId): void
    {
        if ($transactionId === null || $transactionId === '') {
            return;
        }

        if ($locked->wompi_transaction_id === $transactionId) {
            return;
        }

        if (is_string($locked->wompi_transaction_id) && $locked->wompi_transaction_id !== '') {
            return;
        }

        $locked->wompi_transaction_id = $transactionId;
        $locked->save();
    }

    /**
     * Cancela un pedido pendiente y devuelve su stock al inventario una sola vez.
     *
     * Es la misma operación segura que usa el webhook cuando Wompi rechaza el
     * pago (DECLINED/ERROR/VOIDED/EXPIRED), pero invocable sin evento: la usa el
     * cierre de pedidos vencidos cuando el webhook nunca llega. Toma el bloqueo
     * de escritura del pedido dentro de una transacción, así dos llamadores
     * concurrentes (webhook y comando) no restauran el stock dos veces: el
     * segundo ve el estado ya resuelto y no hace nada.
     *
     * @return string 'cancelled' | 'ignored' | 'not_found'
     */
    public function cancelPendingOrder(Order $order): string
    {
        return DB::transaction(function () use ($order): string {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return 'not_found';
            }

            return $this->cancelLockedOrder($locked);
        });
    }

    /**
     * Cancela un pedido que ya tiene el bloqueo de escritura tomado.
     *
     * Solo un pedido en `pendiente_de_pago` se cancela y restaura stock; un
     * pedido ya cancelado, pagado o en cualquier otro estado se ignora. Los
     * items sin producto asociado se saltan sin romper el lote.
     *
     * @return string 'cancelled' | 'ignored'
     */
    private function cancelLockedOrder(Order $locked): string
    {
        if ($locked->status !== self::ORDER_PENDING) {
            return 'ignored';
        }

        $this->restoreStock($locked);

        $locked->status = self::ORDER_CANCELLED;
        $locked->save();

        return 'cancelled';
    }

    /**
     * Devuelve al inventario el stock de cada item del pedido bloqueado, una sola
     * vez. Los items sin producto asociado se saltan sin romper el lote.
     */
    private function restoreStock(Order $locked): void
    {
        foreach ($locked->items()->get() as $item) {
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

            if ($product) {
                $product->increment('stock', (int) $item->quantity);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cierre de pedidos pendientes
    |--------------------------------------------------------------------------
    */

    /**
     * Minutos que se espera el pago de un pedido Wompi antes de cerrarlo.
     *
     * Se configura con `services.wompi.pending_ttl_minutes`
     * (WOMPI_PENDING_TTL_MINUTES). Un valor no numérico o menor que 1 se trata
     * como ausente y cae al default, para que una variable de entorno mal escrita
     * no cierre pedidos recién creados.
     */
    public function pendingTtlMinutes(): int
    {
        $configured = config(self::PENDING_TTL_CONFIG);
        $minutes = is_numeric($configured) ? (int) $configured : 0;

        return $minutes >= 1 ? $minutes : self::PENDING_TTL_DEFAULT_MINUTES;
    }

    /**
     * Cierra los pedidos Wompi que llevan más de la TTL en `pendiente_de_pago`.
     *
     * Solo considera `payment_method = wompi` con `status = pendiente_de_pago` y
     * `created_at` igual o anterior al umbral: la contraentrega y cualquier pedido
     * ya pagado, cancelado o en un estado posterior quedan fuera. Cada pedido se
     * cancela con `cancelPendingOrder`, que bloquea la fila y restaura el stock
     * una sola vez, así que repetir la ejecución no duplica stock. Los pedidos sin
     * items (o con items cuyo producto ya no existe) se cierran sin romper el lote.
     *
     * @return int número de pedidos cerrados en esta pasada
     */
    public function expirePendingOrders(): int
    {
        $threshold = now()->subMinutes($this->pendingTtlMinutes());
        $expired = 0;

        Order::query()
            ->where('payment_method', CheckoutService::WOMPI_PAYMENT_METHOD)
            ->where('status', self::ORDER_PENDING)
            ->where('created_at', '<=', $threshold)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$expired): void {
                foreach ($orders as $order) {
                    if ($this->cancelPendingOrder($order) === 'cancelled') {
                        $expired++;
                    }
                }
            });

        return $expired;
    }

    /**
     * Monto en centavos declarado por un evento (transacción o reembolso), o null
     * si falta o no es un entero (los montos manipulados o de tipo inesperado se
     * rechazan).
     *
     * @param  array<string, mixed>  $event
     */
    private function declaredAmountInCents(array $event): ?int
    {
        $amount = $event['amount_in_cents'] ?? null;

        if (is_int($amount)) {
            return $amount;
        }

        if (is_string($amount) && ctype_digit($amount)) {
            return (int) $amount;
        }

        return null;
    }

    /**
     * Normaliza un valor escalar del payload (string o entero) a string no
     * vacío, o null si falta o no es utilizable.
     */
    private function stringValue(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Resuelve un path con puntos ("transaction.status") dentro del payload.
     */
    private function valueAtPath(array $data, string $path): string
    {
        $current = $data;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return '';
            }

            $current = $current[$segment];
        }

        if (is_bool($current)) {
            return $current ? 'true' : 'false';
        }

        if (is_int($current) || is_float($current)) {
            return (string) $current;
        }

        return is_string($current) ? $current : '';
    }

    private function publicKey(): string
    {
        return (string) (config('services.wompi.public_key') ?? '');
    }

    private function integritySecret(): string
    {
        return (string) (config('services.wompi.integrity_secret') ?? '');
    }

    private function eventsSecret(): string
    {
        return (string) (config('services.wompi.events_secret') ?? '');
    }
}
