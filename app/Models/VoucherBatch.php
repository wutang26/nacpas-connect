<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherBatch extends Model
{
    protected $fillable = [
        'organization_id',
        'site_id',
        'package_id',
        'created_by',
        'batch_number',
        'quantity',
        'generated_count',
        'wholesale_unit_price',
        'retail_unit_price',
        'currency',
        'status',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'generated_count' => 'integer',
            'wholesale_unit_price' => 'decimal:2',
            'retail_unit_price' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(VoucherAgentPurchase::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
