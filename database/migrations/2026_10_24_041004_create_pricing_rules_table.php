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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('vehicle_type'); // MOTORCYCLE, VAN, BOX_TRUCK, SEMI_TRAILER
            $table->decimal('base_fare', 10, 2)->default(10000.00);
            $table->decimal('rate_per_km', 10, 2)->default(2500.00);
            $table->decimal('rate_per_kg', 10, 2)->default(1000.00);
            $table->decimal('surge_multiplier', 4, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'vehicle_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
