# CargoTrace Synthetic Demo Dataset Documentation

This document describes the synthetic dataset generated for the **CargoTrace** ocean-container shipment tracking system (Web Programming individual final project).

The dataset is realistic, deterministic relative to `now()`, and designed to showcase all application features—including global geographic visualization, multi-currency conversion, live weather snapshots, delay calculation, exception workflows, and role-based access control.

---

## 1. Architecture & Seeder Modularization

The dataset is split into seven focused seeders orchestrated by `DatabaseSeeder.php`:

| Order | Seeder Class | Responsibility | Record Count |
|:-----:|:---|:---|:---:|
| 1 | `UserSeeder` | Core demo accounts and supplemental test customers | 6 users |
| 2 | `CarrierSeeder` | International container shipping lines (pure SEA mode) | 10 carriers |
| 3 | `LocationSeeder` | Major container ports with UN/LOCODE, coordinates, IANA timezones | 24 ports |
| 4 | `ShipmentSeeder` | Ocean-container consignments across 6 shipping corridors | 45 shipments |
| 5 | `TrackingEventSeeder` | Chronologically ordered transit milestones and coordinates | 165+ events |
| 6 | `ExceptionSeeder` | Real maritime exceptions across 6 codes, 3 severities, 3 statuses | 12 exceptions |
| 7 | `NotificationSeeder` | System notices for customer and operator accounts | 5 notices |

Database cache priming for exchange rates (`open.er-api.com`) and maritime news feeds (`gCaptain`, `Maritime Executive`) is maintained in `DatabaseSeeder.php`.

---

## 2. Demo User Accounts

All demo accounts share the standard password: `password`.

| Email | Role | Company / Name | Primary Scope | Assigned Shipments |
|:---|:---:|:---|:---|:---:|
| `admin@cargotrace.test` | `admin` | CargoTrace Maritime Global HQ | Global fleet visibility | All 45 shipments |
| `operator@cargotrace.test` | `operator` | CargoTrace Seaport Operations Center | Port & milestone operations | All 45 shipments |
| `customer@cargotrace.test` | `customer` | Global Roasters & Trade Corp | Commercial cargo owner 1 | 15 shipments |
| `customer2@cargotrace.test` | `customer` | Pacific Electric & Battery Solutions | Commercial cargo owner 2 | 14 shipments |
| `customer3@cargotrace.test` | `customer` | Nordic Industrial Equipment AB | Commercial cargo owner 3 | 7 shipments |
| `customer4@cargotrace.test` | `customer` | Trans-Ocean Logistics Partners | Commercial cargo owner 4 | 9 shipments |

*Note: Role-based policies restrict customers strictly to their own shipments (`ShipmentPolicy::view` and `Shipment::forUser`). The customer shipment count is kept at $\le 15$ to ensure standard REST API pagination (`per_page = 15`) displays all scoped records cleanly on initial view.*

---

## 3. Global Container Ports (Locations)

The database includes 24 tier-1 container hubs covering all continents, each with verified UN/LOCODE, geographic coordinates, IANA timezone string, and domestic currency:

| UN/LOCODE | Port Name | Country | Coordinates (Lat, Lng) | IANA Timezone | Currency |
|:---:|:---|:---:|:---:|:---|:---:|
| `IDJKT` | Port of Tanjung Priok (Jakarta) | ID | -6.1038, 106.8827 | `Asia/Jakarta` | IDR |
| `SGSIN` | Port of Singapore (PSA / Tuas) | SG | 1.2644, 103.8228 | `Asia/Singapore` | SGD |
| `CNSHA` | Port of Shanghai (Yangshan) | CN | 30.6275, 122.0642 | `Asia/Shanghai` | CNY |
| `NLRTM` | Port of Rotterdam (Maasvlakte) | NL | 51.9566, 4.1458 | `Europe/Amsterdam` | EUR |
| `USLAX` | Port of Los Angeles (San Pedro) | US | 33.7432, -118.2673 | `America/Los_Angeles` | USD |
| `AEDXB` | Port of Jebel Ali (Dubai) | AE | 25.0113, 55.0612 | `Asia/Dubai` | AED |
| `DEHAM` | Port of Hamburg | DE | 53.5353, 9.9678 | `Europe/Berlin` | EUR |
| `AUSYD` | Port Botany (Sydney) | AU | -33.9719, 151.2181 | `Australia/Sydney` | AUD |
| `HKHKG` | Port of Hong Kong (Kwai Tsing) | HK | 22.3550, 114.1200 | `Asia/Hong_Kong` | HKD |
| `KRPUS` | Port of Busan | KR | 35.1028, 129.0403 | `Asia/Seoul` | KRW |
| `JPNGO` | Port of Nagoya | JP | 35.0833, 136.8833 | `Asia/Tokyo` | JPY |
| `MYPKG` | Port Klang | MY | 2.9999, 101.3928 | `Asia/Kuala_Lumpur` | MYR |
| `THLCH` | Laem Chabang Port | TH | 13.0833, 100.8833 | `Asia/Bangkok` | THB |
| `VNSGN` | Port of Ho Chi Minh City (Cat Lai) | VN | 10.7626, 106.7939 | `Asia/Ho_Chi_Minh` | VND |
| `TWKHH` | Port of Kaohsiung | TW | 22.6167, 120.2833 | `Asia/Taipei` | TWD |
| `INNSA` | Jawaharlal Nehru Port (Nhava Sheva) | IN | 18.9500, 72.9500 | `Asia/Kolkata` | INR |
| `BEANR` | Port of Antwerp-Bruges | BE | 51.2667, 4.3333 | `Europe/Brussels` | EUR |
| `ESVLC` | Port of Valencia | ES | 39.4444, -0.3250 | `Europe/Madrid` | EUR |
| `GBFXT` | Port of Felixstowe | GB | 51.9560, 1.3110 | `Europe/London` | GBP |
| `USNYC` | Port of New York & New Jersey | US | 40.6700, -74.1300 | `America/New_York` | USD |
| `USOAK` | Port of Oakland | US | 37.8044, -122.2711 | `America/Los_Angeles` | USD |
| `BRSSZ` | Port of Santos | BR | -23.9667, -46.3333 | `America/Sao_Paulo` | BRL |
| `ZADUR` | Port of Durban | ZA | -29.8700, 31.0200 | `Africa/Johannesburg` | ZAR |
| `LKCMB` | Port of Colombo | LK | 6.9500, 79.8500 | `Asia/Colombo` | LKR |

---

## 4. Ocean Container Carriers

10 real container shipping lines operating in pure maritime mode (`mode = 'SEA'`):

| BIC Code | Carrier Name | HQ Country | Standard Service |
|:---:|:---|:---:|:---|
| `MAEU` | Maersk Line (A.P. Møller – Mærsk) | DK | Global Deep-Sea Container Line |
| `MSCU` | Mediterranean Shipping Company (MSC) | CH | Global Deep-Sea Container Line |
| `CMDU` | CMA CGM Group | FR | Transpacific & Transatlantic Liner |
| `COSU` | COSCO Shipping Lines | CN | East-West Mega Vessel Network |
| `HLCU` | Hapag-Lloyd AG | DE | Atlantic & Middle East Specialist |
| `ONEY` | Ocean Network Express (ONE) | JP | Global Magenta Container Liner |
| `EGLV` | Evergreen Marine Corporation | TW | Global Transpacific & Intra-Asia |
| `YMLU` | Yang Ming Marine Transport | TW | Far East & European Routes |
| `HMMU` | HMM Co., Ltd. (Hyundai Merchant Marine) | KR | Pacific Express Container Fleet |
| `ZIMU` | ZIM Integrated Shipping Services | IL | Dedicated Global Container Carrier |

---

## 5. Shipments & Corridors (45 Consignments)

The 45 consignments span 10 weeks of historical and live traffic across 6 primary international maritime corridors:

1. **Asia to Europe (Far East – North Europe / Med)**: 8 shipments (e.g. Shanghai &rarr; Rotterdam, Kaohsiung &rarr; Hamburg, Busan &rarr; Antwerp).
2. **Asia to North America (Transpacific Eastbound)**: 8 shipments (e.g. Shanghai &rarr; Los Angeles, Shenzhen &rarr; Oakland, Ningbo &rarr; NY/NJ).
3. **Southeast Asia to Australia (Oceania Corridor)**: 6 shipments (e.g. Singapore &rarr; Sydney, Jakarta &rarr; Sydney, Port Klang &rarr; Melbourne).
4. **Intra-Asia Container Network**: 8 shipments (e.g. Shanghai &rarr; Tokyo/Nagoya, Cat Lai &rarr; Singapore, Bangkok &rarr; Kaohsiung).
5. **Europe to Americas (Transatlantic & South America)**: 8 shipments (e.g. Rotterdam &rarr; Santos, Hamburg &rarr; New York, Valencia &rarr; Santos).
6. **Middle East & Subcontinent to Asia/World**: 7 shipments (e.g. Jebel Ali &rarr; Jakarta, Nhava Sheva &rarr; Singapore, Colombo &rarr; Rotterdam).

### Status Breakdown

- **`CREATED` / `BOOKED`**: Exactly 6 shipments (new bookings staged at origin container yards).
- **`IN_TRANSIT`**: Exactly 20 shipments (underway in open sea or transshipment anchorage).
- **`DELIVERED`**: Exactly 19 shipments (discharged, customs cleared, and delivered to consignee).

### Delay Invariants

- **Delivered Late**: Preserved historical shipments (e.g. `CTG-SEA-2026-00189`, delayed by 17h due to North Sea storm).
- **In-Transit Delays**: Categorized into realistic severities:
  - `LOW` (< 6h delay, e.g. `CTG-SEA-2026-00678` with 3.5h Coral Sea swell delay).
  - `MEDIUM` (6h–24h delay, e.g. Port congestion or yard queue).
  - `HIGH` (> 24h delay, e.g. `CTG-SEA-2026-00244` with intensive US CBP customs hold).

### Canonical Preserved Shipments

| Tracking Number | Corridor | Status | Delay Status | Primary Exception |
|:---|:---|:---:|:---:|:---|
| `CTG-SEA-2026-00189` | Jakarta &rarr; Rotterdam | `DELIVERED` | Delivered Late (+17h) | `WEATHER_DELAY` (LOW, RESOLVED) |
| `CTG-SEA-2026-00244` | Shanghai &rarr; Los Angeles | `IN_TRANSIT` | Delayed (+32h) | `CUSTOMS_HOLD` (HIGH, INVESTIGATING) |
| `CTG-SEA-2026-00678` | Singapore &rarr; Sydney | `IN_TRANSIT` | Delayed (+3.5h) | `WEATHER_DELAY` (LOW, OPEN) |

---

## 6. ISO 6346 Container Number Generator

All shipment items utilize valid ISO 6346 container numbers calculated via `app/Support/Iso6346.php`.

### Standard Format: `AAAA 123456 C` (11 characters)
- **Owner Code**: 3 uppercase letters assigned to the carrier (e.g., `MAE`, `MSC`, `HLX`, `ONE`).
- **Category Identifier**: Always `U` for freight containers.
- **Serial Number**: 6 numeric digits.
- **Check Digit**: Modulus 11 weighted sum check digit.

### Check Digit Algorithm

1. Letter values are mapped using standard ISO 6346 weights (where multiples of 11: 11, 22, 33 are omitted):
   $$\text{A}=10, \text{B}=12, \dots, \text{K}=21, \text{L}=23, \dots, \text{U}=32, \dots, \text{Z}=38$$
2. Each character $c_i$ at position $i$ ($0 \le i \le 9$) is multiplied by $2^i$:
   $$\text{Sum} = \sum_{i=0}^9 \text{val}(c_i) \times 2^i$$
3. The check digit $C$ is computed as:
   $$C = (\text{Sum} \pmod{11}) \pmod{10}$$

Examples in dataset: `MAEU1000004`, `MSCU1000010`, `CSQU3054383`.

---

## 7. Tracking Events & Chronology Invariants

Tracking milestones strictly obey real maritime operations:

1. **Chronological Order**: `occurred_at` timestamps are strictly non-decreasing per consignment.
2. **No Premature Events**: For `IN_TRANSIT` and `DELIVERED` shipments, no tracking event precedes the vessel's `expected_departure_at`.
3. **Terminal State Guarantee**: Every `DELIVERED` shipment contains a terminal delivery event where `occurred_at == actual_arrival_at` at the destination port.
4. **Realistic Geographic Waypoints**: Open-ocean events interpolate realistically along maritime sea corridors.

---

## 8. Shipment Exceptions (12 Curated Events)

The 12 exceptions cover all 6 system exception codes, all 3 severities, and all 3 lifecycle statuses:

| Tracking Number | Code | Severity | Status | Detected At | Resolved At | Notes Summary |
|:---|:---:|:---:|:---:|:---:|:---:|:---|
| `CTG-SEA-2026-00244` | `CUSTOMS_HOLD` | `HIGH` | `INVESTIGATING` | -14h | — | US CBP Intensive examination hold (UN 38.3 battery review) |
| `CTG-SEA-2026-00104` | `CUSTOMS_HOLD` | `MEDIUM` | `RESOLVED` | -12d | -10d | Rotterdam border control phytosanitary timber verification |
| `CTG-SEA-2026-00189` | `WEATHER_DELAY` | `LOW` | `RESOLVED` | -6d | -4d | North Sea Force 8 gale suspended pilotage at Maasvlakte |
| `CTG-SEA-2026-00678` | `WEATHER_DELAY` | `LOW` | `OPEN` | -6h | — | Coral Sea southerly swell advisory |
| `CTG-SEA-2026-00203` | `PORT_CONGESTION` | `MEDIUM` | `OPEN` | -20h | — | Long Beach Pier 400 berth queue dwell |
| `CTG-SEA-2026-00206` | `PORT_CONGESTION` | `HIGH` | `INVESTIGATING` | -18h | — | Jebel Ali transshipment basin queue delay |
| `CTG-SEA-2026-00302` | `DAMAGED` | `LOW` | `RESOLVED` | -8d | -7d | Container top corner casting surface scrape (surveyor cleared) |
| `CTG-SEA-2026-00404` | `DAMAGED` | `MEDIUM` | `INVESTIGATING` | -10h | — | Reefer unit auxiliary power socket circuit breaker trip |
| `CTG-SEA-2026-00205` | `MISROUTED` | `HIGH` | `OPEN` | -8h | — | Yard straddle carrier staged container to wrong feeder bay |
| `CTG-SEA-2026-00402` | `MISROUTED` | `MEDIUM` | `INVESTIGATING` | -15h | — | Busan terminal yard block inventory discrepancy (12 vs 14) |
| `CTG-SEA-2026-00106` | `DOCUMENTATION_ISSUE` | `LOW` | `OPEN` | -12h | — | Missing consular phytosanitary stamp on manifest |
| `CTG-SEA-2026-00105` | `DOCUMENTATION_ISSUE` | `HIGH` | `RESOLVED` | -3w | -3w+1d | SOLAS VGM weight variance resolved with certified port scale |

---

## 9. Verification & Testing

To refresh and verify the dataset invariants, run:

```bash
# Reset database and execute all modular seeders
php artisan migrate:fresh --seed

# Execute automated invariant test suite
php artisan test --filter=SeedInvariantsTest

# Execute entire test suite
php artisan test
```

All invariants—including ISO 6346 validation, chronological event timestamps, customer scoping boundaries, and terminal event matching—are validated on every run.
