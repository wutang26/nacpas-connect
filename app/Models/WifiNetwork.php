<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WifiNetwork extends Model
{
    protected $fillable = [
        'site_id',
        'name',
        'ssid',
        'security_type',
        'password',
        'controller_wlan_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }
}
