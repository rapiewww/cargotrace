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
                'read_at' => $now->copy()->subDays(4)->setMinute(14)->setSecond(0),
                'created_at' => $now->copy()->subDays(4)->subHours(12)->setMinute(14)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00244',
                'recipient_role' => 'customer',
                'type' => 'CUSTOMS_HOLD_ALERT',
                'title' => 'Action Required: Customs Hold on CTG-SEA-2026-00244',
                'message' => 'US CBP and USDA at Port Newark initiated an examination hold on your Sumatra cassia and black pepper consignment.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(14)->setMinute(17)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-AIR-2026-00678',
                'recipient_role' => 'customer',
                'type' => 'WEATHER_DELAY_NOTICE',
                'title' => 'Airfreight Weather Advisory for CTG-AIR-2026-00678',
                'message' => 'Passing equatorial squall at Singapore Changi delayed patchouli essential oil uplift by 2.5 hours.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(4)->setMinute(42)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00509',
                'recipient_role' => 'customer',
                'type' => 'PORT_CONGESTION_ALERT',
                'title' => 'Berth Queue Delay on CTG-SEA-2026-00509',
                'message' => 'Jebel Ali Terminal 2 berth congestion added 28 hours to your Korintje cinnamon arrival schedule.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(20)->setMinute(31)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00582',
                'recipient_role' => 'customer',
                'type' => 'MISROUTED_ALERT',
                'title' => 'Container Delivered After Re-Staging: CTG-SEA-2026-00582',
                'message' => 'Your Hamburg-bound Lampung pepper container was re-allocated at Singapore transshipment yard and delivered (+64h).',
                'read_at' => $now->copy()->subDays(11)->setMinute(8)->setSecond(0),
                'created_at' => $now->copy()->subDays(12)->setMinute(33)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-AIR-2026-00451',
                'recipient_role' => 'customer',
                'type' => 'FLIGHT_OFFLOAD_ALERT',
                'title' => 'Flight Offload Notice: CTG-AIR-2026-00451',
                'message' => 'Patchouli oil ULD offloaded at Dubai (DXB) due to payload limits and rebooked on the next Paris CDG freighter.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(11)->setMinute(29)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-AIR-2026-00195',
                'recipient_role' => 'customer',
                'type' => 'DOCUMENTATION_ISSUE_ALERT',
                'title' => 'Documentation Cleared: CTG-AIR-2026-00195',
                'message' => 'Amended EU TRACES phytosanitary filing accepted at Amsterdam Schiphol for your clove bud air consignment.',
                'read_at' => $now->copy()->subDays(32)->setMinute(22)->setSecond(0),
                'created_at' => $now->copy()->subDays(33)->setMinute(21)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-ROAD-2026-00638',
                'recipient_role' => 'customer',
                'type' => 'BORDER_QUEUE_ALERT',
                'title' => 'Border Queue Hold: CTG-ROAD-2026-00638',
                'message' => 'Bonded nutmeg trailer is queued at Venlo NL/DE agricultural inspection lane (+28h variance).',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(16)->setMinute(53)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-ROAD-2026-00562',
                'recipient_role' => 'operator',
                'type' => 'VEHICLE_BREAKDOWN_ALERT',
                'title' => 'Relief Truck Dispatched: CTG-ROAD-2026-00562',
                'message' => 'CV Bukit Barisan Haulage reported a coolant fault near Tebing Tinggi; relief unit en route from Medan.',
                'read_at' => null,
                'created_at' => $now->copy()->subHours(7)->setMinute(36)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00218',
                'recipient_role' => 'customer',
                'type' => 'SHIPMENT_EXCEPTION_RESOLVED',
                'title' => 'Customs Hold Resolved: CTG-SEA-2026-00218',
                'message' => 'Hamburg phytosanitary inspection completed and your cassia cinnamon container was delivered.',
                'read_at' => $now->copy()->subDays(9)->setMinute(48)->setSecond(0),
                'created_at' => $now->copy()->subDays(10)->setMinute(24)->setSecond(0),
            ],
            [
                'tracking' => 'CTG-SEA-2026-00279',
                'recipient_role' => 'admin',
                'type' => 'FLEET_UPDATE',
                'title' => 'VGM Discrepancy Closed: CTG-SEA-2026-00279',
                'message' => 'Revised SOLAS VGM certificate verified for Marseille-Fos patchouli drum shipment.',
                'read_at' => $now->copy()->subDays(18)->setMinute(11)->setSecond(0),
                'created_at' => $now->copy()->subDays(21)->setMinute(45)->setSecond(0),
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
