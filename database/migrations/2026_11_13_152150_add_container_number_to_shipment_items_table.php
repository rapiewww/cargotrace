<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->string('container_number', 20)->nullable()->index()->after('shipment_id');
        });

        // Backfill existing rows from sku where container_number is null
        DB::table('shipment_items')->whereNull('container_number')->update([
            'container_number' => DB::raw('sku'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn('container_number');
        });
    }
};
