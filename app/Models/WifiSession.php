<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WifiSession extends Model
{
    protected $fillable = [
        'site_id',
        'subscription_id',
        'customer_id',
        'device_id',
        'client_mac',
        'client_ip',
        'external_session_id',
        'started_at',
        'expires_at',
        'ended_at',
        'bytes_in',
        'bytes_out',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
            'bytes_in' => 'integer',
            'bytes_out' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function networkCommands(): HasMany
{
    return $this->hasMany(NetworkCommand::class);
}
}