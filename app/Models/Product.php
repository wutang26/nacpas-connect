<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'category',
        'brand',
        'model',
        'description',
        'specifications',
        'default_purchase_price',
        'default_selling_price',
        'currency',
        'unit_of_measure',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_purchase_price' => 'decimal:2',
            'default_selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function deviceAssets(): HasMany
    {
        return $this->hasMany(DeviceAsset::class);
    }
}
