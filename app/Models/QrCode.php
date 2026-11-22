<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    protected $fillable = [
        'site_id',
        'wifi_network_id',
        'qr_type',
        'code',
        'label',
        'target_url',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function wifiNetwork(): BelongsTo
    {
        return $this->belongsTo(WifiNetwork::class);
    }
}
