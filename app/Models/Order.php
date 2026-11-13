<?php

namespace App\Models;

use App\Services\CheckoutService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'status',
        'total',
        'shipping_cost',
        'advance_confirmed_at',
        'advance_confirmed_by_id',
        'payment_method',
        'shipping_address',
        'wompi_transaction_id',
        'wompi_refund_id',
        'refund_status',
        'refund_requested_at',
        'refund_idempotency_key',
        'refund_error',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'shipping_cost' => 'integer',
            'shipping_address' => 'array',
            'advance_confirmed_at' => 'datetime',
            'refund_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function advanceConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advance_confirmed_by_id');
    }

    /**
     * ¿El pedido exige abono de envío por adelantado?
     *
     * Solo contraentrega: el envío se paga por transferencia antes del
     * despacho. Con envío gratis no hay abono que cobrar.
     */
    public function requiresAdvance(): bool
    {
        return (string) $this->payment_method === CheckoutService::PAYMENT_METHOD
            && (int) $this->shipping_cost > 0;
    }

    /** Monto del abono de envío; es el costo de envío cobrado por adelantado. */
    public function advanceAmount(): int
    {
        return $this->requiresAdvance() ? (int) $this->shipping_cost : 0;
    }

    public function hasConfirmedAdvance(): bool
    {
        return $this->advance_confirmed_at !== null;
    }

    /** Saldo que el cliente paga al recibir: total menos el abono ya cubierto. */
    public function balanceDue(): int
    {
        return max(0, (int) $this->total - $this->advanceAmount());
    }
}
