<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TrackingEventSeeder extends Seeder
{
    /**
     * Seed chronologically valid, mode-specific tracking events across SEA, AIR, and ROAD.
     *
     * Invariants enforced:
     * - Strictly non-decreasing occurred_at per shipment.
     * - No event before expected_departure_at for IN_TRANSIT or DELIVERED shipments.
     * - DELIVERED shipments always end with a DELIVERED event at destination_location_id
     *   where occurred_at == actual_arrival_at.
     * - Event location_id (when non-null) strictly matches shipment transport_mode.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $operatorId = $operator?->id;
        $now = Carbon::now();
        $locations = Location::all()->keyBy('unlocode');

        $shipments = Shipment::with(['originLocation', 'destinationLocation', 'currentLocation'])->get();

        foreach ($shipments as $shipment) {
            $mode = strtoupper($shipment->transport_mode ?? 'SEA');
            $origin = $shipment->originLocation;
            $dest = $shipment->destinationLocation;

            if (in_array($shipment->status, ['CREATED', 'BOOKED'], true)) {
                $this->seedPendingEvents($shipment, $origin, $mode, $operatorId, $now);

                continue;
            }

            if ($shipment->status === 'IN_TRANSIT') {
                $this->seedInTransitEvents($shipment, $origin, $dest, $mode, $locations, $operatorId, $now);

                continue;
            }

            if ($shipment->status === 'DELIVERED') {
                $this->seedDeliveredEvents($shipment, $origin, $dest, $mode, $locations, $operatorId);
            }
        }
    }

    protected function seedPendingEvents(Shipment $shipment, Location $origin, string $mode, ?int $operatorId, Carbon $now): void
    {
        $title = match ($mode) {
            'AIR' => 'AWB Booked & Cargo Accepted at '.$origin->name,
            'ROAD' => 'Consignment Scheduled & Staged at '.$origin->name,
            default => 'Gate-In & Container Staged at '.$origin->name,
        };

        $desc = match ($mode) {
            'AIR' => 'Air waybill manifested. Spice cargo screened and staged in temperature-controlled airfreight bay.',
            'ROAD' => 'CMR consignment note issued. Sealed spice pallets staged for haulier loading.',
            default => 'Ocean bill of lading manifested. Export container gated in and stacked for vessel loading.',
        };

        TrackingEvent::firstOrCreate(
            [
                'shipment_id' => $shipment->id,
                'status' => $shipment->status,
                'title' => $title,
            ],
            [
                'location_id' => $origin->id,
                'event_type' => 'NORMAL',
                'description' => $desc,
                'latitude' => $origin->latitude,
                'longitude' => $origin->longitude,
                'weather_snapshot' => $origin->weather_snapshot,
                'occurred_at' => $shipment->created_at ?? $now->copy()->subDays(1),
                'created_by' => $operatorId,
            ]
        );
    }

    protected function seedInTransitEvents(
        Shipment $shipment,
        Location $origin,
        Location $dest,
        string $mode,
        $locations,
        ?int $operatorId,
        Carbon $now
    ): void {
        $depTime = $shipment->expected_departure_at->copy();
        $elapsedMinutes = max(120, $depTime->diffInMinutes($now, false));
        $currentLoc = $shipment->currentLocation ?? $origin;

        $t1 = $depTime->copy();
        $t2 = $depTime->copy()->addMinutes(max(30, (int) ($elapsedMinutes * 0.20)));
        $t3 = $depTime->copy()->addMinutes(max(60, (int) ($elapsedMinutes * 0.60)));
        $t4 = $depTime->copy()->addMinutes(max(90, (int) ($elapsedMinutes * 0.88)));

        if ($t4->greaterThanOrEqualTo($now)) {
            $t4 = $now->copy()->subMinutes(5);
        }

        if ($mode === 'SEA') {
            $this->createEvent($shipment, $origin, 'PICKED_UP', 'NORMAL', 'Gate-In & Loaded on Vessel at '.$origin->name,
                'Export container cleared customs gate-in and loaded aboard container vessel.', $t1, $operatorId);

            $this->createEvent($shipment, $origin, 'IN_TRANSIT', 'NORMAL', 'Vessel Departed '.$origin->name,
                'Vessel cleared port pilotage and commenced deep-sea spice corridor passage.', $t2, $operatorId);

            $hub = $this->pickHub($origin, $dest, $currentLoc, $locations, ['SGSIN', 'AEJEA'], 'PORT');
            if ($hub) {
                $this->createEvent($shipment, $hub, 'IN_TRANSIT', 'NORMAL', 'Transshipment Transfer at '.$hub->name,
                    'Container discharged from feeder and transferred to mainline deep-sea vessel.', $t3, $operatorId);
            } else {
                [$lat, $lng] = $this->interpolate($origin, $dest, 0.55);
                $this->createWaypoint($shipment, 'IN_TRANSIT', 'NORMAL', 'Sea Passage Waypoint En Route to '.$dest->city,
                    'Vessel maintaining scheduled speed along international maritime lane.', $lat, $lng, $t3, [
                        'temperature_c' => 26.0,
                        'condition' => 'Moderate Sea State',
                        'wind_kph' => 21.0,
                        'visibility_km' => 12.0,
                    ]);
            }

            $loc4 = $this->isLocationValidForMode($currentLoc, 'SEA') ? $currentLoc : null;
            $this->createEvent(
                $shipment,
                $loc4,
                'IN_TRANSIT',
                $shipment->is_delayed ? 'EXCEPTION' : 'NORMAL',
                $shipment->is_delayed ? 'Maritime Advisory: '.$this->shortReason($shipment) : 'Vessel En Route to '.$dest->name,
                $shipment->is_delayed ? ($shipment->delay_reason ?? 'Schedule variance logged at port control.') : 'Container vessel tracking normally toward discharge port.',
                $t4,
                $shipment->is_delayed ? $operatorId : null,
                $loc4 ? null : $this->interpolate($origin, $dest, 0.85)
            );

            return;
        }

        if ($mode === 'AIR') {
            $this->createEvent($shipment, $origin, 'PICKED_UP', 'NORMAL', 'Received at Origin Airport '.$origin->iata_code,
                'Essential oil flasks and spice air parcels weighed, security screened, and built into ULD.', $t1, $operatorId);

            $this->createEvent($shipment, $origin, 'IN_TRANSIT', 'NORMAL', 'Departed on Cargo Flight from '.$origin->iata_code,
                'Air cargo freighter departed origin runway under validated AWB manifest.', $t2, $operatorId);

            $hub = $this->pickHub($origin, $dest, $currentLoc, $locations, ['SGCHG', 'AEDXB', 'DEFRA'], 'AIRPORT');
            if ($hub) {
                $this->createEvent($shipment, $hub, 'IN_TRANSIT', 'NORMAL', 'Air Transit Hub Screening at '.$hub->iata_code,
                    'ULD transferred at airfreight transit mega-hub awaiting onward widebody uplift.', $t3, $operatorId);
            } else {
                [$lat, $lng] = $this->interpolate($origin, $dest, 0.50);
                $this->createWaypoint($shipment, 'IN_TRANSIT', 'NORMAL', 'Airway Transit Cruise Toward '.$dest->iata_code,
                    'Flight operating along high-altitude international cargo corridor.', $lat, $lng, $t3, [
                        'temperature_c' => 18.0,
                        'condition' => 'Pressurized Cargo Hold',
                        'wind_kph' => 0.0,
                        'visibility_km' => 16.0,
                    ]);
            }

            $loc4 = $this->isLocationValidForMode($currentLoc, 'AIR') ? $currentLoc : null;
            $this->createEvent(
                $shipment,
                $loc4,
                'IN_TRANSIT',
                $shipment->is_delayed ? 'EXCEPTION' : 'NORMAL',
                $shipment->is_delayed ? 'Flight Hold Advisory: '.$this->shortReason($shipment) : 'Onward Flight Segment to '.$dest->iata_code,
                $shipment->is_delayed ? ($shipment->delay_reason ?? 'Airfreight connection variance logged.') : 'Cargo ULD confirmed on connecting flight segment.',
                $t4,
                $shipment->is_delayed ? $operatorId : null,
                $loc4 ? null : $this->interpolate($origin, $dest, 0.85)
            );

            return;
        }

        // ROAD
        $this->createEvent($shipment, $origin, 'PICKED_UP', 'NORMAL', 'Loaded at Depot '.$origin->name,
            'Spice pallets and essential oil drums loaded onto sealed box truck under CMR note.', $t1, $operatorId);

        $this->createEvent($shipment, $origin, 'IN_TRANSIT', 'NORMAL', 'Departed by Road from '.$origin->city,
            'Vehicle departed origin facility with security seal and data logger active.', $t2, $operatorId);

        $borderLoc = ($origin->country_code === 'NL' && $dest->country_code === 'DE') ? ($locations['NLVEN'] ?? null) : null;
        if ($borderLoc) {
            $this->createEvent($shipment, $borderLoc, 'IN_TRANSIT', $shipment->is_delayed ? 'EXCEPTION' : 'NORMAL',
                'Border Crossing Inspection at '.$borderLoc->name,
                $shipment->is_delayed ? ($shipment->delay_reason ?? 'Agricultural inspection queue at NL/DE border.') : 'Cross-border EU transit documents verified at Venlo.',
                $t3, $operatorId);
        } else {
            [$lat, $lng] = $this->interpolate($origin, $dest, 0.50);
            $this->createWaypoint($shipment, 'IN_TRANSIT', 'NORMAL', 'Highway Corridor Checkpoint en route to '.$dest->city,
                'Vehicle progressing along scheduled regional freight highway corridor.', $lat, $lng, $t3, [
                    'temperature_c' => 27.0,
                    'condition' => 'Clear Highway Conditions',
                    'wind_kph' => 12.0,
                    'visibility_km' => 12.0,
                ]);
        }

        $loc4 = $this->isLocationValidForMode($currentLoc, 'ROAD') ? $currentLoc : null;
        $this->createEvent(
            $shipment,
            $loc4,
            'IN_TRANSIT',
            $shipment->is_delayed ? 'EXCEPTION' : 'NORMAL',
            $shipment->is_delayed ? 'Road Delay Advisory: '.$this->shortReason($shipment) : 'Approaching Destination '.$dest->city,
            $shipment->is_delayed ? ($shipment->delay_reason ?? 'Road transport delay logged.') : 'Truck approaching destination warehouse gate.',
            $t4,
            $shipment->is_delayed ? $operatorId : null,
            $loc4 ? null : $this->interpolate($origin, $dest, 0.82)
        );
    }

    protected function seedDeliveredEvents(
        Shipment $shipment,
        Location $origin,
        Location $dest,
        string $mode,
        $locations,
        ?int $operatorId
    ): void {
        $depTime = $shipment->expected_departure_at->copy();
        $arrTime = $shipment->actual_arrival_at->copy();
        $totalMinutes = max(240, $depTime->diffInMinutes($arrTime, false));

        $t1 = $depTime->copy();
        $t2 = $depTime->copy()->addMinutes(max(30, (int) ($totalMinutes * 0.10)));
        $t3 = $depTime->copy()->addMinutes(max(60, (int) ($totalMinutes * 0.50)));
        $t4 = $depTime->copy()->addMinutes(max(90, (int) ($totalMinutes * 0.85)));
        $t5 = $depTime->copy()->addMinutes(max(120, (int) ($totalMinutes * 0.94)));
        $t6 = $arrTime->copy();

        if ($mode === 'SEA') {
            $this->createEvent($shipment, $origin, 'PICKED_UP', 'NORMAL', 'Gate-In & Loaded on Vessel at '.$origin->name,
                'Container gated in at origin terminal and stowed aboard scheduled container ship.', $t1, $operatorId);

            $this->createEvent($shipment, $origin, 'IN_TRANSIT', 'NORMAL', 'Vessel Departed '.$origin->name,
                'Ship cast off lines and cleared departure channel.', $t2, $operatorId);

            $hub = $this->pickHub($origin, $dest, null, $locations, ['SGSIN', 'AEJEA'], 'PORT');
            if ($hub) {
                $this->createEvent($shipment, $hub, 'IN_TRANSIT', 'NORMAL', 'Transshipment Call at '.$hub->name,
                    'Container completed intermediate transshipment connection without moisture ingress.', $t3, $operatorId);
            } else {
                [$lat, $lng] = $this->interpolate($origin, $dest, 0.50);
                $this->createWaypoint($shipment, 'IN_TRANSIT', 'NORMAL', 'Deep-Sea Passage Midpoint',
                    'Vessel transiting oceanic corridor on course to destination.', $lat, $lng, $t3, [
                        'temperature_c' => 24.0,
                        'condition' => 'Moderate Sea State',
                        'wind_kph' => 19.0,
                    ]);
            }

            $this->createEvent($shipment, $dest, 'IN_TRANSIT', 'NORMAL', 'Vessel Arrived at '.$dest->name,
                'Vessel berthed alongside container terminal quay.', $t4, $operatorId);

            $this->createEvent($shipment, $dest, 'IN_TRANSIT', 'NORMAL', 'Container Discharged & Customs Cleared at '.$dest->name,
                'Gantry crane discharged container; phytosanitary and import customs release granted.', $t5, $operatorId);

            $this->createEvent($shipment, $dest, 'DELIVERED', 'NORMAL', 'Delivered to Consignee at '.$dest->name,
                'Container gated out from terminal and delivered to spice buyer facility.', $t6, $operatorId);

            return;
        }

        if ($mode === 'AIR') {
            $this->createEvent($shipment, $origin, 'PICKED_UP', 'NORMAL', 'Received at Origin Airport '.$origin->iata_code,
                'Essential oil cargo accepted at origin airfreight terminal after security screening.', $t1, $operatorId);

            $this->createEvent($shipment, $origin, 'IN_TRANSIT', 'NORMAL', 'Departed on Flight from '.$origin->iata_code,
                'Cargo aircraft departed origin airport.', $t2, $operatorId);

            $hub = $this->pickHub($origin, $dest, null, $locations, ['SGCHG', 'AEDXB', 'DEFRA'], 'AIRPORT');
            if ($hub) {
                $this->createEvent($shipment, $hub, 'IN_TRANSIT', 'NORMAL', 'Transit Flight Connection at '.$hub->iata_code,
                    'ULD transferred seamlessly to connecting widebody cargo flight.', $t3, $operatorId);
            } else {
                [$lat, $lng] = $this->interpolate($origin, $dest, 0.50);
                $this->createWaypoint($shipment, 'IN_TRANSIT', 'NORMAL', 'International Airway Transit',
                    'Freighter cruising along scheduled flight corridor.', $lat, $lng, $t3, [
                        'temperature_c' => 18.0,
                        'condition' => 'Pressurized Hold',
                        'wind_kph' => 0.0,
                    ]);
            }

            $this->createEvent($shipment, $dest, 'IN_TRANSIT', 'NORMAL', 'Arrived at Destination Airport '.$dest->iata_code,
                'Cargo aircraft landed and ULD transferred to import airfreight warehouse.', $t4, $operatorId);

            $this->createEvent($shipment, $dest, 'IN_TRANSIT', 'NORMAL', 'Customs Cleared at '.$dest->iata_code,
                'Import customs and botanical entry clearance completed.', $t5, $operatorId);

            $this->createEvent($shipment, $dest, 'DELIVERED', 'NORMAL', 'Air Cargo Delivered at '.$dest->name,
                'Consignee agent collected flasks and signed air waybill delivery receipt.', $t6, $operatorId);

            return;
        }

        // ROAD
        $this->createEvent($shipment, $origin, 'PICKED_UP', 'NORMAL', 'Loaded at Depot '.$origin->name,
            'Vehicle loaded and CMR consignment note signed at origin depot.', $t1, $operatorId);

        $this->createEvent($shipment, $origin, 'IN_TRANSIT', 'NORMAL', 'Departed by Road from '.$origin->city,
            'Truck departed on scheduled road corridor.', $t2, $operatorId);

        [$lat, $lng] = $this->interpolate($origin, $dest, 0.50);
        $this->createWaypoint($shipment, 'IN_TRANSIT', 'NORMAL', 'Regional Highway & Border Checkpoint',
            'Seal verified intact at mid-route checkpoint along road haul corridor.', $lat, $lng, $t3, [
                'temperature_c' => 25.0,
                'condition' => 'Clear Road',
                'wind_kph' => 10.0,
            ]);

        $this->createEvent($shipment, $dest, 'IN_TRANSIT', 'NORMAL', 'Arrived at '.$dest->name,
            'Vehicle arrived at destination unloading bay.', $t4, $operatorId);

        $this->createEvent($shipment, $dest, 'DELIVERED', 'NORMAL', 'Delivered & POD Signed at '.$dest->name,
            'Spice consignment offloaded, weight checked, and proof of delivery signed.', $t6, $operatorId);
    }

    /**
     * @param  array<int, string>  $candidates
     */
    protected function pickHub(Location $origin, Location $dest, ?Location $current, $locations, array $candidates, string $requiredType): ?Location
    {
        if ($current && $current->id !== $origin->id && $current->id !== $dest->id && $current->type === $requiredType) {
            return $current;
        }

        foreach ($candidates as $code) {
            $hub = $locations[$code] ?? null;
            if ($hub && $hub->id !== $origin->id && $hub->id !== $dest->id && $hub->type === $requiredType) {
                return $hub;
            }
        }

        return null;
    }

    protected function isLocationValidForMode(?Location $loc, string $mode): bool
    {
        if (! $loc) {
            return false;
        }

        $type = strtoupper($loc->type);

        return match (strtoupper($mode)) {
            'SEA' => in_array($type, ['PORT', 'SEAPORT'], true),
            'AIR' => $type === 'AIRPORT',
            default => true,
        };
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function interpolate(Location $origin, Location $dest, float $ratio): array
    {
        $lat = round((float) $origin->latitude + $ratio * ((float) $dest->latitude - (float) $origin->latitude), 6);
        $lng = round((float) $origin->longitude + $ratio * ((float) $dest->longitude - (float) $origin->longitude), 6);

        return [$lat, $lng];
    }

    protected function shortReason(Shipment $shipment): string
    {
        $reason = trim((string) ($shipment->delay_reason ?? 'ETA Variance'));

        return strlen($reason) > 48 ? substr($reason, 0, 48).'...' : $reason;
    }

    /**
     * @param  array{0: float, 1: float}|null  $fallbackCoords
     */
    protected function createEvent(
        Shipment $shipment,
        ?Location $location,
        string $status,
        string $eventType,
        string $title,
        string $description,
        Carbon $occurredAt,
        ?int $createdBy,
        ?array $fallbackCoords = null
    ): void {
        TrackingEvent::firstOrCreate(
            [
                'shipment_id' => $shipment->id,
                'title' => $title,
            ],
            [
                'location_id' => $location?->id,
                'status' => $status,
                'event_type' => $eventType,
                'description' => $description,
                'latitude' => $location?->latitude ?? ($fallbackCoords[0] ?? null),
                'longitude' => $location?->longitude ?? ($fallbackCoords[1] ?? null),
                'weather_snapshot' => $location?->weather_snapshot,
                'occurred_at' => $occurredAt,
                'created_by' => $createdBy,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $weather
     */
    protected function createWaypoint(
        Shipment $shipment,
        string $status,
        string $eventType,
        string $title,
        string $description,
        float $lat,
        float $lng,
        Carbon $occurredAt,
        array $weather
    ): void {
        TrackingEvent::firstOrCreate(
            [
                'shipment_id' => $shipment->id,
                'title' => $title,
            ],
            [
                'location_id' => null,
                'status' => $status,
                'event_type' => $eventType,
                'description' => $description,
                'latitude' => $lat,
                'longitude' => $lng,
                'weather_snapshot' => $weather,
                'occurred_at' => $occurredAt,
                'created_by' => null,
            ]
        );
    }
}
