<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceManagementCapability extends Model
{
    protected $fillable = [
        'device_id',
        'capability_key',
        'source',
        'is_supported',
        'is_enabled',
        'details',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_supported' => 'boolean',
            'is_enabled' => 'boolean',
            'details' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
