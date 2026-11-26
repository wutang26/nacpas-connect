<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceManagementCommand extends Model
{
    protected $fillable = [
        'organization_id',
        'site_id',
        'device_id',
        'connection_id',
        'requested_by',
        'command_uuid',
        'command_type',
        'payload',
        'result',
        'status',
        'attempts',
        'scheduled_at',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'attempts' => 'integer',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function connection(): BelongsTo
    {
        return $this->belongsTo(
            DeviceManagementConnection::class,
            'connection_id'
        );
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
