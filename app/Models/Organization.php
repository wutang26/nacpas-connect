<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'code',
        'business_email',
        'business_phone',
        'billing_email',
        'address',
        'region',
        'district',
        'timezone',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(OrganizationSubscription::class)
            ->latestOfMany();
    }

    public function networkIntegrations(): HasMany
{
    return $this->hasMany(NetworkIntegration::class);
}
}
