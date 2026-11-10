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
        Schema::create('shipment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->enum('exception_code', [
                'CUSTOMS_HOLD',
                'WEATHER_DELAY',
                'PORT_CONGESTION',
                'DAMAGED',
                'MISROUTED',
                'DOCUMENTATION_ISSUE',
            ])->index();
            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH'])->index();
            $table->enum('status', ['OPEN', 'INVESTIGATING', 'RESOLVED'])->default('OPEN')->index();
            $table->string('title');
            $table->text('description');
            $table->string('related_news_url')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_exceptions');
    }
};
