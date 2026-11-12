<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentRecord extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'email',
        'document',
        'version',
        'document_hash',
        'source',
        'ip_address',
        'user_agent',
        'context',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
