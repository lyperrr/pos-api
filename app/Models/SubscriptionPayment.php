<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPayment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'plan_code',
        'billing_cycle',
        'amount',
        'status',
        'payment_gateway',
        'payment_channel',
        'gateway_transaction_id',
        'snap_token',
        'payment_url',
        'paid_at',
        'period_starts_at',
        'period_ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
