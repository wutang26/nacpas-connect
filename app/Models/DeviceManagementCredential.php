<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceManagementCredential extends Model
{
    protected $hidden = ['secret'];

    protected $fillable = [
        'connection_id',
        'device_id',
        'credential_type',
        'label',
        'username',
        'secret',
        'endpoint',
        'metadata',
        'status',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'metadata' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(
            DeviceManagementConnection::class,
            'connection_id'
        );
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
