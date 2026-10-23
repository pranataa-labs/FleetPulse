<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendUiAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $driverUser;
    protected Driver $driver;
    protected Fleet $fleet;
    protected Order $order;
    protected PricingRule $pricingRule;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->tenant = Tenant::create([
            'name' => 'SwiftExpress Courier',
            'slug' => 'swiftexpress',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Swift Admin',
            'email' => 'admin@swiftexpress.com',
            'password' => bcrypt('password123'),
            'role' => 'TENANT_ADMIN',
        ]);

        $this->fleet = Fleet::create([
            'tenant_id' => $this->tenant->id,
            'vehicle_type' => 'MOTORCYCLE',
            'license_plate' => 'B 3012 SFE',
        ]);

        $this->driverUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@swiftexpress.com',
            'password' => bcrypt('password123'),
            'role' => 'DRIVER',
        ]);

        $this->driver = Driver::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->driverUser->id,
            'fleet_id' => $this->fleet->id,
            'license_number' => 'SIM-C-987654',
            'is_online' => true,
            'is_on_duty' => true,
            'last_lat' => -6.1753924,
            'last_lng' => 106.8271528,
        ]);

        $this->pricingRule = PricingRule::create([
            'tenant_id' => $this->tenant->id,
            'vehicle_type' => 'MOTORCYCLE',
            'base_fare' => 10000.00,
            'rate_per_km' => 2500.00,
            'rate_per_kg' => 1000.00,
            'surge_multiplier' => 1.00,
        ]);

        $this->order = Order::create([
            'tenant_id' => $this->tenant->id,
            'driver_id' => $this->driver->id,
            'order_number' => 'FP-TEST-001',
            'sender_name' => 'Toko Senayan',
            'sender_phone' => '0811223344',
            'pickup_address' => 'Jl. Asia Afrika No. 1, Senayan',
            'pickup_lat' => -6.218556,
            'pickup_lng' => 106.802553,
            'recipient_name' => 'Rina Wijaya',
            'recipient_phone' => '0899887766',
            'dropoff_address' => 'Jl. Thamrin No. 10, Menteng',
            'dropoff_lat' => -6.195000,
            'dropoff_lng' => 106.823000,
            'vehicle_type' => 'MOTORCYCLE',
            'weight_kg' => 1.5,
            'distance_km' => 3.45,
            'total_fee' => 20125.00,
            'delivery_status' => 'IN_TRANSIT',
        ]);
    }

    public function test_landing_portal_loads_with_active_shipments(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200)
            ->assertSee('FleetPulse')
            ->assertSee('FP-TEST-001')
            ->assertSee('Enterprise Logistics Platform')
            ->assertSee('SwiftExpress Courier');
    }

    public function test_public_customer_tracking_view_renders_live_map_and_timeline(): void
    {
        $response = $this->get(route('tracking.show', $this->order->tracking_token));

        $response->assertStatus(200)
            ->assertSee('FP-TEST-001')
            ->assertSee('liveMap')
            ->assertSee('Budi Santoso')
            ->assertSee('B 3012 SFE')
            ->assertSee('Jl. Asia Afrika No. 1, Senayan')
            ->assertSee('Jl. Thamrin No. 10, Menteng')
            ->assertSee('IN_TRANSIT');
    }

    public function test_invalid_tracking_token_returns_404(): void
    {
        $response = $this->get('/track/invalid-uuid-token-0000');

        $response->assertStatus(404);
    }

    public function test_dispatcher_center_loads_and_displays_kpis_and_radar(): void
    {
        $response = $this->get(route('dispatcher.index'));

        $response->assertStatus(200)
            ->assertSee('Dispatcher Operations Center')
            ->assertSee('Active Fleets')
            ->assertSee('dispatchMap')
            ->assertSee('FP-TEST-001');
    }

    public function test_dispatcher_can_create_new_order_with_automated_haversine_and_pricing(): void
    {
        $response = $this->post(route('dispatcher.orders.store'), [
            'sender_name' => 'PT Sumber Rezeki',
            'sender_phone' => '081299998888',
            'pickup_address' => 'SCBD Jakarta',
            'pickup_lat' => -6.2250000,
            'pickup_lng' => 106.8090000,
            'recipient_name' => 'Andi Setiawan',
            'recipient_phone' => '081377776666',
            'dropoff_address' => 'Kebayoran Baru',
            'dropoff_lat' => -6.2400000,
            'dropoff_lng' => 106.8000000,
            'vehicle_type' => 'MOTORCYCLE',
            'weight_kg' => 2.0,
        ]);

        $response->assertRedirect(route('dispatcher.index'));

        $created = Order::where('recipient_name', 'Andi Setiawan')->first();
        $this->assertNotNull($created);
        $this->assertEquals('DISPATCHED', $created->delivery_status);
        $this->assertGreaterThan(0, $created->distance_km);
        $this->assertGreaterThan(10000, $created->total_fee);
        $this->assertCount(1, $created->checkpoints);
    }

    public function test_driver_mobile_view_renders_duty_and_available_orders(): void
    {
        $response = $this->get(route('driver.index', ['driver_id' => $this->driver->id]));

        $response->assertStatus(200)
            ->assertSee('Driver Mobile Cockpit')
            ->assertSee('Budi Santoso')
            ->assertSee('Toggle Duty Status')
            ->assertSee('Ping GPS (Simulasi)')
            ->assertSee('PESANAN AKTIF DITUGASKAN');
    }

    public function test_obfuscated_admin_dashboard_shows_tenants_and_daily_logs(): void
    {
        $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');

        $response = $this->actingAs($this->adminUser)
            ->get("/{$adminPrefix}/dashboard");

        $response->assertStatus(200)
            ->assertSee('Platform Operations')
            ->assertSee('SwiftExpress Courier')
            ->assertSee('Dynamic Logistics Pricing Matrix')
            ->assertSee('Daily Rotating Telemetry Logs');
    }

    public function test_admin_can_update_pricing_rule_rate_card(): void
    {
        $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');

        $response = $this->actingAs($this->adminUser)
            ->post("/{$adminPrefix}/pricing/{$this->pricingRule->id}", [
                'base_fare' => 15000,
                'rate_per_km' => 3000,
                'rate_per_kg' => 1500,
                'surge_multiplier' => 1.2,
            ]);

        $response->assertRedirect();

        $updated = $this->pricingRule->fresh();
        $this->assertEquals(15000.00, $updated->base_fare);
        $this->assertEquals(3000.00, $updated->rate_per_km);
        $this->assertEquals(1500.00, $updated->rate_per_kg);
        $this->assertEquals(1.20, $updated->surge_multiplier);
    }
}
