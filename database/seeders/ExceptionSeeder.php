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
     * Run the exception seeds.
     *
     * Invariants enforced:
     * - Exactly 12 exceptions total.
     * - Exactly 2 of each of the 6 codes (CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE).
     * - Exactly 4 of each severity (LOW, MEDIUM, HIGH).
     * - Exactly 4 of each status (OPEN, INVESTIGATING, RESOLVED).
     * - CTG-SEA-2026-00244 has CUSTOMS_HOLD (HIGH, INVESTIGATING).
     * - CTG-SEA-2026-00189 has WEATHER_DELAY (LOW, RESOLVED).
     * - CTG-SEA-2026-00678 has WEATHER_DELAY (LOW, OPEN).
     * - For RESOLVED exceptions: resolved_at > detected_at, resolution_notes is filled, resolved_by is operator.
     * - For non-RESOLVED exceptions: resolved_at is null, resolved_by is null, resolution_notes is null.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $operatorId = $operator?->id;
        $now = Carbon::now();

        $exceptionsData = [
            // 1. CUSTOMS_HOLD (HIGH, INVESTIGATING) on CTG-SEA-2026-00244
            [
                'tracking_number' => 'CTG-SEA-2026-00244',
                'exception_code' => 'CUSTOMS_HOLD',
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'title' => 'US CBP Intensive Compliance Examination Hold',
                'description' => 'Customs and Border Protection flagged commercial invoice and UN 38.3 lithium battery safety documentation for detailed physical exam.',
                'related_news_url' => 'https://www.cbp.gov/trade/basic-import-export',
                'detected_at' => $now->copy()->subHours(14),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 2. CUSTOMS_HOLD (MEDIUM, RESOLVED) on CTG-SEA-2026-00104
            [
                'tracking_number' => 'CTG-SEA-2026-00104',
                'exception_code' => 'CUSTOMS_HOLD',
                'severity' => 'MEDIUM',
                'status' => 'RESOLVED',
                'title' => 'Rotterdam Border Control Phytosanitary Hold',
                'description' => 'Port of Rotterdam health authority requested verification of phytosanitary wooden pallet stamp under ISPM 15 standards.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(12),
                'resolved_at' => $now->copy()->subDays(10),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Authorized port phytosanitary inspector completed physical check and issued electronic release certificate.',
            ],

            // 3. WEATHER_DELAY (LOW, RESOLVED) on CTG-SEA-2026-00189
            [
                'tracking_number' => 'CTG-SEA-2026-00189',
                'exception_code' => 'WEATHER_DELAY',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'title' => 'North Sea Gale Force Storm Swell Advisory',
                'description' => 'Force 8 Beaufort winds and 4.8m wave swells prompted harbor master to suspend pilot boat boarding at Maasvlakte entrance.',
                'related_news_url' => 'https://gcaptain.com/north-sea-storm-shipping-advisory',
                'detected_at' => $now->copy()->subDays(6),
                'resolved_at' => $now->copy()->subDays(4)->subHours(8),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Wind calmed below operational limit; harbor pilot boarded and vessel safely berthed at APM Terminals.',
            ],

            // 4. WEATHER_DELAY (LOW, OPEN) on CTG-SEA-2026-00678
            [
                'tracking_number' => 'CTG-SEA-2026-00678',
                'exception_code' => 'WEATHER_DELAY',
                'severity' => 'LOW',
                'status' => 'OPEN',
                'title' => 'Coral Sea Southerly Swell Advisory',
                'description' => 'Long-period southerly ocean swell off Queensland coast caused master to reduce speed by 2.5 knots to prevent cargo roll acceleration.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(6),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 5. PORT_CONGESTION (MEDIUM, OPEN) on CTG-SEA-2026-00203
            [
                'tracking_number' => 'CTG-SEA-2026-00203',
                'exception_code' => 'PORT_CONGESTION',
                'severity' => 'MEDIUM',
                'status' => 'OPEN',
                'title' => 'Long Beach Pier 400 Berth Queue Congestion',
                'description' => 'Container vessel awaiting berth allocation due to high yard dwell times and rail wagon shortage.',
                'related_news_url' => 'https://www.joc.com/port-news/us-ports/port-long-beach',
                'detected_at' => $now->copy()->subHours(20),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 6. PORT_CONGESTION (HIGH, INVESTIGATING) on CTG-SEA-2026-00206
            [
                'tracking_number' => 'CTG-SEA-2026-00206',
                'exception_code' => 'PORT_CONGESTION',
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'title' => 'Jebel Ali Transshipment Basin Congestion Delay',
                'description' => 'Connecting mother vessel delayed 36 hours; container temporarily held in transshipment block 4B.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(18),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 7. DAMAGED (LOW, RESOLVED) on CTG-SEA-2026-00302
            [
                'tracking_number' => 'CTG-SEA-2026-00302',
                'exception_code' => 'DAMAGED',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'title' => 'Container Top Corner Casting Surface Scrape',
                'description' => 'Quay crane spreader guide fin scraped exterior corner casting during vessel discharge; structural integrity undisturbed.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subDays(8),
                'resolved_at' => $now->copy()->subDays(7),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Joint surveyor report confirmed zero breach of container envelope; TIR high security seal verified intact.',
            ],

            // 8. DAMAGED (MEDIUM, INVESTIGATING) on CTG-SEA-2026-00404
            [
                'tracking_number' => 'CTG-SEA-2026-00404',
                'exception_code' => 'DAMAGED',
                'severity' => 'MEDIUM',
                'status' => 'INVESTIGATING',
                'title' => 'Reefer Unit Electrical Power Receptacle Trip',
                'description' => 'Shipboard electrical log noted intermittent breaker trips on 440V auxiliary socket serving reefer refrigeration unit.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(10),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 9. MISROUTED (HIGH, OPEN) on CTG-SEA-2026-00205
            [
                'tracking_number' => 'CTG-SEA-2026-00205',
                'exception_code' => 'MISROUTED',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'title' => 'Transshipment Feeder Bay Mis-Allocation at Santos',
                'description' => 'Yard straddle carrier staged container to coastal feeder bay instead of deep-sea mother vessel stack.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(8),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 10. MISROUTED (MEDIUM, INVESTIGATING) on CTG-SEA-2026-00402
            [
                'tracking_number' => 'CTG-SEA-2026-00402',
                'exception_code' => 'MISROUTED',
                'severity' => 'MEDIUM',
                'status' => 'INVESTIGATING',
                'title' => 'Terminal Block Inventory Discrepancy at Busan',
                'description' => 'Yard operating system recorded container in Block 12, but physical yard inventory detected container in Block 14.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(15),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 11. DOCUMENTATION_ISSUE (LOW, OPEN) on CTG-SEA-2026-00106
            [
                'tracking_number' => 'CTG-SEA-2026-00106',
                'exception_code' => 'DOCUMENTATION_ISSUE',
                'severity' => 'LOW',
                'status' => 'OPEN',
                'title' => 'Missing Phytosanitary Import Endorsement',
                'description' => 'Destination customs broker notified carrier that commercial plant quarantine manifest requires consular attestation.',
                'related_news_url' => null,
                'detected_at' => $now->copy()->subHours(12),
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_notes' => null,
            ],

            // 12. DOCUMENTATION_ISSUE (HIGH, RESOLVED) on CTG-SEA-2026-00105
            [
                'tracking_number' => 'CTG-SEA-2026-00105',
                'exception_code' => 'DOCUMENTATION_ISSUE',
                'severity' => 'HIGH',
                'status' => 'RESOLVED',
                'title' => 'SOLAS Verified Gross Mass (VGM) Discrepancy Flag',
                'description' => 'Terminal gate weighbridge detected 820 kg variance between shipper-declared VGM certificate and actual weighbridge weight.',
                'related_news_url' => 'https://www.worldshipping.org/vgm',
                'detected_at' => $now->copy()->subWeeks(3),
                'resolved_at' => $now->copy()->subWeeks(3)->addDays(1),
                'resolved_by' => $operatorId,
                'resolution_notes' => 'Shipper submitted revised certified weighing ticket from licensed port scale. VGM updated and accepted.',
            ],
        ];

        foreach ($exceptionsData as $data) {
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
