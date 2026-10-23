<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('tracking_token')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->text('pickup_address');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('dropoff_address');
            $table->decimal('dropoff_lat', 10, 7);
            $table->decimal('dropoff_lng', 10, 7);
            $table->string('vehicle_type')->default('MOTORCYCLE');
            $table->decimal('weight_kg', 8, 2)->default(1.00);
            $table->decimal('distance_km', 8, 2)->default(0.00);
            $table->decimal('total_fee', 10, 2)->default(0.00);
            $table->string('payment_status')->default('PENDING'); // PENDING, PAID, REFUNDED
            $table->string('delivery_status')->default('DRAFT'); // DRAFT, DISPATCHED, ACCEPTED, PICKED_UP, IN_TRANSIT, DELIVERED, CANCELLED
            $table->string('proof_of_delivery_path')->nullable();
            $table->text('proof_signature')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'delivery_status']);
            $table->index(['driver_id', 'delivery_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
