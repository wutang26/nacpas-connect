<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkCommand extends Model
{
    protected $fillable = [
        'network_integration_id',
        'site_id',
        'device_id',
        'wifi_session_id',
        'command_uuid',
        'command_type',
        'target_mac',
        'payload',
        'response',
        'status',
        'attempts',
        'last_attempt_at',
        'executed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(NetworkIntegration::class, 'network_integration_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function wifiSession(): BelongsTo
    {
        return $this->belongsTo(WifiSession::class);
    }
}
