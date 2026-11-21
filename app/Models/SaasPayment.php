<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasPayment extends Model
{
    protected $fillable = [
        'organization_subscription_id',
        'provider',
        'internal_reference',
        'provider_reference',
        'phone_number',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function organizationSubscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class);
    }
}
