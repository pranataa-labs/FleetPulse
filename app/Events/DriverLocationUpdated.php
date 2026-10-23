<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $driverId,
        public string $driverName,
        public string $vehicleType,
        public string $licensePlate,
        public float $lat,
        public float $lng,
        public ?float $heading = null,
        public ?float $speed = null,
        public ?string $trackingToken = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel("tenant.{$this->tenantId}.dispatch"),
        ];

        if ($this->trackingToken) {
            $channels[] = new Channel("order.{$this->trackingToken}");
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'driver.location.updated';
    }

    /**
     * Get the data that should be broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'driver_id' => $this->driverId,
            'driver_name' => $this->driverName,
            'vehicle_type' => $this->vehicleType,
            'license_plate' => $this->licensePlate,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'tracking_token' => $this->trackingToken,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
