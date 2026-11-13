# CargoTrace Oral Examination & Code Walkthrough Guide

This document is prepared specifically for oral interviews and technical defense examinations. It explains the exact code execution paths, architectural patterns, validation rules, authorization gates, relational database design choices, and three anticipated live-coding interview modifications with exact files and line numbers.

---

## 1. Architectural Philosophy & Layered Pattern

CargoTrace employs an **N-Tier Layered Architecture**:

```
HTTP Request
     │
     ▼
[routes/web.php / routes/api.php] ──► Routing, prefixing, rate limits (`throttle`)
     │
     ▼
[app/Http/Middleware/*] ────────────► Auth, RBAC (`EnsureUserHasRole`), Security Headers
     │
     ▼
[app/Http/Requests/*] ──────────────► Form Request Validation, ISO 6346 Container Rules
     │
     ▼
[app/Http/Controllers/*] ───────────► Request dispatching, Policy gates (`$this->authorize`)
     │
     ▼
[app/Services/*] ───────────────────► 100% of Domain Logic, Arithmetic, Transactions
     │
     ▼
[app/Models/*] ─────────────────────► Eloquent Relationships, Global Scopes (`CustomerScope`)
     │
     ▼
[MySQL 8.4 Database] ───────────────► Relational persistence & constraints
```

**Key Architectural Invariants:**
1. **Controllers are Thin:** Controllers never perform mathematical calculations, manual database transactions, or direct external HTTP requests. They delegate immediately to dedicated service classes.
2. **Services are Pure & Testable:** Business rules (e.g. ETA schedule variances, container free-time demurrage countdown, CSV row validations) live in `app/Services/`.
3. **Policies Enforce Data Isolation:** Model authorization is handled via `ShipmentPolicy` using `$this->authorize('view', $shipment)`.
4. **Transparent Telemetry & Anti-Slop:** No fake AIS satellite data; every metric is derived from verifiable database records or upstream third-party APIs with explicit provenance tags (`LIVE`, `CACHED`, `SIMULATED`).

---

## 2. Core Feature Walkthroughs (Route ➔ Controller ➔ Service ➔ Model)

### Feature 1: Consignment Creation & ISO 6346 Validation

#### End-to-End Execution Path:
- **Route:** `POST /shipments` &bull; [routes/web.php](file:///Users/user/Herd/cargotrace/routes/web.php#L68) (or `POST /api/shipments` &bull; [routes/api.php](file:///Users/user/Herd/cargotrace/routes/api.php#L35))
- **Form Request:** `StoreShipmentRequest` &bull; [app/Http/Requests/Shipment/StoreShipmentRequest.php](file:///Users/user/Herd/cargotrace/app/Http/Requests/Shipment/StoreShipmentRequest.php)
- **Controller:** `ShipmentWebController@store` &bull; [app/Http/Controllers/ShipmentWebController.php](file:///Users/user/Herd/cargotrace/app/Http/Controllers/ShipmentWebController.php)
- **Service:** `ShipmentService::createShipment(array $data, User $actor)` &bull; [app/Services/ShipmentService.php](file:///Users/user/Herd/cargotrace/app/Services/ShipmentService.php)
- **Model:** `Shipment` & `ShipmentItem` &bull; [app/Models/Shipment.php](file:///Users/user/Herd/cargotrace/app/Models/Shipment.php)

#### Validation & Authorization:
- **Authorization:** `EnsureUserHasRole` middleware checks `role:admin,operator`. Customers receive HTTP `403 Forbidden`.
- **Validation:** Validates required carrier ID, UN/LOCODE origin and destination ports (must exist in `locations` table and not be identical), ISO 4217 currency code, expected departure and arrival timestamps (`expected_arrival_at > expected_departure_at`).
- **Container Number Rule:** In `items.*.container_number`, uses `new App\Rules\Iso6346Rule()`. Validates 4-letter owner prefix (last letter equipment category `U`, `J`, or `Z`), 6-digit serial number, and calculates modulus 11 check digit.
- **Service Logic:**
  1. Opens database transaction: `DB::transaction()`.
  2. Generates unique tracking number (`CTG-SEA-YYYY-NNNNN`).
  3. Computes converted value in USD using `CurrencyService`.
  4. Persists `Shipment` record and associates child `ShipmentItem` containers.
  5. Logs initial milestone in `tracking_events` with status `CREATED`.
  6. Dispatches immutable audit record via `AuditService::log('shipment.created', ...)`.

---

### Feature 2: Milestone Tracking & Status Progression (`BOOKED` ➔ `PICKED_UP` ➔ `IN_TRANSIT` ➔ `DELIVERED`)

#### End-to-End Execution Path:
- **Route:** `POST /shipments/{shipment}/tracking` &bull; [routes/web.php](file:///Users/user/Herd/cargotrace/routes/web.php#L73) (or `PATCH /shipments/{shipment}/status`)
- **Controller:** `ShipmentWebController@storeTracking` &bull; [app/Http/Controllers/ShipmentWebController.php](file:///Users/user/Herd/cargotrace/app/Http/Controllers/ShipmentWebController.php)
- **Service:** `TrackingService::recordEvent()` & `ShipmentService::updateStatus()` &bull; [app/Services/TrackingService.php](file:///Users/user/Herd/cargotrace/app/Services/TrackingService.php)
- **Model:** `TrackingEvent` & `Shipment`

#### Validation & State Transitions:
- **Terminal State Lock:** Once a shipment reaches `DELIVERED`, `ShipmentService::canTransitionTo()` forbids backward status mutations.
- **Port Weather Snapping:** When a milestone is logged with seaport coordinates, `WeatherService::getWeatherByCoordinates()` captures point-in-time atmospheric and wave observations into `tracking_events.weather_snapshot`.
- **Actual Arrival Timestamp:** When status transitions to `DELIVERED`, `shipments.actual_arrival_at` is set to the event timestamp.

---

### Feature 3: Deterministic Delay Evaluation & In-App Alerts

#### End-to-End Execution Path:
- **Command:** `php artisan cargotrace:evaluate-delays` &bull; [app/Console/Commands/EvaluateDelaysCommand.php](file:///Users/user/Herd/cargotrace/app/Console/Commands/EvaluateDelaysCommand.php)
- **Schedule:** Scheduled hourly in [routes/console.php](file:///Users/user/Herd/cargotrace/routes/console.php)
- **Service:** `EtaService::evaluateDelaysForShipment(Shipment $shipment)` &bull; [app/Services/EtaService.php](file:///Users/user/Herd/cargotrace/app/Services/EtaService.php)
- **Notification Channel:** `app/Channels/InAppChannel.php` & [app/Notifications/ShipmentDelayEscalatedNotification.php](file:///Users/user/Herd/cargotrace/app/Notifications/ShipmentDelayEscalatedNotification.php)

#### Business Logic:
- **Variance Formula:**
  $$\Delta t_{\text{hours}} = \frac{\text{estimated\_arrival\_at} - \text{expected\_arrival\_at}}{3600}$$
- **Threshold Tiers (Configurable via `settings` table):**
  - $\Delta t \le 0$: `is_delayed = false`, `delay_severity = NONE`
  - $0 < \Delta t \le 6\text{h}$: `is_delayed = true`, `delay_severity = LOW`
  - $6\text{h} < \Delta t \le 24\text{h}$: `is_delayed = true`, `delay_severity = MEDIUM`
  - $\Delta t > 24\text{h}$: `is_delayed = true`, `delay_severity = HIGH`
- **Idempotency & Alert Dispatch:**
  - If `delay_severity` escalates (e.g. `NONE` &rarr; `MEDIUM` or `MEDIUM` &rarr; `HIGH`), notifications are dispatched to the customer owner and active operators.
  - If rerun without severity change, **zero duplicate notifications** are created.
  - Automatically skipped for already `DELIVERED` consignments.

---

### Feature 4: Container Demurrage Free-Time Countdown

#### End-to-End Execution Path:
- **Service:** `FreeTimeService::calculateFreeTime(Shipment $shipment, ?CarbonInterface $now = null)` &bull; [app/Services/FreeTimeService.php](file:///Users/user/Herd/cargotrace/app/Services/FreeTimeService.php)
- **UI Presentation:** Shipment detail banner & Dashboard attention list &bull; [resources/views/shipments/show.blade.php](file:///Users/user/Herd/cargotrace/resources/views/shipments/show.blade.php)

#### Business Logic:
- **Free Days Configuration:** Read from `settings.free_days_after_arrival` (default: 5 calendar days).
- **Trigger Event:** Starts upon the milestone arrival timestamp (`actual_arrival_at` or `DELIVERED` tracking event).
- **Three Boundary States:**
  1. `within_free_time`: Days elapsed $\le 5$ (e.g. "3 estimated free days remaining").
  2. `last_free_day`: Exactly on boundary day 5 ("Last free day").
  3. `over_limit`: Days elapsed $> 5$ (e.g. "Over limit by 2 days, estimated demurrage charges accruing").
- **Audit Logging:** Any administrative change to the free-time threshold on the settings page writes an immutable record to `audit_logs`.

---

### Feature 5: Bulk Consignment CSV Import

#### End-to-End Execution Path:
- **Route:** `GET /shipments/import`, `POST /shipments/import/preview`, `POST /shipments/import/commit` &bull; [routes/web.php](file:///Users/user/Herd/cargotrace/routes/web.php#L77-L79)
- **Form Request:** `ImportShipmentRequest` (enforces max 2 MB, `.csv` or `.txt`, max 500 rows)
- **Controller:** `ShipmentImportWebController` &bull; [app/Http/Controllers/ShipmentImportWebController.php](file:///Users/user/Herd/cargotrace/app/Http/Controllers/ShipmentImportWebController.php)
- **Service:** `ShipmentImportService` &bull; [app/Services/ShipmentImportService.php](file:///Users/user/Herd/cargotrace/app/Services/ShipmentImportService.php)

#### Two-Step Workflow:
1. **Preview Step:** Parses uploaded CSV, maps columns (`customer_email`, `carrier_code`, `origin_unlocode`, `destination_unlocode`, `container_number`, `cargo_type`, `declared_value`, `departure_date`, `arrival_date`), runs validation rules per row, and displays a categorized preview table (valid rows highlighted in emerald, invalid rows with specific column errors highlighted in rose).
2. **Commit Step:** Operator reviews preview and clicks "Commit Import".
   - Encapsulated inside `DB::transaction()`.
   - Re-validates valid rows and creates shipments and items atomically.
   - Dispatches single `audit_logs` record with action `shipments.bulk_imported` and total count.

---

### Feature 6: Fleet & Lane Performance Analytics

#### End-to-End Execution Path:
- **Route:** `GET /analytics`, `GET /analytics/export` &bull; [routes/web.php](file:///Users/user/Herd/cargotrace/routes/web.php#L49-L50)
- **Controller:** `AnalyticsWebController` &bull; [app/Http/Controllers/AnalyticsWebController.php](file:///Users/user/Herd/cargotrace/app/Http/Controllers/AnalyticsWebController.php)
- **Service:** `AnalyticsService` &bull; [app/Services/AnalyticsService.php](file:///Users/user/Herd/cargotrace/app/Services/AnalyticsService.php)
- **Charts:** Chart.js canvas with accessible tabular data mirrors &bull; [resources/js/charts.js](file:///Users/user/Herd/cargotrace/resources/js/charts.js)

#### SQL Aggregations (Zero in-memory array crunching):
- **On-Time Rate per Carrier:**
  ```sql
  SELECT carriers.name, COUNT(*) as total,
         SUM(CASE WHEN delay_hours <= 0 THEN 1 ELSE 0 END) as on_time
  FROM shipments JOIN carriers ON shipments.carrier_id = carriers.id
  WHERE shipments.status = 'DELIVERED'
  GROUP BY carriers.id
  ```
- **Top Delayed Shipping Lanes:**
  ```sql
  SELECT CONCAT(orig.unlocode, ' → ', dest.unlocode) as lane,
         AVG(delay_hours) as avg_delay, COUNT(*) as count
  FROM shipments
  JOIN locations orig ON shipments.origin_location_id = orig.id
  JOIN locations dest ON shipments.destination_location_id = dest.id
  WHERE shipments.is_delayed = 1
  GROUP BY orig.unlocode, dest.unlocode
  ORDER BY avg_delay DESC LIMIT 5
  ```
- **Role Isolation:** If logged in as customer, `$analyticsService->forCustomer($user->id)` automatically adds `WHERE customer_id = ?` to all aggregate queries.

---

### Feature 7: Multi-Tenant Role Isolation (`CustomerScope` & `ShipmentPolicy`)

#### Architectural Defense-in-Depth:
1. **Global Eloquent Scope (`CustomerScope`):**
   - Implemented in `app/Models/Scopes/CustomerScope.php` applied to `Shipment`.
   - When `auth()->user()->role === 'customer'`, automatically constrains all queries:
     `$builder->where('customer_id', auth()->id())`.
   - Admins and operators are unrestricted.
2. **Policy Authorization (`ShipmentPolicy`):**
   - `view(User $user, Shipment $shipment)`: Returns true if user is admin, operator, or `$user->id === $shipment->customer_id`.
   - `create(User $user)`, `update(...)`: Returns true only for `admin` and `operator`.
   - `delete(...)`: Returns true strictly for `admin`.

---

## 3. Relational Database Design: Why `hasMany` vs `belongsTo`

| Model Relationship | Type | Foreign Key Placement | Architectural Justification |
| :--- | :---: | :---: | :--- |
| `User` $\rightarrow$ `Shipment` | `hasMany` | `shipments.customer_id` | A customer (shipper) books many consignments over time; each consignment belongs to exactly one legal account owner. |
| `Carrier` $\rightarrow$ `Shipment` | `hasMany` | `shipments.carrier_id` | An ocean carrier (e.g. MSC) hauls thousands of independent consignments worldwide. |
| `Location` $\rightarrow$ `Shipment` (Origin/Dest) | `hasMany` | `origin_location_id`, `destination_location_id` | A physical container terminal (e.g. Port of Singapore `SGSIN`) acts as origin or destination for multiple sea voyages. |
| `Shipment` $\rightarrow$ `ShipmentItem` | `hasMany` | `shipment_items.shipment_id` | A single bill of lading consignment contains one or more shipping containers / cargo items (`cascadeOnDelete`). |
| `Shipment` $\rightarrow$ `TrackingEvent` | `hasMany` | `tracking_events.shipment_id` | A consignment accumulates an ordered chronological sequence of physical milestone checkpoints during oceanic transit (`cascadeOnDelete`). |
| `Shipment` $\rightarrow$ `ShipmentException` | `hasMany` | `shipment_exceptions.shipment_id` | A consignment may encounter zero, one, or several operational incidents (e.g. customs inspection, weather hold) during voyage. |
| `User` $\rightarrow$ `Notification` | `hasMany` | `notifications.user_id` | In-app alerts are delivered to individual users; each notification belongs to one recipient (`cascadeOnDelete`). |
| `TrackingEvent` $\rightarrow$ `Location` | `belongsTo`| `tracking_events.location_id` | A milestone event occurs at a specific seaport terminal (`nullOnDelete` to preserve history if port is modified). |
| `AuditLog` $\rightarrow$ `User` | `belongsTo`| `audit_logs.user_id` | Each audit entry records the specific human or system actor who executed the database state mutation (`nullOnDelete`). |

---

## 4. Live Coding Interview Scenarios & Exact Files to Modify

During your oral technical defense, examiners frequently ask you to make small live modifications to prove you wrote and understand the codebase. Here are the three most likely requests and the exact files to edit:

### Scenario A: Modify or Add a Delay Threshold Tier

**Examiner Request:** *"Change the delay calculation so that delays over 48 hours are classified as 'CRITICAL' instead of 'HIGH', or adjust the LOW threshold from 6 hours to 8 hours."*

**Exact Files to Modify:**
1. **Config & Defaults:** [config/cargotrace.php](file:///Users/user/Herd/cargotrace/config/cargotrace.php#L30-L33)
   ```php
   'delay' => [
       'low_hours' => (float) env('CARGOTRACE_DELAY_LOW_HOURS', 8), // was 6
       'medium_hours' => (float) env('CARGOTRACE_DELAY_MEDIUM_HOURS', 24),
       'critical_hours' => (float) env('CARGOTRACE_DELAY_CRITICAL_HOURS', 48),
   ],
   ```
2. **Logic Engine:** [app/Services/EtaService.php](file:///Users/user/Herd/cargotrace/app/Services/EtaService.php#L68-L85)
   Update the severity assignment logic:
   ```php
   if ($delayHours > $criticalHours) {
       $severity = 'CRITICAL';
   } elseif ($delayHours > $mediumHours) {
       $severity = 'HIGH';
   }
   ```
3. **Database Migration (if adding enum value):**
   Update `shipments.delay_severity` enum in `database/migrations/2026_11_10_050003_create_shipments_table.php` (or add a migration adding `'CRITICAL'`).
4. **UI Chip Badge:** [resources/views/components/delay-severity-badge.blade.php](file:///Users/user/Herd/cargotrace/resources/views/components/delay-severity-badge.blade.php)
   Add the color mapping for `'CRITICAL'` (e.g. `bg-rose-950 text-rose-300 border-rose-800`).

---

### Scenario B: Add a Status Progression Rule (Terminal State & Prerequisite Check)

**Examiner Request:** *"Ensure that a shipment cannot be marked as 'IN_TRANSIT' unless at least one tracking event exists, or prevent marking 'DELIVERED' unless all shipment exceptions are resolved."*

**Exact Files to Modify:**
1. **Service Layer Method:** [app/Services/ShipmentService.php](file:///Users/user/Herd/cargotrace/app/Services/ShipmentService.php#L140-L165)
   Inside `updateStatus(Shipment $shipment, string $newStatus)`:
   ```php
   // Interview modification: Prerequisite rule before DELIVERED
   if ($newStatus === 'DELIVERED') {
       $hasUnresolvedExceptions = $shipment->exceptions()
           ->whereIn('status', ['OPEN', 'INVESTIGATING'])
           ->exists();

       if ($hasUnresolvedExceptions) {
           throw new \DomainException('Cannot deliver shipment while operational exceptions remain unresolved.');
       }
   }
   ```
2. **Controller Error Handling:** [app/Http/Controllers/ShipmentWebController.php](file:///Users/user/Herd/cargotrace/app/Http/Controllers/ShipmentWebController.php#L130)
   Catch `\DomainException` and redirect back with `->withErrors(['status' => $e->getMessage()])`.

---

### Scenario C: Add a New Filter to the Shipments List (e.g. Filter by Origin Seaport or Cargo Type)

**Examiner Request:** *"Add a dropdown on the shipments index page so operators can filter by origin seaport."*

**Exact Files to Modify:**
1. **Repository / Query Scoping:** [app/Services/ShipmentService.php](file:///Users/user/Herd/cargotrace/app/Services/ShipmentService.php#L35-L60)
   Inside `getPaginatedShipments(array $filters = [], int $perPage = 15)`:
   ```php
   if (!empty($filters['origin_id'])) {
       $query->where('origin_location_id', $filters['origin_id']);
   }
   ```
2. **Controller Parameter Passing:** [app/Http/Controllers/ShipmentWebController.php](file:///Users/user/Herd/cargotrace/app/Http/Controllers/ShipmentWebController.php#L35-L45)
   Pass `$request->only(['search', 'status', 'is_delayed', 'carrier_id', 'origin_id'])` to `getPaginatedShipments()`, and load `$origins = Location::where('type', 'PORT')->orderBy('name')->get();` into the view.
3. **Blade Filter Bar:** [resources/views/shipments/index.blade.php](file:///Users/user/Herd/cargotrace/resources/views/shipments/index.blade.php#L40-L70)
   Add the select element:
   ```html
   <select name="origin_id" onchange="this.form.submit()" class="theme-input text-xs">
       <option value="">All origin ports</option>
       @foreach($origins as $origin)
           <option value="{{ $origin->id }}" {{ request('origin_id') == $origin->id ? 'selected' : '' }}>
               {{ $origin->name }} ({{ $origin->unlocode }})
           </option>
       @endforeach
   </select>
   ```

---

## 5. Security & Verification Checklist for Oral Presentation

- [x] **Rate Limiting:** Public tracking is protected by `throttle:30,1`; Web and API login protected by `throttle:10,1`.
- [x] **Source Code & Skill Integrity:** Sensitive files (`.env*`, `.git`, `.claude`) return HTTP 404 in `SecurityHeadersMiddleware`.
- [x] **Container Security:** Non-root execution (`USER www-data`) and automated `/up` healthcheck.
- [x] **WCAG Contrast & Anti-Slop:** Tested across light mode (`#F4F7FB`) and dark mode (`#050B14`) at 1440px desktop and 390px mobile.

