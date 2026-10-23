<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fleet extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'vehicle_type',
        'license_plate',
        'max_weight_kg',
        'max_volume_cbm',
        'status',
    ];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'max_volume_cbm' => 'decimal:2',
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}
