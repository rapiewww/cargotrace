# CargoTrace Synthetic Spice Freight Dataset Documentation

> **Synthetic Data Notice:** All shipments, cargo lots, buyers, trucking companies, tracking events, exceptions, notifications, and audit logs in this database are **100% synthetic**. Real-world ocean shipping lines (Maersk, MSC, CMA CGM, Hapag-Lloyd, ONE) and air cargo carriers (Garuda Indonesia Cargo, Singapore Airlines Cargo, Emirates SkyCargo, Qatar Airways Cargo, Lufthansa Cargo) are used strictly as realistic industry labels and are not connected to live carrier APIs. All road trucking companies, buyer companies, waybills, container serials, and operational incidents are fictional.

---

## 1. Story & Operational Scope

CargoTrace is the shipment-tracking portal of **Nusa Spice Freight** (fictional), a multimodal freight forwarder moving Indonesian spices and essential oils from Sumatra and Java to spice wholesalers and fragrance houses in Europe, the Middle East, South Asia, and North America across **SEA**, **AIR**, and **ROAD** transport modes.

The dataset is deterministic (`mt_srand(20261125)` / `fake()->seed(20261125)`), requires zero network calls during seeding, and anchors all timestamps relative to `Carbon::now()` across the last 10 weeks.

---

## 2. Seeder Architecture (8 Modular Seeders)

`DatabaseSeeder` primes offline stale caches for `CurrencyService` and `MaritimeNewsService` and invokes eight focused seeders in dependency order:

| Order | Seeder Class | Responsibility | Seeded Count |
|:-----:|:---|:---|:---:|
| 1 | `UserSeeder` | Core demo accounts (4 preserved) + 4 fictional spice/fragrance buyers | 8 users |
| 2 | `LocationSeeder` | Seaports, airports, depots/warehouses, and border crossing | 28 locations |
| 3 | `CarrierSeeder` | Multimodal carriers across SEA, AIR, and ROAD | 15 carriers |
| 4 | `ShipmentSeeder` | Spice & essential oil consignments across 10 weeks | 48 shipments |
| 5 | `TrackingEventSeeder` | Chronological mode-specific checkpoints and route waypoints | 190+ events |
| 6 | `ExceptionSeeder` | Curated operational incidents across all 9 exception codes | 14 exceptions |
| 7 | `NotificationSeeder` | Customer, operator, and admin alerts aligned to shipment events | 11 notices |
| 8 | `AuditSeeder` | Actor-attributed audit trail entries matching seeded lifecycle events | 10 logs |

---

## 3. Demo User Accounts

All accounts share the password `password`. The four core demo emails and roles are preserved unchanged.

| Email | Role | Company / Buyer Name | Preferred Currency | Shipments |
|:---|:---:|:---|:---:|:---:|
| `admin@cargotrace.test` | `admin` | Nusa Spice Freight HQ (Hendra Wijaya) | `USD` | All 48 |
| `operator@cargotrace.test` | `operator` | Nusa Spice Freight Operations Desk (Sari Kusuma) | `SGD` | All 48 |
| `customer@cargotrace.test` | `customer` | PT Rempah Nusantara Sejahtera | `IDR` | 8 |
| `customer2@cargotrace.test` | `customer` | Van der Berg Spice Trading B.V. | `EUR` | 8 |
| `customer3@cargotrace.test` | `customer` | Maison L'Essence Grasse S.A.S. | `EUR` | 8 |
| `customer4@cargotrace.test` | `customer` | Hudson Valley Botanicals Inc. | `USD` | 8 |
| `customer5@cargotrace.test` | `customer` | Deccan Aroma & Spices Pvt. Ltd. | `INR` | 8 |
| `customer6@cargotrace.test` | `customer` | Al-Mina Gulf Spice Merchants LLC | `USD` | 8 |

Every customer account is strictly scoped to its own 8 consignments via `ShipmentPolicy` and `Shipment::forUser`, with shipments spanning SEA, AIR, and ROAD.

---

## 4. Locations (28 Hubs, Ports, Airports, Depots & Border)

### Seaports (11 UN/LOCODE Ports, `type = PORT`)

| Code | Name | Country | Coordinates (Lat, Lng) | Timezone |
|:---:|:---|:---:|:---:|:---|
| `IDBLW` | Port of Belawan | ID | `3.7889000, 98.6869000` | `Asia/Jakarta` |
| `IDJKT` | Port of Tanjung Priok | ID | `-6.1038190, 106.8824240` | `Asia/Jakarta` |
| `NLRTM` | Port of Rotterdam | NL | `51.9560410, 4.0538980` | `Europe/Amsterdam` |
| `DEHAM` | Port of Hamburg | DE | `53.5048000, 9.9355000` | `Europe/Berlin` |
| `FRMRS` | Port of Marseille-Fos | FR | `43.4139000, 4.8556000` | `Europe/Paris` |
| `AEJEA` | Port of Jebel Ali | AE | `25.0112000, 55.0612000` | `Asia/Dubai` |
| `INNSA` | Jawaharlal Nehru Port (Nhava Sheva) | IN | `18.9499000, 72.9512000` | `Asia/Kolkata` |
| `SGSIN` | Port of Singapore | SG | `1.2640470, 103.8222660` | `Asia/Singapore` |
| `MYPKG` | Port Klang | MY | `2.9999000, 101.3928000` | `Asia/Kuala_Lumpur` |
| `USNYC` | Port of New York and New Jersey | US | `40.6840000, -74.1503000` | `America/New_York` |
| `USSAV` | Port of Savannah | US | `32.1282000, -81.1419000` | `America/New_York` |

### Airports (10 IATA Cargo Hubs, `type = AIRPORT`)

| Locode / IATA | Name | Country | Coordinates (Lat, Lng) | Timezone |
|:---:|:---|:---:|:---:|:---|
| `IDKNO` / `KNO` | Kualanamu International Airport Cargo | ID | `3.6422220, 98.8852780` | `Asia/Jakarta` |
| `IDCGK` / `CGK` | Soekarno-Hatta International Airport Cargo | ID | `-6.1255670, 106.6558970` | `Asia/Jakarta` |
| `SGCHG` / `SIN` | Singapore Changi Airport Airfreight Centre | SG | `1.3644200, 103.9915310` | `Asia/Singapore` |
| `AEDXB` / `DXB` | Dubai International Airport Cargo | AE | `25.2527780, 55.3644440` | `Asia/Dubai` |
| `NLAMS` / `AMS` | Amsterdam Airport Schiphol Cargo | NL | `52.3086130, 4.7638890` | `Europe/Amsterdam` |
| `FRCDG` / `CDG` | Paris Charles de Gaulle Cargo City | FR | `49.0096910, 2.5479250` | `Europe/Paris` |
| `DEFRA` / `FRA` | Frankfurt Airport CargoCity South | DE | `50.0379330, 8.5621520` | `Europe/Berlin` |
| `INBOM` / `BOM` | Chhatrapati Shivaji Maharaj Cargo | IN | `19.0895590, 72.8656140` | `Asia/Kolkata` |
| `USJFK` / `JFK` | John F. Kennedy Cargo Center | US | `40.6413110, -73.7781390` | `America/New_York` |
| `IDBTJ` / `BTJ` | Sultan Iskandar Muda Cargo | ID | `5.5235220, 95.4203720` | `Asia/Jakarta` |

### Depots, Warehouses & Border Crossing (7 ROAD Locations)

| Code | Type | Name | Country | Coordinates (Lat, Lng) | Timezone |
|:---:|:---:|:---|:---:|:---:|:---|
| `IDLSW` | `WAREHOUSE` | Lhokseumawe Spice & Patchouli Warehouse | ID | `5.1801000, 97.1507000` | `Asia/Jakarta` |
| `IDMES` | `DEPOT` | Medan Consolidation Depot | ID | `3.5951960, 98.6722230` | `Asia/Jakarta` |
| `IDACE` | `DEPOT` | Banda Aceh Spice Collection Depot | ID | `5.5482900, 95.3237530` | `Asia/Jakarta` |
| `INNSC` | `DEPOT` | Nhava Sheva Container Freight Station | IN | `18.9632000, 72.9765000` | `Asia/Kolkata` |
| `FRGRS` | `DEPOT` | Grasse Aromatics & Fragrance Depot | FR | `43.6584000, 6.9224000` | `Europe/Paris` |
| `NLRDD` | `DEPOT` | Rotterdam South Spice Distribution Depot | NL | `51.8742000, 4.4628000` | `Europe/Amsterdam` |
| `NLVEN` | `BORDER` | Venlo NL/DE Freight Border Crossing | NL | `51.3703750, 6.1724030` | `Europe/Amsterdam` |

---

## 5. Carriers by Transport Mode (15 Total)

| Mode | Code | Carrier Name | Country | Notes |
|:---:|:---:|:---|:---:|:---|
| `SEA` | `MAEU` | Maersk | DK | Real company used as synthetic label |
| `SEA` | `MSCU` | MSC | CH | Real company used as synthetic label |
| `SEA` | `CMDU` | CMA CGM | FR | Real company used as synthetic label |
| `SEA` | `HLCU` | Hapag-Lloyd | DE | Real company used as synthetic label |
| `SEA` | `ONEY` | ONE | SG | Real company used as synthetic label |
| `AIR` | `GIA` | Garuda Indonesia Cargo | ID | Real airline used as synthetic label (`126`) |
| `AIR` | `SQC` | Singapore Airlines Cargo | SG | Real airline used as synthetic label (`618`) |
| `AIR` | `EKC` | Emirates SkyCargo | AE | Real airline used as synthetic label (`176`) |
| `AIR` | `QRC` | Qatar Airways Cargo | QA | Real airline used as synthetic label (`157`) |
| `AIR` | `LHC` | Lufthansa Cargo | DE | Real airline used as synthetic label (`020`) |
| `ROAD` | `SMTR` | PT Sumatra Rempah Express | ID | Fictional trucking company |
| `ROAD` | `BKTJ` | CV Bukit Barisan Haulage | ID | Fictional trucking company |
| `ROAD` | `PRVF` | Provence Aromatiques Transport S.A.R.L. | FR | Fictional trucking company |
| `ROAD` | `RHNL` | Rijnmond Spice Logistics B.V. | NL | Fictional trucking company |
| `ROAD` | `KHND` | Konkan Freight Carriers Pvt. Ltd. | IN | Fictional trucking company |

---

## 6. Shipments (48 Consignments Across 10 Weeks)

- **Mode Split:** 22 `SEA`, 14 `AIR`, 12 `ROAD` (Total = 48).
- **Status Split:** 6 `CREATED`/`BOOKED` (3 `CREATED`, 3 `BOOKED`), 24 `IN_TRANSIT`, 18 `DELIVERED`.
- **Tracking Numbers:** `CTG-{SEA|AIR|ROAD}-2026-NNNNN`.
- **Commodities:** Nutmeg, cloves, cinnamon (cassia), black pepper, ginger, and patchouli essential oil, billed in `USD`, `EUR`, `INR`, and `IDR` with Incoterms (`FOB`, `CIF`, `CFR`, `CIP`, `DAP`, `FCA`, `EXW`).
- **Reference Generators:**
  - `SEA`: ISO 6346 container numbers (`App\Support\Iso6346`) and Ocean B/L (`App\Support\RoadReference::billOfLading`).
  - `AIR`: 11-digit AWB numbers (`App\Support\Awb`, check digit `serial % 7`).
  - `ROAD`: Indonesian-style (`BL`, `BK`, `B`) and regional truck plates plus `CMR-2026-NNNNN` (`App\Support\RoadReference`).

### Canonical Preserved Shipments

| Tracking Number | Mode | Route | Status | Delay Severity | Key Exception |
|:---|:---:|:---|:---:|:---:|:---|
| `CTG-SEA-2026-00189` | `SEA` | `IDJKT` to `NLRTM` | `DELIVERED` | `LOW` (+10.0h) | `WEATHER_DELAY` (`RESOLVED`) |
| `CTG-SEA-2026-00244` | `SEA` | `IDBLW` to `USNYC` | `IN_TRANSIT` | `HIGH` (+56.0h) | `CUSTOMS_HOLD` (`INVESTIGATING`) |
| `CTG-AIR-2026-00678` | `AIR` | `IDKNO` to `FRCDG` | `IN_TRANSIT` | `LOW` (+2.5h) | `WEATHER_DELAY` (`OPEN`) |

### Per-Mode Delay Thresholds

Delays follow the deterministic per-mode thresholds in `config/cargotrace.php` and `EtaService`:

| Mode | `LOW` Threshold | `MEDIUM` Threshold | `HIGH` Threshold | Seeded Delay Coverage |
|:---:|:---:|:---:|:---:|:---|
| `AIR` | `<= 3h` | `<= 12h` | `> 12h` | `LOW` (+2.0h, +2.5h), `MEDIUM` (+7.5h, +9.0h), `HIGH` (+16.0h, +20.0h) |
| `ROAD` | `<= 6h` | `<= 24h` | `> 24h` | `LOW` (+4.5h, +5.0h), `MEDIUM` (+12.0h, +14.0h), `HIGH` (+28.0h) |
| `SEA` | `<= 12h` | `<= 48h` | `> 48h` | `LOW` (+8.0h, +10.0h), `MEDIUM` (+28.0h, +30.0h), `HIGH` (+56.0h, +64.0h) |

---

## 7. Exceptions (14 Across All 9 Codes)

Every exception is valid for its shipment's transport mode (`ExceptionService::MODE_ALLOWED_EXCEPTIONS`), and every `OPEN` or `INVESTIGATING` exception belongs to an `IN_TRANSIT` shipment.

| Tracking Number | Mode | Code | Severity | Status | Detected / Resolved |
|:---|:---:|:---|:---:|:---:|:---|
| `CTG-SEA-2026-00244` | `SEA` | `CUSTOMS_HOLD` | `HIGH` | `INVESTIGATING` | `-14h` / open |
| `CTG-SEA-2026-00104` | `SEA` | `CUSTOMS_HOLD` | `MEDIUM` | `RESOLVED` | `-12d` / `-10d` |
| `CTG-SEA-2026-00189` | `SEA` | `WEATHER_DELAY` | `LOW` | `RESOLVED` | `-6d` / `-5d 14h` |
| `CTG-AIR-2026-00678` | `AIR` | `WEATHER_DELAY` | `LOW` | `OPEN` | `-4h` / open |
| `CTG-SEA-2026-00203` | `SEA` | `PORT_CONGESTION` | `MEDIUM` | `OPEN` | `-20h` / open |
| `CTG-AIR-2026-00308` | `AIR` | `PORT_CONGESTION` | `LOW` | `INVESTIGATING` | `-8h` / open |
| `CTG-SEA-2026-00102` | `SEA` | `DAMAGED` | `LOW` | `RESOLVED` | `-35d` / `-34d` |
| `CTG-ROAD-2026-00406` | `ROAD` | `DAMAGED` | `MEDIUM` | `INVESTIGATING` | `-5h` / open |
| `CTG-SEA-2026-00205` | `SEA` | `MISROUTED` | `HIGH` | `OPEN` | `-18h` / open |
| `CTG-AIR-2026-00307` | `AIR` | `DOCUMENTATION_ISSUE` | `HIGH` | `OPEN` | `-10h` / open |
| `CTG-SEA-2026-00105` | `SEA` | `DOCUMENTATION_ISSUE` | `MEDIUM` | `RESOLVED` | `-22d` / `-21d` |
| `CTG-AIR-2026-00306` | `AIR` | `FLIGHT_OFFLOAD` | `HIGH` | `INVESTIGATING` | `-11h` / open |
| `CTG-ROAD-2026-00408` | `ROAD` | `BORDER_QUEUE` | `HIGH` | `OPEN` | `-16h` / open |
| `CTG-ROAD-2026-00407` | `ROAD` | `VEHICLE_BREAKDOWN` | `MEDIUM` | `INVESTIGATING` | `-7h` / open |

---

## 8. Verify Before Final Report

The following reference codes, HS tariff subheadings, airline prefixes, and depot/hub coordinates should be double-checked against official tariff schedules and UN/LOCODE directories before submitting the final academic report:

### HS Codes (Verify)

| Commodity | Seeded HS Code | Status |
|:---|:---:|:---|
| Whole Nutmeg | `0908.11` | **Verify** (neither crushed nor ground nutmeg under 0908.1) |
| Whole Cloves | `0907.10` | **Verify** (cloves whole fruit, neither crushed nor ground) |
| Cassia Cinnamon Quills | `0906.11` | **Verify** (distinguish `0906.11` Ceylon vs `0906.19` cassia/other) |
| Black Pepper Whole | `0904.11` | **Verify** (dried pepper neither crushed nor ground) |
| Dried Ginger Root | `0910.11` | **Verify** (ginger neither crushed nor ground) |
| Patchouli Essential Oil | `3301.29` | **Verify** (essential oils other than citrus, mint, vetiver) |

### Airline 3-Digit AWB Prefixes (Verify)

| Carrier | IATA Code | Seeded AWB Prefix | Status |
|:---|:---:|:---:|:---|
| Garuda Indonesia Cargo | `GA` / `GIA` | `126` | **Verify** |
| Singapore Airlines Cargo | `SQ` / `SQC` | `618` | **Verify** |
| Emirates SkyCargo | `EK` / `EKC` | `176` | **Verify** |
| Qatar Airways Cargo | `QR` / `QRC` | `157` | **Verify** |
| Lufthansa Cargo | `LH` / `LHC` | `020` | **Verify** |

### Location Codes & Coordinates to Double-Check (Verify)

- `SGCHG`: synthetic unique `unlocode` used so Changi Airport `SIN` does not collide with `SGSIN` Seaport in the unique `unlocode` column (**Verify**).
- `IDACE` (Banda Aceh Spice Collection Depot), `IDLSW` (Lhokseumawe Warehouse), `INNSC` (Nhava Sheva CFS), `FRGRS` (Grasse Depot), `NLRDD` (Rotterdam South Depot), `NLVEN` (Venlo Border Crossing): synthetic facility sub-codes and approximate city-level coordinates (**Verify** exact warehouse/CFS gate coordinates).
- `IDBLW` (`3.7889, 98.6869`), `FRMRS` (`43.4139, 4.8556`), `AEJEA` (`25.0112, 55.0612`), `IDKNO` (`3.642222, 98.885278`), `IDBTJ` (`5.523522, 95.420372`): **Verify** exact terminal coordinates against UN/LOCODE and AIP publications.

---

## 9. Verification Commands

```bash
php artisan migrate:fresh --seed
php artisan test --filter=SeedInvariantsTest
php artisan test
```
