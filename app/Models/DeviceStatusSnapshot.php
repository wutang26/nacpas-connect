<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceStatusSnapshot extends Model
{
    protected $fillable = [
        'organization_id',
        'site_id',
        'device_id',
        'status',
        'ip_address',
        'firmware_version',
        'uptime_seconds',
        'connected_clients',
        'cpu_usage',
        'memory_usage',
        'metrics',
        'observed_at',
    ];

    protected function casts(): array
    {
        return [
            'uptime_seconds' => 'integer',
            'connected_clients' => 'integer',
            'cpu_usage' => 'decimal:2',
            'memory_usage' => 'decimal:2',
            'metrics' => 'array',
            'observed_at' => 'datetime',
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
