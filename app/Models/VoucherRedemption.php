<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherRedemption extends Model
{
    protected $fillable = [
        'voucher_id',
        'voucher_batch_id',
        'site_id',
        'package_id',
        'voucher_agent_id',
        'customer_id',
        'subscription_id',
        'wifi_session_id',
        'client_mac',
        'client_ip',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(VoucherAgent::class, 'voucher_agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
