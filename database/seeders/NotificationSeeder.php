<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Seed notifications consistent with seeded spice shipments and exceptions.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $admin = User::where('email', 'admin@cargotrace.test')->first();
        $now = Carbon::now();

        $entries = [
            [
                'tracking' => 'CTG-SEA-2026-00189',
                'recipient_role' => 'customer',
                'type' => 'SHIPMENT_DELIVERED',
                'title' => 'Shipment CTG-SEA-2026-00189 Delivered',
                'message' => 'Your nutmeg and cloves container from Tanjung Priok cleared customs and was delivered at Port of Rotterdam (+10h North Sea squall variance).',
                'read_at' => $now->copy()->subDays(4),
                'created_at' => $now->copy()->subDays(4)->subHours(12),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00244',
                'recipient_role' => 'customer',
                'type' => 'CUSTOMS_HOLD_ALERT',
                'title' => 'Action Required: Customs Hold on CTG-SEA-2026-00244',
                'message' => 'US CBP and USDA at Port Newark initiated an examination hold on your Sumatra cassia and black pepper consignment.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(14),
            ],
            [
                'tracking' => 'CTG-AIR-2026-00678',
                'recipient_role' => 'customer',
                'type' => 'WEATHER_DELAY_NOTICE',
                'title' => 'Airfreight Weather Advisory for CTG-AIR-2026-00678',
                'message' => 'Passing equatorial squall at Singapore Changi delayed patchouli essential oil uplift by 2.5 hours.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(4),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00203',
                'recipient_role' => 'customer',
                'type' => 'PORT_CONGESTION_ALERT',
                'title' => 'Berth Queue Delay on CTG-SEA-2026-00203',
                'message' => 'Jebel Ali Terminal 2 berth congestion added 28 hours to your Korintje cinnamon arrival schedule.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(20),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00205',
                'recipient_role' => 'customer',
                'type' => 'MISROUTED_ALERT',
                'title' => 'High Delay Alert: CTG-SEA-2026-00205 Re-Staged',
                'message' => 'Your Hamburg-bound Lampung pepper container is being re-allocated at Singapore transshipment yard (+64h).',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(18),
            ],
            [
                'tracking' => 'CTG-AIR-2026-00306',
                'recipient_role' => 'customer',
                'type' => 'FLIGHT_OFFLOAD_ALERT',
                'title' => 'Flight Offload Notice: CTG-AIR-2026-00306',
                'message' => 'Patchouli oil ULD offloaded at Dubai (DXB) due to payload limits and rebooked on the next Paris CDG freighter.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(11),
            ],
            [
                'tracking' => 'CTG-AIR-2026-00307',
                'recipient_role' => 'customer',
                'type' => 'DOCUMENTATION_ISSUE_ALERT',
                'title' => 'Documentation Check: CTG-AIR-2026-00307',
                'message' => 'Frankfurt transit desk requested revised GC-MS assay batch details for your cinnamon oil consignment.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(10),
            ],
            [
                'tracking' => 'CTG-ROAD-2026-00408',
                'recipient_role' => 'customer',
                'type' => 'BORDER_QUEUE_ALERT',
                'title' => 'Border Queue Hold: CTG-ROAD-2026-00408',
                'message' => 'Bonded nutmeg trailer is queued at Venlo NL/DE agricultural inspection lane (+28h variance).',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(16),
            ],
            [
                'tracking' => 'CTG-ROAD-2026-00407',
                'recipient_role' => 'operator',
                'type' => 'VEHICLE_BREAKDOWN_ALERT',
                'title' => 'Relief Truck Dispatched: CTG-ROAD-2026-00407',
                'message' => 'CV Bukit Barisan Haulage reported a coolant fault near Tebing Tinggi; relief unit en route from Medan.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(7),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00104',
                'recipient_role' => 'customer',
                'type' => 'SHIPMENT_EXCEPTION_RESOLVED',
                'title' => 'Customs Hold Resolved: CTG-SEA-2026-00104',
                'message' => 'Hamburg phytosanitary inspection completed and your cassia cinnamon container was delivered.',
                'read_at' => $now->copy()->subDays(9),
                'created_at' => $now->copy()->subDays(10),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00105',
                'recipient_role' => 'admin',
                'type' => 'FLEET_UPDATE',
                'title' => 'VGM Discrepancy Closed: CTG-SEA-2026-00105',
                'message' => 'Revised SOLAS VGM certificate verified for Marseille-Fos patchouli drum shipment.',
                'read_at' => $now->copy()->subDays(18),
                'created_at' => $now->copy()->subDays(21),
            ],
        ];

        foreach ($entries as $entry) {
            $shipment = Shipment::where('tracking_number', $entry['tracking'])->first();
            if (! $shipment) {
                continue;
            }

            $userId = match ($entry['recipient_role']) {
                'operator' => $operator?->id,
                'admin' => $admin?->id,
                default => $shipment->customer_id,
            };

            if (! $userId) {
                continue;
            }

            $notif = Notification::firstOrCreate(
                [
                    'user_id' => $userId,
                    'shipment_id' => $shipment->id,
                    'type' => $entry['type'],
                ],
                [
                    'title' => $entry['title'],
                    'message' => $entry['message'],
                    'read_at' => $entry['read_at'],
                ]
            );

            $notif->created_at = $entry['created_at'];
            $notif->updated_at = $entry['read_at'] ?? $entry['created_at'];
            $notif->saveQuietly();
        }
    }
}
