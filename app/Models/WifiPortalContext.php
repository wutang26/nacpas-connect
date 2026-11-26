<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WifiPortalContext extends Model
{
    protected $fillable = [
        'context_token',
        'session_hash',
        'site_id',
        'payment_id',
        'subscription_id',
        'wifi_session_id',
        'client_mac',
        'client_ip',
        'ap_mac',
        'ssid_name',
        'radio_id',
        'controller_site_id',
        'redirect_url',
        'omada_timestamp',
        'context_status',
        'authorization_status',
        'authorization_attempts',
        'authorized_at',
        'expires_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'omada_timestamp' => 'integer',
            'authorization_attempts' => 'integer',
            'authorized_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function wifiSession(): BelongsTo
    {
        return $this->belongsTo(WifiSession::class);
    }
}
