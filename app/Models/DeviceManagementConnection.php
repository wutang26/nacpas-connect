<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceManagementConnection extends Model
{
    protected $fillable = [
        'organization_id',
        'site_id',
        'name',
        'provider',
        'connection_type',
        'management_url',
        'controller_id',
        'region',
        'status',
        'last_connected_at',
        'last_checked_at',
        'last_error',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'last_connected_at' => 'datetime',
            'last_checked_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(DeviceManagementCredential::class, 'connection_id');
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceManagementCommand::class, 'connection_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeviceManagementLog::class, 'connection_id');
    }
}
