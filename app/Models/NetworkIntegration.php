<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkIntegration extends Model
{
    protected $fillable = [
        'organization_id',
        'site_id',
        'provider',
        'name',
        'controller_url',
        'controller_version',
        'controller_site_id',
        'status',
        'last_connected_at',
        'last_error',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'last_connected_at' => 'datetime',
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

    public function networkCommands(): HasMany
    {
        return $this->hasMany(NetworkCommand::class);
    }
}
