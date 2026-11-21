<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkCredential extends Model
{
    protected $fillable = [
        'site_id',
        'device_id',
        'provider',
        'credential_type',
        'username',
        'secret',
        'endpoint',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'metadata' => 'array',
        ];
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

