<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'site_id',
        'customer_id',
        'package_id',
        'reference',
        'package_name',
        'amount',
        'duration_minutes',
        'package_snapshot',
        'access_token_hash',
        'status',
        'starts_at',
        'expires_at',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'duration_minutes' => 'integer',
            'package_snapshot' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function wifiSessions(): HasMany
    {
        return $this->hasMany(WifiSession::class);
    }
}
