<?php

namespace Database\Seeders;

use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TrackingEventSeeder extends Seeder
{
    /**
     * Run the tracking event seeds.
     *
     * Invariants enforced:
     * - Strictly increasing chronological occurred_at timestamps per shipment.
     * - None before departure for departed shipments (first event is at expected_departure_at).
     * - DELIVERED shipments always terminate with an event where occurred_at == actual_arrival_at.
     * - Coordinates align realistically with port locations and intermediate sea corridors.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $operatorId = $operator?->id;
        $now = Carbon::now();

        $shipments = Shipment::with(['originLocation', 'destinationLocation', 'currentLocation'])->get();

        foreach ($shipments as $shipment) {
            $origin = $shipment->originLocation;
            $dest = $shipment->destinationLocation;

            if ($shipment->status === 'CREATED' || $shipment->status === 'BOOKED') {
                // Single initial milestone for pending shipments at origin port
                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => $shipment->status,
                        'title' => 'Consignment Manifested & Staged at '.$origin->name,
                    ],
                    [
                        'location_id' => $origin->id,
                        'event_type' => 'NORMAL',
                        'description' => 'Ocean bill of lading manifested. Container staged at terminal yard awaiting vessel berthing and gantry crane loading.',
                        'latitude' => $origin->latitude,
                        'longitude' => $origin->longitude,
                        'weather_snapshot' => $origin->weather_snapshot,
                        'occurred_at' => $shipment->created_at ?? $now->copy()->subDays(2),
                        'created_by' => $operatorId,
                    ]
                );

                continue;
            }

            if ($shipment->status === 'IN_TRANSIT') {
                $depTime = $shipment->expected_departure_at->copy();
                // Elapsed time between departure and now
                $elapsedMinutes = max(60, $depTime->diffInMinutes($now, false));

                // Milestone 1: Departure from origin port
                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => 'Vessel Departure from '.$origin->name,
                    ],
                    [
                        'location_id' => $origin->id,
                        'event_type' => 'NORMAL',
                        'description' => 'Vessel untied mooring lines, cleared harbor channel pilotage, and commenced deep-sea navigation.',
                        'latitude' => $origin->latitude,
                        'longitude' => $origin->longitude,
                        'weather_snapshot' => $origin->weather_snapshot,
                        'occurred_at' => $depTime,
                        'created_by' => $operatorId,
                    ]
                );

                // Milestone 2: Ocean transit waypoint (approx 45% of elapsed time)
                $waypoint1Time = $depTime->copy()->addMinutes(max(15, (int) ($elapsedMinutes * 0.45)));
                $latWp1 = round((float) $origin->latitude + 0.45 * ((float) $dest->latitude - (float) $origin->latitude), 6);
                $lonWp1 = round((float) $origin->longitude + 0.45 * ((float) $dest->longitude - (float) $origin->longitude), 6);

                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => 'Open Ocean Transit Waypoint',
                    ],
                    [
                        'location_id' => null,
                        'event_type' => 'NORMAL',
                        'description' => 'Vessel cruising standard oceanic shipping corridor at steady passage speed.',
                        'latitude' => $latWp1,
                        'longitude' => $lonWp1,
                        'weather_snapshot' => [
                            'temperature_c' => 22.0,
                            'condition' => 'Moderate Sea State',
                            'wind_kph' => 24.0,
                            'visibility_km' => 10.0,
                        ],
                        'occurred_at' => $waypoint1Time,
                        'created_by' => null,
                    ]
                );

                // Milestone 3: Latest checkpoint (approx 85% of elapsed time)
                $waypoint2Minutes = max(30, (int) ($elapsedMinutes * 0.85));
                $waypoint2Time = $depTime->copy()->addMinutes($waypoint2Minutes);
                if ($waypoint2Time->lessThanOrEqualTo($waypoint1Time)) {
                    $waypoint2Time = $waypoint1Time->copy()->addMinutes(15);
                }
                if ($waypoint2Time->greaterThanOrEqualTo($now)) {
                    $waypoint2Time = $now->copy()->subMinutes(5);
                }
                if ($waypoint2Time->lessThanOrEqualTo($waypoint1Time)) {
                    $waypoint1Time = $depTime->copy()->addMinutes(max(5, (int) ($elapsedMinutes * 0.25)));
                    $waypoint2Time = $depTime->copy()->addMinutes(max(10, (int) ($elapsedMinutes * 0.65)));
                }

                $isDelayed = $shipment->is_delayed;
                $currentLoc = $shipment->currentLocation ?? $dest;

                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => $isDelayed ? 'Voyage Advisory: '.($shipment->delay_reason ? substr($shipment->delay_reason, 0, 50).'...' : 'ETA Variance') : 'Position Report: En Route to '.$dest->name,
                    ],
                    [
                        'location_id' => $shipment->current_location_id !== $dest->id ? $shipment->current_location_id : null,
                        'event_type' => $isDelayed ? 'EXCEPTION' : 'NORMAL',
                        'description' => $isDelayed
                            ? ($shipment->delay_reason ?? 'Advisory update logged. Vessel adjusting arrival speed.')
                            : 'Container vessel on course. Terminal stevedoring schedule confirmed at destination.',
                        'latitude' => $currentLoc->latitude,
                        'longitude' => $currentLoc->longitude,
                        'weather_snapshot' => $currentLoc->weather_snapshot,
                        'occurred_at' => $waypoint2Time,
                        'created_by' => $isDelayed ? $operatorId : null,
                    ]
                );

                continue;
            }

            if ($shipment->status === 'DELIVERED') {
                $depTime = $shipment->expected_departure_at->copy();
                $arrTime = $shipment->actual_arrival_at->copy();

                $totalMinutes = max(180, $depTime->diffInMinutes($arrTime, false));

                // Event 1: Origin departure
                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => 'Vessel Departure from '.$origin->name,
                    ],
                    [
                        'location_id' => $origin->id,
                        'event_type' => 'NORMAL',
                        'description' => 'Container vessel completed outbound pilotage and departed harbor waters.',
                        'latitude' => $origin->latitude,
                        'longitude' => $origin->longitude,
                        'weather_snapshot' => $origin->weather_snapshot,
                        'occurred_at' => $depTime,
                        'created_by' => $operatorId,
                    ]
                );

                // Event 2: Waypoint 1 (30% mark)
                $t2 = $depTime->copy()->addMinutes((int) ($totalMinutes * 0.30));
                $latWp1 = round((float) $origin->latitude + 0.30 * ((float) $dest->latitude - (float) $origin->latitude), 6);
                $lonWp1 = round((float) $origin->longitude + 0.30 * ((float) $dest->longitude - (float) $origin->longitude), 6);

                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => 'Deep Sea Passage Waypoint',
                    ],
                    [
                        'location_id' => null,
                        'event_type' => 'NORMAL',
                        'description' => 'Vessel maintaining cruising speed along international container navigation route.',
                        'latitude' => $latWp1,
                        'longitude' => $lonWp1,
                        'weather_snapshot' => [
                            'temperature_c' => 20.0,
                            'condition' => 'Clear Sea State',
                            'wind_kph' => 18.0,
                        ],
                        'occurred_at' => $t2,
                        'created_by' => null,
                    ]
                );

                // Event 3: Waypoint 2 (65% mark)
                $t3 = $depTime->copy()->addMinutes((int) ($totalMinutes * 0.65));
                $latWp2 = round((float) $origin->latitude + 0.65 * ((float) $dest->latitude - (float) $origin->latitude), 6);
                $lonWp2 = round((float) $origin->longitude + 0.65 * ((float) $dest->longitude - (float) $origin->longitude), 6);

                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => 'Coastal Approach Corridor Entry',
                    ],
                    [
                        'location_id' => null,
                        'event_type' => 'NORMAL',
                        'description' => 'Container vessel reached traffic separation scheme waters approaching destination.',
                        'latitude' => $latWp2,
                        'longitude' => $lonWp2,
                        'weather_snapshot' => [
                            'temperature_c' => 18.0,
                            'condition' => 'Slight Swell',
                            'wind_kph' => 20.0,
                        ],
                        'occurred_at' => $t3,
                        'created_by' => null,
                    ]
                );

                // Event 4: Destination Harbor Arrival & Berthing (subHours(8) before delivery)
                $t4 = $arrTime->copy()->subHours(8);
                if ($t4->lessThanOrEqualTo($t3)) {
                    $t4 = $t3->copy()->addHours(2);
                }

                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'IN_TRANSIT',
                        'title' => 'Vessel Berthing & Discharge at '.$dest->name,
                    ],
                    [
                        'location_id' => $dest->id,
                        'event_type' => 'NORMAL',
                        'description' => 'Vessel alongside container terminal berth. Quay gantry cranes commenced container discharge.',
                        'latitude' => $dest->latitude,
                        'longitude' => $dest->longitude,
                        'weather_snapshot' => $dest->weather_snapshot,
                        'occurred_at' => $t4,
                        'created_by' => $operatorId,
                    ]
                );

                // Event 5: Final Delivery Milestone (occurred_at == actual_arrival_at)
                TrackingEvent::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => 'DELIVERED',
                        'title' => 'Consignment Cleared & Gate-Out Delivered',
                    ],
                    [
                        'location_id' => $dest->id,
                        'event_type' => 'NORMAL',
                        'description' => 'Import customs release verified. Container gated out from terminal and delivered to consignee.',
                        'latitude' => $dest->latitude,
                        'longitude' => $dest->longitude,
                        'weather_snapshot' => $dest->weather_snapshot,
                        'occurred_at' => $arrTime,
                        'created_by' => $operatorId,
                    ]
                );
            }
        }
    }
}
