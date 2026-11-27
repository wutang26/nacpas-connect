<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherAgentPurchase extends Model
{
    protected $fillable = [
        'voucher_agent_id',
        'voucher_batch_id',
        'site_id',
        'reference',
        'quantity',
        'wholesale_unit_price',
        'retail_unit_price',
        'total_amount',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'confirmed_by',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'wholesale_unit_price' => 'decimal:2',
            'retail_unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(VoucherAgent::class, 'voucher_agent_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
