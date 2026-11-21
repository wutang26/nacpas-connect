<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasPlan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'price_monthly',
        'price_annual',
        'currency',
        'max_sites',
        'max_users',
        'max_devices',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organizationSubscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }
}
