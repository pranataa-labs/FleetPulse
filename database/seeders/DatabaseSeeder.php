<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Tenant;
use App\Models\TrackingCheckpoint;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Superadmin (No Tenant)
        User::firstOrCreate(
            ['email' => 'superadmin@fleetpulse.io'],
            [
                'name' => 'FleetPulse Platform Superadmin',
                'password' => Hash::make('password123'),
                'role' => 'SUPERADMIN',
                'phone' => '+628110000001',
                'is_active' => true,
            ]
        );

        // 2. Tenant 1: SwiftExpress
        $swift = Tenant::firstOrCreate(
            ['slug' => 'swiftexpress'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'SwiftExpress Courier & Logistics',
                'domain' => 'swiftexpress.fleetpulse.test',
                'plan_tier' => 'enterprise',
                'subscription_status' => 'active',
                'settings' => [
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'support_email' => 'support@swiftexpress.com',
                ],
            ]
        );

        // 3. Tenant 2: MetroCargo
        $metro = Tenant::firstOrCreate(
            ['slug' => 'metrocargo'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'MetroCargo Freight Systems',
                'domain' => 'metrocargo.fleetpulse.test',
                'plan_tier' => 'standard',
                'subscription_status' => 'active',
                'settings' => [
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'support_email' => 'ops@metrocargo.com',
                ],
            ]
        );

        // SwiftExpress Admin & Staff
        User::firstOrCreate(
            ['email' => 'admin@swiftexpress.com'],
            [
                'tenant_id' => $swift->id,
                'name' => 'SwiftExpress Admin',
                'password' => Hash::make('password123'),
                'role' => 'TENANT_ADMIN',
                'phone' => '+628120000001',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'dispatcher@swiftexpress.com'],
            [
                'tenant_id' => $swift->id,
                'name' => 'SwiftExpress Dispatcher',
                'password' => Hash::make('password123'),
                'role' => 'DISPATCHER',
                'phone' => '+628120000002',
                'is_active' => true,
            ]
        );

        // MetroCargo Admin
        User::firstOrCreate(
            ['email' => 'admin@metrocargo.com'],
            [
                'tenant_id' => $metro->id,
                'name' => 'MetroCargo Admin',
                'password' => Hash::make('password123'),
                'role' => 'TENANT_ADMIN',
                'phone' => '+628130000001',
                'is_active' => true,
            ]
        );

        // Fleets for SwiftExpress
        $fleetMotor = Fleet::firstOrCreate(
            ['tenant_id' => $swift->id, 'license_plate' => 'B 3012 SFE'],
            [
                'vehicle_type' => 'MOTORCYCLE',
                'max_weight_kg' => 30.00,
                'max_volume_cbm' => 0.50,
                'status' => 'ACTIVE',
            ]
        );

        $fleetVan = Fleet::firstOrCreate(
            ['tenant_id' => $swift->id, 'license_plate' => 'B 7890 SFE'],
            [
                'vehicle_type' => 'VAN',
                'max_weight_kg' => 800.00,
                'max_volume_cbm' => 5.00,
                'status' => 'ACTIVE',
            ]
        );

        // Drivers for SwiftExpress
        $driverUser1 = User::firstOrCreate(
            ['email' => 'budi.driver@swiftexpress.com'],
            [
                'tenant_id' => $swift->id,
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'DRIVER',
                'phone' => '+628151122334',
                'is_active' => true,
            ]
        );

        $driver1 = Driver::firstOrCreate(
            ['tenant_id' => $swift->id, 'user_id' => $driverUser1->id],
            [
                'fleet_id' => $fleetMotor->id,
                'license_number' => 'SIM-C-987654321',
                'is_online' => true,
                'is_on_duty' => true,
                'last_lat' => -6.1753924,
                'last_lng' => 106.8271528, // Monas, Jakarta Pusat
                'last_heading' => 45.0,
                'last_ping_at' => now(),
            ]
        );

        $driverUser2 = User::firstOrCreate(
            ['email' => 'agus.driver@swiftexpress.com'],
            [
                'tenant_id' => $swift->id,
                'name' => 'Agus Pratama',
                'password' => Hash::make('password123'),
                'role' => 'DRIVER',
                'phone' => '+628159988776',
                'is_active' => true,
            ]
        );

        $driver2 = Driver::firstOrCreate(
            ['tenant_id' => $swift->id, 'user_id' => $driverUser2->id],
            [
                'fleet_id' => $fleetVan->id,
                'license_number' => 'SIM-A-123456789',
                'is_online' => true,
                'is_on_duty' => false,
                'last_lat' => -6.2087634,
                'last_lng' => 106.8455990, // Sudirman, Jakarta
                'last_heading' => 180.0,
                'last_ping_at' => now(),
            ]
        );

        // Pricing Rules for SwiftExpress
        PricingRule::firstOrCreate(
            ['tenant_id' => $swift->id, 'vehicle_type' => 'MOTORCYCLE'],
            [
                'base_fare' => 10000.00,
                'rate_per_km' => 2500.00,
                'rate_per_kg' => 1000.00,
                'surge_multiplier' => 1.00,
                'is_active' => true,
            ]
        );

        PricingRule::firstOrCreate(
            ['tenant_id' => $swift->id, 'vehicle_type' => 'VAN'],
            [
                'base_fare' => 30000.00,
                'rate_per_km' => 4500.00,
                'rate_per_kg' => 1500.00,
                'surge_multiplier' => 1.00,
                'is_active' => true,
            ]
        );

        // Sample Orders
        $activeOrder = Order::firstOrCreate(
            ['order_number' => 'FP-JAKARTA-001'],
            [
                'tracking_token' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                'tenant_id' => $swift->id,
                'driver_id' => $driver1->id,
                'sender_name' => 'PT Toko Elektronik Maju',
                'sender_phone' => '+628128888999',
                'pickup_address' => 'Jl. MH Thamrin No. 1, Menteng, Jakarta Pusat',
                'pickup_lat' => -6.1950000,
                'pickup_lng' => 106.8230000,
                'recipient_name' => 'Siti Nurhaliza',
                'recipient_phone' => '+628137777888',
                'dropoff_address' => 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan',
                'dropoff_lat' => -6.2300000,
                'dropoff_lng' => 106.8100000,
                'vehicle_type' => 'MOTORCYCLE',
                'weight_kg' => 2.50,
                'distance_km' => 5.20,
                'total_fee' => 25500.00,
                'payment_status' => 'PAID',
                'delivery_status' => 'IN_TRANSIT',
                'notes' => 'Fragile - Laptop accessories. Please handle with care.',
            ]
        );

        // Checkpoints for Active Order
        TrackingCheckpoint::firstOrCreate(
            ['order_id' => $activeOrder->id, 'status' => 'CREATED'],
            [
                'lat' => -6.1950000,
                'lng' => 106.8230000,
                'note' => 'Order created and assigned to SwiftExpress logistics system.',
                'recorded_at' => now()->subMinutes(45),
            ]
        );

        TrackingCheckpoint::firstOrCreate(
            ['order_id' => $activeOrder->id, 'status' => 'ACCEPTED'],
            [
                'lat' => -6.1950000,
                'lng' => 106.8230000,
                'note' => 'Driver Budi Santoso accepted the delivery assignment.',
                'recorded_at' => now()->subMinutes(35),
            ]
        );

        TrackingCheckpoint::firstOrCreate(
            ['order_id' => $activeOrder->id, 'status' => 'PICKED_UP'],
            [
                'lat' => -6.1950000,
                'lng' => 106.8230000,
                'note' => 'Parcel picked up from sender at Jl. MH Thamrin.',
                'recorded_at' => now()->subMinutes(20),
            ]
        );

        TrackingCheckpoint::firstOrCreate(
            ['order_id' => $activeOrder->id, 'status' => 'IN_TRANSIT'],
            [
                'lat' => -6.2100000,
                'lng' => 106.8180000,
                'note' => 'Courier en route to recipient location.',
                'recorded_at' => now()->subMinutes(5),
            ]
        );

        // Sample pending order for dispatch
        Order::firstOrCreate(
            ['order_number' => 'FP-JAKARTA-002'],
            [
                'tracking_token' => 'b2c3d4e5-f6a7-8901-bcde-fa2345678901',
                'tenant_id' => $swift->id,
                'driver_id' => null,
                'sender_name' => 'Apotek Sehat Sentosa',
                'sender_phone' => '+628114444555',
                'pickup_address' => 'Jl. Gatot Subroto No. 12, Jakarta Selatan',
                'pickup_lat' => -6.2350000,
                'pickup_lng' => 106.8250000,
                'recipient_name' => 'Hendra Wijaya',
                'recipient_phone' => '+628123333222',
                'dropoff_address' => 'Jl. Tebet Raya No. 88, Jakarta Selatan',
                'dropoff_lat' => -6.2280000,
                'dropoff_lng' => 106.8550000,
                'vehicle_type' => 'MOTORCYCLE',
                'weight_kg' => 1.00,
                'distance_km' => 4.10,
                'total_fee' => 20250.00,
                'payment_status' => 'PAID',
                'delivery_status' => 'DISPATCHED',
                'notes' => 'Urgent medical supplies.',
            ]
        );
    }
}
