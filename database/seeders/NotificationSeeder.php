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
     * Run the notification seeds.
     */
    public function run(): void
    {
        $c1 = User::where('email', 'customer@cargotrace.test')->first();
        $c2 = User::where('email', 'customer2@cargotrace.test')->first();
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $admin = User::where('email', 'admin@cargotrace.test')->first();

        $now = Carbon::now();

        $s189 = Shipment::where('tracking_number', 'CTG-SEA-2026-00189')->first();
        $s244 = Shipment::where('tracking_number', 'CTG-SEA-2026-00244')->first();
        $s678 = Shipment::where('tracking_number', 'CTG-SEA-2026-00678')->first();
        $s203 = Shipment::where('tracking_number', 'CTG-SEA-2026-00203')->first();
        $s101 = Shipment::where('tracking_number', 'CTG-SEA-2026-00101')->first();

        if ($s189 && $c1) {
            Notification::firstOrCreate(
                [
                    'shipment_id' => $s189->id,
                    'type' => 'SHIPMENT_DELIVERED',
                ],
                [
                    'user_id' => $c1->id,
                    'title' => 'Shipment CTG-SEA-2026-00189 Delivered',
                    'message' => 'Your FCL coffee container from Tanjung Priok has arrived and cleared customs release at Port of Rotterdam.',
                    'read_at' => $now->copy()->subDays(3),
                ]
            );
        }

        if ($s244 && $c2) {
            Notification::firstOrCreate(
                [
                    'shipment_id' => $s244->id,
                    'type' => 'CUSTOMS_HOLD_ALERT',
                ],
                [
                    'user_id' => $c2->id,
                    'title' => 'Action Required: Customs Hold on CTG-SEA-2026-00244',
                    'message' => 'US Customs at Port of Los Angeles has initiated an examination hold on your lithium battery container. Please verify UN 38.3 paperwork.',
                    'read_at' => null,
                ]
            );
        }

        if ($s678 && $c1) {
            Notification::firstOrCreate(
                [
                    'shipment_id' => $s678->id,
                    'type' => 'WEATHER_DELAY_NOTICE',
                ],
                [
                    'user_id' => $c1->id,
                    'title' => 'Maritime Swell Advisory for CTG-SEA-2026-00678',
                    'message' => 'Heavy southerly swell in the Coral Sea resulted in a 3.5 hour transit delay. Estimated Sydney berthing is now adjusted accordingly.',
                    'read_at' => $now->copy()->subHours(2),
                ]
            );
        }

        if ($s203 && $operator) {
            Notification::firstOrCreate(
                [
                    'shipment_id' => $s203->id,
                    'type' => 'PORT_CONGESTION_ALERT',
                ],
                [
                    'user_id' => $operator->id,
                    'title' => 'Berth Queue Alert: CTG-SEA-2026-00203',
                    'message' => 'Vessel arriving at Long Beach Pier 400 is queued for anchorage due to high terminal dwell times.',
                    'read_at' => null,
                ]
            );
        }

        if ($s101 && $admin) {
            Notification::firstOrCreate(
                [
                    'shipment_id' => $s101->id,
                    'type' => 'FLEET_UPDATE',
                ],
                [
                    'user_id' => $admin->id,
                    'title' => 'Vessel Departure Verified: CTG-SEA-2026-00101',
                    'message' => 'Maersk Mc-Kinney Moller departed Port of Shanghai on schedule along the Asia-Europe westbound corridor.',
                    'read_at' => $now->copy()->subDays(10),
                ]
            );
        }
    }
}
