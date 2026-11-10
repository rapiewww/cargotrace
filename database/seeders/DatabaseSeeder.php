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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 1. DEMO USERS (Admin, Operator, Customers)
        // ---------------------------------------------------------------------
        $admin = User::firstOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'CargoTrace Admin (Global Director)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'company_name' => 'CargoTrace Global HQ Ltd.',
                'phone' => '+1-555-010-9900',
                'preferred_currency' => 'USD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $operator = User::firstOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Senior Operations Dispatcher',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'company_name' => 'CargoTrace Port Operations Center',
                'phone' => '+65-6789-0123',
                'preferred_currency' => 'SGD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $customer1 = User::firstOrCreate(
            ['email' => 'customer@cargotrace.test'],
            [
                'name' => 'PT Nusantara Global Trade',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'Nusantara Global Trade Inc.',
                'phone' => '+62-21-555-4321',
                'preferred_currency' => 'IDR',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $customer2 = User::firstOrCreate(
            ['email' => 'customer2@cargotrace.test'],
            [
                'name' => 'EuroPacific Electronics B.V.',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'EuroPacific Logistics & Tech B.V.',
                'phone' => '+31-10-890-1234',
                'preferred_currency' => 'EUR',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // ---------------------------------------------------------------------
        // 2. 6 GLOBAL CARRIERS
        // ---------------------------------------------------------------------
        $carriers = [
            'MAEU' => Carrier::firstOrCreate(
                ['code' => 'MAEU'],
                [
                    'name' => 'A.P. Moller – Maersk',
                    'mode' => 'SEA',
                    'headquarters_country' => 'Denmark',
                    'contact_email' => 'customer.support@maersk.com',
                    'website' => 'https://www.maersk.com',
                    'is_active' => true,
                ]
            ),
            'MSC' => Carrier::firstOrCreate(
                ['code' => 'MSC'],
                [
                    'name' => 'Mediterranean Shipping Company (MSC)',
                    'mode' => 'SEA',
                    'headquarters_country' => 'Switzerland',
                    'contact_email' => 'operations@msc.com',
                    'website' => 'https://www.msc.com',
                    'is_active' => true,
                ]
            ),
            'DHL' => Carrier::firstOrCreate(
                ['code' => 'DHL'],
                [
                    'name' => 'DHL Global Forwarding',
                    'mode' => 'MULTIMODAL',
                    'headquarters_country' => 'Germany',
                    'contact_email' => 'freight.support@dhl.com',
                    'website' => 'https://www.dhl.com',
                    'is_active' => true,
                ]
            ),
            'SQC' => Carrier::firstOrCreate(
                ['code' => 'SQC'],
                [
                    'name' => 'Singapore Airlines Cargo',
                    'mode' => 'AIR',
                    'headquarters_country' => 'Singapore',
                    'contact_email' => 'cargo_helpdesk@singaporeair.com.sg',
                    'website' => 'https://www.siacargo.com',
                    'is_active' => true,
                ]
            ),
            'EKSC' => Carrier::firstOrCreate(
                ['code' => 'EKSC'],
                [
                    'name' => 'Emirates SkyCargo',
                    'mode' => 'AIR',
                    'headquarters_country' => 'United Arab Emirates',
                    'contact_email' => 'skycargosupport@emirates.com',
                    'website' => 'https://www.skycargo.com',
                    'is_active' => true,
                ]
            ),
            'FDX' => Carrier::firstOrCreate(
                ['code' => 'FDX'],
                [
                    'name' => 'FedEx Express Global',
                    'mode' => 'MULTIMODAL',
                    'headquarters_country' => 'United States',
                    'contact_email' => 'trackingsupport@fedex.com',
                    'website' => 'https://www.fedex.com',
                    'is_active' => true,
                ]
            ),
        ];

        // ---------------------------------------------------------------------
        // 3. 8 GLOBAL LOCATIONS LINTAS BENUA
        // ---------------------------------------------------------------------
        $locationsData = [
            'IDJKT' => [
                'unlocode' => 'IDJKT',
                'name' => 'Port of Tanjung Priok',
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
                    'source' => "Lloyd's List Asia",
                    'headline' => 'Tanjung Priok Container Terminal Upgrades Digital Gate System',
                    'summary' => 'Port of Tanjung Priok implements automated optical character recognition at gate 3 to decrease truck queuing time by 35%.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                    'url' => 'https://lloydslist.maritimeintelligence.informa.com/tanjung-priok-gate-upgrade',
                ],
            ],
            'SGSIN' => [
                'unlocode' => 'SGSIN',
                'name' => 'Port of Singapore (PSA & Jurong)',
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
                'name' => 'Yangshan Deepwater Port',
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
                    'source' => 'China Daily Shipping',
                    'headline' => 'Seasonal Sea Fog Causes Intermittent Vessel Pilot Delays in East China Sea',
                    'summary' => 'Yangshan Maritime Safety Administration issues 6-hour holding pattern advisory for inbound ultra-large container carriers.',
                    'impact_level' => 'MEDIUM',
                    'published_at' => Carbon::now()->subHours(8)->toIso8601String(),
                    'url' => 'https://chinadaily.com.cn/shipping/fog-advisory-yangshan',
                ],
            ],
            'NLRTM' => [
                'unlocode' => 'NLRTM',
                'name' => 'Port of Rotterdam (Maasvlakte 2)',
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
                    'summary' => 'Gale force winds gusting above 40 knots force temporary crane speed safety restrictions at Maasvlakte terminals.',
                    'impact_level' => 'MEDIUM',
                    'published_at' => Carbon::now()->subHours(18)->toIso8601String(),
                    'url' => 'https://portofrotterdam.com/news/north-sea-gale-operations',
                ],
            ],
            'USLAX' => [
                'unlocode' => 'USLAX',
                'name' => 'Port of Los Angeles / Long Beach Complex',
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
                    'source' => 'Journal of Commerce',
                    'headline' => 'San Pedro Bay Ports Process Robust Holiday Volume Without Vessel Anchorage Backlogs',
                    'summary' => 'PierPass off-peak incentives maintain fluid gate operations with zero container dwell spikes across San Pedro Bay.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(3)->toIso8601String(),
                    'url' => 'https://joc.com/maritime-news/ports/port-of-la-october-cargo',
                ],
            ],
            'AEDXB' => [
                'unlocode' => 'AEDXB',
                'name' => 'Dubai Jebel Ali Hub & DWC Logistics District',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'currency_code' => 'AED',
                'type' => 'HUB',
                'latitude' => 25.0069350,
                'longitude' => 55.0645060,
                'timezone' => 'Asia/Dubai',
                'weather_snapshot' => [
                    'temperature_c' => 32.0,
                    'feels_like_c' => 35.1,
                    'condition' => 'Sunny & Hazy Desert Atmosphere',
                    'humidity' => 55,
                    'wind_kph' => 16.0,
                    'wind_direction' => 'NNW',
                    'visibility_km' => 9.0,
                    'uv_index' => 8,
                    'pressure_mb' => 1012,
                    'recorded_at' => Carbon::now()->subHours(2)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Gulf News Logistics',
                    'headline' => 'DP World Streamlines Sea-Air Bonded Transit Between Jebel Ali and Al Maktoum Airport',
                    'summary' => 'Seamless digital customs corridor connects seaport container freight to widebody airfreight in under 3.5 hours.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                    'url' => 'https://gulfnews.com/business/aviation/dp-world-air-sea-corridor',
                ],
            ],
            'DEFRA' => [
                'unlocode' => 'DEFRA',
                'name' => 'Frankfurt Airport CargoCity South',
                'city' => 'Frankfurt',
                'country' => 'Germany',
                'country_code' => 'DE',
                'currency_code' => 'EUR',
                'type' => 'AIRPORT',
                'latitude' => 50.0379330,
                'longitude' => 8.5621520,
                'timezone' => 'Europe/Berlin',
                'weather_snapshot' => [
                    'temperature_c' => 9.5,
                    'feels_like_c' => 7.8,
                    'condition' => 'Misty & Overcast',
                    'humidity' => 91,
                    'wind_kph' => 15.0,
                    'wind_direction' => 'NE',
                    'visibility_km' => 6.0,
                    'uv_index' => 1,
                    'pressure_mb' => 1018,
                    'recorded_at' => Carbon::now()->subHours(3)->toIso8601String(),
                ],
                'news_snapshot' => [
                    'source' => 'Air Cargo World',
                    'headline' => 'Fraport Opens State-of-the-Art Pharmaceutical Cold-Chain Terminal',
                    'summary' => 'Certified Good Distribution Practice (GDP) facility expands European vaccine and biomedical air transit capacity.',
                    'impact_level' => 'LOW',
                    'published_at' => Carbon::now()->subDays(4)->toIso8601String(),
                    'url' => 'https://aircargoworld.com/frankfurt-pharma-terminal-expansion',
                ],
            ],
            'AUSYD' => [
                'unlocode' => 'AUSYD',
                'name' => 'Port Botany Intermodal Freight Terminal',
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
        // 4. 6 GLOBAL SHIPMENTS LINTAS BENUA
        // ---------------------------------------------------------------------

        // SHIPMENT 1: Jakarta (IDJKT) -> Rotterdam (NLRTM) [SEA / FCL / IDR -> DELIVERED (MINOR DELAY RESOLVED)]
        $shipment1 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00189'],
            [
                'reference_number' => 'PO-NTR-2026-901',
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
                'notes' => 'Export documentation complete. Clean bill of lading issued by Maersk Indonesia.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'sku' => 'ID-COF-GAYO-01'],
            [
                'description' => 'Specialty Grade 1 Gayo Organic Arabica Green Coffee Beans',
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
                'description' => 'Single Origin Toraja High-Altitude Roasted Whole Beans',
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
                'title' => 'Shipment Booking Created & Cargo Cleared for Export',
                'description' => 'Export booking confirmed with Maersk. 2x 40ft High Cube containers staged at Tanjung Priok terminal.',
                'latitude' => $locations['IDJKT']->latitude,
                'longitude' => $locations['IDJKT']->longitude,
                'weather_snapshot' => [
                    'temperature_c' => 32.0,
                    'condition' => 'Clear & Hot',
                    'humidity' => 75,
                    'wind_kph' => 10.0,
                ],
                'occurred_at' => Carbon::now()->subDays(36),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'PICKED_UP'],
            [
                'location_id' => $locations['IDJKT']->id,
                'event_type' => 'NORMAL',
                'title' => 'Vessel Loaded and Departed Jakarta',
                'description' => 'Containers loaded aboard M/V Maersk Mc-Kinney Moller. Departed Tanjung Priok for Malacca Strait transit.',
                'latitude' => $locations['IDJKT']->latitude,
                'longitude' => $locations['IDJKT']->longitude,
                'weather_snapshot' => [
                    'temperature_c' => 30.5,
                    'condition' => 'Partly Cloudy',
                    'humidity' => 80,
                    'wind_kph' => 14.0,
                ],
                'occurred_at' => Carbon::now()->subDays(35),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => $locations['AEDXB']->id,
                'event_type' => 'NORMAL',
                'title' => 'Vessel Transited Suez Canal & Entered Mediterranean Sea',
                'description' => 'Convoy transit successfully executed without incident. Approaching Gibraltar straits.',
                'latitude' => 27.8540,
                'longitude' => 34.2980,
                'weather_snapshot' => [
                    'temperature_c' => 26.0,
                    'condition' => 'Sunny & Calm',
                    'humidity' => 45,
                    'wind_kph' => 18.0,
                ],
                'occurred_at' => Carbon::now()->subDays(18),
                'created_by' => null,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => 'DELIVERED'],
            [
                'location_id' => $locations['NLRTM']->id,
                'event_type' => 'NORMAL',
                'title' => 'Container Discharged & Gate Out Complete',
                'description' => 'Both containers safely discharged at Maasvlakte Terminal. Consignee logistics representative signed customs release.',
                'latitude' => $locations['NLRTM']->latitude,
                'longitude' => $locations['NLRTM']->longitude,
                'weather_snapshot' => $locations['NLRTM']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(4)->subHours(7),
                'created_by' => $operator->id,
            ]
        );

        ShipmentException::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'exception_code' => 'WEATHER_DELAY'],
            [
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'title' => 'North Sea Gale Weather Hold at Hook of Holland',
                'description' => 'Harbor master suspended ultra-large vessel pilotage into Rotterdam entrance for 17 hours due to 42 knot wind gusts.',
                'related_news_url' => 'https://portofrotterdam.com/news/north-sea-gale-operations',
                'resolution_notes' => 'Wind dropped under safety threshold of 28 knots at dawn. Vessel berthed safely and cargo unloaded with zero damage.',
                'detected_at' => Carbon::now()->subDays(5)->subHours(4),
                'resolved_at' => Carbon::now()->subDays(4)->subHours(11),
                'reported_by' => $operator->id,
                'resolved_by' => $operator->id,
            ]
        );

        // SHIPMENT 2: Shanghai (CNSHA) -> Los Angeles (USLAX) [SEA / FCL / CNY -> IN_TRANSIT (CUSTOMS HOLD DELAY)]
        $shipment2 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-SEA-2026-00244'],
            [
                'reference_number' => 'PO-APL-88390',
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['MSC']->id,
                'origin_location_id' => $locations['CNSHA']->id,
                'destination_location_id' => $locations['USLAX']->id,
                'current_location_id' => $locations['USLAX']->id,
                'cargo_type' => 'FCL',
                'total_weight_kg' => 18200.00,
                'total_volume_cbm' => 54.00,
                'declared_value' => 1850000.00, // CNY 1.85 Million
                'currency_code' => 'CNY',
                'shipping_cost' => 45000.00, // CNY 45k
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
                'expected_arrival_at' => Carbon::now()->addDays(2),
                'estimated_arrival_at' => Carbon::now()->addDays(4)->addHours(10),
                'actual_arrival_at' => null,
                'is_delayed' => true,
                'delay_hours' => 58.00,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'US Customs & Border Protection (CBP) Intensive Exam Hold on Lithium Battery Class 9 Manifest.',
                'route_news_snapshot' => [
                    'source' => 'CBP Trade Outreach',
                    'headline' => 'Heightened Inspection Protocols on Commercial Lithium-Ion Energy Imports at West Coast Ports',
                    'impact' => 'Mandatory x-ray physical verification causing 48-72h terminal hold at LA/Long Beach.',
                    'published_at' => Carbon::now()->subHours(24)->toIso8601String(),
                ],
                'notes' => 'Hazmat documentation (UN3480 / Class 9) attached. Requires bonded warehouse inspection.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'sku' => 'CN-BAT-LIFEPO4-01'],
            [
                'description' => 'Industrial LiFePO4 48V 200Ah Energy Storage Battery Packs (UN3480)',
                'quantity' => 120,
                'unit_value' => 12000.00,
                'currency_code' => 'CNY',
                'weight_kg' => 13000.00,
                'volume_cbm' => 36.00,
                'is_hazardous' => true,
            ]
        );
        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'sku' => 'CN-INV-HYB-02'],
            [
                'description' => 'Grid-Tie Solar Hybrid Three-Phase Inverter Units (15kW)',
                'quantity' => 110,
                'unit_value' => 3727.00,
                'currency_code' => 'CNY',
                'weight_kg' => 5200.00,
                'volume_cbm' => 18.00,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['CNSHA']->id,
                'event_type' => 'NORMAL',
                'title' => 'Export Booking Registered & DG Declarations Approved',
                'description' => 'MSC booking accepted with approved IMO Dangerous Goods declaration for lithium battery modules.',
                'latitude' => $locations['CNSHA']->latitude,
                'longitude' => $locations['CNSHA']->longitude,
                'weather_snapshot' => [
                    'temperature_c' => 19.0,
                    'condition' => 'Overcast',
                    'humidity' => 84,
                    'wind_kph' => 15.0,
                ],
                'occurred_at' => Carbon::now()->subDays(17),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => $locations['USLAX']->id,
                'event_type' => 'EXCEPTION',
                'title' => 'Vessel Arrived at Port of LA Roads; CBP Custom Hold Triggered',
                'description' => 'Container flagged for Vacis X-Ray & physical CET examination upon discharge at Terminal 400.',
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
                'title' => 'US Customs CET Examination & Battery Cert Validation Hold',
                'description' => 'CBP Officer requested supplementary test report certification (UN 38.3 test summary) prior to releasing cargo container.',
                'related_news_url' => 'https://joc.com/maritime-news/ports/port-of-la-october-cargo',
                'resolution_notes' => null,
                'detected_at' => Carbon::now()->subHours(12),
                'resolved_at' => null,
                'reported_by' => $operator->id,
                'resolved_by' => null,
            ]
        );

        // SHIPMENT 3: Frankfurt (DEFRA) -> Singapore (SGSIN) [AIR / REEFER / EUR -> DELIVERED (ON-TIME)]
        $shipment3 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-AIR-2026-00512'],
            [
                'reference_number' => 'PO-BIO-67120',
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['SQC']->id,
                'origin_location_id' => $locations['DEFRA']->id,
                'destination_location_id' => $locations['SGSIN']->id,
                'current_location_id' => $locations['SGSIN']->id,
                'cargo_type' => 'REEFER',
                'total_weight_kg' => 1850.00,
                'total_volume_cbm' => 8.20,
                'declared_value' => 480000.00, // EUR 480,000
                'currency_code' => 'EUR',
                'shipping_cost' => 12400.00, // EUR 12.4k
                'exchange_rate_to_usd' => 1.086956, // 1 EUR = 1.086956 USD (~0.92 EUR/USD)
                'converted_value_usd' => 521738.88,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'EUR' => 0.92, 'SGD' => 1.34, 'IDR' => 15873.0, 'CNY' => 7.23, 'AUD' => 1.52, 'AED' => 3.67],
                    'origin_currency' => 'EUR',
                    'destination_currency' => 'SGD',
                    'rate_origin_to_usd' => 1.086956,
                    'rate_usd_to_dest' => 1.34,
                    'recorded_at' => Carbon::now()->subDays(3)->toIso8601String(),
                ],
                'status' => 'DELIVERED',
                'expected_departure_at' => Carbon::now()->subDays(3),
                'expected_arrival_at' => Carbon::now()->subDays(2),
                'estimated_arrival_at' => Carbon::now()->subDays(2)->subMinutes(30),
                'actual_arrival_at' => Carbon::now()->subDays(2)->subMinutes(45),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'route_news_snapshot' => [
                    'source' => 'IATA Cargo Information Network',
                    'headline' => 'Optimal Jetstream Tailwinds Reduce Europe-Southeast Asia Flight Times',
                    'impact' => 'Boeing 777F cargo flight arrived 45 minutes ahead of schedule.',
                    'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                ],
                'notes' => 'Envirotainer e2 active containers maintaining constant +4°C temperature log throughout flight.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'sku' => 'DE-BIO-VAX-01'],
            [
                'description' => 'Active Cold-Chain Temperature Controlled Bio-Vaccines (+2°C to +8°C)',
                'quantity' => 6,
                'unit_value' => 80000.00,
                'currency_code' => 'EUR',
                'weight_kg' => 1850.00,
                'volume_cbm' => 8.20,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['DEFRA']->id,
                'event_type' => 'NORMAL',
                'title' => 'Pharma Shipment Tendered at CargoCity South',
                'description' => 'Pre-flight temperature log verified at 3.8°C. Transferred to SQ Cargo Pharma Coolcenter.',
                'latitude' => $locations['DEFRA']->latitude,
                'longitude' => $locations['DEFRA']->longitude,
                'weather_snapshot' => $locations['DEFRA']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(3),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'status' => 'DELIVERED'],
            [
                'location_id' => $locations['SGSIN']->id,
                'event_type' => 'NORMAL',
                'title' => 'Delivered to Singapore Changi SATS Coolport',
                'description' => 'Cold chain data logger extracted without breach. Received by Singapore General Hospital biomedical team.',
                'latitude' => $locations['SGSIN']->latitude,
                'longitude' => $locations['SGSIN']->longitude,
                'weather_snapshot' => $locations['SGSIN']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(2)->subMinutes(45),
                'created_by' => $operator->id,
            ]
        );

        // SHIPMENT 4: Singapore (SGSIN) -> Sydney (AUSYD) [AIR / AIR_FREIGHT / SGD -> IN_TRANSIT (LOW DELAY)]
        $shipment4 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-AIR-2026-00678'],
            [
                'reference_number' => 'PO-SEM-8819',
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['FDX']->id,
                'origin_location_id' => $locations['SGSIN']->id,
                'destination_location_id' => $locations['AUSYD']->id,
                'current_location_id' => $locations['SGSIN']->id,
                'cargo_type' => 'AIR_FREIGHT',
                'total_weight_kg' => 840.00,
                'total_volume_cbm' => 3.80,
                'declared_value' => 320000.00, // SGD 320k
                'currency_code' => 'SGD',
                'shipping_cost' => 5600.00, // SGD 5.6k
                'exchange_rate_to_usd' => 0.746268, // 1 SGD = 0.746268 USD (~1.34 SGD/USD)
                'converted_value_usd' => 238805.76,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'SGD' => 1.34, 'AUD' => 1.52, 'EUR' => 0.92, 'IDR' => 15873.0, 'CNY' => 7.23, 'AED' => 3.67],
                    'origin_currency' => 'SGD',
                    'destination_currency' => 'AUD',
                    'rate_origin_to_usd' => 0.746268,
                    'rate_usd_to_dest' => 1.52,
                    'recorded_at' => Carbon::now()->subHours(10)->toIso8601String(),
                ],
                'status' => 'IN_TRANSIT',
                'expected_departure_at' => Carbon::now()->subHours(8),
                'expected_arrival_at' => Carbon::now()->addHours(2),
                'estimated_arrival_at' => Carbon::now()->addHours(5)->addMinutes(30),
                'actual_arrival_at' => null,
                'is_delayed' => true,
                'delay_hours' => 3.50,
                'delay_severity' => 'LOW',
                'delay_reason' => 'Tropical monsoonal storm at Changi delayed ground ramp operations and ATC departure slot.',
                'route_news_snapshot' => [
                    'source' => 'CAAS Aviation Advisory',
                    'headline' => 'Heavy Monsoon Downpours Cause Runway Flow Restrictions at Changi',
                    'impact' => 'Departure taxi delays averaging 45 to 70 minutes.',
                    'published_at' => Carbon::now()->subHours(7)->toIso8601String(),
                ],
                'notes' => 'Electrostatic discharge sensitive components. Air ride trucking scheduled upon landing in Sydney.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'sku' => 'SG-SEMI-ASIC-01'],
            [
                'description' => 'Automotive ASIC Microcontrollers & AI Vision Edge Processors',
                'quantity' => 12,
                'unit_value' => 26666.66,
                'currency_code' => 'SGD',
                'weight_kg' => 840.00,
                'volume_cbm' => 3.80,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['SGSIN']->id,
                'event_type' => 'NORMAL',
                'title' => 'High-Tech Cargo Received at FedEx Changi Facility',
                'description' => 'Palletized and strapped for MD-11 freighter flight FX-9210.',
                'latitude' => $locations['SGSIN']->latitude,
                'longitude' => $locations['SGSIN']->longitude,
                'weather_snapshot' => $locations['SGSIN']->weather_snapshot,
                'occurred_at' => Carbon::now()->subHours(10),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => null,
                'event_type' => 'NORMAL',
                'title' => 'Flight Airborne En Route Across Timor Sea',
                'description' => 'Cruising at FL350. Estimated arrival into Sydney Kingsford Smith Airport revised to 14:00 AEDT.',
                'latitude' => -12.4634,
                'longitude' => 130.8456,
                'weather_snapshot' => [
                    'temperature_c' => -42.0,
                    'condition' => 'High Altitude Clear Sky',
                    'humidity' => 10,
                    'wind_kph' => 85.0,
                ],
                'occurred_at' => Carbon::now()->subHours(4),
                'created_by' => null,
            ]
        );

        // SHIPMENT 5: Dubai (AEDXB) -> Jakarta (IDJKT) [AIR / AIR_FREIGHT / USD -> CREATED]
        $shipment5 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-AIR-2026-00890'],
            [
                'reference_number' => 'PO-OIL-4431',
                'customer_id' => $customer1->id,
                'carrier_id' => $carriers['EKSC']->id,
                'origin_location_id' => $locations['AEDXB']->id,
                'destination_location_id' => $locations['IDJKT']->id,
                'current_location_id' => $locations['AEDXB']->id,
                'cargo_type' => 'AIR_FREIGHT',
                'total_weight_kg' => 4200.00,
                'total_volume_cbm' => 15.40,
                'declared_value' => 650000.00, // USD 650k
                'currency_code' => 'USD',
                'shipping_cost' => 28000.00, // USD 28k
                'exchange_rate_to_usd' => 1.000000,
                'converted_value_usd' => 650000.00,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'AED' => 3.67, 'IDR' => 15873.0, 'EUR' => 0.92, 'SGD' => 1.34, 'CNY' => 7.23, 'AUD' => 1.52],
                    'origin_currency' => 'AED',
                    'destination_currency' => 'IDR',
                    'rate_origin_to_usd' => 1.0,
                    'rate_usd_to_dest' => 15873.0,
                    'recorded_at' => Carbon::now()->subHours(2)->toIso8601String(),
                ],
                'status' => 'CREATED',
                'expected_departure_at' => Carbon::now()->addDays(2),
                'expected_arrival_at' => Carbon::now()->addDays(2)->addHours(12),
                'estimated_arrival_at' => null,
                'actual_arrival_at' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'route_news_snapshot' => [
                    'source' => 'Emirates SkyCargo Network Bulletin',
                    'headline' => 'Dubai Al Maktoum Airport Freight Operations Operating at 100% On-Time Performance',
                    'impact' => 'Cargo handling space clear; flight schedule fully committed.',
                    'published_at' => Carbon::now()->subHours(4)->toIso8601String(),
                ],
                'notes' => 'Heavy industrial machinery parts. Crane rigging required for unloading at Soekarno-Hatta terminal.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment5->id, 'sku' => 'AE-ENG-VALVE-01'],
            [
                'description' => 'High-Pressure Subsea Wellhead Emergency Shut-off Valves',
                'quantity' => 4,
                'unit_value' => 162500.00,
                'currency_code' => 'USD',
                'weight_kg' => 4200.00,
                'volume_cbm' => 15.40,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment5->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['AEDXB']->id,
                'event_type' => 'NORMAL',
                'title' => 'Shipment Booked on Emirates SkyCargo EK-9382',
                'description' => 'Dangerous goods check cleared. Cargo awaiting loading into Boeing 777F lower deck.',
                'latitude' => $locations['AEDXB']->latitude,
                'longitude' => $locations['AEDXB']->longitude,
                'weather_snapshot' => $locations['AEDXB']->weather_snapshot,
                'occurred_at' => Carbon::now()->subHours(2),
                'created_by' => $operator->id,
            ]
        );

        // SHIPMENT 6: Rotterdam (NLRTM) -> Shanghai (CNSHA) [MULTIMODAL / LCL / EUR -> IN_TRANSIT (MEDIUM DELAY)]
        $shipment6 = Shipment::firstOrCreate(
            ['tracking_number' => 'CTG-MUL-2026-00995'],
            [
                'reference_number' => 'PO-EUR-88770',
                'customer_id' => $customer2->id,
                'carrier_id' => $carriers['DHL']->id,
                'origin_location_id' => $locations['NLRTM']->id,
                'destination_location_id' => $locations['CNSHA']->id,
                'current_location_id' => $locations['AEDXB']->id,
                'cargo_type' => 'LCL',
                'total_weight_kg' => 8600.00,
                'total_volume_cbm' => 26.50,
                'declared_value' => 890000.00, // EUR 890k
                'currency_code' => 'EUR',
                'shipping_cost' => 34000.00, // EUR 34k
                'exchange_rate_to_usd' => 1.086956, // 1 EUR = 1.086956 USD
                'converted_value_usd' => 967390.84,
                'currency_snapshot' => [
                    'base' => 'USD',
                    'rates' => ['USD' => 1.0, 'EUR' => 0.92, 'CNY' => 7.23, 'AED' => 3.67, 'IDR' => 15873.0, 'SGD' => 1.34, 'AUD' => 1.52],
                    'origin_currency' => 'EUR',
                    'destination_currency' => 'CNY',
                    'rate_origin_to_usd' => 1.086956,
                    'rate_usd_to_dest' => 7.23,
                    'recorded_at' => Carbon::now()->subDays(11)->toIso8601String(),
                ],
                'status' => 'IN_TRANSIT',
                'expected_departure_at' => Carbon::now()->subDays(11),
                'expected_arrival_at' => Carbon::now()->addDays(7),
                'estimated_arrival_at' => Carbon::now()->addDays(8)->addHours(8),
                'actual_arrival_at' => null,
                'is_delayed' => true,
                'delay_hours' => 32.00,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Missed connecting feeder vessel in Jebel Ali Hub due to Gulf maritime traffic congestion.',
                'route_news_snapshot' => [
                    'source' => "Lloyd's Register Middle East",
                    'headline' => 'Strait of Hormuz Feeder Vessel Turnaround Times Extended by 30 Hours',
                    'impact' => 'Transshipment container consolidations rescheduled for next outbound sailing window.',
                    'published_at' => Carbon::now()->subDays(1)->toIso8601String(),
                ],
                'notes' => 'Multimodal journey: Ocean to Dubai, sea-to-sea transshipment feeder to Shanghai Yangshan.',
            ]
        );

        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'sku' => 'NL-OPT-LITH-01'],
            [
                'description' => 'Precision Photolithography Optics & UV Laser Mirrors',
                'quantity' => 8,
                'unit_value' => 65000.00,
                'currency_code' => 'EUR',
                'weight_kg' => 3800.00,
                'volume_cbm' => 12.00,
                'is_hazardous' => false,
            ]
        );
        ShipmentItem::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'sku' => 'NL-LAB-SPEC-02'],
            [
                'description' => 'Automated Chromatography Mass Spectrometry Analysers',
                'quantity' => 6,
                'unit_value' => 61666.67,
                'currency_code' => 'EUR',
                'weight_kg' => 4800.00,
                'volume_cbm' => 14.50,
                'is_hazardous' => false,
            ]
        );

        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'status' => 'CREATED'],
            [
                'location_id' => $locations['NLRTM']->id,
                'event_type' => 'NORMAL',
                'title' => 'LCL Cargo Consolidated at DHL Rotterdam CFS',
                'description' => 'Container sealed with high-security bolt seal and trucked to terminal.',
                'latitude' => $locations['NLRTM']->latitude,
                'longitude' => $locations['NLRTM']->longitude,
                'weather_snapshot' => $locations['NLRTM']->weather_snapshot,
                'occurred_at' => Carbon::now()->subDays(12),
                'created_by' => $operator->id,
            ]
        );
        TrackingEvent::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'status' => 'IN_TRANSIT'],
            [
                'location_id' => $locations['AEDXB']->id,
                'event_type' => 'EXCEPTION',
                'title' => 'Arrived Jebel Ali Transshipment Hub; Feeder Connection Rescheduled',
                'description' => 'Connecting feeder container ship delayed at berth. Container reallocated to M/V Ever Forward departing in 32 hours.',
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
                'title' => 'Jebel Ali Transshipment Feeder Connection Delay',
                'description' => 'Primary feeder vessel missed arrival window due to berth congestion in the inner basin. Re-manifesting onto secondary vessel.',
                'related_news_url' => 'https://gulfnews.com/business/aviation/dp-world-air-sea-corridor',
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
                'message' => 'Your FCL coffee cargo from Jakarta has arrived and passed customs release at Port of Rotterdam.',
                'read_at' => Carbon::now()->subDays(3),
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment2->id, 'type' => 'CUSTOMS_HOLD_ALERT'],
            [
                'user_id' => $customer2->id,
                'title' => 'Action Required: Customs Hold on CTG-SEA-2026-00244',
                'message' => 'US Customs at Port of Los Angeles has initiated an examination hold on your lithium battery shipment. Please verify UN 38.3 test paperwork.',
                'read_at' => null,
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment3->id, 'type' => 'COLD_CHAIN_DELIVERED'],
            [
                'user_id' => $customer2->id,
                'title' => 'Cold-Chain Delivery Confirmed: CTG-AIR-2026-00512',
                'message' => 'Your biomedical vaccine shipment was delivered to Changi Coolport with zero temperature excursion.',
                'read_at' => Carbon::now()->subDays(1),
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment4->id, 'type' => 'WEATHER_DELAY_NOTICE'],
            [
                'user_id' => $customer1->id,
                'title' => 'Weather Delay Notice for CTG-AIR-2026-00678',
                'message' => 'Tropical thunderstorm at Changi Airport caused a 3.5 hour delay. Expected Sydney landing is now 14:00 AEDT.',
                'read_at' => Carbon::now()->subHours(2),
            ]
        );

        Notification::firstOrCreate(
            ['shipment_id' => $shipment6->id, 'type' => 'PORT_CONGESTION_ALERT'],
            [
                'user_id' => $customer2->id,
                'title' => 'Transshipment Update: CTG-MUL-2026-00995 at Dubai Hub',
                'message' => 'Feeder vessel re-booking in progress at Jebel Ali Hub due to port congestion. Estimated arrival in Shanghai adjusted by +32 hours.',
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
