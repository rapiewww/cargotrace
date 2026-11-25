<?php

namespace Database\Seeders;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExceptionSeeder extends Seeder
{
    /**
     * Seed 14 exceptions covering all 9 exception codes, all 3 severities, and all 3 statuses:
     * - Mode-valid per ExceptionService::MODE_ALLOWED_EXCEPTIONS
     * - All OPEN and INVESTIGATING exceptions belong to IN_TRANSIT shipments
     * - RESOLVED exceptions satisfy resolved_at > detected_at with notes and resolver
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $operatorId = $operator?->id;
        $now = Carbon::now();

        $exceptions = [
            // 1. CUSTOMS_HOLD (HIGH, INVESTIGATING) -> SEA IN_TRANSIT CTG-SEA-2026-00244
            [
                'tracking_number' => 'CTG-SEA-2026-00244',
                'exception_code' => 'CUSTOMS_HOLD',
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'title' => 'US CBP and USDA Agricultural Hold at Port Newark',
                'description' => 'Intensive examination hold on Sumatra cassia cinnamon and black pepper containers pending aflatoxin and moisture lab clearance.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(14)->setMinute(17)->setSecond(0),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 2. CUSTOMS_HOLD (MEDIUM, RESOLVED) -> SEA DELIVERED CTG-SEA-2026-00218 (exact 48h resolution)
            [
                'tracking_number' => 'CTG-SEA-2026-00218',
                'exception_code' => 'CUSTOMS_HOLD',
                'severity' => 'MEDIUM',
                'status' => 'RESOLVED',
                'title' => 'Hamburg Phytosanitary Pallet Stamp Verification',
                'description' => 'Hamburg import authority requested physical check of ISPM 15 pallet stamps and botanical origin certificate.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(12)->setMinute(24)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(10)->setMinute(24)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Port phytosanitary inspector verified original certificate and issued electronic customs release.',
            ],

            // 3. WEATHER_DELAY (LOW, RESOLVED) -> SEA DELIVERED CTG-SEA-2026-00189
            [
                'tracking_number' => 'CTG-SEA-2026-00189',
                'exception_code' => 'WEATHER_DELAY',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'title' => 'North Sea Squall Pilotage Suspension at Maasvlakte',
                'description' => 'Gusty westerly winds delayed harbor pilot boarding at Rotterdam Maasvlakte 2 entrance by 10 hours.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(6)->setMinute(9)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(5)->subHours(14)->setMinute(9)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Wind speeds dropped below 28 knots; pilot boarded and vessel berthed safely.',
            ],

            // 4. WEATHER_DELAY (LOW, OPEN) -> AIR IN_TRANSIT CTG-AIR-2026-00678
            [
                'tracking_number' => 'CTG-AIR-2026-00678',
                'exception_code' => 'WEATHER_DELAY',
                'severity' => 'LOW',
                'status' => 'OPEN',
                'title' => 'Equatorial Squall Apron Metering at Changi (SIN)',
                'description' => 'Passing lightning advisory halted ramp loading at Changi Airfreight Centre for 2.5 hours.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(4)->setMinute(42)->setSecond(0),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 5. PORT_CONGESTION (MEDIUM, OPEN) -> SEA IN_TRANSIT CTG-SEA-2026-00509
            [
                'tracking_number' => 'CTG-SEA-2026-00509',
                'exception_code' => 'PORT_CONGESTION',
                'severity' => 'MEDIUM',
                'status' => 'OPEN',
                'title' => 'Jebel Ali Terminal 2 Berth Queue Congestion',
                'description' => 'Feeder congestion at Jebel Ali Terminal 2 extended vessel anchorage wait and gate-out queue.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(20)->setMinute(31)->setSecond(0),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 6. PORT_CONGESTION (LOW, RESOLVED) -> AIR DELIVERED CTG-AIR-2026-00642
            [
                'tracking_number' => 'CTG-AIR-2026-00642',
                'exception_code' => 'PORT_CONGESTION',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'title' => 'Changi Freighter Apron Congestion',
                'description' => 'Peak cargo ramp slot congestion delayed ULD transfer between regional feeder and Dubai freighter.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(9)->setMinute(14)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(8)->subHours(16)->setMinute(44)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Ramp slot reassigned; ULD uplifted to DXB and delivered to consignee.',
            ],

            // 7. DAMAGED (LOW, RESOLVED) -> SEA DELIVERED CTG-SEA-2026-00137
            [
                'tracking_number' => 'CTG-SEA-2026-00137',
                'exception_code' => 'DAMAGED',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'title' => 'Outer Jute Sack Moisture Spot Check at Rotterdam',
                'description' => 'Two outer nutmeg sacks near container door showed minor condensation dampness during discharge survey.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(35)->setMinute(51)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(34)->setMinute(19)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Surveyor confirmed inner polyethylene liners intact and cargo moisture within 10% export spec.',
            ],

            // 8. DAMAGED (MEDIUM, RESOLVED) -> ROAD DELIVERED CTG-ROAD-2026-00356
            [
                'tracking_number' => 'CTG-ROAD-2026-00356',
                'exception_code' => 'DAMAGED',
                'severity' => 'MEDIUM',
                'status' => 'RESOLVED',
                'title' => 'Outer Gunny Bag Seam Abrasion During CFS Transfer',
                'description' => 'Two clove gunny sacks rubbed against trailer stanchion during Nhava Sheva CFS transfer.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(13)->setMinute(38)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(12)->subHours(10)->setMinute(12)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Outer sacks re-bagged at CFS and net weight verified against manifest before delivery.',
            ],

            // 9. MISROUTED (HIGH, RESOLVED) -> SEA DELIVERED CTG-SEA-2026-00582
            [
                'tracking_number' => 'CTG-SEA-2026-00582',
                'exception_code' => 'MISROUTED',
                'severity' => 'HIGH',
                'status' => 'RESOLVED',
                'title' => 'Transshipment Yard Stack Misallocation at Singapore',
                'description' => 'Black pepper container placed in East Asia feeder block instead of North Europe mother vessel bay.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(18)->setMinute(6)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(16)->setMinute(33)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Container re-shifted to Hamburg-bound mainline vessel and delivered.',
            ],

            // 10. DOCUMENTATION_ISSUE (HIGH, RESOLVED) -> AIR DELIVERED CTG-AIR-2026-00195
            [
                'tracking_number' => 'CTG-AIR-2026-00195',
                'exception_code' => 'DOCUMENTATION_ISSUE',
                'severity' => 'HIGH',
                'status' => 'RESOLVED',
                'title' => 'EU TRACES Certificate Batch Number Correction at AMS',
                'description' => 'Amsterdam customs desk requested updated phytosanitary batch reference on clove bud export certificate.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(34)->setMinute(47)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(33)->setMinute(21)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Amended TRACES phytosanitary filing accepted and consignment cleared.',
            ],

            // 11. DOCUMENTATION_ISSUE (MEDIUM, RESOLVED) -> SEA DELIVERED CTG-SEA-2026-00279
            [
                'tracking_number' => 'CTG-SEA-2026-00279',
                'exception_code' => 'DOCUMENTATION_ISSUE',
                'severity' => 'MEDIUM',
                'status' => 'RESOLVED',
                'title' => 'SOLAS VGM Weight Variance at Origin Weighbridge',
                'description' => 'Origin weighbridge logged 420 kg tare difference on patchouli drum container prior to loading.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(22)->setMinute(13)->setSecond(0),
                'resolved_at' => $now->copy()->subDays(21)->setMinute(45)->setSecond(0),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Revised certified VGM declaration uploaded to carrier portal before vessel cut-off.',
            ],

            // 12. FLIGHT_OFFLOAD (HIGH, INVESTIGATING) -> AIR IN_TRANSIT CTG-AIR-2026-00451
            [
                'tracking_number' => 'CTG-AIR-2026-00451',
                'exception_code' => 'FLIGHT_OFFLOAD',
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'title' => 'Payload Offload at Dubai (DXB) Transit Hub',
                'description' => 'Essential oil ULD offloaded from DXB-CDG freighter due to aircraft payload weight restrictions; rebooked on next flight.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(11)->setMinute(29)->setSecond(0),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 13. BORDER_QUEUE (HIGH, OPEN) -> ROAD IN_TRANSIT CTG-ROAD-2026-00638
            [
                'tracking_number' => 'CTG-ROAD-2026-00638',
                'exception_code' => 'BORDER_QUEUE',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'title' => 'Venlo NL/DE Agricultural Border Inspection Queue',
                'description' => 'Bonded spice trailer held in A67 Venlo inspection lane for random phytosanitary seal verification.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(16)->setMinute(53)->setSecond(0),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 14. VEHICLE_BREAKDOWN (MEDIUM, INVESTIGATING) -> ROAD IN_TRANSIT CTG-ROAD-2026-00562
            [
                'tracking_number' => 'CTG-ROAD-2026-00562',
                'exception_code' => 'VEHICLE_BREAKDOWN',
                'severity' => 'MEDIUM',
                'status' => 'INVESTIGATING',
                'title' => 'Engine Coolant Fault on Trans-Sumatra Highway',
                'description' => 'Box truck experienced radiator hose failure near Tebing Tinggi; replacement tractor unit dispatched from Medan.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(7)->setMinute(36)->setSecond(0),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],
        ];

        foreach ($exceptions as $data) {
            $shipment = Shipment::where('tracking_number', $data['tracking_number'])->first();
            if (! $shipment) {
                continue;
            }

            ShipmentException::firstOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'exception_code' => $data['exception_code'],
                ],
                [
                    'severity' => $data['severity'],
                    'status' => $data['status'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'related_news_url' => $data['related_news_url'],
                    'resolution_notes' => $data['resolution_notes'],
                    'detected_at' => $data['detected_at'],
                    'resolved_at' => $data['resolved_at'],
                    'reported_by' => $operatorId,
                    'resolved_by' => $data['resolved_by'],
                ]
            );
        }
    }
}
