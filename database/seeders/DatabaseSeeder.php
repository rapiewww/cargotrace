<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with pure maritime container shipping data.
     */
    public function run(): void
    {
        // Prime currency exchange rates in cache
        Cache::forever(CurrencyService::STALE_CACHE_KEY, [
            'base' => 'USD',
            'rates' => [
                'USD' => 1.0, 'EUR' => 0.92, 'CNY' => 7.23, 'IDR' => 15873.0, 'SGD' => 1.34, 'AUD' => 1.52, 'GBP' => 0.79, 'JPY' => 154.5, 'AED' => 3.67, 'CHF' => 0.88, 'CAD' => 1.39,
            ],
            'time_last_update_utc' => 'Wed, 11 Nov 2026 00:00:00 +0000',
            'time_next_update_utc' => 'Thu, 12 Nov 2026 00:00:00 +0000',
            'last_updated' => 'Wed, 11 Nov 2026 00:00:00 +0000',
            'source' => 'ExchangeRate-API (open.er-api.com)',
            'provider_url' => 'https://www.exchangerate-api.com',
            'attribution_text' => 'Rates By ExchangeRate-API',
            'data_source' => 'cached',
        ]);
        // ---------------------------------------------------------------------
        // 1. DEMO USERS (Admin, Operator, Customers)
        // ---------------------------------------------------------------------
        $admin = User::firstOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'CargoTrace Admin (Fleet Director)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'company_name' => 'CargoTrace Maritime Global HQ',
                'phone' => '+1-555-010-9900',
                'preferred_currency' => 'USD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $operator = User::firstOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Port Operations Harbor Master',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'company_name' => 'CargoTrace Seaport Operations Center',
                'phone' => '+65-6789-0123',
                'preferred_currency' => 'SGD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $customer1 = User::firstOrCreate(
            ['email' => 'customer@cargotrace.test'],
            [
                'name' => 'PT Nusantara Ocean Cargo',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'Nusantara Ocean Shipping Lines Ltd.',
                'phone' => '+62-21-555-4321',
                'preferred_currency' => 'IDR',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $customer2 = User::firstOrCreate(
            ['email' => 'customer2@cargotrace.test'],
            [
                'name' => 'EuroPacific Maritime B.V.',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'EuroPacific Container Logistics B.V.',
                'phone' => '+31-10-890-1234',
                'preferred_currency' => 'EUR',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // ---------------------------------------------------------------------
        // 2. 6 PURE MARITIME CONTAINER CARRIERS (ALL MODE: SEA)
        // ---------------------------------------------------------------------
        $carriers = [
            'MAEU' => Carrier::firstOrCreate(
                ['code' => 'MAEU'],
                [
                    'name' => 'A.P. Moller – Maersk',
                    'mode' => 'SEA',
                    'headquarters_country' => 'Denmark',
                    'contact_email' => 'maritime.support@maersk.com',
                    'website' => 'https://www.maersk.com',
                    'is_active' => true,
                ]
            ),
            'MSCU' => Carrier::firstOrCreate(
                ['code' => 'MSCU'],
                [
                    'name' => 'Mediterranean Shipping Company (MSC)',
                    'mode' => 'SEA',
                    'headquarters_country' => 'Switzerland',
                    'contact_email' => 'vessel.ops@msc.com',
                    'website' => 'https://www.msc.com',
                    'is_active' => true,
                ]
            ),
            'CMDU' => Carrier::firstOrCreate(
                ['code' => 'CMDU'],
                [
                    'name' => 'CMA CGM Group',
                    'mode' => 'SEA',
                    'headquarters_country' => 'France',
                    'contact_email' => 'ocean.booking@cma-cgm.com',
                    'website' => 'https://www.cma-cgm.com',
                    'is_active' => true,
                ]
            ),
            'COSU' => Carrier::firstOrCreate(
                ['code' => 'COSU'],
                [
                    'name' => 'COSCO Shipping Lines',
                    'mode' => 'SEA',
                    'headquarters_country' => 'China',
                    'contact_email' => 'container.dispatch@coscon.com',
                    'website' => 'https://lines.coscoshipping.com',
                    'is_active' => true,
                ]
            ),
            'HLCU' => Carrier::firstOrCreate(
                ['code' => 'HLCU'],
                [
                    'name' => 'Hapag-Lloyd Container Lines',
                    'mode' => 'SEA',
                    'headquarters_country' => 'Germany',
                    'contact_email' => 'vessel.traffic@hlag.com',
                    'website' => 'https://www.hapag-lloyd.com',
                    'is_active' => true,
                ]
            ),
            'ONEY' => Carrier::firstOrCreate(
                ['code' => 'ONEY'],
                [
                    'name' => 'Ocean Network Express (ONE)',
                    'mode' => 'SEA',
                    'headquarters_country' => 'Singapore',
                    'contact_email' => 'ocean.tracking@one-line.com',
                    'website' => 'https://www.one-line.com',
                    'is_active' => true,
                ]
            ),
        ];

        // ---------------------------------------------------------------------
        // 3. 8 GLOBAL SEAPORTS (ALL TYPE: PORT)
        // ---------------------------------------------------------------------
        $locationsData = [
            'IDJKT' => [
                'unlocode' => 'IDJKT',
                'name' => 'Port of Tanjung Priok (Jakarta International Container Terminal)',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'country_code' => 'ID',
                'currency_code' => 'IDR',
                'type' => 'PORT',
                'latitude' => -6.1038190,
                'longitude' => 106.8824240,
                'timezone' => 'Asia/Jakarta',
                'weather_snapshot' => [
                    'temperature_c' => 31.5,
                    'feels_like_c' => 37.2,
                    'condition' => 'Scattered Thunderstorms',
                    'humidity' => 82,
                    'wind_kph' => 14.5,
                    'wind_direction' => 'NW',
                    'visibility_km' => 8.0,
                    'uv_index' => 8,
                    'pressure_mb' => 1009,
                    'recorded_at' => Carbon::now()->subHours(2)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => "Lloyd's List Maritime Asia",
                    'headline' => 'Tanjung Priok Container Terminal Upgrades Digital Gate System',
                    'summary' => 'Automated optical character recognition at JICT berth gates decreases container dwell and truck queuing time by 35%.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                    'url' => 'https://lloydslist.maritimeintelligence.informa.com/tanjung-priok-gate-upgrade',
                ],
            ],
            'SGSIN' => [
                'unlocode' => 'SGSIN',
                'name' => 'Port of Singapore (PSA Tuas Megaport Container Terminal)',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'country_code' => 'SG',
                'currency_code' => 'SGD',
                'type' => 'PORT',
                'latitude' => 1.2640470,
                'longitude' => 103.8222660,
                'timezone' => 'Asia/Singapore',
                'weather_snapshot' => [
                    'temperature_c' => 29.0,
                    'feels_like_c' => 34.0,
                    'condition' => 'Partly Cloudy',
                    'humidity' => 78,
                    'wind_kph' => 11.2,
                    'wind_direction' => 'E',
                    'visibility_km' => 10.0,
                    'uv_index' => 7,
                    'pressure_mb' => 1011,
                    'recorded_at' => Carbon::now()->subHours(1)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'The Maritime Executive',
                    'headline' => 'Tuas Megaport Phase 2 Expansion Reaches Target Berth Depth',
                    'summary' => 'Singapore MPA confirms deepwater dredging milestone enabling simultaneous handling of four 24,000 TEU container giants.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(1)->toIso8601String(),
                    'url' => 'https://maritime-executive.com/article/tuas-port-phase-2-berth-depth',
                ],
            ],
            'CNSHA' => [
                'unlocode' => 'CNSHA',
                'name' => 'Yangshan Deepwater Port Container Terminal',
                'city' => 'Shanghai',
                'country' => 'China',
                'country_code' => 'CN',
                'currency_code' => 'CNY',
                'type' => 'PORT',
                'latitude' => 30.6274430,
                'longitude' => 122.0641170,
                'timezone' => 'Asia/Shanghai',
                'weather_snapshot' => [
                    'temperature_c' => 18.0,
                    'feels_like_c' => 17.5,
                    'condition' => 'Overcast & Dense Sea Fog',
                    'humidity' => 89,
                    'wind_kph' => 22.0,
                    'wind_direction' => 'NE',
                    'visibility_km' => 4.2,
                    'uv_index' => 3,
                    'pressure_mb' => 1020,
                    'recorded_at' => Carbon::now()->subHours(3)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'China Maritime Daily',
                    'headline' => 'Seasonal Sea Fog Causes Intermittent Vessel Pilot Delays in East China Sea',
                    'summary' => 'Yangshan Maritime Safety Administration issues 6-hour holding pattern advisory for inbound ultra-large container carriers.',
                    'impact_level' => 'MEDIUM',
                    'published_at' => Carbon::now()->subHours(8)->toIso8601String(),
                    'url' => 'https://chinadaily.com.cn/shipping/fog-advisory-yangshan',
                ],
            ],
            'NLRTM' => [
                'unlocode' => 'NLRTM',
                'name' => 'Port of Rotterdam (Maasvlakte 2 Deepsea Gateway)',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'country_code' => 'NL',
                'currency_code' => 'EUR',
                'type' => 'PORT',
                'latitude' => 51.9560410,
                'longitude' => 4.0538980,
                'timezone' => 'Europe/Amsterdam',
                'weather_snapshot' => [
                    'temperature_c' => 11.2,
                    'feels_like_c' => 9.8,
                    'condition' => 'Light Rain & Gale Winds',
                    'humidity' => 85,
                    'wind_kph' => 38.0,
                    'wind_direction' => 'WSW',
                    'visibility_km' => 7.5,
                    'uv_index' => 2,
                    'pressure_mb' => 1004,
                    'recorded_at' => Carbon::now()->subHours(4)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Port of Rotterdam Authority',
                    'headline' => 'North Sea Storm Front: Quay Crane Wind Throttling Enacted',
                    'summary' => 'Gale force winds gusting above 40 knots force temporary crane speed safety restrictions at Maasvlakte deepsea terminals.',
                    'impact_level' => 'MEDIUM',
                    'published_at' => Carbon::now()->subHours(18)->toIso8601String(),
                    'url' => 'https://portofrotterdam.com/news/north-sea-gale-operations',
                ],
            ],
            'USLAX' => [
                'unlocode' => 'USLAX',
                'name' => 'Port of Los Angeles / Long Beach Harbor Complex',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'country_code' => 'US',
                'currency_code' => 'USD',
                'type' => 'PORT',
                'latitude' => 33.7431840,
                'longitude' => -118.2672540,
                'timezone' => 'America/Los_Angeles',
                'weather_snapshot' => [
                    'temperature_c' => 22.4,
                    'feels_like_c' => 21.8,
                    'condition' => 'Clear Sky & Moderate Coastal Breeze',
                    'humidity' => 45,
                    'wind_kph' => 12.0,
                    'wind_direction' => 'SW',
                    'visibility_km' => 16.0,
                    'uv_index' => 6,
                    'pressure_mb' => 1015,
                    'recorded_at' => Carbon::now()->subHours(5)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Journal of Commerce Maritime',
                    'headline' => 'San Pedro Bay Ports Process Robust Holiday Volume Without Vessel Anchorage Backlogs',
                    'summary' => 'PierPass off-peak incentives maintain fluid gate operations with zero container dwell spikes across San Pedro Bay berths.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(3)->toIso8601String(),
                    'url' => 'https://joc.com/maritime-news/ports/port-of-la-october-cargo',
                ],
            ],
            'AEDXB' => [
                'unlocode' => 'AEDXB',
                'name' => 'Port of Jebel Ali Deepwater Container Terminal',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'currency_code' => 'AED',
                'type' => 'PORT',
                'latitude' => 25.0069350,
                'longitude' => 55.0645060,
                'timezone' => 'Asia/Dubai',
                'weather_snapshot' => [
                    'temperature_c' => 32.0,
                    'feels_like_c' => 35.1,
                    'condition' => 'Sunny & Clear Maritime Sky',
                    'humidity' => 55,
                    'wind_kph' => 16.0,
                    'wind_direction' => 'NNW',
                    'visibility_km' => 9.0,
                    'uv_index' => 8,
                    'pressure_mb' => 1012,
                    'recorded_at' => Carbon::now()->subHours(2)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Gulf Maritime Review',
                    'headline' => 'DP World Streamlines Transshipment Container Processing at Jebel Ali Berths 1-4',
                    'summary' => 'Seamless digital harbor clearance connects feeder container vessels to main-haul liners with record crane moves per hour.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                    'url' => 'https://gulfnews.com/business/maritime/dp-world-jebel-ali-expansion',
                ],
            ],
            'DEHAM' => [
                'unlocode' => 'DEHAM',
                'name' => 'Port of Hamburg (CTA Container Terminal Altenwerder)',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'country_code' => 'DE',
                'currency_code' => 'EUR',
                'type' => 'PORT',
                'latitude' => 53.5320000,
                'longitude' => 9.9850000,
                'timezone' => 'Europe/Berlin',
                'weather_snapshot' => [
                    'temperature_c' => 12.5,
                    'feels_like_c' => 10.8,
                    'condition' => 'Overcast & River Elbe Breeze',
                    'humidity' => 84,
                    'wind_kph' => 18.0,
                    'wind_direction' => 'WNW',
                    'visibility_km' => 10.0,
                    'uv_index' => 2,
                    'pressure_mb' => 1014,
                    'recorded_at' => Carbon::now()->subHours(3)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Hafen Hamburg News',
                    'headline' => 'Automated Container Handling at Altenwerder Breaks Monthly Throughput Record',
                    'summary' => 'Battery-powered AGVs and electric gantry cranes achieve 100% climate-neutral container moves across CTA berths.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(4)->toIso8601String(),
                    'url' => 'https://hafen-hamburg.de/en/news/altenwerder-record-throughput',
                ],
            ],
            'AUSYD' => [
                'unlocode' => 'AUSYD',
                'name' => 'Port Botany Intermodal Container Terminal',
                'city' => 'Sydney',
                'country' => 'Australia',
                'country_code' => 'AU',
                'currency_code' => 'AUD',
                'type' => 'PORT',
                'latitude' => -33.9723220,
                'longitude' => 151.2185060,
                'timezone' => 'Australia/Sydney',
                'weather_snapshot' => [
                    'temperature_c' => 24.1,
                    'feels_like_c' => 24.5,
                    'condition' => 'Partly Cloudy with Coastal Breeze',
                    'humidity' => 62,
                    'wind_kph' => 20.0,
                    'wind_direction' => 'SE',
                    'visibility_km' => 12.0,
                    'uv_index' => 7,
                    'pressure_mb' => 1016,
                    'recorded_at' => Carbon::now()->subHours(1)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Daily Cargo News Australia',
                    'headline' => 'Biosecurity Mandatory Treatment Directives Issued for Autumn Container Ships',
                    'summary' => 'Australian Department of Agriculture enhances quarantine clearance speed through pre-arrival electronic documentation protocols.',
                    'impact_level' => 'MEDIUM',
                    'published_at' => Carbon::now()->subDays(1)->toIso8601String(),
                    'url' => 'https://thedcn.com.au/biosecurity-port-botany-update',
                ],
            ],
        ];

        $locations = [];
        foreach ($locationsData as $code => $data) {
            $locations[$code] = Location::firstOrCreate(['unlocode' => $code], $data);
        }

        // ---------------------------------------------------------------------
        // 4. 6 GLOBAL OCEAN CONTAINER SHIPMENTS (ALL PURE MARITIME)
        // ---------------------------------------------------------------------

        // SHIPMENT 1: Jakarta (IDJKT) -> Rotterdam (NLRTM) [MAEU / FCL / DELIVERED]
        $shipment1 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00189'],
            [
                'reference_number' => 'B/L-MAEU-901842',
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['MAEU']->id,
                'origin_location_id' => $locations['IDJKT']->id,
                'destination_location_id' => $locations['NLRTM']->id,
                'current_location_id' => $locations['NLRTM']->id,
                'cargo_type' => 'FCL',
                'total_weight_kg' => 24500.00,
                'total_volume_cbm' => 68.50,
                'declared_value' => 1450000000.00, // IDR 1.45 Billion
                'currency_code' => 'IDR',
                'shipping_cost' => 78500000.00, // IDR 78.5 Million
                'exchange_rate_to_usd' => 0.000063, // 1 IDR = 0.000063 USD (~15,873 IDR/USD)
                'converted_value_usd' => 91350.00,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'IDR' => 15873.0, 'EUR' => 0.92, 'SGD' => 1.34, 'CNY' => 7.23, 'AUD' => 1.52, 'AED' => 3.67],
                    'origin_currency' => 'IDR',
                    'destination_currency' => 'EUR',
                    'rate_origin_to_usd' => 0.000063,
                    'rate_usd_to_dest' => 0.92,
                    'recorded_at' => Carbon::now()->subDays(35)->toIso8601String(),
                ],
                'status' => 'DELIVERED',
                'expected_departure_at' => Carbon::now()->subDays(35),
                'expected_arrival_at' => Carbon::now()->subDays(5),
                'estimated_arrival_at' => Carbon::now()->subDays(4)->subHours(6),
                'actual_arrival_at' => Carbon::now()->subDays(4)->subHours(7),
                'is_delayed' => true,
                'delay_hours' => 17.00,
                'delay_severity' => 'LOW',
                'delay_reason' => 'North Sea storm gale winds delayed pilot boarding at Maasvlakte entrance.',
                'route_news_snapshot' => [
                    'source' => 'Port of Rotterdam Vessel Traffic',
                    'headline' => 'North Sea Storm Calms; Maasvlakte Quay Cranes Resume Full Handling Capacity',
                    'impact' => 'Cargo discharged smoothly following a 17-hour weather hold.',
                    'published_at' => Carbon::now()->subDays(4)->toIso8601String(),
                ],
                'notes' => 'Export documentation complete. Clean bill of lading issued by Maersk Line Indonesia.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'sku' => 'ID-COF-GAYO-01'],
            [
                'description' => '40ft High Cube Container: Specialty Grade 1 Gayo Organic Arabica Green Coffee',
                'quantity' => 350,
                'unit_value' => 2600000.00,
                'currency_code' => 'IDR',
                'weight_kg' => 14000.00,
                'volume_cbm' => 38.00,
                'is_hazardous' => false,
            ]
        );
        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'sku' => 'ID-COF-TOR-02'],
            [
                'description' => '20ft Dry Container: Toraja Single Origin High-Altitude Raw Beans',
                'quantity' => 280,
                'unit_value' => 1928571.00,
                'currency_code' => 'IDR',
                'weight_kg' => 10500.00,
                'volume_cbm' => 30.50,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['IDJKT']->id,
                'event_type' => 'NORMAL',
                'title' => 'Ocean Booking Confirmed & Sealed at JICT Terminal',
                'description' => 'Maersk container vessel booking confirmed. Containers passed tare weight calibration and customs verification.',
                'latitude' => $locations['IDJKT']->latitude,
                'longitude' => $locations['IDJKT']->longitude,
                'weather_snapshot' => $locations['IDJKT']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(36),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'BOOKED'],
            [
                'location_id' => $locations['IDJKT']->id,
                'event_type' => 'NORMAL',
                'title' => 'Vessel Space Assigned & Container Staged at Quay',
                'description' => 'Containers loaded to stack block 2C awaiting vessel arrival.',
                'latitude' => $locations['IDJKT']->latitude,
                'longitude' => $locations['IDJKT']->longitude,
                'weather_snapshot' => $locations['IDJKT']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(35)->addHours(4),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => $locations['IDJKT']->id,
                'event_type' => 'NORMAL',
                'title' => 'Vessel Departed Tanjung Priok Outbound to Red Sea / Suez',
                'description' => 'Liner underway at cruising speed of 18 knots.',
                'latitude' => $locations['IDJKT']->latitude,
                'longitude' => $locations['IDJKT']->longitude,
                'weather_snapshot' => $locations['IDJKT']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(34),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'DELIVERED'],
            [
                'location_id' => $locations['NLRTM']->id,
                'event_type' => 'NORMAL',
                'title' => 'Container Discharged & Gate Out at Maasvlakte 2',
                'description' => 'Both 40ft HC containers unloaded via quay crane and transferred to bonded port warehouse.',
                'latitude' => $locations['NLRTM']->latitude,
                'longitude' => $locations['NLRTM']->longitude,
                'weather_snapshot' => $locations['NLRTM']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(4)->subHours(7),
                'created_by' => $operator->id,
            ]
        );

        // SHIPMENT 2: Shanghai (CNSHA) -> Los Angeles (USLAX) [MSCU / FCL / IN_TRANSIT / CUSTOMS HOLD]
        $shipment2 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00244'],
            [
                'reference_number' => 'B/L-MSCU-440192',
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['MSCU']->id,
                'origin_location_id' => $locations['CNSHA']->id,
                'destination_location_id' => $locations['USLAX']->id,
                'current_location_id' => $locations['USLAX']->id,
                'cargo_type' => 'FCL',
                'total_weight_kg' => 18200.00,
                'total_volume_cbm' => 54.00,
                'declared_value' => 1850000.00, // CNY 1.85 Million
                'currency_code' => 'CNY',
                'shipping_cost' => 62000.00, // CNY 62k
                'exchange_rate_to_usd' => 0.138312, // 1 CNY = 0.138312 USD (~7.23 CNY/USD)
                'converted_value_usd' => 255877.20,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'CNY' => 7.23, 'EUR' => 0.92, 'IDR' => 15873.0, 'SGD' => 1.34, 'AUD' => 1.52, 'AED' => 3.67],
                    'origin_currency' => 'CNY',
                    'destination_currency' => 'USD',
                    'rate_origin_to_usd' => 0.138312,
                    'rate_usd_to_dest' => 1.0,
                    'recorded_at' => Carbon::now()->subDays(16)->toIso8601String(),
                ],
                'status' => 'IN_TRANSIT',
                'expected_departure_at' => Carbon::now()->subDays(16),
                'expected_arrival_at' => Carbon::now()->subDays(2),
                'estimated_arrival_at' => Carbon::now()->addDays(1),
                'actual_arrival_at' => null,
                'is_delayed' => true,
                'delay_hours' => 58.00,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Vessel anchored awaiting CET customs examination berth at Pier 400.',
                'route_news_snapshot' => [
                    'source' => 'US Customs & Border Protection',
                    'headline' => 'Pier 400 Container Inspection Checkpoint Routine Verification',
                    'impact' => 'Selected hazmat battery containers undergoing standard physical examination.',
                    'published_at' => Carbon::now()->subHours(12)->toIso8601String(),
                ],
                'notes' => 'Class 9 Lithium-ion energy storage racks. IMO DG placards verified before trans-Pacific voyage.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'sku' => 'CN-EV-BAT-01'],
            [
                'description' => '40ft HC Container: Industrial LFP Battery Energy Storage Racks (UN 3480, Class 9)',
                'quantity' => 24,
                'unit_value' => 55000.00,
                'currency_code' => 'CNY',
                'weight_kg' => 13000.00,
                'volume_cbm' => 36.00,
                'is_hazardous' => true,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['CNSHA']->id,
                'event_type' => 'NORMAL',
                'title' => 'Vessel Manifest Approved at Yangshan Deepwater Terminal',
                'description' => 'MSC container vessel booking accepted with IMO Dangerous Goods certification.',
                'latitude' => $locations['CNSHA']->latitude,
                'longitude' => $locations['CNSHA']->longitude,
                'weather_snapshot' => $locations['CNSHA']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(17),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => $locations['USLAX']->id,
                'event_type' => 'EXCEPTION',
                'title' => 'Vessel Moored at LA Outer Harbor; Customs Hold Enacted',
                'description' => 'Container flagged for Vacis X-Ray & physical examination upon discharge at Terminal 400.',
                'latitude' => $locations['USLAX']->latitude,
                'longitude' => $locations['USLAX']->longitude,
                'weather_snapshot' => $locations['USLAX']->weather_snapshot,
                'occurred_at' => Carbon::now()->subHours(12),
                'created_by' => $operator->id,
            ]
        );

        ShipmentException::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'exception_code' => 'CUSTOMS_HOLD'],
            [
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'title' => 'CBP Customs Physical Examination Hold at Pier 400',
                'description' => 'Customs Officer requested UN 38.3 battery certification report verification prior to container gate release.',
                'related_news_url' => 'https://joc.com/maritime-news/ports/port-of-la-october-cargo',
                'resolution_notes' => null,
                'detected_at' => Carbon::now()->subHours(12),
                'resolved_at' => null,
                'reported_by' => $operator->id,
                'resolved_by' => null,
            ]
        );

        // SHIPMENT 3: Hamburg (DEHAM) -> Singapore (SGSIN) [CMDU / REEFER / DELIVERED ON-TIME]
        $shipment3 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00512'],
            [
                'reference_number' => 'B/L-CMDU-671204',
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['CMDU']->id,
                'origin_location_id' => $locations['DEHAM']->id,
                'destination_location_id' => $locations['SGSIN']->id,
                'current_location_id' => $locations['SGSIN']->id,
                'cargo_type' => 'REEFER',
                'total_weight_kg' => 21850.00,
                'total_volume_cbm' => 58.20,
                'declared_value' => 480000.00, // EUR 480k
                'currency_code' => 'EUR',
                'shipping_cost' => 14200.00, // EUR 14.2k
                'exchange_rate_to_usd' => 1.086956, // 1 EUR = 1.086956 USD
                'converted_value_usd' => 521738.88,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'EUR' => 0.92, 'SGD' => 1.34, 'IDR' => 15873.0, 'CNY' => 7.23, 'AUD' => 1.52, 'AED' => 3.67],
                    'origin_currency' => 'EUR',
                    'destination_currency' => 'SGD',
                    'rate_origin_to_usd' => 1.086956,
                    'rate_usd_to_dest' => 1.34,
                    'recorded_at' => Carbon::now()->subDays(28)->toIso8601String(),
                ],
                'status' => 'DELIVERED',
                'expected_departure_at' => Carbon::now()->subDays(28),
                'expected_arrival_at' => Carbon::now()->subDays(2),
                'estimated_arrival_at' => Carbon::now()->subDays(2)->subHours(4),
                'actual_arrival_at' => Carbon::now()->subDays(2)->subHours(5),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'route_news_snapshot' => [
                    'source' => 'CMA CGM Marine Advisory',
                    'headline' => 'Favorable Ocean Currents Accelerate Suez-Malacca Transit Window',
                    'impact' => 'Container vessel CMA CGM Palais arrived 5 hours ahead of scheduled pilot rendezvous.',
                    'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                ],
                'notes' => 'Active 40ft high cube refrigerated container set to constant -20°C for frozen pharmaceuticals.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'sku' => 'DE-MED-REEF-01'],
            [
                'description' => '40ft Reefer Container: Cold-Chain Bio-Pharmaceuticals & Serum Concentrates (-20°C)',
                'quantity' => 18,
                'unit_value' => 26666.66,
                'currency_code' => 'EUR',
                'weight_kg' => 21850.00,
                'volume_cbm' => 58.20,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['DEHAM']->id,
                'event_type' => 'NORMAL',
                'title' => 'Reefer Container Loaded at CTA Altenwerder Berth 3',
                'description' => 'Reefer genset power connected and telemetry validated at -20.2°C.',
                'latitude' => $locations['DEHAM']->latitude,
                'longitude' => $locations['DEHAM']->longitude,
                'weather_snapshot' => $locations['DEHAM']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(28),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'status' => 'DELIVERED'],
            [
                'location_id' => $locations['SGSIN']->id,
                'event_type' => 'NORMAL',
                'title' => 'Discharged at Singapore Tuas Megaport Berth T12',
                'description' => 'Reefer data logger retrieved without temperature deviation. Container released to cold storage operator.',
                'latitude' => $locations['SGSIN']->latitude,
                'longitude' => $locations['SGSIN']->longitude,
                'weather_snapshot' => $locations['SGSIN']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(2)->subHours(5),
                'created_by' => $operator->id,
            ]
        );

        // SHIPMENT 4: Singapore (SGSIN) -> Sydney (AUSYD) [ONEY / FCL / IN_TRANSIT (LOW DELAY)]
        $shipment4 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00678'],
            [
                'reference_number' => 'B/L-ONEY-881902',
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['ONEY']->id,
                'origin_location_id' => $locations['SGSIN']->id,
                'destination_location_id' => $locations['AUSYD']->id,
                'current_location_id' => $locations['SGSIN']->id,
                'cargo_type' => 'FCL',
                'total_weight_kg' => 19840.00,
                'total_volume_cbm' => 52.80,
                'declared_value' => 320000.00, // SGD 320k
                'currency_code' => 'SGD',
                'shipping_cost' => 8600.00, // SGD 8.6k
                'exchange_rate_to_usd' => 0.746268, // 1 SGD = 0.746268 USD
                'converted_value_usd' => 238805.76,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'SGD' => 1.34, 'AUD' => 1.52, 'EUR' => 0.92, 'IDR' => 15873.0, 'CNY' => 7.23, 'AED' => 3.67],
                    'origin_currency' => 'SGD',
                    'destination_currency' => 'AUD',
                    'rate_origin_to_usd' => 0.746268,
                    'rate_usd_to_dest' => 1.52,
                    'recorded_at' => Carbon::now()->subDays(6)->toIso8601String(),
                ],
                'status' => 'IN_TRANSIT',
                'expected_departure_at' => Carbon::now()->subDays(6),
                'expected_arrival_at' => Carbon::now()->addHours(12),
                'estimated_arrival_at' => Carbon::now()->addHours(15)->addMinutes(30),
                'actual_arrival_at' => null,
                'is_delayed' => true,
                'delay_hours' => 3.50,
                'delay_severity' => 'LOW',
                'delay_reason' => 'Severe swell in Coral Sea required vessel speed adjustment to 14.5 knots.',
                'route_news_snapshot' => [
                    'source' => 'Australian Maritime Safety Authority',
                    'headline' => 'Tasman Sea & Coral Sea Southerly Gale Warning for Coastal Container Feeders',
                    'impact' => 'Swell wave heights up to 4.2m causing minor ETA variances.',
                    'published_at' => Carbon::now()->subHours(6)->toIso8601String(),
                ],
                'notes' => 'Container vessel ONE Cygnus voy. 2603S. Stowed midships on tier 3.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'sku' => 'SG-SOLAR-CELL-01'],
            [
                'description' => '40ft Standard Dry Container: Monocrystalline Bifacial Photovoltaic Solar Panels',
                'quantity' => 640,
                'unit_value' => 500.00,
                'currency_code' => 'SGD',
                'weight_kg' => 19840.00,
                'volume_cbm' => 52.80,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['SGSIN']->id,
                'event_type' => 'NORMAL',
                'title' => 'Container Loaded Aboard M/V ONE Cygnus at Pasir Panjang',
                'description' => 'Sealed with ISO/PAS 17712 high-security container seal.',
                'latitude' => $locations['SGSIN']->latitude,
                'longitude' => $locations['SGSIN']->longitude,
                'weather_snapshot' => $locations['SGSIN']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(6),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => null,
                'event_type' => 'NORMAL',
                'title' => 'Vessel Underway in Coral Sea off Queensland Coast',
                'description' => 'Vessel cruising at 15.2 knots. Revised pilot ETA at Port Botany set to 15:30 AEST.',
                'latitude' => -18.2450,
                'longitude' => 149.3200,
                'weather_snapshot' => [
                    'temperature_c' => 26.0,
                    'condition' => 'Moderate Sea Swell',
                    'humidity' => 70,
                    'wind_kph' => 28.0,
                ],
                'occurred_at' => Carbon::now()->subHours(5),
                'created_by' => null,
            ]
        );

        // SHIPMENT 5: Dubai (AEDXB) -> Jakarta (IDJKT) [HLCU / FCL / BOOKED]
        $shipment5 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00890'],
            [
                'reference_number' => 'B/L-HLCU-990145',
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['HLCU']->id,
                'origin_location_id' => $locations['AEDXB']->id,
                'destination_location_id' => $locations['IDJKT']->id,
                'current_location_id' => $locations['AEDXB']->id,
                'cargo_type' => 'FCL',
                'total_weight_kg' => 26400.00,
                'total_volume_cbm' => 64.00,
                'declared_value' => 650000.00, // USD 650k
                'currency_code' => 'USD',
                'shipping_cost' => 18500.00, // USD 18.5k
                'exchange_rate_to_usd' => 1.000000,
                'converted_value_usd' => 650000.00,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'AED' => 3.67, 'IDR' => 15873.0, 'EUR' => 0.92, 'SGD' => 1.34, 'CNY' => 7.23, 'AUD' => 1.52],
                    'origin_currency' => 'AED',
                    'destination_currency' => 'IDR',
                    'rate_origin_to_usd' => 1.0,
                    'rate_usd_to_dest' => 15873.0,
                    'recorded_at' => Carbon::now()->subHours(3)->toIso8601String(),
                ],
                'status' => 'CREATED',
                'expected_departure_at' => Carbon::now()->addDays(2),
                'expected_arrival_at' => Carbon::now()->addDays(14),
                'estimated_arrival_at' => null,
                'actual_arrival_at' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'route_news_snapshot' => [
                    'source' => 'Hapag-Lloyd Fleet Dispatch',
                    'headline' => 'Indian Ocean Express Service (IOX) Vessel Berthing on Schedule at Jebel Ali',
                    'impact' => 'Outbound voyage M/V Hamburg Express scheduled for departure in 48 hours.',
                    'published_at' => Carbon::now()->subHours(4)->toIso8601String(),
                ],
                'notes' => 'Heavy industrial marine pump components packed in open-top heavy-duty steel containers.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment5->id, 'sku' => 'AE-PUMP-DESAL-01'],
            [
                'description' => '40ft Open Top Container: Industrial Reverse Osmosis High-Pressure Desalination Pumps',
                'quantity' => 4,
                'unit_value' => 162500.00,
                'currency_code' => 'USD',
                'weight_kg' => 26400.00,
                'volume_cbm' => 64.00,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment5->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['AEDXB']->id,
                'event_type' => 'NORMAL',
                'title' => 'Ocean Waybill Manifested on M/V Hamburg Express',
                'description' => 'Cargo received and containerized at Jebel Ali Terminal 2. Staged for gantry crane loading.',
                'latitude' => $locations['AEDXB']->latitude,
                'longitude' => $locations['AEDXB']->longitude,
                'weather_snapshot' => $locations['AEDXB']->weather_snapshot,
                'occurred_at' => Carbon::now()->subHours(3),
                'created_by' => $operator->id,
            ]
        );

        // SHIPMENT 6: Rotterdam (NLRTM) -> Shanghai (CNSHA) [COSU / FCL / IN_TRANSIT (MEDIUM DELAY / PORT CONGESTION)]
        $shipment6 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00995'],
            [
                'reference_number' => 'B/L-COSU-887701',
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['COSU']->id,
                'origin_location_id' => $locations['NLRTM']->id,
                'destination_location_id' => $locations['CNSHA']->id,
                'current_location_id' => $locations['AEDXB']->id,
                'cargo_type' => 'FCL',
                'total_weight_kg' => 28600.00,
                'total_volume_cbm' => 66.50,
                'declared_value' => 890000.00, // EUR 890k
                'currency_code' => 'EUR',
                'shipping_cost' => 24000.00, // EUR 24k
                'exchange_rate_to_usd' => 1.086956, // 1 EUR = 1.086956 USD
                'converted_value_usd' => 967390.84,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'EUR' => 0.92, 'CNY' => 7.23, 'AED' => 3.67, 'IDR' => 15873.0, 'SGD' => 1.34, 'AUD' => 1.52],
                    'origin_currency' => 'EUR',
                    'destination_currency' => 'CNY',
                    'rate_origin_to_usd' => 1.086956,
                    'rate_usd_to_dest' => 7.23,
                    'recorded_at' => Carbon::now()->subDays(14)->toIso8601String(),
                ],
                'status' => 'IN_TRANSIT',
                'expected_departure_at' => Carbon::now()->subDays(14),
                'expected_arrival_at' => Carbon::now()->addDays(7),
                'estimated_arrival_at' => Carbon::now()->addDays(8)->addHours(8),
                'actual_arrival_at' => null,
                'is_delayed' => true,
                'delay_hours' => 32.00,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Transshipment container connection delayed at Jebel Ali due to berth queuing congestion.',
                'route_news_snapshot' => [
                    'source' => "Lloyd's List Marine Traffic",
                    'headline' => 'Strait of Hormuz Container Vessel Turnaround Times Extended by 30 Hours',
                    'impact' => 'Transshipment container re-manifested to mother vessel COSCO Shipping Universe.',
                    'published_at' => Carbon::now()->subDays(1)->toIso8601String(),
                ],
                'notes' => '2x 40ft HC Containers carrying precision robotics and industrial UV optics.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'sku' => 'NL-IND-ROBOT-01'],
            [
                'description' => '40ft HC Container: Automated Precision Assembly Robotics & CNC Controllers',
                'quantity' => 12,
                'unit_value' => 74166.66,
                'currency_code' => 'EUR',
                'weight_kg' => 28600.00,
                'volume_cbm' => 66.50,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['NLRTM']->id,
                'event_type' => 'NORMAL',
                'title' => 'Container Loaded Aboard Feeder at Rotterdam Maasvlakte',
                'description' => 'Container verified under verified gross mass (VGM) standards and loaded onto feeder vessel.',
                'latitude' => $locations['NLRTM']->latitude,
                'longitude' => $locations['NLRTM']->longitude,
                'weather_snapshot' => $locations['NLRTM']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(14),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => $locations['AEDXB']->id,
                'event_type' => 'EXCEPTION',
                'title' => 'Transshipment Feeder Connection Delay at Jebel Ali Terminal',
                'description' => 'Connecting mother vessel berthing delayed due to harbor congestion. Container safely staged at yard block 4B.',
                'latitude' => $locations['AEDXB']->latitude,
                'longitude' => $locations['AEDXB']->longitude,
                'weather_snapshot' => $locations['AEDXB']->weather_snapshot,
                'occurred_at' => Carbon::now()->subHours(16),
                'created_by' => $operator->id,
            ]
        );

        ShipmentException::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'exception_code' => 'PORT_CONGESTION'],
            [
                'severity' => 'MEDIUM',
                'status' => 'OPEN',
                'title' => 'Jebel Ali Transshipment Vessel Berthing Delay',
                'description' => 'Connecting container vessel missed berthing window due to basin queue. Re-manifesting onto secondary mother liner.',
                'related_news_url' => 'https://gulfnews.com/business/maritime/dp-world-jebel-ali-expansion',
                'resolution_notes' => null,
                'detected_at' => Carbon::now()->subHours(16),
                'resolved_at' => null,
                'reported_by' => $operator->id,
                'resolved_by' => null,
            ]
        );

        // ---------------------------------------------------------------------
        // 5. NOTIFICATIONS
        // ---------------------------------------------------------------------
        Notification::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'type' => 'SHIPMENT_DELIVERED'],
            [
                'user_id' => $customer1->id,
                'title' => 'Shipment CTG-SEA-2026-00189 Delivered',
                'message' => 'Your FCL coffee container from Tanjung Priok has arrived and cleared customs release at Port of Rotterdam.',
                'read_at' => Carbon::now()->subDays(3),
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'type' => 'CUSTOMS_HOLD_ALERT'],
            [
                'user_id' => $customer2->id,
                'title' => 'Action Required: Customs Hold on CTG-SEA-2026-00244',
                'message' => 'US Customs at Port of Los Angeles has initiated an examination hold on your lithium battery container. Please verify UN 38.3 paperwork.',
                'read_at' => null,
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'type' => 'COLD_CHAIN_DELIVERED'],
            [
                'user_id' => $customer2->id,
                'title' => 'Reefer Container Discharged: CTG-SEA-2026-00512',
                'message' => 'Your pharmaceutical reefer container was discharged at Singapore Tuas Megaport with zero temperature excursion.',
                'read_at' => Carbon::now()->subDays(1),
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'type' => 'WEATHER_DELAY_NOTICE'],
            [
                'user_id' => $customer1->id,
                'title' => 'Maritime Swell Advisory for CTG-SEA-2026-00678',
                'message' => 'Heavy southerly swell in the Coral Sea resulted in a 3.5 hour transit delay. Estimated Sydney berthing is now 15:30 AEST.',
                'read_at' => Carbon::now()->subHours(2),
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'type' => 'PORT_CONGESTION_ALERT'],
            [
                'user_id' => $customer2->id,
                'title' => 'Transshipment Update: CTG-SEA-2026-00995 at Jebel Ali',
                'message' => 'Container transfer re-scheduled to M/V COSCO Shipping Universe due to harbor basin congestion. Arrival in Shanghai adjusted by +32h.',
                'read_at' => null,
            ]
        );

        // ---------------------------------------------------------------------
        // 6. AUDIT LOGS
        // ---------------------------------------------------------------------
        $auditLogs = [
            [
                'user_id' => $admin->id,
                'action' => 'SHIPMENT_CREATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment1->id,
                'old_values' => null,
                'new_values' => [
                    'tracking_number' => $shipment1->tracking_number,
                    'customer' => $customer1->name,
                    'origin' => 'IDJKT',
                    'destination' => 'NLRTM',
                    'declared_value' => $shipment1->declared_value,
                    'currency_code' => $shipment1->currency_code,
                ],
                'ip_address' => '10.0.0.1',
                'created_at' => Carbon::now()->subDays(36),
            ],
            [
                'user_id' => $operator->id,
                'action' => 'SHIPMENT_STATUS_UPDATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment1->id,
                'old_values' => ['status' => 'IN_TRANSIT'],
                'new_values' => ['status' => 'DELIVERED', 'actual_arrival_at' => $shipment1->actual_arrival_at],
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::now()->subDays(4),
            ],
            [
                'user_id' => $operator->id,
                'action' => 'EXCEPTION_RAISED',
                'auditable_type' => ShipmentException::class,
                'auditable_id' => 1,
                'old_values' => null,
                'new_values' => [
                    'shipment_tracking' => $shipment2->tracking_number,
                    'exception_code' => 'CUSTOMS_HOLD',
                    'severity' => 'HIGH',
                    'status' => 'INVESTIGATING',
                ],
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::now()->subHours(12),
            ],
            [
                'user_id' => null, // Automated External Integration
                'action' => 'WEATHER_SNAPSHOT_SYNCED',
                'auditable_type' => Location::class,
                'auditable_id' => $locations['CNSHA']->id,
                'old_values' => ['condition' => 'Clear'],
                'new_values' => ['condition' => 'Overcast & Dense Sea Fog', 'visibility_km' => 4.2],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'user_id' => null, // Automated External Integration
                'action' => 'CURRENCY_RATES_SYNCED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment2->id,
                'old_values' => ['exchange_rate_to_usd' => 0.1378],
                'new_values' => ['exchange_rate_to_usd' => 0.138312, 'converted_value_usd' => 255877.20],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subDays(16),
            ],
        ];

        foreach ($auditLogs as $log) {
            AuditLog::create($log);
        }
    }
}
