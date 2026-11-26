<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Site extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by',
        'name',
        'code',
        'slug',
        'description',
        'address',
        'region',
        'district',
        'latitude',
        'longitude',
        'timezone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user')
            ->withPivot(['role', 'is_default', 'status'])
            ->withTimestamps();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function networkIntegrations(): HasMany
{
    return $this->hasMany(NetworkIntegration::class);
}

public function networkCommands(): HasMany
{
    return $this->hasMany(NetworkCommand::class);
}

public function wifiNetworks(): HasMany
{
    return $this->hasMany(WifiNetwork::class);
}

public function qrCodes(): HasMany
{
    return $this->hasMany(QrCode::class);
}

public function deviceAssets(): HasMany
{
    return $this->hasMany(DeviceAsset::class);
}

    public function deviceManagementConnections(): HasMany
    {
        return $this->hasMany(DeviceManagementConnection::class);
    }

    public function deviceManagementCommands(): HasMany
    {
        return $this->hasMany(DeviceManagementCommand::class);
    }

    public function deviceManagementLogs(): HasMany
    {
        return $this->hasMany(DeviceManagementLog::class);
    }

    public function deviceStatusSnapshots(): HasMany
    {
        return $this->hasMany(DeviceStatusSnapshot::class);
    }

}