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
        // 1. shipments table: transport_mode, container_number, bill_of_lading_number, awb_number, vehicle_plate, cmr_number
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('transport_mode', 10)->default('SEA')->index()->after('tracking_number');
            $table->string('container_number', 50)->nullable()->index()->after('reference_number');
            $table->string('bill_of_lading_number', 50)->nullable()->index()->after('container_number');
            $table->string('awb_number', 50)->nullable()->index()->after('bill_of_lading_number');
            $table->string('vehicle_plate', 50)->nullable()->index()->after('awb_number');
            $table->string('cmr_number', 50)->nullable()->index()->after('vehicle_plate');
        });

        // 2. locations table: add iata_code and timezone if missing; expand/allow type SEAPORT, AIRPORT, DEPOT, BORDER (or PORT)
        Schema::table('locations', function (Blueprint $table) {
            $table->string('iata_code', 10)->nullable()->index()->after('unlocode');
            if (! Schema::hasColumn('locations', 'timezone')) {
                $table->string('timezone')->nullable()->after('longitude');
            }
        });

        // 3. carriers table: mode enum SEA|AIR|ROAD (keep LAND/MULTIMODAL support in DB if needed or expand)
        // If MySQL enum, modify to include SEA, AIR, ROAD, LAND, MULTIMODAL so existing tests/rows don't break
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE carriers MODIFY COLUMN mode ENUM('SEA', 'AIR', 'ROAD', 'LAND', 'MULTIMODAL') DEFAULT 'SEA'");
            DB::statement("ALTER TABLE locations MODIFY COLUMN type ENUM('SEAPORT', 'AIRPORT', 'DEPOT', 'BORDER', 'PORT', 'HUB', 'WAREHOUSE') DEFAULT 'SEAPORT'");
            DB::statement("ALTER TABLE shipment_exceptions MODIFY COLUMN exception_code ENUM('CUSTOMS_HOLD', 'WEATHER_DELAY', 'PORT_CONGESTION', 'DAMAGED', 'MISROUTED', 'DOCUMENTATION_ISSUE', 'FLIGHT_OFFLOAD', 'BORDER_QUEUE', 'VEHICLE_BREAKDOWN')");
        }

        // 4. shipment_items: add hs_code and packaging if missing
        Schema::table('shipment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_items', 'hs_code')) {
                $table->string('hs_code', 20)->nullable()->index()->after('sku');
            }
            if (! Schema::hasColumn('shipment_items', 'packaging')) {
                $table->string('packaging', 50)->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_items', 'hs_code')) {
                $table->dropColumn('hs_code');
            }
            if (Schema::hasColumn('shipment_items', 'packaging')) {
                $table->dropColumn('packaging');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'iata_code')) {
                $table->dropColumn('iata_code');
            }
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'transport_mode',
                'container_number',
                'bill_of_lading_number',
                'awb_number',
                'vehicle_plate',
                'cmr_number',
            ]);
        });
    }
};
