<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'site_id',
        'name',
        'vendor',
        'model',
        'device_type',
        'mac_address',
        'ip_address',
        'serial_number',
        'controller_device_id',
        'firmware_version',
        'status',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function wifiSessions(): HasMany
    {
        return $this->hasMany(WifiSession::class);
    }

    public function networkLogs(): HasMany
    {
        return $this->hasMany(NetworkLog::class);
    }

    public function networkCredentials(): HasMany
    {
        return $this->hasMany(NetworkCredential::class);
    }

    public function networkCommands(): HasMany
{
    return $this->hasMany(NetworkCommand::class);
}
}