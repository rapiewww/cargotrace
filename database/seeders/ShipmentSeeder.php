<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Support\Awb;
use App\Support\Iso6346;
use App\Support\RoadReference;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    /**
     * Offline FX rates relative to USD (matches DatabaseSeeder cache).
     *
     * @var array<string, float>
     */
    protected const FX_RATES_TO_USD = [
        'USD' => 1.0,
        'EUR' => 1.086957,
        'INR' => 0.011905,
        'IDR' => 0.000063,
        'SGD' => 0.746269,
        'AED' => 0.272480,
        'MYR' => 0.224719,
    ];

    /**
     * Airline 3-digit AWB prefixes (mark in docs/DEMO_DATA.md for verification).
     *
     * @var array<string, string>
     */
    protected const AWB_PREFIXES = [
        'GIA' => '126',
        'SQC' => '618',
        'EKC' => '176',
        'QRC' => '157',
        'LHC' => '020',
    ];

    /**
     * Commodity catalog for Indonesian spices and essential oils.
     *
     * @var array<string, array{name: string, hs_code: string, packaging: string, haz: bool}>
     */
    protected const COMMODITIES = [
        'nutmeg' => [
            'name' => 'Whole Nutmeg ABCD Grade (Banda & North Maluku)',
            'hs_code' => '0908.11',
            'packaging' => '50 kg polypropylene woven sacks',
            'haz' => false,
        ],
        'cloves' => [
            'name' => 'Whole Cloves Lal Pari Hand-Picked Grade',
            'hs_code' => '0907.10',
            'packaging' => '50 kg double jute gunny bags',
            'haz' => false,
        ],
        'cinnamon' => [
            'name' => 'Sumatra Cassia Cinnamon Quills (Korintje Grade A)',
            'hs_code' => '0906.11',
            'packaging' => '25 kg kraft-lined export cartons',
            'haz' => false,
        ],
        'pepper' => [
            'name' => 'Lampung Black Pepper Whole ASTA 570',
            'hs_code' => '0904.11',
            'packaging' => '50 kg moisture-barrier lined bags',
            'haz' => false,
        ],
        'ginger' => [
            'name' => 'Java Dried Sliced Ginger Root (Jahe Emprit)',
            'hs_code' => '0910.11',
            'packaging' => '25 kg laminated woven sacks on pallets',
            'haz' => false,
        ],
        'patchouli' => [
            'name' => 'Aceh Steam-Distilled Patchouli Essential Oil (PA 32%)',
            'hs_code' => '3301.29',
            'packaging' => '200 kg galvanized steel drums / 25 kg aluminum flasks',
            'haz' => false,
        ],
    ];

    /**
     * Seed 48 multimodal spice and essential oil shipments across the last 10 weeks:
     * 22 SEA, 14 AIR, 12 ROAD | 6 CREATED/BOOKED, 22 IN_TRANSIT, 20 DELIVERED.
     */
    public function run(): void
    {
        $customers = [
            'c1' => User::where('email', 'customer@cargotrace.test')->firstOrFail(),
            'c2' => User::where('email', 'customer2@cargotrace.test')->firstOrFail(),
            'c3' => User::where('email', 'customer3@cargotrace.test')->firstOrFail(),
            'c4' => User::where('email', 'customer4@cargotrace.test')->firstOrFail(),
            'c5' => User::where('email', 'customer5@cargotrace.test')->firstOrFail(),
            'c6' => User::where('email', 'customer6@cargotrace.test')->firstOrFail(),
        ];

        $carriers = Carrier::all()->keyBy('code');
        $locations = Location::all()->keyBy('unlocode');
        $now = Carbon::now();
        $week0Start = $now->copy()->startOfWeek();

        // Helper closure to produce deterministic timestamps anchored in the last 10 weeks with varied minutes.
        $atWeekOffset = function (int $weeksAgo, int $dayOffset, int $hour = 9, int $minute = 17) use ($week0Start, $now): Carbon {
            $dt = $week0Start->copy()->subWeeks($weeksAgo)->addDays($dayOffset)->setTime($hour, $minute % 60, 0);
            if ($dt->greaterThan($now->copy()->subHours(2))) {
                return $now->copy()->subHours(2 + ($weeksAgo + $dayOffset))->setMinute($minute % 60)->setSecond(0);
            }

            return $dt;
        };

        $seaSerial = 201417;
        $airSerial = 603812;
        $roadSerial = 419;

        $shipments = $this->buildDataset($now, $atWeekOffset);

        foreach ($shipments as $idx => $spec) {
            $mode = $spec['mode'];
            $carrier = $carriers[$spec['carrier']] ?? Carrier::where('mode', $mode)->firstOrFail();
            $origin = $locations[$spec['origin']];
            $dest = $locations[$spec['destination']];
            $curr = $locations[$spec['current']];
            $customer = $customers[$spec['customer']];

            $currency = $spec['currency'];
            $rateToUsd = self::FX_RATES_TO_USD[$currency] ?? 1.0;
            $convertedUsd = round($spec['declared_value'] * $rateToUsd, 2);

            $delayHours = (float) ($spec['delay_hours'] ?? 0.0);
            $delaySeverity = $this->computeSeverity($mode, $delayHours);
            $isDelayed = $delayHours > 0;

            // Spread minutes deterministically across 0..59 so ETAs and events do not cluster on one minute.
            $minCreated = ($idx * 13 + 7) % 60;
            $minDep = ($idx * 19 + 11) % 60;
            $minExpArr = ($idx * 23 + 17) % 60;
            $minActArr = ($idx * 29 + 3) % 60;

            $createdAt = $spec['created_at']->copy()->setMinute($minCreated)->setSecond(0);
            $expDep = $spec['expected_departure']->copy()->setMinute($minDep)->setSecond(0);
            $expArr = $spec['expected_arrival']->copy()->setMinute($minExpArr)->setSecond(0);
            $actArr = $spec['actual_arrival'] ? $spec['actual_arrival']->copy()->setMinute($minActArr)->setSecond(0) : null;

            if ($actArr && $delayHours > 0) {
                $expArr = $actArr->copy()->subMinutes((int) round($delayHours * 60));
            }
            $estArr = $actArr ? $actArr->copy() : ($delayHours > 0 ? $expArr->copy()->addMinutes((int) round($delayHours * 60)) : $expArr->copy());

            if (in_array($spec['status'], ['IN_TRANSIT', 'DELIVERED'], true) && $createdAt->greaterThan($expDep)) {
                $createdAt = $expDep->copy()->subHours(6)->setMinute($minCreated)->setSecond(0);
            }

            $containerNum = null;
            $blNum = null;
            $awbNum = null;
            $vehiclePlate = null;
            $cmrNum = null;
            $refNum = null;

            if ($mode === 'SEA') {
                $containerNum = Iso6346::generate($carrier->code, $seaSerial);
                $blNum = RoadReference::billOfLading($carrier->code, 2026, $seaSerial);
                $refNum = $blNum;
            } elseif ($mode === 'AIR') {
                $prefix = self::AWB_PREFIXES[$carrier->code] ?? '126';
                $awbNum = Awb::generate($prefix, $airSerial);
                $refNum = Awb::format($awbNum);
                $airSerial += 29;
            } else {
                $vehiclePlate = RoadReference::vehiclePlate($spec['plate_region'] ?? 'BK', $roadSerial);
                $cmrNum = RoadReference::cmrNumber(2026, $roadSerial);
                $refNum = $cmrNum;
                $roadSerial += 23;
            }

            $routeNews = null;
            if ($isDelayed && ! empty($spec['delay_reason'])) {
                $routeNews = [
                    'source' => 'Nusa Spice Freight Operations Desk',
                    'headline' => 'Corridor Advisory: '.$spec['delay_reason'],
                    'impact' => 'Schedule variance: +'.number_format($delayHours, 1)." hours ({$delaySeverity}).",
                    'published_at' => $createdAt->copy()->addHours(12)->toIso8601String(),
                ];
            }

            $currencySnapshot = [
                'base' => 'USD',
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.92,
                    'INR' => 84.0,
                    'IDR' => 15873.0,
                    'SGD' => 1.34,
                    'AED' => 3.67,
                    'MYR' => 4.45,
                ],
                'origin_currency' => $currency,
                'destination_currency' => $dest->currency_code,
                'rate_origin_to_usd' => $rateToUsd,
                'rate_usd_to_dest' => self::FX_RATES_TO_USD[$dest->currency_code] ?? 1.0,
                'recorded_at' => $expDep->toIso8601String(),
            ];

            $shipment = Shipment::firstOrCreate(
                ['tracking_number' => $spec['tracking_number']],
                [
                    'transport_mode' => $mode,
                    'reference_number' => $refNum,
                    'container_number' => $containerNum,
                    'bill_of_lading_number' => $blNum,
                    'awb_number' => $awbNum,
                    'vehicle_plate' => $vehiclePlate,
                    'cmr_number' => $cmrNum,
                    'customer_id' => $customer->id,
                    'carrier_id' => $carrier->id,
                    'origin_location_id' => $origin->id,
                    'destination_location_id' => $dest->id,
                    'current_location_id' => $curr->id,
                    'cargo_type' => $spec['cargo_type'],
                    'total_weight_kg' => $spec['weight_kg'],
                    'total_volume_cbm' => $spec['volume_cbm'],
                    'declared_value' => $spec['declared_value'],
                    'currency_code' => $currency,
                    'shipping_cost' => $spec['shipping_cost'],
                    'exchange_rate_to_usd' => $rateToUsd,
                    'converted_value_usd' => $convertedUsd,
                    'currency_snapshot' => $currencySnapshot,
                    'status' => $spec['status'],
                    'expected_departure_at' => $expDep,
                    'expected_arrival_at' => $expArr,
                    'estimated_arrival_at' => $estArr,
                    'actual_arrival_at' => $actArr,
                    'is_delayed' => $isDelayed,
                    'delay_hours' => $delayHours,
                    'delay_severity' => $delaySeverity,
                    'delay_reason' => $spec['delay_reason'] ?? null,
                    'route_news_snapshot' => $routeNews,
                    'notes' => $spec['notes'],
                ]
            );

            $shipment->created_at = $createdAt;
            $shipment->updated_at = $actArr ?? ($expDep->isPast() ? $now->copy()->subHours(2)->setMinute($minExpArr)->setSecond(0) : $createdAt);
            $shipment->saveQuietly();

            foreach ($spec['items'] as $itemIdx => $itemSpec) {
                $commodity = self::COMMODITIES[$itemSpec['commodity']];

                if ($mode === 'SEA') {
                    $itemContainer = $itemIdx === 0 ? $containerNum : Iso6346::generate($carrier->code, $seaSerial);
                    $seaSerial += 11;
                    $sku = $itemContainer;
                } elseif ($mode === 'AIR') {
                    $itemContainer = null;
                    $sku = 'AIR-'.$carrier->code.'-'.sprintf('%06d', $airSerial + $itemIdx);
                } else {
                    $itemContainer = null;
                    $sku = 'ROAD-'.$carrier->code.'-'.sprintf('%06d', ($idx + 1) * 13 + $itemIdx);
                }

                ShipmentItem::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'sku' => $sku,
                    ],
                    [
                        'container_number' => $itemContainer,
                        'hs_code' => $commodity['hs_code'],
                        'description' => $itemSpec['description'] ?? $commodity['name'],
                        'packaging' => $itemSpec['packaging'] ?? $commodity['packaging'],
                        'quantity' => $itemSpec['quantity'],
                        'unit_value' => $itemSpec['unit_value'],
                        'currency_code' => $currency,
                        'weight_kg' => $itemSpec['weight_kg'],
                        'volume_cbm' => $itemSpec['volume_cbm'],
                        'is_hazardous' => $commodity['haz'],
                    ]
                );
            }

            if ($mode === 'SEA') {
                $seaSerial += 37;
            }
        }
    }

    /**
     * Compute deterministic delay severity matching EtaService per-mode thresholds:
     * AIR 3/12h, ROAD 6/24h, SEA 12/48h.
     */
    protected function computeSeverity(string $mode, float $delayHours): string
    {
        if ($delayHours <= 0) {
            return 'NONE';
        }

        [$low, $medium] = match (strtoupper($mode)) {
            'AIR' => [3.0, 12.0],
            'ROAD' => [6.0, 24.0],
            default => [12.0, 48.0],
        };

        return match (true) {
            $delayHours <= $low => 'LOW',
            $delayHours <= $medium => 'MEDIUM',
            default => 'HIGH',
        };
    }

    /**
     * Build the 48 deterministic synthetic spice freight consignments.
     *
     * @return list<array<string, mixed>>
     */
    protected function buildDataset(Carbon $now, callable $atWeekOffset): array
    {
        $rows = [];

        // Helper for SEA shipments
        $sea = function (
            string $tracking,
            string $customer,
            string $carrier,
            string $origin,
            string $dest,
            string $current,
            string $status,
            Carbon $createdAt,
            Carbon $expDep,
            Carbon $expArr,
            ?Carbon $actArr,
            float $delayHours,
            ?string $delayReason,
            string $currency,
            float $declaredValue,
            float $shippingCost,
            float $weightKg,
            float $volumeCbm,
            string $cargoType,
            string $incoterm,
            array $items,
            ?string $notes = null
        ): array {
            $estArr = $actArr ? $actArr->copy() : ($delayHours > 0 ? $expArr->copy()->addMinutes((int) round($delayHours * 60)) : $expArr->copy());

            return [
                'tracking_number' => $tracking,
                'mode' => 'SEA',
                'customer' => $customer,
                'carrier' => $carrier,
                'origin' => $origin,
                'destination' => $dest,
                'current' => $current,
                'status' => $status,
                'created_at' => $createdAt,
                'expected_departure' => $expDep,
                'expected_arrival' => $expArr,
                'estimated_arrival' => $estArr,
                'actual_arrival' => $actArr,
                'delay_hours' => $delayHours,
                'delay_reason' => $delayReason,
                'currency' => $currency,
                'declared_value' => $declaredValue,
                'shipping_cost' => $shippingCost,
                'weight_kg' => $weightKg,
                'volume_cbm' => $volumeCbm,
                'cargo_type' => $cargoType,
                'notes' => ($notes ?? "Incoterm: {$incoterm}. Phytosanitary and Certificate of Origin verified by Nusa Spice Freight."),
                'items' => $items,
            ];
        };

        // Helper for AIR shipments
        $air = function (
            string $tracking,
            string $customer,
            string $carrier,
            string $origin,
            string $dest,
            string $current,
            string $status,
            Carbon $createdAt,
            Carbon $expDep,
            Carbon $expArr,
            ?Carbon $actArr,
            float $delayHours,
            ?string $delayReason,
            string $currency,
            float $declaredValue,
            float $shippingCost,
            float $weightKg,
            float $volumeCbm,
            string $incoterm,
            array $items,
            ?string $notes = null
        ): array {
            $estArr = $actArr ? $actArr->copy() : ($delayHours > 0 ? $expArr->copy()->addMinutes((int) round($delayHours * 60)) : $expArr->copy());

            return [
                'tracking_number' => $tracking,
                'mode' => 'AIR',
                'customer' => $customer,
                'carrier' => $carrier,
                'origin' => $origin,
                'destination' => $dest,
                'current' => $current,
                'status' => $status,
                'created_at' => $createdAt,
                'expected_departure' => $expDep,
                'expected_arrival' => $expArr,
                'estimated_arrival' => $estArr,
                'actual_arrival' => $actArr,
                'delay_hours' => $delayHours,
                'delay_reason' => $delayReason,
                'currency' => $currency,
                'declared_value' => $declaredValue,
                'shipping_cost' => $shippingCost,
                'weight_kg' => $weightKg,
                'volume_cbm' => $volumeCbm,
                'cargo_type' => 'AIR_FREIGHT',
                'notes' => ($notes ?? "Incoterm: {$incoterm}. GC-MS assay report and COA attached for airfreight screening."),
                'items' => $items,
            ];
        };

        // Helper for ROAD shipments
        $road = function (
            string $tracking,
            string $customer,
            string $carrier,
            string $origin,
            string $dest,
            string $current,
            string $status,
            string $plateRegion,
            Carbon $createdAt,
            Carbon $expDep,
            Carbon $expArr,
            ?Carbon $actArr,
            float $delayHours,
            ?string $delayReason,
            string $currency,
            float $declaredValue,
            float $shippingCost,
            float $weightKg,
            float $volumeCbm,
            string $incoterm,
            array $items,
            ?string $notes = null
        ): array {
            $estArr = $actArr ? $actArr->copy() : ($delayHours > 0 ? $expArr->copy()->addMinutes((int) round($delayHours * 60)) : $expArr->copy());

            return [
                'tracking_number' => $tracking,
                'mode' => 'ROAD',
                'customer' => $customer,
                'carrier' => $carrier,
                'origin' => $origin,
                'destination' => $dest,
                'current' => $current,
                'status' => $status,
                'plate_region' => $plateRegion,
                'created_at' => $createdAt,
                'expected_departure' => $expDep,
                'expected_arrival' => $expArr,
                'estimated_arrival' => $estArr,
                'actual_arrival' => $actArr,
                'delay_hours' => $delayHours,
                'delay_reason' => $delayReason,
                'currency' => $currency,
                'declared_value' => $declaredValue,
                'shipping_cost' => $shippingCost,
                'weight_kg' => $weightKg,
                'volume_cbm' => $volumeCbm,
                'cargo_type' => 'ROAD',
                'notes' => ($notes ?? "Incoterm: {$incoterm}. Sealed box truck with temperature and humidity logger."),
                'items' => $items,
            ];
        };

        // =====================================================================
        // CANONICAL PRESERVED SHIPMENTS (3)
        // =====================================================================
        // 1. CTG-SEA-2026-00189: IDJKT -> NLRTM [SEA, DELIVERED LATE +10h = LOW]
        $rows[] = $sea(
            'CTG-SEA-2026-00189', 'c1', 'MAEU', 'IDJKT', 'NLRTM', 'NLRTM', 'DELIVERED',
            $now->copy()->subDays(36),
            $now->copy()->subDays(34),
            $now->copy()->subDays(5),
            $now->copy()->subDays(5)->addHours(10),
            10.0,
            'North Sea squall front delayed Maasvlakte pilot boarding by 10 hours.',
            'IDR', 1450000000.0, 78500000.0, 24500.0, 62.0, 'FCL', 'CIF Rotterdam',
            [
                ['commodity' => 'nutmeg', 'description' => '40ft Dry Container: Whole Nutmeg ABCD Grade (Banda & Maluku)', 'quantity' => 280, 'unit_value' => 3200000.0, 'weight_kg' => 14000.0, 'volume_cbm' => 34.0],
                ['commodity' => 'cloves', 'description' => '20ft Dry Container: Whole Cloves Lal Pari Hand-Picked Grade', 'quantity' => 210, 'unit_value' => 2638095.0, 'weight_kg' => 10500.0, 'volume_cbm' => 28.0],
            ],
            'Incoterm: CIF Rotterdam. Export phytosanitary certificate and Indonesia COO issued at Tanjung Priok.'
        );

        // 2. CTG-SEA-2026-00244: IDBLW -> USNYC [SEA, IN_TRANSIT DELAYED +56h = HIGH, +2d over 5d free time]
        $rows[] = $sea(
            'CTG-SEA-2026-00244', 'c4', 'MSCU', 'IDBLW', 'USNYC', 'USNYC', 'IN_TRANSIT',
            $now->copy()->subDays(32),
            $now->copy()->subDays(29),
            $now->copy()->subDays(9)->subHours(13),
            $now->copy()->subDays(7)->subHours(5),
            56.0,
            'Port Newark USDA and CBP intensive agricultural examination hold on cassia lot.',
            'USD', 142000.0, 6400.0, 21800.0, 58.5, 'FCL', 'CIF New York',
            [
                ['commodity' => 'cinnamon', 'description' => '40ft HC Container: Sumatra Cassia Cinnamon Quills (Korintje Grade A)', 'quantity' => 520, 'unit_value' => 175.0, 'weight_kg' => 13000.0, 'volume_cbm' => 36.5],
                ['commodity' => 'pepper', 'description' => '20ft Dry Container: Lampung Black Pepper Whole ASTA 570', 'quantity' => 176, 'unit_value' => 289.77, 'weight_kg' => 8800.0, 'volume_cbm' => 22.0],
            ],
            'Incoterm: CIF New York. Discharged at Port Newark; FDA Prior Notice filed, awaiting CET lab release.'
        );

        // 3. CTG-AIR-2026-00678: KNO -> CDG [AIR, IN_TRANSIT DELAYED +2.5h = LOW]
        $rows[] = $air(
            'CTG-AIR-2026-00678', 'c3', 'SQC', 'KNO', 'CDG', 'SIN', 'IN_TRANSIT',
            $now->copy()->subDays(2)->subHours(6),
            $now->copy()->subHours(16),
            $now->copy()->addHours(6),
            null,
            2.5,
            'Equatorial squall at Changi Airfreight Centre metered freighter departure.',
            'EUR', 68500.0, 4150.0, 620.0, 2.4, 'CIP Paris CDG',
            [
                ['commodity' => 'patchouli', 'description' => 'Aceh Steam-Distilled Patchouli Essential Oil (25 kg aluminum flasks)', 'packaging' => '24 aluminum flasks in UN plywood crates', 'quantity' => 24, 'unit_value' => 2854.17, 'weight_kg' => 620.0, 'volume_cbm' => 2.4],
            ],
            'Incoterm: CIP Paris CDG. Bound for Grasse fragrance extraction lab via Changi.'
        );

        // =====================================================================
        // REMAINING SEA SHIPMENTS (20 more = 22 SEA total)
        // =====================================================================
        // SEA #3 (Week 9 - old delivered on-time, c5)
        $rows[] = $sea(
            'CTG-SEA-2026-00114', 'c5', 'HLCU', 'IDJKT', 'INNSA', 'INNSA', 'DELIVERED',
            $atWeekOffset(9, 1, 9), $atWeekOffset(9, 3, 10), $now->copy()->subDays(46), $now->copy()->subDays(46)->subHours(4), 0.0, null,
            'INR', 6450000.0, 210000.0, 19200.0, 48.0, 'FCL', 'CFR Nhava Sheva',
            [['commodity' => 'cloves', 'quantity' => 384, 'unit_value' => 16796.88, 'weight_kg' => 19200.0, 'volume_cbm' => 48.0]]
        );

        // SEA #4 (Week 8 - delivered on-time, c2) -> carries RESOLVED DAMAGED
        $rows[] = $sea(
            'CTG-SEA-2026-00137', 'c2', 'CMDU', 'IDBLW', 'NLRTM', 'NLRTM', 'DELIVERED',
            $atWeekOffset(8, 2, 11), $atWeekOffset(8, 4, 8), $now->copy()->subDays(34), $now->copy()->subDays(34)->subHours(2), 0.0, null,
            'EUR', 118000.0, 4900.0, 22400.0, 56.0, 'FCL', 'CIF Rotterdam',
            [['commodity' => 'nutmeg', 'quantity' => 448, 'unit_value' => 263.39, 'weight_kg' => 22400.0, 'volume_cbm' => 56.0]]
        );

        // SEA #5 (Week 7 - delivered on-time, c6)
        $rows[] = $sea(
            'CTG-SEA-2026-00162', 'c6', 'ONEY', 'IDJKT', 'AEJEA', 'AEJEA', 'DELIVERED',
            $atWeekOffset(7, 1, 10), $atWeekOffset(7, 3, 14), $now->copy()->subDays(32), $now->copy()->subDays(32)->subHours(3), 0.0, null,
            'USD', 89000.0, 3200.0, 18500.0, 46.0, 'FCL', 'CIF Jebel Ali',
            [['commodity' => 'pepper', 'quantity' => 370, 'unit_value' => 240.54, 'weight_kg' => 18500.0, 'volume_cbm' => 46.0]]
        );

        // SEA #6 (Week 7 - delivered LATE +30h MEDIUM, c2) -> carries RESOLVED CUSTOMS_HOLD 48h!
        $rows[] = $sea(
            'CTG-SEA-2026-00218', 'c2', 'MAEU', 'IDBLW', 'DEHAM', 'DEHAM', 'DELIVERED',
            $atWeekOffset(7, 3, 9), $atWeekOffset(7, 5, 10), $now->copy()->subDays(11)->subHours(6), $now->copy()->subDays(10), 30.0,
            'Hamburg phytosanitary pallet inspection delayed container release by 30 hours.',
            'EUR', 132000.0, 5100.0, 23000.0, 60.0, 'FCL', 'CIF Hamburg',
            [['commodity' => 'cinnamon', 'quantity' => 920, 'unit_value' => 143.48, 'weight_kg' => 23000.0, 'volume_cbm' => 60.0]]
        );

        // SEA #7 (Week 6 - delivered on-time, c3) -> carries RESOLVED DOCUMENTATION_ISSUE
        $rows[] = $sea(
            'CTG-SEA-2026-00279', 'c3', 'CMDU', 'IDJKT', 'FRMRS', 'FRMRS', 'DELIVERED',
            $atWeekOffset(6, 1, 8), $atWeekOffset(6, 3, 12), $now->copy()->subDays(18), $now->copy()->subDays(18)->subHours(3), 0.0, null,
            'EUR', 154000.0, 4700.0, 16800.0, 42.0, 'FCL', 'CIF Marseille-Fos',
            [['commodity' => 'patchouli', 'description' => 'Aceh Patchouli Essential Oil in 200 kg Steel Drums', 'packaging' => '84 steel drums on strapped pallets', 'quantity' => 84, 'unit_value' => 1833.33, 'weight_kg' => 16800.0, 'volume_cbm' => 42.0]]
        );

        // SEA #8 (Week 6 - delivered on-time, c4)
        $rows[] = $sea(
            'CTG-SEA-2026-00315', 'c4', 'HLCU', 'IDJKT', 'USSAV', 'USSAV', 'DELIVERED',
            $atWeekOffset(6, 2, 13), $atWeekOffset(6, 4, 15), $now->copy()->subDays(14), $now->copy()->subDays(14)->subHours(5), 0.0, null,
            'USD', 112500.0, 5400.0, 20400.0, 54.0, 'FCL', 'CIF Savannah',
            [['commodity' => 'ginger', 'quantity' => 816, 'unit_value' => 137.87, 'weight_kg' => 20400.0, 'volume_cbm' => 54.0]]
        );

        // SEA #9 (Week 5 - delivered on-time, c1)
        $rows[] = $sea(
            'CTG-SEA-2026-00348', 'c1', 'ONEY', 'IDBLW', 'MYPKG', 'MYPKG', 'DELIVERED',
            $atWeekOffset(5, 1, 10), $atWeekOffset(5, 2, 14), $now->copy()->subDays(31), $now->copy()->subDays(31)->subHours(1), 0.0, null,
            'IDR', 690000000.0, 24000000.0, 14200.0, 32.0, 'LCL', 'CFR Port Klang',
            [['commodity' => 'pepper', 'quantity' => 284, 'unit_value' => 2429577.46, 'weight_kg' => 14200.0, 'volume_cbm' => 32.0]]
        );

        // SEA #10 (Week 4 - in-transit on-time, c2, Singapore-consolidated nutmeg leg)
        $rows[] = $sea(
            'CTG-SEA-2026-00391', 'c2', 'MAEU', 'SGSIN', 'NLRTM', 'AEJEA', 'IN_TRANSIT',
            $atWeekOffset(4, 1, 9), $now->copy()->subDays(22), $now->copy()->addDays(6), null, 0.0, null,
            'EUR', 124000.0, 4650.0, 22000.0, 58.0, 'FCL', 'CIF Rotterdam',
            [['commodity' => 'nutmeg', 'quantity' => 440, 'unit_value' => 281.82, 'weight_kg' => 22000.0, 'volume_cbm' => 58.0]]
        );

        // SEA #11 (Week 3 - in-transit arrived 2d ago under limit, c5)
        $rows[] = $sea(
            'CTG-SEA-2026-00426', 'c5', 'ONEY', 'IDBLW', 'INNSA', 'INNSA', 'IN_TRANSIT',
            $atWeekOffset(3, 1, 10), $now->copy()->subDays(14), $now->copy()->subDays(2)->subHours(2), $now->copy()->subDays(2)->subHours(4), 0.0, null,
            'INR', 7250000.0, 195000.0, 19600.0, 50.0, 'FCL', 'CIF Nhava Sheva',
            [['commodity' => 'cloves', 'quantity' => 392, 'unit_value' => 18494.90, 'weight_kg' => 19600.0, 'volume_cbm' => 50.0]]
        );

        // SEA #12 (Week 4 - in-transit DELAYED +28h MEDIUM, arrived 9d ago -> +4d over limit, c6) -> carries OPEN PORT_CONGESTION
        $rows[] = $sea(
            'CTG-SEA-2026-00509', 'c6', 'MSCU', 'IDJKT', 'AEJEA', 'AEJEA', 'IN_TRANSIT',
            $atWeekOffset(4, 2, 11), $now->copy()->subDays(25), $now->copy()->subDays(10)->subHours(7), $now->copy()->subDays(9)->subHours(3), 28.0,
            'Jebel Ali Terminal 2 berth congestion delayed vessel mooring and gate-out.',
            'USD', 96000.0, 3450.0, 18900.0, 49.0, 'FCL', 'CIF Jebel Ali',
            [['commodity' => 'cinnamon', 'quantity' => 756, 'unit_value' => 126.98, 'weight_kg' => 18900.0, 'volume_cbm' => 49.0]]
        );

        // SEA #13 (Week 3 - in-transit arrived 4d ago under limit, c3)
        $rows[] = $sea(
            'CTG-SEA-2026-00463', 'c3', 'CMDU', 'IDBLW', 'FRMRS', 'FRMRS', 'IN_TRANSIT',
            $atWeekOffset(3, 4, 9), $now->copy()->subDays(21), $now->copy()->subDays(4)->subHours(3), $now->copy()->subDays(4)->subHours(6), 0.0, null,
            'EUR', 162000.0, 5100.0, 17500.0, 44.0, 'FCL', 'CIF Marseille-Fos',
            [['commodity' => 'patchouli', 'description' => 'Aceh Patchouli Essential Oil in 200 kg Drums', 'packaging' => '88 steel drums on strapped pallets', 'quantity' => 88, 'unit_value' => 1840.91, 'weight_kg' => 17500.0, 'volume_cbm' => 44.0]]
        );

        // SEA #14 (Week 5 - DELIVERED LATE +64h HIGH, c2) -> carries RESOLVED MISROUTED
        $rows[] = $sea(
            'CTG-SEA-2026-00582', 'c2', 'HLCU', 'IDBLW', 'DEHAM', 'DEHAM', 'DELIVERED',
            $atWeekOffset(5, 2, 14), $now->copy()->subDays(31), $now->copy()->subDays(14)->subHours(16), $now->copy()->subDays(12), 64.0,
            'Container staged to wrong feeder stack at Singapore transshipment yard.',
            'EUR', 114000.0, 4800.0, 21200.0, 55.0, 'FCL', 'CIF Hamburg',
            [['commodity' => 'pepper', 'quantity' => 424, 'unit_value' => 268.87, 'weight_kg' => 21200.0, 'volume_cbm' => 55.0]]
        );

        // SEA #15 (Week 2 - in-transit on-time, c4)
        $rows[] = $sea(
            'CTG-SEA-2026-00547', 'c4', 'MAEU', 'IDJKT', 'USSAV', 'SGSIN', 'IN_TRANSIT',
            $atWeekOffset(2, 3, 10), $now->copy()->subDays(10), $now->copy()->addDays(18), null, 0.0, null,
            'USD', 136000.0, 6100.0, 22600.0, 60.0, 'FCL', 'CIF Savannah',
            [['commodity' => 'nutmeg', 'quantity' => 452, 'unit_value' => 300.88, 'weight_kg' => 22600.0, 'volume_cbm' => 60.0]]
        );

        // SEA #16 (Week 1 - in-transit DELAYED +8h LOW, c1)
        $rows[] = $sea(
            'CTG-SEA-2026-00619', 'c1', 'ONEY', 'IDJKT', 'SGSIN', 'MYPKG', 'IN_TRANSIT',
            $atWeekOffset(1, 0, 8), $now->copy()->subDays(4), $now->copy()->addDays(1), null, 8.0,
            'Port Klang feeder bunkering stop extended Malacca Strait transit by 8 hours.',
            'IDR', 540000000.0, 19500000.0, 15000.0, 36.0, 'LCL', 'FOB Tanjung Priok',
            [['commodity' => 'ginger', 'quantity' => 600, 'unit_value' => 900000.0, 'weight_kg' => 15000.0, 'volume_cbm' => 36.0]]
        );

        // SEA #17 (Week 1 - in-transit on-time, c5)
        $rows[] = $sea(
            'CTG-SEA-2026-00654', 'c5', 'MSCU', 'IDJKT', 'INNSA', 'SGSIN', 'IN_TRANSIT',
            $atWeekOffset(1, 1, 13), $now->copy()->subDays(5), $now->copy()->addDays(7), null, 0.0, null,
            'INR', 5800000.0, 175000.0, 16500.0, 41.0, 'FCL', 'CFR Nhava Sheva',
            [['commodity' => 'nutmeg', 'quantity' => 330, 'unit_value' => 17575.76, 'weight_kg' => 16500.0, 'volume_cbm' => 41.0]]
        );

        // SEA #18 (Week 1 - in-transit on-time, c6)
        $rows[] = $sea(
            'CTG-SEA-2026-00703', 'c6', 'HLCU', 'IDBLW', 'AEJEA', 'MYPKG', 'IN_TRANSIT',
            $atWeekOffset(1, 2, 11), $now->copy()->subDays(4), $now->copy()->addDays(9), null, 0.0, null,
            'USD', 84500.0, 3100.0, 17800.0, 45.0, 'FCL', 'CIF Jebel Ali',
            [['commodity' => 'cloves', 'quantity' => 356, 'unit_value' => 237.36, 'weight_kg' => 17800.0, 'volume_cbm' => 45.0]]
        );

        // SEA #19 (Week 0 - in-transit on-time, c4)
        $rows[] = $sea(
            'CTG-SEA-2026-00741', 'c4', 'CMDU', 'IDJKT', 'USNYC', 'SGSIN', 'IN_TRANSIT',
            $now->copy()->subDays(2), $now->copy()->subHours(28), $now->copy()->addDays(26), null, 0.0, null,
            'USD', 148000.0, 6500.0, 23200.0, 61.0, 'FCL', 'CIF New York',
            [['commodity' => 'cinnamon', 'quantity' => 928, 'unit_value' => 159.48, 'weight_kg' => 23200.0, 'volume_cbm' => 61.0]]
        );

        // SEA #20 (Week 1 - CREATED, c1)
        $rows[] = $sea(
            'CTG-SEA-2026-00788', 'c1', 'MAEU', 'IDBLW', 'NLRTM', 'IDBLW', 'CREATED',
            $atWeekOffset(1, 4, 15), $now->copy()->addDays(3), $now->copy()->addDays(31), null, 0.0, null,
            'IDR', 1280000000.0, 68000000.0, 21000.0, 56.0, 'FCL', 'FOB Belawan',
            [['commodity' => 'nutmeg', 'quantity' => 420, 'unit_value' => 3047619.05, 'weight_kg' => 21000.0, 'volume_cbm' => 56.0]]
        );

        // SEA #21 (Week 0 - BOOKED, c3)
        $rows[] = $sea(
            'CTG-SEA-2026-00836', 'c3', 'CMDU', 'IDJKT', 'FRMRS', 'IDJKT', 'BOOKED',
            $now->copy()->subDays(1), $now->copy()->addDays(2), $now->copy()->addDays(27), null, 0.0, null,
            'EUR', 105000.0, 4400.0, 14800.0, 38.0, 'FCL', 'FOB Tanjung Priok',
            [['commodity' => 'cloves', 'quantity' => 296, 'unit_value' => 354.73, 'weight_kg' => 14800.0, 'volume_cbm' => 38.0]]
        );

        // SEA #22 (Week 0 - BOOKED, c5)
        $rows[] = $sea(
            'CTG-SEA-2026-00892', 'c5', 'ONEY', 'IDBLW', 'INNSA', 'IDBLW', 'BOOKED',
            $now->copy()->subDays(2), $now->copy()->addDays(4), $now->copy()->addDays(16), null, 0.0, null,
            'INR', 6100000.0, 185000.0, 18000.0, 46.0, 'FCL', 'FOB Belawan',
            [['commodity' => 'pepper', 'quantity' => 360, 'unit_value' => 16944.44, 'weight_kg' => 18000.0, 'volume_cbm' => 46.0]]
        );

        // =====================================================================
        // AIR SHIPMENTS (13 more = 14 AIR total)
        // =====================================================================
        // AIR #2 (Week 6 - delivered on-time, c3)
        $rows[] = $air(
            'CTG-AIR-2026-00142', 'c3', 'LHC', 'CGK', 'FRA', 'FRA', 'DELIVERED',
            $atWeekOffset(6, 3, 10), $now->copy()->subDays(41), $now->copy()->subDays(40), $now->copy()->subDays(40)->subHours(1), 0.0, null,
            'EUR', 54000.0, 3400.0, 480.0, 1.8, 'CIP Frankfurt',
            [['commodity' => 'patchouli', 'description' => 'Aceh Patchouli Essential Oil (25 kg aluminum flasks)', 'packaging' => '19 aluminum flasks in UN crates', 'quantity' => 19, 'unit_value' => 2842.11, 'weight_kg' => 480.0, 'volume_cbm' => 1.8]]
        );

        // AIR #3 (Week 5 - delivered LATE +2.0h LOW, c2) -> carries RESOLVED DOCUMENTATION_ISSUE
        $rows[] = $air(
            'CTG-AIR-2026-00195', 'c2', 'SQC', 'CGK', 'AMS', 'AMS', 'DELIVERED',
            $atWeekOffset(5, 2, 11), $now->copy()->subDays(34), $now->copy()->subDays(33), $now->copy()->subDays(33)->addHours(2), 2.0,
            'Schiphol apron slot hold delayed freighter unloading by 2 hours.',
            'EUR', 41200.0, 2900.0, 520.0, 2.1, 'CIP Amsterdam',
            [['commodity' => 'cloves', 'description' => 'Whole Clove Buds Export Grade Samples', 'packaging' => '26 foil-lined cartons', 'quantity' => 26, 'unit_value' => 1584.62, 'weight_kg' => 520.0, 'volume_cbm' => 2.1]]
        );

        // AIR #4 (Week 4 - delivered on-time, c6)
        $rows[] = $air(
            'CTG-AIR-2026-00261', 'c6', 'EKC', 'CGK', 'DXB', 'DXB', 'DELIVERED',
            $atWeekOffset(4, 2, 14), $now->copy()->subDays(27), $now->copy()->subDays(26), $now->copy()->subDays(26)->subHours(1), 0.0, null,
            'USD', 38500.0, 2400.0, 640.0, 2.6, 'CIP Dubai DXB',
            [['commodity' => 'nutmeg', 'description' => 'Graded Banda Nutmeg & Mace Air Urgent Lot', 'packaging' => '32 sealed export cartons', 'quantity' => 32, 'unit_value' => 1203.13, 'weight_kg' => 640.0, 'volume_cbm' => 2.6]]
        );

        // AIR #5 (Week 3 - delivered LATE +9.0h MEDIUM, c5)
        $rows[] = $air(
            'CTG-AIR-2026-00328', 'c5', 'GIA', 'KNO', 'BOM', 'BOM', 'DELIVERED',
            $atWeekOffset(3, 3, 9), $now->copy()->subDays(20), $now->copy()->subDays(19), $now->copy()->subDays(19)->addHours(9), 9.0,
            'Connecting airfreight rotation hold via Jakarta CGK.',
            'INR', 2950000.0, 145000.0, 410.0, 1.5, 'CIP Mumbai BOM',
            [['commodity' => 'patchouli', 'description' => 'Aceh Patchouli Oil Rectified Lot', 'packaging' => '16 aluminum flasks', 'quantity' => 16, 'unit_value' => 184375.0, 'weight_kg' => 410.0, 'volume_cbm' => 1.5]]
        );

        // AIR #6 (Week 2 - delivered on-time, c4)
        $rows[] = $air(
            'CTG-AIR-2026-00384', 'c4', 'QRC', 'CGK', 'JFK', 'JFK', 'DELIVERED',
            $atWeekOffset(2, 1, 10), $now->copy()->subDays(13), $now->copy()->subDays(12), $now->copy()->subDays(12)->subHours(1), 0.0, null,
            'USD', 74000.0, 5200.0, 780.0, 3.1, 'CIP New York JFK',
            [['commodity' => 'patchouli', 'description' => 'High-Purity Patchouli Oil for US Botanicals', 'packaging' => '30 aluminum flasks in UN crates', 'quantity' => 30, 'unit_value' => 2466.67, 'weight_kg' => 780.0, 'volume_cbm' => 3.1]]
        );

        // AIR #7 (Week 1 - in-transit DELAYED +16.0h HIGH, c3) -> carries INVESTIGATING FLIGHT_OFFLOAD
        $rows[] = $air(
            'CTG-AIR-2026-00451', 'c3', 'EKC', 'KNO', 'CDG', 'DXB', 'IN_TRANSIT',
            $atWeekOffset(1, 3, 10), $now->copy()->subDays(2), $now->copy()->subHours(4), null, 16.0,
            'Cargo pallet offloaded at DXB transit hub due to aircraft weight-and-balance limits.',
            'EUR', 61000.0, 3900.0, 540.0, 2.2, 'CIP Paris CDG',
            [['commodity' => 'patchouli', 'description' => 'Sumatra Patchouli & Clove Leaf Oil Assay Lot', 'packaging' => '22 aluminum flasks', 'quantity' => 22, 'unit_value' => 2772.73, 'weight_kg' => 540.0, 'volume_cbm' => 2.2]]
        );

        // AIR #8 (Week 1 - in-transit DELAYED +20.0h HIGH, arrived 6d ago -> +1d over limit, c2)
        $rows[] = $air(
            'CTG-AIR-2026-00593', 'c2', 'LHC', 'CGK', 'AMS', 'AMS', 'IN_TRANSIT',
            $atWeekOffset(2, 0, 11), $now->copy()->subDays(9), $now->copy()->subDays(7)->subHours(3), $now->copy()->subDays(6)->subHours(7), 20.0,
            'EU phytosanitary and GC-MS certificate check at Schiphol import terminal.',
            'EUR', 49500.0, 3250.0, 460.0, 1.9, 'CIP Amsterdam',
            [['commodity' => 'cinnamon', 'description' => 'Organic Cassia Essential Oil & Bark Samples', 'packaging' => '20 UN-certified drums', 'quantity' => 20, 'unit_value' => 2475.0, 'weight_kg' => 460.0, 'volume_cbm' => 1.9]]
        );

        // AIR #9 (Week 2 - DELIVERED LATE +7.5h MEDIUM, c6) -> carries RESOLVED PORT_CONGESTION
        $rows[] = $air(
            'CTG-AIR-2026-00642', 'c6', 'QRC', 'BTJ', 'DXB', 'DXB', 'DELIVERED',
            $atWeekOffset(2, 2, 9), $now->copy()->subDays(10), $now->copy()->subDays(8)->subHours(12), $now->copy()->subDays(8)->subHours(4), 7.5,
            'Peak-hour cargo ramp congestion at Changi freighter apron.',
            'USD', 44800.0, 2850.0, 390.0, 1.6, 'CIP Dubai DXB',
            [['commodity' => 'patchouli', 'description' => 'Banda Aceh First-Distillation Patchouli Oil', 'packaging' => '16 aluminum flasks', 'quantity' => 16, 'unit_value' => 2800.0, 'weight_kg' => 390.0, 'volume_cbm' => 1.6]]
        );

        // AIR #10 (Week 0 - in-transit arrived 1d ago under limit, c5)
        $rows[] = $air(
            'CTG-AIR-2026-00517', 'c5', 'SQC', 'KNO', 'BOM', 'BOM', 'IN_TRANSIT',
            $now->copy()->subDays(4), $now->copy()->subDays(3), $now->copy()->subDays(1)->subHours(6), $now->copy()->subDays(1)->subHours(9), 0.0, null,
            'INR', 2450000.0, 128000.0, 350.0, 1.4, 'CIP Mumbai BOM',
            [['commodity' => 'cloves', 'description' => 'High-Eugenol Clove Bud Oil Urgent Batch', 'packaging' => '14 aluminum flasks', 'quantity' => 14, 'unit_value' => 175000.0, 'weight_kg' => 350.0, 'volume_cbm' => 1.4]]
        );

        // AIR #11 (Week 0 - in-transit on-time, c4)
        $rows[] = $air(
            'CTG-AIR-2026-00729', 'c4', 'LHC', 'CGK', 'JFK', 'FRA', 'IN_TRANSIT',
            $now->copy()->subDays(2), $now->copy()->subHours(20), $now->copy()->addHours(10), null, 0.0, null,
            'USD', 58000.0, 4300.0, 610.0, 2.5, 'CIP New York JFK',
            [['commodity' => 'ginger', 'description' => 'Java Dried Ginger Oleoresin & Sliced Root', 'packaging' => '25 sealed export drums', 'quantity' => 25, 'unit_value' => 2320.0, 'weight_kg' => 610.0, 'volume_cbm' => 2.5]]
        );

        // AIR #12 (Week 0 - in-transit on-time, c1)
        $rows[] = $air(
            'CTG-AIR-2026-00814', 'c1', 'GIA', 'BTJ', 'SIN', 'CGK', 'IN_TRANSIT',
            $now->copy()->subDays(1), $now->copy()->subHours(8), $now->copy()->addHours(6), null, 0.0, null,
            'IDR', 485000000.0, 22000000.0, 320.0, 1.2, 'CIP Singapore SIN',
            [['commodity' => 'patchouli', 'description' => 'Aceh Patchouli Oil Export Pre-Lot', 'packaging' => '12 aluminum flasks', 'quantity' => 12, 'unit_value' => 40416666.67, 'weight_kg' => 320.0, 'volume_cbm' => 1.2]]
        );

        // AIR #13 (Week 0 - CREATED, c6)
        $rows[] = $air(
            'CTG-AIR-2026-00867', 'c6', 'EKC', 'CGK', 'DXB', 'CGK', 'CREATED',
            $now->copy()->subDays(1), $now->copy()->addHours(18), $now->copy()->addDays(2), null, 0.0, null,
            'USD', 36000.0, 2100.0, 280.0, 1.1, 'FCA Jakarta CGK',
            [['commodity' => 'pepper', 'description' => 'Lampung White & Black Pepper Sample Air Lot', 'packaging' => '14 vacuum cartons', 'quantity' => 14, 'unit_value' => 2571.43, 'weight_kg' => 280.0, 'volume_cbm' => 1.1]]
        );

        // AIR #14 (Week 0 - BOOKED, c6)
        $rows[] = $air(
            'CTG-AIR-2026-00931', 'c6', 'QRC', 'KNO', 'DXB', 'KNO', 'BOOKED',
            $now->copy()->subDays(2), $now->copy()->addDays(1), $now->copy()->addDays(2)->addHours(12), null, 0.0, null,
            'USD', 56800.0, 3600.0, 440.0, 1.7, 'FCA Medan KNO',
            [['commodity' => 'nutmeg', 'description' => 'Aceh Essential Nutmeg Oil Airfreight', 'packaging' => '18 aluminum flasks', 'quantity' => 18, 'unit_value' => 3155.56, 'weight_kg' => 440.0, 'volume_cbm' => 1.7]]
        );

        // =====================================================================
        // ROAD SHIPMENTS (12 ROAD total)
        // =====================================================================
        // ROAD #1 (Week 5 - delivered on-time, Lhokseumawe -> Medan depot, c1)
        $rows[] = $road(
            'CTG-ROAD-2026-00127', 'c1', 'SMTR', 'DEP-LSW', 'DEP-MES', 'DEP-MES', 'DELIVERED', 'BL',
            $atWeekOffset(5, 3, 8), $now->copy()->subDays(36), $now->copy()->subDays(35), $now->copy()->subDays(35)->subHours(1), 0.0, null,
            'IDR', 420000000.0, 6800000.0, 6200.0, 18.0, 'EXW Lhokseumawe',
            [['commodity' => 'patchouli', 'description' => 'Aceh Patchouli Oil Drums for Medan Consolidation', 'packaging' => '31 steel drums', 'quantity' => 31, 'unit_value' => 13548387.10, 'weight_kg' => 6200.0, 'volume_cbm' => 18.0]]
        );

        // ROAD #2 (Week 4 - delivered LATE +5.0h LOW, Aceh depot -> Belawan port, c1)
        $rows[] = $road(
            'CTG-ROAD-2026-00204', 'c1', 'BKTJ', 'DEP-ACE', 'IDBLW', 'IDBLW', 'DELIVERED', 'BK',
            $atWeekOffset(4, 3, 7), $now->copy()->subDays(28), $now->copy()->subDays(27), $now->copy()->subDays(27)->addHours(5), 5.0,
            'Heavy monsoon rain along East Sumatra Highway slowed convoy speed.',
            'IDR', 385000000.0, 7400000.0, 8400.0, 22.0, 'FCA Port of Belawan',
            [['commodity' => 'nutmeg', 'description' => 'Aceh Nutmeg Sacks for Belawan Container Stuffing', 'packaging' => '168 polypropylene sacks', 'quantity' => 168, 'unit_value' => 2291666.67, 'weight_kg' => 8400.0, 'volume_cbm' => 22.0]]
        );

        // ROAD #3 (Week 3 - delivered on-time, Marseille-Fos -> Grasse depot, c3)
        $rows[] = $road(
            'CTG-ROAD-2026-00289', 'c3', 'PRVF', 'FRMRS', 'DEP-GRS', 'DEP-GRS', 'DELIVERED', 'FR',
            $atWeekOffset(3, 4, 9), $now->copy()->subDays(21), $now->copy()->subDays(20), $now->copy()->subDays(20)->subHours(1), 0.0, null,
            'EUR', 78000.0, 1450.0, 4200.0, 12.5, 'DAP Grasse Depot',
            [['commodity' => 'patchouli', 'description' => 'Last-Mile Aromatics Delivery from Marseille-Fos to Grasse', 'packaging' => '21 steel drums', 'quantity' => 21, 'unit_value' => 3714.29, 'weight_kg' => 4200.0, 'volume_cbm' => 12.5]]
        );

        // ROAD #4 (Week 2 - delivered LATE +14.0h MEDIUM, Nhava Sheva -> CFS, c5) -> carries RESOLVED DAMAGED
        $rows[] = $road(
            'CTG-ROAD-2026-00356', 'c5', 'KHND', 'INNSA', 'DEP-NSC', 'DEP-NSC', 'DELIVERED', 'IN',
            $atWeekOffset(2, 4, 8), $now->copy()->subDays(13), $now->copy()->subDays(12), $now->copy()->subDays(12)->addHours(14), 14.0,
            'Port gate drayage queue at Nhava Sheva delayed container transfer to CFS.',
            'INR', 3100000.0, 48000.0, 9600.0, 26.0, 'DAP Nhava Sheva CFS',
            [['commodity' => 'cloves', 'description' => 'Clove Gunny Bags Port-to-CFS Bonded Drayage', 'packaging' => '192 jute gunny bags', 'quantity' => 192, 'unit_value' => 16145.83, 'weight_kg' => 9600.0, 'volume_cbm' => 26.0]]
        );

        // ROAD #5 (Week 2 - delivered on-time, Rotterdam Port -> Rotterdam Depot, c2)
        $rows[] = $road(
            'CTG-ROAD-2026-00419', 'c2', 'RHNL', 'NLRTM', 'DEP-RTM', 'DEP-RTM', 'DELIVERED', 'NL',
            $atWeekOffset(2, 5, 10), $now->copy()->subDays(11), $now->copy()->subDays(10), $now->copy()->subDays(10)->subHours(2), 0.0, null,
            'EUR', 66000.0, 980.0, 7800.0, 20.5, 'DAP Rotterdam Depot',
            [['commodity' => 'pepper', 'description' => 'Lampung Black Pepper Pallets Maasvlakte Shuttle', 'packaging' => '156 lined sacks on Euro-pallets', 'quantity' => 156, 'unit_value' => 423.08, 'weight_kg' => 7800.0, 'volume_cbm' => 20.5]]
        );

        // ROAD #6 (Week 1 - in-transit DELAYED +4.5h LOW, Lhokseumawe -> KNO Airport, c1)
        $rows[] = $road(
            'CTG-ROAD-2026-00483', 'c1', 'SMTR', 'DEP-LSW', 'KNO', 'DEP-MES', 'IN_TRANSIT', 'BL',
            $atWeekOffset(1, 2, 7), $now->copy()->subHours(14), $now->copy()->subHours(2), null, 4.5,
            'Pallet shrink-wrap re-strapping at Medan cross-dock added 4.5 hours.',
            'IDR', 295000000.0, 5600000.0, 2800.0, 8.5, 'FCA Medan KNO',
            [['commodity' => 'cinnamon', 'description' => 'Korintje Cinnamon Export Cartons Linehaul to KNO', 'packaging' => '112 export cartons', 'quantity' => 112, 'unit_value' => 2633928.57, 'weight_kg' => 2800.0, 'volume_cbm' => 8.5]]
        );

        // ROAD #7 (Week 1 - in-transit DELAYED +12.0h MEDIUM, Banda Aceh -> Medan Depot, c4) -> carries INVESTIGATING VEHICLE_BREAKDOWN
        $rows[] = $road(
            'CTG-ROAD-2026-00562', 'c4', 'BKTJ', 'DEP-ACE', 'DEP-MES', 'DEP-LSW', 'IN_TRANSIT', 'BK',
            $atWeekOffset(1, 3, 6), $now->copy()->subHours(20), $now->copy()->subHours(4), null, 12.0,
            'Tractor radiator coolant hose fault near Tebing Tinggi; relief truck dispatched.',
            'USD', 46000.0, 950.0, 7400.0, 19.5, 'FCA Medan Depot',
            [['commodity' => 'ginger', 'description' => 'Aceh & North Sumatra Dried Ginger Root Lots', 'packaging' => '296 laminated sacks', 'quantity' => 296, 'unit_value' => 155.41, 'weight_kg' => 7400.0, 'volume_cbm' => 19.5]]
        );

        // ROAD #8 (Week 1 - in-transit DELAYED +28.0h HIGH, Rotterdam Depot -> Hamburg via Venlo, c2) -> carries OPEN BORDER_QUEUE
        $rows[] = $road(
            'CTG-ROAD-2026-00638', 'c2', 'RHNL', 'DEP-RTM', 'DEHAM', 'BRD-VEN', 'IN_TRANSIT', 'NL',
            $atWeekOffset(1, 4, 8), $now->copy()->subDays(2), $now->copy()->subHours(16), null, 28.0,
            'Venlo NL/DE agricultural border inspection queue holding bonded spice trailer.',
            'EUR', 88000.0, 2150.0, 11200.0, 29.0, 'DAP Hamburg',
            [['commodity' => 'nutmeg', 'description' => 'Bonded Nutmeg & Mace Distribution to Hamburg', 'packaging' => '224 woven sacks on 16 pallets', 'quantity' => 224, 'unit_value' => 392.86, 'weight_kg' => 11200.0, 'volume_cbm' => 29.0]]
        );

        // ROAD #9 (Week 0 - in-transit arrived 3d ago under limit, Medan Depot -> Belawan Port, c6)
        $rows[] = $road(
            'CTG-ROAD-2026-00715', 'c6', 'SMTR', 'DEP-MES', 'IDBLW', 'IDBLW', 'IN_TRANSIT', 'BK',
            $now->copy()->subDays(5), $now->copy()->subDays(4), $now->copy()->subDays(3)->subHours(1), $now->copy()->subDays(3)->subHours(2), 0.0, null,
            'USD', 39000.0, 420.0, 9200.0, 24.0, 'FOB Belawan',
            [['commodity' => 'pepper', 'description' => 'Medan Consolidated Black Pepper Drayage to Belawan', 'packaging' => '184 moisture-lined sacks', 'quantity' => 184, 'unit_value' => 211.96, 'weight_kg' => 9200.0, 'volume_cbm' => 24.0]]
        );

        // ROAD #10 (Week 0 - in-transit on-time, Paris CDG -> Grasse Depot, c3)
        $rows[] = $road(
            'CTG-ROAD-2026-00794', 'c3', 'PRVF', 'CDG', 'DEP-GRS', 'CDG', 'IN_TRANSIT', 'FR',
            $now->copy()->subDays(1)->subHours(3), $now->copy()->subHours(12), $now->copy()->addHours(5), null, 0.0, null,
            'EUR', 64000.0, 1850.0, 1100.0, 4.2, 'DAP Grasse Depot',
            [['commodity' => 'patchouli', 'description' => 'CDG Cleared Patchouli Flasks Express Road Linehaul', 'packaging' => '44 aluminum flasks on climate pallet', 'quantity' => 44, 'unit_value' => 1454.55, 'weight_kg' => 1100.0, 'volume_cbm' => 4.2]]
        );

        // ROAD #11 (Week 0 - in-transit on-time, Mumbai BOM -> Nhava Sheva CFS, c5)
        $rows[] = $road(
            'CTG-ROAD-2026-00851', 'c5', 'KHND', 'BOM', 'DEP-NSC', 'BOM', 'IN_TRANSIT', 'IN',
            $now->copy()->subDays(2), $now->copy()->subHours(7), $now->copy()->addHours(3), null, 0.0, null,
            'INR', 1850000.0, 32000.0, 1450.0, 4.8, 'DAP Nhava Sheva CFS',
            [['commodity' => 'cloves', 'description' => 'BOM Airfreight Clove Oil Transfer to Nhava Sheva CFS', 'packaging' => '58 aluminum flasks in crates', 'quantity' => 58, 'unit_value' => 31896.55, 'weight_kg' => 1450.0, 'volume_cbm' => 4.8]]
        );

        // ROAD #12 (Week 0 - CREATED, Lhokseumawe -> Medan Depot, c4)
        $rows[] = $road(
            'CTG-ROAD-2026-00918', 'c4', 'SMTR', 'DEP-LSW', 'DEP-MES', 'DEP-LSW', 'CREATED', 'B',
            $now->copy()->subDays(1), $now->copy()->addDays(1), $now->copy()->addDays(2), null, 0.0, null,
            'USD', 51500.0, 780.0, 6800.0, 17.5, 'FCA Medan Depot',
            [['commodity' => 'cinnamon', 'description' => 'Lhokseumawe Cassia Cinnamon Bales Scheduled Dispatch', 'packaging' => '272 export cartons', 'quantity' => 272, 'unit_value' => 189.34, 'weight_kg' => 6800.0, 'volume_cbm' => 17.5]]
        );

        return $rows;
    }
}
