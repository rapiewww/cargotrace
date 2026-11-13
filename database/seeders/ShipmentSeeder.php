<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Support\Iso6346;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    /**
     * Exchange rate reference table relative to USD.
     */
    protected const FX_RATES_TO_USD = [
        'USD' => 1.0,
        'EUR' => 1.086956,   // 1 / 0.92
        'CNY' => 0.138312,   // 1 / 7.23
        'IDR' => 0.000063,   // 1 / 15873
        'SGD' => 0.746268,   // 1 / 1.34
        'AUD' => 0.657894,   // 1 / 1.52
        'GBP' => 1.265822,   // 1 / 0.79
        'JPY' => 0.006472,   // 1 / 154.5
        'AED' => 0.272479,   // 1 / 3.67
        'BRL' => 0.173010,   // 1 / 5.78
        'HKD' => 0.128534,   // 1 / 7.78
        'KRW' => 0.000714,   // 1 / 1400
        'MYR' => 0.224719,   // 1 / 4.45
        'THB' => 0.029411,   // 1 / 34.0
        'VND' => 0.000039,   // 1 / 25400
        'ZAR' => 0.055555,   // 1 / 18.0
    ];

    /**
     * Run the shipment seeds.
     */
    public function run(): void
    {
        $users = [
            'c1' => User::where('email', 'customer@cargotrace.test')->first(),
            'c2' => User::where('email', 'customer2@cargotrace.test')->first(),
            'c3' => User::where('email', 'customer3@cargotrace.test')->first(),
            'c4' => User::where('email', 'customer4@cargotrace.test')->first(),
        ];

        $carriers = Carrier::all()->keyBy('code');
        $locations = Location::all()->keyBy('unlocode');

        $now = Carbon::now();

        // 45 curated, realistic ocean-container shipments across 6 trade corridors:
        // Status breakdown: 6 CREATED/BOOKED, 20 IN_TRANSIT, 19 DELIVERED (Total = 45)
        // Customer distribution: c1 = 12, c2 = 12, c3 = 11, c4 = 10 (Total = 45)
        $shipmentsData = [
            // =================================================================
            // CANONICAL SAMPLE SHIPMENTS (PRESERVED FROM PRIOR PHASES)
            // =================================================================
            // 1. CTG-SEA-2026-00189: Jakarta -> Rotterdam (Delivered late)
            [
                'tracking_number' => 'CTG-SEA-2026-00189',
                'reference_number' => 'B/L-MAEU-901842',
                'customer_key' => 'c1',
                'carrier_code' => 'MAEU',
                'origin' => 'IDJKT',
                'destination' => 'NLRTM',
                'current' => 'NLRTM',
                'cargo_type' => 'FCL',
                'weight_kg' => 24500.0,
                'volume_cbm' => 68.5,
                'declared_value' => 1450000000.0,
                'currency' => 'IDR',
                'shipping_cost' => 78500000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(5),
                'expected_arrival' => $now->copy()->subDays(5),
                'actual_arrival' => $now->copy()->subDays(4)->subHours(7),
                'estimated_arrival' => $now->copy()->subDays(4)->subHours(6),
                'is_delayed' => true,
                'delay_hours' => 17.0,
                'delay_severity' => 'LOW',
                'delay_reason' => 'North Sea storm gale winds delayed pilot boarding at Maasvlakte entrance.',
                'notes' => 'Export documentation complete. Clean bill of lading issued by Maersk Line Indonesia.',
                'items' => [
                    ['desc' => '40ft High Cube Container: Specialty Grade 1 Gayo Organic Arabica Green Coffee', 'qty' => 350, 'val' => 2600000.0, 'cur' => 'IDR', 'wt' => 14000.0, 'cbm' => 38.0],
                    ['desc' => '20ft Dry Container: Toraja Single Origin High-Altitude Raw Beans', 'qty' => 280, 'val' => 1928571.0, 'cur' => 'IDR', 'wt' => 10500.0, 'cbm' => 30.5],
                ],
            ],

            // 2. CTG-SEA-2026-00244: Shanghai -> Los Angeles (In transit, customs hold, delayed)
            [
                'tracking_number' => 'CTG-SEA-2026-00244',
                'reference_number' => 'B/L-MSCU-440192',
                'customer_key' => 'c2',
                'carrier_code' => 'MSCU',
                'origin' => 'CNSHA',
                'destination' => 'USLAX',
                'current' => 'USLAX',
                'cargo_type' => 'FCL',
                'weight_kg' => 18200.0,
                'volume_cbm' => 54.0,
                'declared_value' => 1850000.0,
                'currency' => 'CNY',
                'shipping_cost' => 62000.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(2)->subDays(2),
                'expected_arrival' => $now->copy()->subDays(2),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(1),
                'is_delayed' => true,
                'delay_hours' => 58.0,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Vessel anchored awaiting CET customs examination berth at Pier 400.',
                'notes' => 'Class 9 Lithium-ion energy storage racks. IMO DG placards verified before trans-Pacific voyage.',
                'items' => [
                    ['desc' => '40ft Dry Container: Commercial Lithium Iron Phosphate Battery Storage Modules', 'qty' => 120, 'val' => 15416.0, 'cur' => 'CNY', 'wt' => 18200.0, 'cbm' => 54.0, 'haz' => true],
                ],
            ],

            // 3. CTG-SEA-2026-00678: Singapore -> Sydney (In transit, low delay)
            [
                'tracking_number' => 'CTG-SEA-2026-00678',
                'reference_number' => 'B/L-ONEY-881902',
                'customer_key' => 'c1',
                'carrier_code' => 'ONEY',
                'origin' => 'SGSIN',
                'destination' => 'AUSYD',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 19840.0,
                'volume_cbm' => 52.8,
                'declared_value' => 320000.0,
                'currency' => 'SGD',
                'shipping_cost' => 8600.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(6),
                'expected_arrival' => $now->copy()->addHours(12),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addHours(15)->addMinutes(30),
                'is_delayed' => true,
                'delay_hours' => 3.5,
                'delay_severity' => 'LOW',
                'delay_reason' => 'Severe swell in Coral Sea required vessel speed adjustment to 14.5 knots.',
                'notes' => 'Precision automation robotics arm components. Keep dry stowage above waterline.',
                'items' => [
                    ['desc' => '40ft HC Container: Six-Axis Industrial Robotic Articulated Arms', 'qty' => 80, 'val' => 4000.0, 'cur' => 'SGD', 'wt' => 19840.0, 'cbm' => 52.8],
                ],
            ],

            // =================================================================
            // CORRIDOR 1: ASIA -> EUROPE (Deepsea Mainline)
            // =================================================================
            // 4. Ningbo -> Hamburg [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00101',
                'reference_number' => 'B/L-HLCU-710291',
                'customer_key' => 'c2',
                'carrier_code' => 'HLCU',
                'origin' => 'CNNGB',
                'destination' => 'DEHAM',
                'current' => 'DEHAM',
                'cargo_type' => 'FCL',
                'weight_kg' => 22400.0,
                'volume_cbm' => 64.0,
                'declared_value' => 165000.0,
                'currency' => 'EUR',
                'shipping_cost' => 4200.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(7),
                'expected_arrival' => $now->copy()->subWeeks(2),
                'actual_arrival' => $now->copy()->subWeeks(2)->subHours(3),
                'estimated_arrival' => $now->copy()->subWeeks(2)->subHours(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Precision machine tool parts from Zhejiang manufacturing corridor.',
                'items' => [
                    ['desc' => '40ft Dry Container: CNC Machining Spindles & Linear Guide Bearings', 'qty' => 200, 'val' => 825.0, 'cur' => 'EUR', 'wt' => 22400.0, 'cbm' => 64.0],
                ],
            ],

            // 5. Shanghai -> Rotterdam [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00102',
                'reference_number' => 'B/L-COSU-551029',
                'customer_key' => 'c2',
                'carrier_code' => 'COSU',
                'origin' => 'CNSHA',
                'destination' => 'NLRTM',
                'current' => 'NLRTM',
                'cargo_type' => 'FCL',
                'weight_kg' => 26000.0,
                'volume_cbm' => 72.0,
                'declared_value' => 210000.0,
                'currency' => 'EUR',
                'shipping_cost' => 4800.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(6),
                'expected_arrival' => $now->copy()->subWeeks(1)->subDays(3),
                'actual_arrival' => $now->copy()->subWeeks(1)->subDays(3),
                'estimated_arrival' => $now->copy()->subWeeks(1)->subDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Solar photovoltaic panels and smart micro-inverters.',
                'items' => [
                    ['desc' => '40ft HC Container: Monocrystalline Bifacial Solar Modules', 'qty' => 620, 'val' => 338.7, 'cur' => 'EUR', 'wt' => 26000.0, 'cbm' => 72.0],
                ],
            ],

            // 6. Shenzhen -> Antwerp [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00103',
                'reference_number' => 'B/L-CMDU-882019',
                'customer_key' => 'c2',
                'carrier_code' => 'CMDU',
                'origin' => 'CNSZX',
                'destination' => 'BEANR',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 21000.0,
                'volume_cbm' => 58.0,
                'declared_value' => 195000.0,
                'currency' => 'EUR',
                'shipping_cost' => 4100.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(2),
                'expected_arrival' => $now->copy()->addWeeks(2),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addWeeks(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Consumer electronics and telecom network switches. Transiting Singapore hub.',
                'items' => [
                    ['desc' => '40ft Dry Container: Optical Fiber Network Switches & Routing Units', 'qty' => 450, 'val' => 433.3, 'cur' => 'EUR', 'wt' => 21000.0, 'cbm' => 58.0],
                ],
            ],

            // 7. Busan -> Rotterdam [IN_TRANSIT MEDIUM DELAY]
            [
                'tracking_number' => 'CTG-SEA-2026-00104',
                'reference_number' => 'B/L-HMMU-990123',
                'customer_key' => 'c2',
                'carrier_code' => 'HMMU',
                'origin' => 'KRPUS',
                'destination' => 'NLRTM',
                'current' => 'AEDXB',
                'cargo_type' => 'FCL',
                'weight_kg' => 25200.0,
                'volume_cbm' => 66.0,
                'declared_value' => 280000.0,
                'currency' => 'EUR',
                'shipping_cost' => 5200.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(3),
                'expected_arrival' => $now->copy()->addDays(5),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(5)->addHours(16),
                'is_delayed' => true,
                'delay_hours' => 16.0,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Port of Jebel Ali berth queue caused 16-hour transshipment departure lag.',
                'notes' => 'Automotive powertrain components and steel castings from Ulsan.',
                'items' => [
                    ['desc' => '40ft Heavy Duty Container: High-Tensile Automotive Crankshafts', 'qty' => 380, 'val' => 736.8, 'cur' => 'EUR', 'wt' => 25200.0, 'cbm' => 66.0],
                ],
            ],

            // 8. Singapore -> Hamburg [BOOKED]
            [
                'tracking_number' => 'CTG-SEA-2026-00105',
                'reference_number' => 'B/L-MAEU-110294',
                'customer_key' => 'c2',
                'carrier_code' => 'MAEU',
                'origin' => 'SGSIN',
                'destination' => 'DEHAM',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 19500.0,
                'volume_cbm' => 55.0,
                'declared_value' => 140000.0,
                'currency' => 'EUR',
                'shipping_cost' => 3800.0,
                'status' => 'BOOKED',
                'expected_departure' => $now->copy()->addDays(3),
                'expected_arrival' => $now->copy()->addWeeks(4)->addDays(2),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addWeeks(4)->addDays(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Laden containers received at Tuas Terminal container freight station.',
                'items' => [
                    ['desc' => '40ft Dry Container: Industrial Polymer Resins in Bulk Bags', 'qty' => 500, 'val' => 280.0, 'cur' => 'EUR', 'wt' => 19500.0, 'cbm' => 55.0],
                ],
            ],

            // 9. Piraeus -> Rotterdam [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00106',
                'reference_number' => 'B/L-COSU-661028',
                'customer_key' => 'c2',
                'carrier_code' => 'COSU',
                'origin' => 'GRPIR',
                'destination' => 'NLRTM',
                'current' => 'NLRTM',
                'cargo_type' => 'FCL',
                'weight_kg' => 18000.0,
                'volume_cbm' => 50.0,
                'declared_value' => 95000.0,
                'currency' => 'EUR',
                'shipping_cost' => 1800.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(3),
                'expected_arrival' => $now->copy()->subWeeks(1)->subDays(4),
                'actual_arrival' => $now->copy()->subWeeks(1)->subDays(4)->subHours(2),
                'estimated_arrival' => $now->copy()->subWeeks(1)->subDays(4)->subHours(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Mediterranean feeder leg linking Piraeus PCT to North Sea gateway.',
                'items' => [
                    ['desc' => '20ft Container: Greek Extra Virgin Olive Oil Drums', 'qty' => 120, 'val' => 791.6, 'cur' => 'EUR', 'wt' => 18000.0, 'cbm' => 50.0],
                ],
            ],

            // 10. Shanghai -> Valencia [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00107',
                'reference_number' => 'B/L-CMDU-772910',
                'customer_key' => 'c2',
                'carrier_code' => 'CMDU',
                'origin' => 'CNSHA',
                'destination' => 'ESVLC',
                'current' => 'GRPIR',
                'cargo_type' => 'FCL',
                'weight_kg' => 23000.0,
                'volume_cbm' => 62.0,
                'declared_value' => 175000.0,
                'currency' => 'EUR',
                'shipping_cost' => 3900.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(3)->subDays(2),
                'expected_arrival' => $now->copy()->addDays(4),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(4),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Direct call via Suez Canal corridor entering Mediterranean waters.',
                'items' => [
                    ['desc' => '40ft Dry Container: Ceramic Tiles and Engineered Quartz Slabs', 'qty' => 400, 'val' => 437.5, 'cur' => 'EUR', 'wt' => 23000.0, 'cbm' => 62.0],
                ],
            ],

            // =================================================================
            // CORRIDOR 2: ASIA -> NORTH AMERICA (Transpacific Eastbound)
            // =================================================================
            // 11. Shanghai -> Los Angeles [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00201',
                'reference_number' => 'B/L-MAEU-402918',
                'customer_key' => 'c3',
                'carrier_code' => 'MAEU',
                'origin' => 'CNSHA',
                'destination' => 'USLAX',
                'current' => 'USLAX',
                'cargo_type' => 'FCL',
                'weight_kg' => 20500.0,
                'volume_cbm' => 58.0,
                'declared_value' => 240000.0,
                'currency' => 'USD',
                'shipping_cost' => 3900.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(6),
                'expected_arrival' => $now->copy()->subWeeks(3),
                'actual_arrival' => $now->copy()->subWeeks(3)->subHours(5),
                'estimated_arrival' => $now->copy()->subWeeks(3)->subHours(5),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'High-density consumer electronics; Pier 400 offloaded with no demurrage.',
                'items' => [
                    ['desc' => '40ft HC Container: Consumer Smart Home Automation Hubs', 'qty' => 850, 'val' => 282.35, 'cur' => 'USD', 'wt' => 20500.0, 'cbm' => 58.0],
                ],
            ],

            // 12. Shenzhen -> New York [DELIVERED LATE]
            [
                'tracking_number' => 'CTG-SEA-2026-00202',
                'reference_number' => 'B/L-COSU-991044',
                'customer_key' => 'c3',
                'carrier_code' => 'COSU',
                'origin' => 'CNSZX',
                'destination' => 'USNYC',
                'current' => 'USNYC',
                'cargo_type' => 'FCL',
                'weight_kg' => 24000.0,
                'volume_cbm' => 65.0,
                'declared_value' => 310000.0,
                'currency' => 'USD',
                'shipping_cost' => 5800.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(7),
                'expected_arrival' => $now->copy()->subWeeks(2)->subDays(1),
                'actual_arrival' => $now->copy()->subWeeks(2)->addHours(8),
                'estimated_arrival' => $now->copy()->subWeeks(2)->addHours(8),
                'is_delayed' => true,
                'delay_hours' => 32.0,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Panama Canal neopanamax lock scheduling queue backlog.',
                'notes' => 'All-water East Coast loop via Panama Canal.',
                'items' => [
                    ['desc' => '40ft HC Container: Industrial Power Inverters & Battery Enclosures', 'qty' => 320, 'val' => 968.75, 'cur' => 'USD', 'wt' => 24000.0, 'cbm' => 65.0],
                ],
            ],

            // 13. Busan -> Los Angeles [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00203',
                'reference_number' => 'B/L-HMMU-332910',
                'customer_key' => 'c3',
                'carrier_code' => 'HMMU',
                'origin' => 'KRPUS',
                'destination' => 'USLAX',
                'current' => 'USLAX',
                'cargo_type' => 'FCL',
                'weight_kg' => 19000.0,
                'volume_cbm' => 52.0,
                'declared_value' => 180000.0,
                'currency' => 'USD',
                'shipping_cost' => 3400.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(11),
                'expected_arrival' => $now->copy()->addDays(2),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Vessel completing great circle northern Pacific routing.',
                'items' => [
                    ['desc' => '40ft Container: Pre-fabricated Semiconductor Cleanroom Panels', 'qty' => 150, 'val' => 1200.0, 'cur' => 'USD', 'wt' => 19000.0, 'cbm' => 52.0],
                ],
            ],

            // 14. Tokyo -> Savannah [IN_TRANSIT HIGH DELAY]
            [
                'tracking_number' => 'CTG-SEA-2026-00204',
                'reference_number' => 'B/L-ONEY-220194',
                'customer_key' => 'c3',
                'carrier_code' => 'ONEY',
                'origin' => 'JPTYO',
                'destination' => 'USSAV',
                'current' => 'PAPTY',
                'cargo_type' => 'FCL',
                'weight_kg' => 21500.0,
                'volume_cbm' => 60.0,
                'declared_value' => 290000.0,
                'currency' => 'USD',
                'shipping_cost' => 5400.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(3),
                'expected_arrival' => $now->copy()->addDays(3),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(5)->addHours(4),
                'is_delayed' => true,
                'delay_hours' => 52.0,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Balboa anchorage fog hold and auxiliary boiler maintenance in canal basin.',
                'notes' => 'Automotive precision transmission sub-assemblies.',
                'items' => [
                    ['desc' => '40ft Dry Container: Hybrid Vehicle Planetary Gear Sets', 'qty' => 240, 'val' => 1208.33, 'cur' => 'USD', 'wt' => 21500.0, 'cbm' => 60.0],
                ],
            ],

            // 15. Ningbo -> Los Angeles [IN_TRANSIT LOW DELAY]
            [
                'tracking_number' => 'CTG-SEA-2026-00205',
                'reference_number' => 'B/L-EGLV-440182',
                'customer_key' => 'c3',
                'carrier_code' => 'EGLV',
                'origin' => 'CNNGB',
                'destination' => 'USLAX',
                'current' => 'USLAX',
                'cargo_type' => 'FCL',
                'weight_kg' => 23500.0,
                'volume_cbm' => 64.0,
                'declared_value' => 220000.0,
                'currency' => 'USD',
                'shipping_cost' => 3600.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(13),
                'expected_arrival' => $now->copy()->addHours(18),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(1)->subHours(2),
                'is_delayed' => true,
                'delay_hours' => 4.0,
                'delay_severity' => 'LOW',
                'delay_reason' => 'Strong head currents off San Clemente Island reduced vessel cruising speed.',
                'notes' => 'Apparel and footwear consignment for holiday retail distribution.',
                'items' => [
                    ['desc' => '40ft HC Container: Garments On Hanger (GOH) Textile Consignment', 'qty' => 1200, 'val' => 183.33, 'cur' => 'USD', 'wt' => 23500.0, 'cbm' => 64.0],
                ],
            ],

            // 16. Hong Kong -> New York [CREATED]
            [
                'tracking_number' => 'CTG-SEA-2026-00206',
                'reference_number' => 'B/L-ZIMU-881902',
                'customer_key' => 'c3',
                'carrier_code' => 'ZIMU',
                'origin' => 'HKHKG',
                'destination' => 'USNYC',
                'current' => 'HKHKG',
                'cargo_type' => 'FCL',
                'weight_kg' => 17800.0,
                'volume_cbm' => 48.0,
                'declared_value' => 165000.0,
                'currency' => 'USD',
                'shipping_cost' => 4600.0,
                'status' => 'CREATED',
                'expected_departure' => $now->copy()->addDays(5),
                'expected_arrival' => $now->copy()->addWeeks(4)->addDays(4),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addWeeks(4)->addDays(4),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Booking accepted; awaiting customs export declaration at Kwai Tsing gate.',
                'items' => [
                    ['desc' => '40ft Dry Container: Commercial Medical Diagnostic Monitor Kits', 'qty' => 300, 'val' => 550.0, 'cur' => 'USD', 'wt' => 17800.0, 'cbm' => 48.0],
                ],
            ],

            // 17. Shanghai -> Savannah [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00207',
                'reference_number' => 'B/L-YMLU-112093',
                'customer_key' => 'c3',
                'carrier_code' => 'YMLU',
                'origin' => 'CNSHA',
                'destination' => 'USSAV',
                'current' => 'USSAV',
                'cargo_type' => 'FCL',
                'weight_kg' => 22000.0,
                'volume_cbm' => 62.0,
                'declared_value' => 190000.0,
                'currency' => 'USD',
                'shipping_cost' => 4900.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(8),
                'expected_arrival' => $now->copy()->subWeeks(3)->subDays(2),
                'actual_arrival' => $now->copy()->subWeeks(3)->subDays(2),
                'estimated_arrival' => $now->copy()->subWeeks(3)->subDays(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Clean delivery to Garden City Terminal intermodal rail ramp.',
                'items' => [
                    ['desc' => '40ft Dry Container: Furniture & Commercial Architectural Millwork', 'qty' => 420, 'val' => 452.38, 'cur' => 'USD', 'wt' => 22000.0, 'cbm' => 62.0],
                ],
            ],

            // =================================================================
            // CORRIDOR 3: SOUTHEAST ASIA -> AUSTRALIA
            // =================================================================
            // 18. Jakarta -> Sydney [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00301',
                'reference_number' => 'B/L-MAEU-501928',
                'customer_key' => 'c1',
                'carrier_code' => 'MAEU',
                'origin' => 'IDJKT',
                'destination' => 'AUSYD',
                'current' => 'AUSYD',
                'cargo_type' => 'FCL',
                'weight_kg' => 21800.0,
                'volume_cbm' => 58.0,
                'declared_value' => 1650000000.0,
                'currency' => 'IDR',
                'shipping_cost' => 42000000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(4),
                'expected_arrival' => $now->copy()->subWeeks(2)->subDays(1),
                'actual_arrival' => $now->copy()->subWeeks(2)->subDays(1)->subHours(4),
                'estimated_arrival' => $now->copy()->subWeeks(2)->subDays(1)->subHours(4),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Indonesian timber plywood & certified teak flooring consignments.',
                'items' => [
                    ['desc' => '40ft Dry Container: Certified Sustainable Engineered Teak Flooring', 'qty' => 450, 'val' => 3666666.0, 'cur' => 'IDR', 'wt' => 21800.0, 'cbm' => 58.0],
                ],
            ],

            // 19. Port Klang -> Sydney [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00302',
                'reference_number' => 'B/L-ONEY-661092',
                'customer_key' => 'c1',
                'carrier_code' => 'ONEY',
                'origin' => 'MYPKG',
                'destination' => 'AUSYD',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 20200.0,
                'volume_cbm' => 54.0,
                'declared_value' => 260000.0,
                'currency' => 'AUD',
                'shipping_cost' => 4800.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(8),
                'expected_arrival' => $now->copy()->addDays(6),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(6),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Semiconductor packaging components from Penang corridor.',
                'items' => [
                    ['desc' => '40ft Dry Container: Precision IC Substrates & Leadframes', 'qty' => 600, 'val' => 433.33, 'cur' => 'AUD', 'wt' => 20200.0, 'cbm' => 54.0],
                ],
            ],

            // 20. Laem Chabang -> Sydney [DELIVERED LATE]
            [
                'tracking_number' => 'CTG-SEA-2026-00303',
                'reference_number' => 'B/L-MSCU-330192',
                'customer_key' => 'c1',
                'carrier_code' => 'MSCU',
                'origin' => 'THLCH',
                'destination' => 'AUSYD',
                'current' => 'AUSYD',
                'cargo_type' => 'FCL',
                'weight_kg' => 23000.0,
                'volume_cbm' => 60.0,
                'declared_value' => 210000.0,
                'currency' => 'AUD',
                'shipping_cost' => 4400.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(5),
                'expected_arrival' => $now->copy()->subWeeks(3),
                'actual_arrival' => $now->copy()->subWeeks(3)->addHours(14),
                'estimated_arrival' => $now->copy()->subWeeks(3)->addHours(14),
                'is_delayed' => true,
                'delay_hours' => 14.0,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Port Botany DAFF mandatory biosecurity treatment clearance.',
                'notes' => 'Agricultural rubber machinery & tractor components.',
                'items' => [
                    ['desc' => '40ft Heavy Container: Agricultural Tractor Drive Axles', 'qty' => 140, 'val' => 1500.0, 'cur' => 'AUD', 'wt' => 23000.0, 'cbm' => 60.0],
                ],
            ],

            // 21. Singapore -> Sydney [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00304',
                'reference_number' => 'B/L-CMDU-901844',
                'customer_key' => 'c1',
                'carrier_code' => 'CMDU',
                'origin' => 'SGSIN',
                'destination' => 'AUSYD',
                'current' => 'AUSYD',
                'cargo_type' => 'FCL',
                'weight_kg' => 18500.0,
                'volume_cbm' => 49.0,
                'declared_value' => 240000.0,
                'currency' => 'SGD',
                'shipping_cost' => 5100.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(10),
                'expected_arrival' => $now->copy()->addDays(3),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Medical lab reagents and diagnostic consumables in temperature-controlled reefers.',
                'items' => [
                    ['desc' => '40ft Reefer Container: Laboratory In-Vitro Diagnostic Reagents', 'qty' => 480, 'val' => 500.0, 'cur' => 'SGD', 'wt' => 18500.0, 'cbm' => 49.0],
                ],
            ],

            // 22. Jakarta -> Sydney [BOOKED]
            [
                'tracking_number' => 'CTG-SEA-2026-00305',
                'reference_number' => 'B/L-COSU-119284',
                'customer_key' => 'c1',
                'carrier_code' => 'COSU',
                'origin' => 'IDJKT',
                'destination' => 'AUSYD',
                'current' => 'IDJKT',
                'cargo_type' => 'FCL',
                'weight_kg' => 19000.0,
                'volume_cbm' => 52.0,
                'declared_value' => 980000000.0,
                'currency' => 'IDR',
                'shipping_cost' => 38000000.0,
                'status' => 'BOOKED',
                'expected_departure' => $now->copy()->addDays(4),
                'expected_arrival' => $now->copy()->addWeeks(2)->addDays(3),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addWeeks(2)->addDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Desiccated coconut and palm-based oleochemical drums booked on COSCO feeder.',
                'items' => [
                    ['desc' => '20ft Container: Refined Palm Fatty Acid Distillate Drums', 'qty' => 100, 'val' => 9800000.0, 'cur' => 'IDR', 'wt' => 19000.0, 'cbm' => 52.0],
                ],
            ],

            // =================================================================
            // CORRIDOR 4: INTRA-ASIA (Short-Sea & Feeder Lanes)
            // =================================================================
            // 23. Shanghai -> Jakarta [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00401',
                'reference_number' => 'B/L-COSU-801948',
                'customer_key' => 'c1',
                'carrier_code' => 'COSU',
                'origin' => 'CNSHA',
                'destination' => 'IDJKT',
                'current' => 'IDJKT',
                'cargo_type' => 'FCL',
                'weight_kg' => 24000.0,
                'volume_cbm' => 62.0,
                'declared_value' => 880000.0,
                'currency' => 'CNY',
                'shipping_cost' => 18000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(5),
                'expected_arrival' => $now->copy()->subWeeks(3)->subDays(3),
                'actual_arrival' => $now->copy()->subWeeks(3)->subDays(3),
                'estimated_arrival' => $now->copy()->subWeeks(3)->subDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Structural steel fasteners & mechanical components for Java infrastructure project.',
                'items' => [
                    ['desc' => '20ft Heavy Container: High-Tensile Structural Fasteners & Stud Bolts', 'qty' => 240, 'val' => 3666.6, 'cur' => 'CNY', 'wt' => 24000.0, 'cbm' => 62.0],
                ],
            ],

            // 24. Tokyo -> Singapore [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00402',
                'reference_number' => 'B/L-ONEY-550192',
                'customer_key' => 'c4',
                'carrier_code' => 'ONEY',
                'origin' => 'JPTYO',
                'destination' => 'SGSIN',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 16500.0,
                'volume_cbm' => 45.0,
                'declared_value' => 38000000.0,
                'currency' => 'JPY',
                'shipping_cost' => 420000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(4),
                'expected_arrival' => $now->copy()->subWeeks(3),
                'actual_arrival' => $now->copy()->subWeeks(3)->subHours(2),
                'estimated_arrival' => $now->copy()->subWeeks(3)->subHours(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Precision camera optics & wafer inspection stepper components.',
                'items' => [
                    ['desc' => '40ft Dry Container: Optical Glass Prism Elements & Calibration Stages', 'qty' => 180, 'val' => 211111.0, 'cur' => 'JPY', 'wt' => 16500.0, 'cbm' => 45.0],
                ],
            ],

            // 25. Busan -> Ho Chi Minh City [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00403',
                'reference_number' => 'B/L-HMMU-440192',
                'customer_key' => 'c4',
                'carrier_code' => 'HMMU',
                'origin' => 'KRPUS',
                'destination' => 'VNSGN',
                'current' => 'CNSZX',
                'cargo_type' => 'FCL',
                'weight_kg' => 22000.0,
                'volume_cbm' => 58.0,
                'declared_value' => 195000000.0,
                'currency' => 'KRW',
                'shipping_cost' => 2800000.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(6),
                'expected_arrival' => $now->copy()->addDays(3),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Synthetic textile yarns for Binh Duong garment assembly plant.',
                'items' => [
                    ['desc' => '40ft Dry Container: Industrial Polyester Filament Yarn Spools', 'qty' => 800, 'val' => 243750.0, 'cur' => 'KRW', 'wt' => 22000.0, 'cbm' => 58.0],
                ],
            ],

            // 26. Shenzhen -> Port Klang [IN_TRANSIT LOW DELAY]
            [
                'tracking_number' => 'CTG-SEA-2026-00404',
                'reference_number' => 'B/L-CMDU-661029',
                'customer_key' => 'c1',
                'carrier_code' => 'CMDU',
                'origin' => 'CNSZX',
                'destination' => 'MYPKG',
                'current' => 'VNSGN',
                'cargo_type' => 'FCL',
                'weight_kg' => 19500.0,
                'volume_cbm' => 50.0,
                'declared_value' => 740000.0,
                'currency' => 'CNY',
                'shipping_cost' => 15000.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(7),
                'expected_arrival' => $now->copy()->addHours(8),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addHours(13)->addMinutes(30),
                'is_delayed' => true,
                'delay_hours' => 5.5,
                'delay_severity' => 'LOW',
                'delay_reason' => 'South China Sea heavy monsoonal rain squalls reduced cruising speed.',
                'notes' => 'Air conditioner rotary compressors for Selangor assembly line.',
                'items' => [
                    ['desc' => '40ft Dry Container: Hermetic Inverter Rotary Compressors', 'qty' => 550, 'val' => 1345.45, 'cur' => 'CNY', 'wt' => 19500.0, 'cbm' => 50.0],
                ],
            ],

            // 27. Ningbo -> Laem Chabang [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00405',
                'reference_number' => 'B/L-EGLV-220194',
                'customer_key' => 'c4',
                'carrier_code' => 'EGLV',
                'origin' => 'CNNGB',
                'destination' => 'THLCH',
                'current' => 'THLCH',
                'cargo_type' => 'FCL',
                'weight_kg' => 21000.0,
                'volume_cbm' => 56.0,
                'declared_value' => 620000.0,
                'currency' => 'CNY',
                'shipping_cost' => 14000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(3),
                'expected_arrival' => $now->copy()->subWeeks(1)->subDays(6),
                'actual_arrival' => $now->copy()->subWeeks(1)->subDays(6),
                'estimated_arrival' => $now->copy()->subWeeks(1)->subDays(6),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Automotive stamping dies and hydraulic press tooling.',
                'items' => [
                    ['desc' => '20ft Heavy Container: High-Precision Progressive Stamping Dies', 'qty' => 6, 'val' => 103333.3, 'cur' => 'CNY', 'wt' => 21000.0, 'cbm' => 56.0],
                ],
            ],

            // 28. Singapore -> Jakarta [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00406',
                'reference_number' => 'B/L-MAEU-881029',
                'customer_key' => 'c1',
                'carrier_code' => 'MAEU',
                'origin' => 'SGSIN',
                'destination' => 'IDJKT',
                'current' => 'IDJKT',
                'cargo_type' => 'FCL',
                'weight_kg' => 17000.0,
                'volume_cbm' => 46.0,
                'declared_value' => 145000.0,
                'currency' => 'SGD',
                'shipping_cost' => 2100.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(2),
                'expected_arrival' => $now->copy()->addHours(6),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addHours(6),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Shuttle feeder across Sunda Strait corridor entering Tanjung Priok outer roads.',
                'items' => [
                    ['desc' => '20ft Dry Container: Water Treatment Poly-Aluminium Chloride Coagulant', 'qty' => 180, 'val' => 805.5, 'cur' => 'SGD', 'wt' => 17000.0, 'cbm' => 46.0],
                ],
            ],

            // 29. Hong Kong -> Tokyo [DELIVERED LATE]
            [
                'tracking_number' => 'CTG-SEA-2026-00407',
                'reference_number' => 'B/L-ONEY-772910',
                'customer_key' => 'c4',
                'carrier_code' => 'ONEY',
                'origin' => 'HKHKG',
                'destination' => 'JPTYO',
                'current' => 'JPTYO',
                'cargo_type' => 'FCL',
                'weight_kg' => 18800.0,
                'volume_cbm' => 51.0,
                'declared_value' => 29000000.0,
                'currency' => 'JPY',
                'shipping_cost' => 380000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(4),
                'expected_arrival' => $now->copy()->subWeeks(2)->subDays(4),
                'actual_arrival' => $now->copy()->subWeeks(2)->subDays(3)->addHours(4),
                'estimated_arrival' => $now->copy()->subWeeks(2)->subDays(3)->addHours(4),
                'is_delayed' => true,
                'delay_hours' => 20.0,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Subtropical low off Okinawa forced vessel to alter course south.',
                'notes' => 'Commercial display panels for Kanto regional assembly center.',
                'items' => [
                    ['desc' => '40ft HC Container: Industrial Touchscreen Displays & Bezels', 'qty' => 350, 'val' => 82857.0, 'cur' => 'JPY', 'wt' => 18800.0, 'cbm' => 51.0],
                ],
            ],

            // 30. Jakarta -> Singapore [CREATED]
            [
                'tracking_number' => 'CTG-SEA-2026-00408',
                'reference_number' => 'B/L-MAEU-991024',
                'customer_key' => 'c1',
                'carrier_code' => 'MAEU',
                'origin' => 'IDJKT',
                'destination' => 'SGSIN',
                'current' => 'IDJKT',
                'cargo_type' => 'FCL',
                'weight_kg' => 21000.0,
                'volume_cbm' => 55.0,
                'declared_value' => 780000000.0,
                'currency' => 'IDR',
                'shipping_cost' => 24000000.0,
                'status' => 'CREATED',
                'expected_departure' => $now->copy()->addDays(2),
                'expected_arrival' => $now->copy()->addDays(5),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(5),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Container booking confirmed; waiting for feeder vessel berthing slot at JICT.',
                'items' => [
                    ['desc' => '40ft Container: High-Grade Industrial Cocoa Butter Cartons', 'qty' => 400, 'val' => 1950000.0, 'cur' => 'IDR', 'wt' => 21000.0, 'cbm' => 55.0],
                ],
            ],

            // =================================================================
            // CORRIDOR 5: EUROPE -> AMERICAS (Transatlantic & South America)
            // =================================================================
            // 31. Rotterdam -> New York [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00501',
                'reference_number' => 'B/L-MAEU-770192',
                'customer_key' => 'c2',
                'carrier_code' => 'MAEU',
                'origin' => 'NLRTM',
                'destination' => 'USNYC',
                'current' => 'USNYC',
                'cargo_type' => 'FCL',
                'weight_kg' => 20000.0,
                'volume_cbm' => 55.0,
                'declared_value' => 175000.0,
                'currency' => 'EUR',
                'shipping_cost' => 3200.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(5),
                'expected_arrival' => $now->copy()->subWeeks(3)->subDays(4),
                'actual_arrival' => $now->copy()->subWeeks(3)->subDays(4)->subHours(3),
                'estimated_arrival' => $now->copy()->subWeeks(3)->subDays(4)->subHours(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'European confectionery and specialty brewing malt in dry van.',
                'items' => [
                    ['desc' => '40ft Dry Container: Artisanal Belgian Chocolate Blocks & Cocoa Mass', 'qty' => 600, 'val' => 291.66, 'cur' => 'EUR', 'wt' => 20000.0, 'cbm' => 55.0],
                ],
            ],

            // 32. Antwerp -> Santos [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00502',
                'reference_number' => 'B/L-MSCU-881920',
                'customer_key' => 'c2',
                'carrier_code' => 'MSCU',
                'origin' => 'BEANR',
                'destination' => 'BRSSZ',
                'current' => 'BRSSZ',
                'cargo_type' => 'FCL',
                'weight_kg' => 23400.0,
                'volume_cbm' => 62.0,
                'declared_value' => 160000.0,
                'currency' => 'EUR',
                'shipping_cost' => 4500.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(2)->subDays(2),
                'expected_arrival' => $now->copy()->addDays(4),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(4),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Southbound Atlantic lane crossing equator toward Santos BTP terminal.',
                'items' => [
                    ['desc' => '40ft Container: Agro-Chemical Fertilizer Formulation Additives', 'qty' => 450, 'val' => 355.55, 'cur' => 'EUR', 'wt' => 23400.0, 'cbm' => 62.0],
                ],
            ],

            // 33. Hamburg -> Los Angeles [DELIVERED LATE]
            [
                'tracking_number' => 'CTG-SEA-2026-00503',
                'reference_number' => 'B/L-HLCU-990184',
                'customer_key' => 'c2',
                'carrier_code' => 'HLCU',
                'origin' => 'DEHAM',
                'destination' => 'USLAX',
                'current' => 'USLAX',
                'cargo_type' => 'FCL',
                'weight_kg' => 22000.0,
                'volume_cbm' => 59.0,
                'declared_value' => 280000.0,
                'currency' => 'EUR',
                'shipping_cost' => 6200.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(8),
                'expected_arrival' => $now->copy()->subWeeks(3),
                'actual_arrival' => $now->copy()->subWeeks(3)->addHours(22),
                'estimated_arrival' => $now->copy()->subWeeks(3)->addHours(22),
                'is_delayed' => true,
                'delay_hours' => 22.0,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Panama Canal transit convoy maintenance slowed Pacific transit.',
                'notes' => 'German engineered industrial pump skids and variable-speed drives.',
                'items' => [
                    ['desc' => '40ft Open Top Container: Heavy-Duty Industrial Centrifugal Slurry Pumps', 'qty' => 4, 'val' => 70000.0, 'cur' => 'EUR', 'wt' => 22000.0, 'cbm' => 59.0],
                ],
            ],

            // 34. Valencia -> Balboa [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00504',
                'reference_number' => 'B/L-CMDU-110298',
                'customer_key' => 'c2',
                'carrier_code' => 'CMDU',
                'origin' => 'ESVLC',
                'destination' => 'PAPTY',
                'current' => 'PAPTY',
                'cargo_type' => 'FCL',
                'weight_kg' => 20500.0,
                'volume_cbm' => 54.0,
                'declared_value' => 125000.0,
                'currency' => 'EUR',
                'shipping_cost' => 3600.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(12),
                'expected_arrival' => $now->copy()->addDays(2),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Transatlantic westbound entering Caribbean approach.',
                'items' => [
                    ['desc' => '40ft Dry Container: Spanish Ceramic Wall Tiles & Glazed Porcelain', 'qty' => 380, 'val' => 328.94, 'cur' => 'EUR', 'wt' => 20500.0, 'cbm' => 54.0],
                ],
            ],

            // 35. Rotterdam -> Santos [BOOKED]
            [
                'tracking_number' => 'CTG-SEA-2026-00505',
                'reference_number' => 'B/L-MSCU-441092',
                'customer_key' => 'c2',
                'carrier_code' => 'MSCU',
                'origin' => 'NLRTM',
                'destination' => 'BRSSZ',
                'current' => 'NLRTM',
                'cargo_type' => 'FCL',
                'weight_kg' => 24500.0,
                'volume_cbm' => 66.0,
                'declared_value' => 185000.0,
                'currency' => 'EUR',
                'shipping_cost' => 4300.0,
                'status' => 'BOOKED',
                'expected_departure' => $now->copy()->addDays(3),
                'expected_arrival' => $now->copy()->addWeeks(3)->addDays(1),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addWeeks(3)->addDays(1),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Export containers sealed at Maasvlakte rail transfer depot.',
                'items' => [
                    ['desc' => '40ft Container: Food Processing Sanitation Equipment', 'qty' => 120, 'val' => 1541.66, 'cur' => 'EUR', 'wt' => 24500.0, 'cbm' => 66.0],
                ],
            ],

            // =================================================================
            // CORRIDOR 6: MIDDLE EAST & AFRICA -> ASIA / WORLD
            // =================================================================
            // 36. Jebel Ali -> Singapore [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00601',
                'reference_number' => 'B/L-MAEU-220198',
                'customer_key' => 'c1',
                'carrier_code' => 'MAEU',
                'origin' => 'AEDXB',
                'destination' => 'SGSIN',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 22000.0,
                'volume_cbm' => 58.0,
                'declared_value' => 650000.0,
                'currency' => 'AED',
                'shipping_cost' => 11000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(4),
                'expected_arrival' => $now->copy()->subWeeks(2)->subDays(3),
                'actual_arrival' => $now->copy()->subWeeks(2)->subDays(3),
                'estimated_arrival' => $now->copy()->subWeeks(2)->subDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Petrochemical polymers from Gulf refineries delivered to Jurong Island.',
                'items' => [
                    ['desc' => '40ft Dry Container: Linear Low-Density Polyethylene Granules', 'qty' => 880, 'val' => 738.63, 'cur' => 'AED', 'wt' => 22000.0, 'cbm' => 58.0],
                ],
            ],

            // 37. Jebel Ali -> Shanghai [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00602',
                'reference_number' => 'B/L-COSU-770192',
                'customer_key' => 'c4',
                'carrier_code' => 'COSU',
                'origin' => 'AEDXB',
                'destination' => 'CNSHA',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 25000.0,
                'volume_cbm' => 65.0,
                'declared_value' => 720000.0,
                'currency' => 'AED',
                'shipping_cost' => 13500.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(9),
                'expected_arrival' => $now->copy()->addDays(5),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(5),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Arabian Gulf export loop transiting Strait of Malacca.',
                'items' => [
                    ['desc' => '40ft Container: High-Purity Aluminium Ingots', 'qty' => 500, 'val' => 1440.0, 'cur' => 'AED', 'wt' => 25000.0, 'cbm' => 65.0],
                ],
            ],

            // 38. Jebel Ali -> Jakarta [DELIVERED LATE]
            [
                'tracking_number' => 'CTG-SEA-2026-00603',
                'reference_number' => 'B/L-ONEY-330198',
                'customer_key' => 'c1',
                'carrier_code' => 'ONEY',
                'origin' => 'AEDXB',
                'destination' => 'IDJKT',
                'current' => 'IDJKT',
                'cargo_type' => 'FCL',
                'weight_kg' => 21000.0,
                'volume_cbm' => 54.0,
                'declared_value' => 590000.0,
                'currency' => 'AED',
                'shipping_cost' => 12000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(5),
                'expected_arrival' => $now->copy()->subWeeks(3)->subDays(1),
                'actual_arrival' => $now->copy()->subWeeks(3)->addHours(12),
                'estimated_arrival' => $now->copy()->subWeeks(3)->addHours(12),
                'is_delayed' => true,
                'delay_hours' => 12.0,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Port of Colombo bunker barge scheduling delay en route.',
                'notes' => 'Synthetic lubricant base oils in ISO tank container.',
                'items' => [
                    ['desc' => '20ft ISO Tank: Group III Hydrocracked Synthetic Base Oil', 'qty' => 1, 'val' => 590000.0, 'cur' => 'AED', 'wt' => 21000.0, 'cbm' => 54.0],
                ],
            ],

            // 39. Durban -> Singapore [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00604',
                'reference_number' => 'B/L-MSCU-901824',
                'customer_key' => 'c4',
                'carrier_code' => 'MSCU',
                'origin' => 'ZADUR',
                'destination' => 'SGSIN',
                'current' => 'SGSIN',
                'cargo_type' => 'FCL',
                'weight_kg' => 23500.0,
                'volume_cbm' => 60.0,
                'declared_value' => 2800000.0,
                'currency' => 'ZAR',
                'shipping_cost' => 65000.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(14),
                'expected_arrival' => $now->copy()->addDays(4),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(4),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Southern Indian Ocean crossing with copper cathode sheets.',
                'items' => [
                    ['desc' => '20ft Heavy Container: Electrolytic Copper Cathodes Grade A', 'qty' => 450, 'val' => 6222.2, 'cur' => 'ZAR', 'wt' => 23500.0, 'cbm' => 60.0],
                ],
            ],

            // 40. Jebel Ali -> Durban [IN_TRANSIT HIGH DELAY]
            [
                'tracking_number' => 'CTG-SEA-2026-00605',
                'reference_number' => 'B/L-CMDU-440192',
                'customer_key' => 'c4',
                'carrier_code' => 'CMDU',
                'origin' => 'AEDXB',
                'destination' => 'ZADUR',
                'current' => 'ZADUR',
                'cargo_type' => 'FCL',
                'weight_kg' => 19200.0,
                'volume_cbm' => 50.0,
                'declared_value' => 480000.0,
                'currency' => 'AED',
                'shipping_cost' => 9800.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(12),
                'expected_arrival' => $now->copy()->subHours(8),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(1)->addHours(4),
                'is_delayed' => true,
                'delay_hours' => 36.0,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Durban Pier 2 container terminal crane mechanical failure queue.',
                'notes' => 'East Africa coastal loop feeder vessel awaiting open berth.',
                'items' => [
                    ['desc' => '40ft Container: Packaged Foodstuffs & Dried Dates Consignment', 'qty' => 700, 'val' => 685.71, 'cur' => 'AED', 'wt' => 19200.0, 'cbm' => 50.0],
                ],
            ],

            // =================================================================
            // ADDITIONAL COMPREHENSIVE LANES TO REACH 45 SHIPMENTS
            // =================================================================
            // 41. Tokyo -> Los Angeles [DELIVERED ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00701',
                'reference_number' => 'B/L-ONEY-881024',
                'customer_key' => 'c4',
                'carrier_code' => 'ONEY',
                'origin' => 'JPTYO',
                'destination' => 'USLAX',
                'current' => 'USLAX',
                'cargo_type' => 'FCL',
                'weight_kg' => 20000.0,
                'volume_cbm' => 56.0,
                'declared_value' => 35000000.0,
                'currency' => 'JPY',
                'shipping_cost' => 480000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(6),
                'expected_arrival' => $now->copy()->subWeeks(4),
                'actual_arrival' => $now->copy()->subWeeks(4)->subHours(3),
                'estimated_arrival' => $now->copy()->subWeeks(4)->subHours(3),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Industrial robotics servo motors for West Coast manufacturing.',
                'items' => [
                    ['desc' => '40ft Container: AC Servo Motors & Integrated Encoders', 'qty' => 400, 'val' => 87500.0, 'cur' => 'JPY', 'wt' => 20000.0, 'cbm' => 56.0],
                ],
            ],

            // 42. Santos -> Rotterdam [DELIVERED LATE]
            [
                'tracking_number' => 'CTG-SEA-2026-00702',
                'reference_number' => 'B/L-MSCU-550198',
                'customer_key' => 'c2',
                'carrier_code' => 'MSCU',
                'origin' => 'BRSSZ',
                'destination' => 'NLRTM',
                'current' => 'NLRTM',
                'cargo_type' => 'FCL',
                'weight_kg' => 24000.0,
                'volume_cbm' => 64.0,
                'declared_value' => 1200000.0,
                'currency' => 'BRL',
                'shipping_cost' => 28000.0,
                'status' => 'DELIVERED',
                'expected_departure' => $now->copy()->subWeeks(7),
                'expected_arrival' => $now->copy()->subWeeks(3)->subDays(3),
                'actual_arrival' => $now->copy()->subWeeks(3)->subDays(2)->addHours(6),
                'estimated_arrival' => $now->copy()->subWeeks(3)->subDays(2)->addHours(6),
                'is_delayed' => true,
                'delay_hours' => 30.0,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Tropical squall off Cape Verde caused 30-hour course adjustment.',
                'notes' => 'Brazilian green coffee beans and frozen concentrated orange juice.',
                'items' => [
                    ['desc' => '40ft Reefer Container: Bulk Frozen Orange Juice Drums', 'qty' => 120, 'val' => 10000.0, 'cur' => 'BRL', 'wt' => 24000.0, 'cbm' => 64.0],
                ],
            ],

            // 43. Port Klang -> Rotterdam [IN_TRANSIT ON-TIME]
            [
                'tracking_number' => 'CTG-SEA-2026-00703',
                'reference_number' => 'B/L-MAEU-440182',
                'customer_key' => 'c1',
                'carrier_code' => 'MAEU',
                'origin' => 'MYPKG',
                'destination' => 'NLRTM',
                'current' => 'AEDXB',
                'cargo_type' => 'FCL',
                'weight_kg' => 22500.0,
                'volume_cbm' => 60.0,
                'declared_value' => 180000.0,
                'currency' => 'USD',
                'shipping_cost' => 4200.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subWeeks(2)->subDays(3),
                'expected_arrival' => $now->copy()->addWeeks(1)->addDays(4),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addWeeks(1)->addDays(4),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Medical grade nitrile examination gloves in dry containers.',
                'items' => [
                    ['desc' => '40ft HC Container: Nitrile Powder-Free Medical Examination Gloves', 'qty' => 3000, 'val' => 60.0, 'cur' => 'USD', 'wt' => 22500.0, 'cbm' => 60.0],
                ],
            ],

            // 44. Busan -> Tokyo [HMMU / IN_TRANSIT]
            [
                'tracking_number' => 'CTG-SEA-2026-00704',
                'reference_number' => 'B/L-HMMU-770192',
                'customer_key' => 'c4',
                'carrier_code' => 'HMMU',
                'origin' => 'KRPUS',
                'destination' => 'JPTYO',
                'current' => 'JPTYO',
                'cargo_type' => 'FCL',
                'weight_kg' => 18500.0,
                'volume_cbm' => 48.0,
                'declared_value' => 140000000.0,
                'currency' => 'KRW',
                'shipping_cost' => 1800000.0,
                'status' => 'IN_TRANSIT',
                'expected_departure' => $now->copy()->subDays(2),
                'expected_arrival' => $now->copy()->addDays(2),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(2),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Direct Japan-Korea shuttle feeder container.',
                'items' => [
                    ['desc' => '20ft Container: Precision Electronics Soldering Pastes & Flux', 'qty' => 250, 'val' => 560000.0, 'cur' => 'KRW', 'wt' => 18500.0, 'cbm' => 48.0],
                ],
            ],

            // 45. Laem Chabang -> Singapore [CREATED]
            [
                'tracking_number' => 'CTG-SEA-2026-00705',
                'reference_number' => 'B/L-COSU-330198',
                'customer_key' => 'c1',
                'carrier_code' => 'COSU',
                'origin' => 'THLCH',
                'destination' => 'SGSIN',
                'current' => 'THLCH',
                'cargo_type' => 'FCL',
                'weight_kg' => 20500.0,
                'volume_cbm' => 53.0,
                'declared_value' => 4200000.0,
                'currency' => 'THB',
                'shipping_cost' => 65000.0,
                'status' => 'CREATED',
                'expected_departure' => $now->copy()->addDays(3),
                'expected_arrival' => $now->copy()->addDays(6),
                'actual_arrival' => null,
                'estimated_arrival' => $now->copy()->addDays(6),
                'is_delayed' => false,
                'delay_hours' => 0.0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'notes' => 'Automotive electronic control units awaiting pre-shipment inspection.',
                'items' => [
                    ['desc' => '40ft Dry Container: Electronic Engine Control Modules (ECU)', 'qty' => 600, 'val' => 7000.0, 'cur' => 'THB', 'wt' => 20500.0, 'cbm' => 53.0],
                ],
            ],
        ];

        // Seed each shipment along with ISO 6346 compliant container numbers & items
        $serialCounter = 100001;
        foreach ($shipmentsData as $data) {
            $customer = $users[$data['customer_key']];
            $carrier = $carriers[$data['carrier_code']];
            $origin = $locations[$data['origin']];
            $dest = $locations[$data['destination']];
            $currLoc = isset($data['current']) ? $locations[$data['current']] : $origin;

            $currency = $data['currency'];
            $rateToUsd = self::FX_RATES_TO_USD[$currency] ?? 1.0;
            $convertedUsd = round($data['declared_value'] * $rateToUsd, 2);

            $currencySnapshot = [
                'base' => 'USD',
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.92,
                    'CNY' => 7.23,
                    'IDR' => 15873.0,
                    'SGD' => 1.34,
                    'AUD' => 1.52,
                    'AED' => 3.67,
                    'JPY' => 154.5,
                ],
                'origin_currency' => $currency,
                'destination_currency' => $dest->currency_code,
                'rate_origin_to_usd' => $rateToUsd,
                'rate_usd_to_dest' => self::FX_RATES_TO_USD[$dest->currency_code] ?? 1.0,
                'recorded_at' => $data['expected_departure']->toIso8601String(),
            ];

            $routeNews = null;
            if ($data['is_delayed']) {
                $routeNews = [
                    'source' => 'Maritime Operations Bulletin',
                    'headline' => 'Route Advisory: '.$data['delay_reason'],
                    'impact' => 'Delay evaluation: +'.round($data['delay_hours']).' hours along transit corridor.',
                    'published_at' => $data['expected_departure']->copy()->addDays(2)->toIso8601String(),
                ];
            }

            $shipment = Shipment::firstOrCreate(
                ['tracking_number' => $data['tracking_number']],
                [
                    'reference_number' => $data['reference_number'],
                    'customer_id' => $customer->id,
                    'carrier_id' => $carrier->id,
                    'origin_location_id' => $origin->id,
                    'destination_location_id' => $dest->id,
                    'current_location_id' => $currLoc->id,
                    'cargo_type' => $data['cargo_type'],
                    'total_weight_kg' => $data['weight_kg'],
                    'total_volume_cbm' => $data['volume_cbm'],
                    'declared_value' => $data['declared_value'],
                    'currency_code' => $currency,
                    'shipping_cost' => $data['shipping_cost'],
                    'exchange_rate_to_usd' => $rateToUsd,
                    'converted_value_usd' => $convertedUsd,
                    'currency_snapshot' => $currencySnapshot,
                    'status' => $data['status'],
                    'expected_departure_at' => $data['expected_departure'],
                    'expected_arrival_at' => $data['expected_arrival'],
                    'estimated_arrival_at' => $data['estimated_arrival'],
                    'actual_arrival_at' => $data['actual_arrival'],
                    'is_delayed' => $data['is_delayed'],
                    'delay_hours' => $data['delay_hours'],
                    'delay_severity' => $data['delay_severity'],
                    'delay_reason' => $data['delay_reason'],
                    'route_news_snapshot' => $routeNews,
                    'notes' => $data['notes'],
                ]
            );

            // Establish realistic created_at and updated_at across the last 10 weeks
            $depTime = $data['expected_departure'];
            if ($depTime->isFuture()) {
                $createdAt = $now->copy()->subDays(1 + ($serialCounter % 7))->subHours(($serialCounter * 3) % 18);
            } else {
                $createdAt = $depTime->copy()->subDays(3)->subHours(($serialCounter * 2) % 20);
            }
            $updatedAt = $data['actual_arrival'] ?? ($depTime->isPast() ? $now->copy()->subHours(2) : $createdAt);

            $shipment->created_at = $createdAt;
            $shipment->updated_at = $updatedAt;
            $shipment->saveQuietly();

            // Seed items with ISO 6346 compliant container numbers
            foreach ($data['items'] as $item) {
                // Generate ISO 6346 container number using carrier code
                $containerNumber = Iso6346::generate($carrier->code, $serialCounter++);

                ShipmentItem::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'sku' => $containerNumber,
                    ],
                    [
                        'container_number' => $containerNumber,
                        'description' => $item['desc'],
                        'quantity' => $item['qty'],
                        'unit_value' => $item['val'],
                        'currency_code' => $item['cur'],
                        'weight_kg' => $item['wt'],
                        'volume_cbm' => $item['cbm'],
                        'is_hazardous' => $item['haz'] ?? false,
                    ]
                );
            }
        }
    }
}
