<?php

namespace Tests\Feature;

use App\Events\DriverLocationUpdated;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GeoDistanceService;
use App\Services\PricingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BackendLogicAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $customerUser;
    protected User $driverUser1;
    protected User $driverUser2;
    protected Driver $driver1;
    protected Driver $driver2;
    protected Fleet $fleet;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->tenant = Tenant::create([
            'name' => 'FastTrack Express',
            'slug' => 'fasttrack',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'FastTrack Admin',
            'email' => 'admin@fasttrack.test',
            'password' => bcrypt('password123'),
            'role' => 'TENANT_ADMIN',
        ]);

        $this->customerUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Customer Budi',
            'email' => 'customer@test.com',
            'password' => bcrypt('password123'),
            'role' => 'CUSTOMER',
        ]);

        $this->fleet = Fleet::create([
            'tenant_id' => $this->tenant->id,
            'vehicle_type' => 'MOTORCYCLE',
            'license_plate' => 'B 1111 FT',
        ]);

        $this->driverUser1 = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Driver One',
            'email' => 'driver1@fasttrack.test',
            'password' => bcrypt('password123'),
            'role' => 'DRIVER',
        ]);

        $this->driver1 = Driver::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->driverUser1->id,
            'fleet_id' => $this->fleet->id,
            'license_number' => 'SIM-C-001',
            'is_online' => true,
        ]);

        $this->driverUser2 = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Driver Two',
            'email' => 'driver2@fasttrack.test',
            'password' => bcrypt('password123'),
            'role' => 'DRIVER',
        ]);

        $this->driver2 = Driver::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->driverUser2->id,
            'fleet_id' => $this->fleet->id,
            'license_number' => 'SIM-C-002',
            'is_online' => true,
        ]);

        PricingRule::create([
            'tenant_id' => $this->tenant->id,
            'vehicle_type' => 'MOTORCYCLE',
            'base_fare' => 10000.00,
            'rate_per_km' => 2500.00,
            'rate_per_kg' => 1000.00,
            'surge_multiplier' => 1.00,
        ]);
    }

    public function test_haversine_formula_calculates_accurate_geographic_distance(): void
    {
        // Coordinate 1: Monas Jakarta (-6.175392, 106.827153)
        // Coordinate 2: Gelora Bung Karno Senayan (-6.218556, 106.802553)
        // Expected distance: ~5.47 km
        $distance = GeoDistanceService::haversine(-6.175392, 106.827153, -6.218556, 106.802553);

        $this->assertGreaterThan(5.0, $distance);
        $this->assertLessThan(6.0, $distance);
        $this->assertEqualsWithDelta(5.52, $distance, 0.05);
    }

    public function test_pricing_service_calculates_correct_breakdown_and_total(): void
    {
        // Base: 10,000, 10km * 2500 = 25,000, 2kg * 1000 = 2000 -> Total = 37,000
        $pricing = PricingService::calculateFee($this->tenant->id, 'MOTORCYCLE', 10.0, 2.0);

        $this->assertEquals(10000.00, $pricing['base_fare']);
        $this->assertEquals(25000.00, $pricing['distance_cost']);
        $this->assertEquals(2000.00, $pricing['weight_cost']);
        $this->assertEquals(37000.00, $pricing['total_fee']);
    }

    public function test_pessimistic_locking_prevents_duplicate_order_claiming(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'sender_name' => 'Toko A',
            'sender_phone' => '08123',
            'pickup_address' => 'Jl. A',
            'pickup_lat' => -6.2,
            'pickup_lng' => 106.8,
            'recipient_name' => 'Budi',
            'recipient_phone' => '08456',
            'dropoff_address' => 'Jl. B',
            'dropoff_lat' => -6.21,
            'dropoff_lng' => 106.81,
            'delivery_status' => 'DISPATCHED',
        ]);

        // First driver claims the order
        $response1 = $this->postJson(route('api.driver.claim_order', ['orderId' => $order->id]), [
            'driver_id' => $this->driver1->id,
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pesanan berhasil diambil.',
            ]);

        $this->assertEquals('ACCEPTED', $order->fresh()->delivery_status);
        $this->assertEquals($this->driver1->id, $order->fresh()->driver_id);

        // Second driver tries to claim the same order concurrently -> Must receive 409 Conflict
        $response2 = $this->postJson(route('api.driver.claim_order', ['orderId' => $order->id]), [
            'driver_id' => $this->driver2->id,
        ]);

        $response2->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => 'ORDER_ALREADY_CLAIMED',
            ]);

        // Ensure driver 1 remains the exclusive assignee
        $this->assertEquals($this->driver1->id, $order->fresh()->driver_id);
    }

    public function test_customer_role_is_strictly_forbidden_from_admin_route(): void
    {
        $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');

        // Customer attempts to access obfuscated admin dashboard
        $response = $this->actingAs($this->customerUser)
            ->get("/{$adminPrefix}/dashboard");

        $response->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Akses ditolak. Anda bukan admin.');
    }

    public function test_dispatcher_role_is_strictly_forbidden_from_admin_route(): void
    {
        $dispatcherUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dispatcher Siska',
            'email' => 'dispatcher@fasttrack.test',
            'password' => bcrypt('password123'),
            'role' => 'DISPATCHER',
        ]);

        $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');

        // Dispatcher attempts to access obfuscated admin dashboard -> Must be rejected
        $response = $this->actingAs($dispatcherUser)
            ->get("/{$adminPrefix}/dashboard");

        $response->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Akses ditolak. Anda bukan admin.');
    }

    public function test_old_predictable_admin_route_is_obfuscated_and_blocked(): void
    {
        // Direct access to /admin/dashboard
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Akses ditolak. Anda bukan admin.');
    }

    public function test_authorized_admin_can_access_obfuscated_admin_dashboard(): void
    {
        $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');

        $response = $this->actingAs($this->adminUser)
            ->get("/{$adminPrefix}/dashboard");

        $response->assertStatus(200);
    }

    public function test_driver_location_update_broadcasts_websocket_event(): void
    {
        Event::fake([DriverLocationUpdated::class]);

        $response = $this->postJson(route('api.driver.location'), [
            'driver_id' => $this->driver1->id,
            'lat' => -6.1800000,
            'lng' => 106.8200000,
            'heading' => 90.0,
            'speed' => 35.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        Event::assertDispatched(DriverLocationUpdated::class, function ($event) {
            return $event->driverId === $this->driver1->id &&
                   $event->lat === -6.18 &&
                   $event->lng === 106.82;
        });

        $this->assertEquals(-6.1800000, $this->driver1->fresh()->last_lat);
    }

    public function test_daily_rotating_log_channel_is_active(): void
    {
        $this->assertEquals('daily', config('logging.default'));
        $this->assertEquals(14, config('logging.channels.daily.max_files'));

        Log::info('PZN Daily Log Test verification for FleetPulse');

        $todayLogFilename = 'laravel-' . date('Y-m-d') . '.log';
        $logPath = storage_path('logs/' . $todayLogFilename);

        $this->assertTrue(File::exists($logPath));
        $this->assertStringContainsString('PZN Daily Log Test verification for FleetPulse', File::get($logPath));
    }

    public function test_claiming_order_automatically_creates_transparent_chat_room(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'sender_name' => 'Toko Buku Maju',
            'sender_phone' => '081233445566',
            'pickup_address' => 'Jl. Sudirman No. 1',
            'pickup_lat' => -6.2000000,
            'pickup_lng' => 106.8160000,
            'recipient_name' => 'Siti Rahma',
            'recipient_phone' => '081399887766',
            'dropoff_address' => 'Jl. Gatot Subroto No. 5',
            'dropoff_lat' => -6.2300000,
            'dropoff_lng' => 106.8200000,
            'vehicle_type' => 'MOTORCYCLE',
            'weight_kg' => 1.5,
            'distance_km' => 3.5,
            'total_fee' => 18000,
            'delivery_status' => 'DISPATCHED',
        ]);

        $this->assertCount(0, $order->messages);

        // Driver claims order
        $response = $this->postJson(route('api.driver.claim_order', ['orderId' => $order->id]), [
            'driver_id' => $this->driver1->id,
        ]);

        $response->assertStatus(200);

        // Verify transparent chat thread was auto-created
        $messages = $order->fresh()->messages;
        $this->assertCount(1, $messages);
        $this->assertEquals('SYSTEM', $messages->first()->sender_role);
        $this->assertStringContainsString('Ruang koordinasi aktif', $messages->first()->message);
        $this->assertStringContainsString('Driver One', $messages->first()->message);
    }

    public function test_transparent_chat_message_creation_and_retrieval(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'sender_name' => 'Toko Elektronik Barokah',
            'sender_phone' => '081211112222',
            'pickup_address' => 'Mall Ambassador',
            'pickup_lat' => -6.2200000,
            'pickup_lng' => 106.8250000,
            'recipient_name' => 'Ahmad Dahlan',
            'recipient_phone' => '081344445555',
            'dropoff_address' => 'Kuningan Barat',
            'dropoff_lat' => -6.2350000,
            'dropoff_lng' => 106.8300000,
            'vehicle_type' => 'MOTORCYCLE',
            'weight_kg' => 2.0,
            'distance_km' => 2.0,
            'total_fee' => 15000,
            'delivery_status' => 'ACCEPTED',
        ]);

        // Post message as SENDER
        $this->postJson(route('api.orders.messages.store', ['orderId' => $order->id]), [
            'sender_role' => 'SENDER',
            'sender_name' => 'Toko Elektronik Barokah',
            'message' => 'Paket sudah siap di lobby timur, tolong hubungi satpam ya mas.',
        ])->assertStatus(201);

        // Post message as DRIVER
        $this->postJson(route('api.orders.messages.store', ['orderId' => $order->id]), [
            'sender_role' => 'DRIVER',
            'sender_name' => 'Driver One (Kurir)',
            'message' => 'Siap pak, saya sudah dekat sekitar 3 menit lagi sampai.',
        ])->assertStatus(201);

        // Retrieve full transparent chat thread
        $response = $this->getJson(route('api.orders.messages.index', ['orderId' => $order->id]));
        $response->assertStatus(200)
            ->assertJsonCount(2, 'messages')
            ->assertJsonFragment(['sender_role' => 'SENDER'])
            ->assertJsonFragment(['sender_role' => 'DRIVER']);
    }

    public function test_dispatcher_can_create_order_without_providing_lat_lng(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('dispatcher.orders.store'), [
            'sender_name' => 'Kopi Kenangan Senayan',
            'sender_phone' => '081288889999',
            'pickup_address' => 'Senayan City Lt. LG',
            'recipient_name' => 'Rina Gunawan',
            'recipient_phone' => '081377778888',
            'dropoff_address' => 'Menara Astra Lt. 15',
            'vehicle_type' => 'MOTORCYCLE',
            'weight_kg' => 1.0,
        ]);

        $response->assertRedirect(route('dispatcher.index'));

        $order = Order::where('recipient_name', 'Rina Gunawan')->first();
        $this->assertNotNull($order);
        $this->assertEquals('DISPATCHED', $order->delivery_status);
        $this->assertNotNull($order->pickup_lat);
        $this->assertNotNull($order->dropoff_lat);
        $this->assertGreaterThan(0, $order->distance_km);
        $this->assertGreaterThan(0, $order->total_fee);
    }
}
