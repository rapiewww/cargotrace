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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique()->index();
            $table->string('reference_number')->nullable()->index();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->cascadeOnDelete();
            $table->foreignId('origin_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('cargo_type'); // e.g., FCL, LCL, AIR_FREIGHT, REEFER
            $table->decimal('total_weight_kg', 12, 2);
            $table->decimal('total_volume_cbm', 10, 2);

            // Currency & Cargo Value
            $table->decimal('declared_value', 15, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('exchange_rate_to_usd', 15, 6)->default(1);
            $table->decimal('converted_value_usd', 15, 2)->default(0);
            $table->json('currency_snapshot')->nullable();

            // Status, Schedule & Delay Tracking
            $table->enum('status', ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'])->default('CREATED')->index();
            $table->timestamp('expected_departure_at');
            $table->timestamp('expected_arrival_at');
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('actual_arrival_at')->nullable();
            $table->boolean('is_delayed')->default(false)->index();
            $table->decimal('delay_hours', 8, 2)->default(0);
            $table->enum('delay_severity', ['NONE', 'LOW', 'MEDIUM', 'HIGH'])->default('NONE')->index();
            $table->string('delay_reason')->nullable();
            $table->json('route_news_snapshot')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

