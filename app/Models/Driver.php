<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'fleet_id',
        'license_number',
        'is_online',
        'is_on_duty',
        'last_lat',
        'last_lng',
        'last_heading',
        'last_ping_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_on_duty' => 'boolean',
        'last_lat' => 'decimal:7',
        'last_lng' => 'decimal:7',
        'last_heading' => 'decimal:2',
        'last_ping_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrder(): ?Order
    {
        return $this->orders()
            ->whereIn('delivery_status', ['ACCEPTED', 'PICKED_UP', 'IN_TRANSIT'])
            ->latest()
            ->first();
    }
}
