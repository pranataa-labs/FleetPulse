<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Tenant;
use App\Models\TrackingCheckpoint;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_all_logistics_database_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('tenants'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('fleets'));
        $this->assertTrue(Schema::hasTable('drivers'));
        $this->assertTrue(Schema::hasTable('pricing_rules'));
        $this->assertTrue(Schema::hasTable('orders'));
        $this->assertTrue(Schema::hasTable('tracking_checkpoints'));
    }

    public function test_users_table_has_tenant_and_role_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'tenant_id',
            'role',
            'phone',
            'is_active',
        ]));
    }

    public function test_tenant_relationships_and_cascading(): void
    {
        $tenant = Tenant::create([
            'name' => 'Apex Delivery',
            'slug' => 'apex-delivery',
            'plan_tier' => 'pro',
            'subscription_status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Apex Admin',
            'email' => 'admin@apex.test',
            'password' => 'secret123',
            'role' => 'TENANT_ADMIN',
        ]);

        $fleet = Fleet::create([
            'tenant_id' => $tenant->id,
            'vehicle_type' => 'VAN',
            'license_plate' => 'B 9999 APX',
        ]);

        $driver = Driver::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'fleet_id' => $fleet->id,
            'license_number' => 'SIM-B2-001',
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'driver_id' => $driver->id,
            'sender_name' => 'John Sender',
            'sender_phone' => '081111111',
            'pickup_address' => 'Jakarta',
            'pickup_lat' => -6.2000,
            'pickup_lng' => 106.8166,
            'recipient_name' => 'Jane Recipient',
            'recipient_phone' => '082222222',
            'dropoff_address' => 'Bandung',
            'dropoff_lat' => -6.9175,
            'dropoff_lng' => 107.6191,
        ]);

        $checkpoint = TrackingCheckpoint::create([
            'order_id' => $order->id,
            'status' => 'CREATED',
            'note' => 'Initial checkpoint',
            'recorded_at' => now(),
        ]);

        $this->assertEquals($tenant->id, $fleet->tenant->id);
        $this->assertEquals($fleet->id, $driver->fleet->id);
        $this->assertEquals($driver->id, $order->driver->id);
        $this->assertEquals($order->id, $checkpoint->order->id);
        $this->assertNotNull($order->tracking_token);
        $this->assertNotNull($order->order_number);
    }

    public function test_tenant_global_scope_isolates_records_between_tenants(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant Alpha', 'slug' => 'alpha']);
        $tenantB = Tenant::create(['name' => 'Tenant Beta', 'slug' => 'beta']);

        // Create orders in both tenants
        TenantContext::setTenant($tenantA);
        $orderA = Order::create([
            'sender_name' => 'Sender A',
            'sender_phone' => '111',
            'pickup_address' => 'Point A',
            'pickup_lat' => -6.20,
            'pickup_lng' => 106.80,
            'recipient_name' => 'Recipient A',
            'recipient_phone' => '222',
            'dropoff_address' => 'Point B',
            'dropoff_lat' => -6.21,
            'dropoff_lng' => 106.81,
        ]);

        TenantContext::setTenant($tenantB);
        $orderB = Order::create([
            'sender_name' => 'Sender B',
            'sender_phone' => '333',
            'pickup_address' => 'Point C',
            'pickup_lat' => -6.30,
            'pickup_lng' => 106.90,
            'recipient_name' => 'Recipient B',
            'recipient_phone' => '444',
            'dropoff_address' => 'Point D',
            'dropoff_lat' => -6.31,
            'dropoff_lng' => 106.91,
        ]);

        // When Tenant Alpha is active:
        TenantContext::setTenant($tenantA);
        $ordersForA = Order::all();
        $this->assertCount(1, $ordersForA);
        $this->assertEquals($orderA->id, $ordersForA->first()->id);

        // When Tenant Beta is active:
        TenantContext::setTenant($tenantB);
        $ordersForB = Order::all();
        $this->assertCount(1, $ordersForB);
        $this->assertEquals($orderB->id, $ordersForB->first()->id);

        // Clear tenant context: without global scope filter, all are visible
        TenantContext::clear();
        $allOrders = Order::withoutGlobalScope('tenant')->get();
        $this->assertCount(2, $allOrders);
    }
}
