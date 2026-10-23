<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tracking_token',
        'tenant_id',
        'driver_id',
        'order_number',
        'sender_name',
        'sender_phone',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'recipient_name',
        'recipient_phone',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'vehicle_type',
        'weight_kg',
        'distance_km',
        'total_fee',
        'payment_status',
        'delivery_status',
        'proof_of_delivery_path',
        'proof_signature',
        'notes',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'dropoff_lat' => 'decimal:7',
        'dropoff_lng' => 'decimal:7',
        'weight_kg' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'total_fee' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->tracking_token)) {
                $order->tracking_token = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $order->order_number = 'FP-' . strtoupper(Str::random(8));
            }
        });
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(TrackingCheckpoint::class)->orderBy('recorded_at', 'asc');
    }
}
