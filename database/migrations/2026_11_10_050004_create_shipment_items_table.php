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
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('sku')->index();
            $table->string('description');
            $table->integer('quantity');
            $table->decimal('unit_value', 12, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('volume_cbm', 10, 2);
            $table->boolean('is_hazardous')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};

