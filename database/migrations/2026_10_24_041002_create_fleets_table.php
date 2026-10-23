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
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('vehicle_type'); // MOTORCYCLE, VAN, BOX_TRUCK, SEMI_TRAILER
            $table->string('license_plate');
            $table->decimal('max_weight_kg', 8, 2)->default(50.00);
            $table->decimal('max_volume_cbm', 8, 2)->default(1.00);
            $table->string('status')->default('ACTIVE'); // ACTIVE, MAINTENANCE, DECOMMISSIONED
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};
