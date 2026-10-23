<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'vehicle_type',
        'base_fare',
        'rate_per_km',
        'rate_per_kg',
        'surge_multiplier',
        'is_active',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'rate_per_km' => 'decimal:2',
        'rate_per_kg' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
