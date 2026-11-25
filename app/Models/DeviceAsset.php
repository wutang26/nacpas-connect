<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAsset extends Model
{
    protected $fillable = [
        'asset_tag',
        'product_id',
        'serial_number',
        'mac_address',
        'status',
        'condition',
        'purchase_date',
        'purchase_cost',
        'currency',
        'warranty_expires_at',
        'organization_id',
        'site_id',
        'device_id',
        'sold_at',
        'installed_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'warranty_expires_at' => 'date',
            'sold_at' => 'datetime',
            'installed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
