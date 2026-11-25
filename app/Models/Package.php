<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'site_id',
        'name',
        'description',
        'price',
        'currency',
        'duration_minutes',
        'download_speed_kbps',
        'upload_speed_kbps',
        'data_limit_bytes',
        'device_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'download_speed_kbps' => 'integer',
            'upload_speed_kbps' => 'integer',
            'data_limit_bytes' => 'integer',
            'device_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
