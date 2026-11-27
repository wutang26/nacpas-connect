<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voucher extends Model
{
    protected $hidden = [
        'code_hash',
        'encrypted_code',
    ];

    protected $fillable = [
        'voucher_batch_id',
        'site_id',
        'package_id',
        'voucher_agent_purchase_id',
        'voucher_agent_id',
        'code_hash',
        'encrypted_code',
        'code_suffix',
        'retail_price',
        'currency',
        'status',
        'assigned_at',
        'expires_at',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'retail_price' => 'decimal:2',
            'assigned_at' => 'datetime',
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function agentPurchase(): BelongsTo
    {
        return $this->belongsTo(
            VoucherAgentPurchase::class,
            'voucher_agent_purchase_id'
        );
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(VoucherAgent::class, 'voucher_agent_id');
    }

    public function redemption(): HasOne
    {
        return $this->hasOne(VoucherRedemption::class);
    }
}
