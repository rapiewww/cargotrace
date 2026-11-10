# CargoTrace — UI Architecture & Design System Notes

This document provides a concise overview of the UI redesign, component hierarchy, client-side modules, and architectural decisions for CargoTrace.

---

## 1. Visual Language & Design Tokens

### Color Palette (Deep Ocean / Maritime Abyssal)
- **Base Surface**: `#050B14` (Deep Maritime Abyssal Canvas)
- **Primary Card Surface**: `#0B1728` (`bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl`)
- **Secondary Card Surface**: `#11233B` (Elevated headers, active tabs, modal interiors)
- **Primary Interactive Accent**: `#F5A524` to `#FB923C` (Amber / Gold CTA gradient)
- **Secondary Telemetry Accent**: `#2DD4BF` (Teal / Cyan for maritime paths, coordinates, UN/LOCODE)
- **Status Semantics**:
  - `ON_TIME` / `DELIVERED` / `RESOLVED`: Emerald (`#10B981`)
  - `WARNING` / `DELAYED` / `IN_TRANSIT`: Amber (`#F59E0B`)
  - `EXCEPTION` / `DANGER` / `CRITICAL`: Rose (`#F43F5E`)
  - `BOOKED` / `PICKED_UP`: Sky / Blue (`#38BDF8`)
  - `CREATED`: Slate (`#94A3B8`)

### Typography
- **UI Body & Headings**: `Plus Jakarta Sans` (400, 500, 600, 700, 800) bundled locally via Vite plugin.
- **Monospace Identifiers**: `JetBrains Mono` (400, 500, 600, 700) for tracking numbers, UN/LOCODE, timestamps, currency rates.

---

## 2. Navigation Architecture
- **Single Unified Top Navigation**: Replaced the cramped dual-navigation (top nav + icon bar) with a spacious, sticky top bar across full container width (`max-w-[1600px]`).
- Includes clear section links (`Dashboard`, `Shipments`, `Exceptions`, `Carriers`, `Global Ports`, `Audit Logs`, `Users`, `Settings`), user badge, live notification popover, and clean mobile drawer.

---

## 3. Reusable Blade Components (`resources/views/components/`)
1. `<x-card>`: Enterprise frosted glass container (`bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl rounded-2xl`).
2. `<x-stat-card>`: Standardized KPI card with icon, label, metric, variance trend badge, and optional live pulse indicator.
3. `<x-badge>`: Status pill with animated pulsing dot and deterministic severity/status styling.
4. `<x-button>`: Interactive button with variants (`primary`, `secondary`, `ghost`, `danger`) and micro-interaction transitions (`hover:scale-[1.02] active:scale-[0.98]`).
5. `<x-input>` & `<x-select>`: Form controls with `#0B1728` background, `border-slate-800`, and `focus:ring-2 focus:ring-amber-500/50`.
6. `<x-table>`: Dark styled data table with rounded headers and hover rows.
7. `<x-empty-state>`: Consistent zero-data state with icon, explanatory message, and optional action button.
8. `<x-page-header>`: Clean title bar with breadcrumb trail, action buttons, and live environment tag.
9. `<x-progress-bar>`: Route milestone progress bar with departure, current location, and destination checkpoint indicators.
10. `<x-status-stepper>`: Visual horizontal/vertical milestone stepper (`CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED`).
11. `<x-data-source-badge>`: Truthful provenance pill (`LIVE`, `CACHED`, `SIMULATED`) for weather, currency, and news telemetry.
12. `<x-toast>`: Flash notification alert banner with auto-dismiss.
13. `<x-modal>`: Accessible modal dialog with backdrop blur.

---

## 4. JavaScript Modules Architecture (`resources/js/`)
- `resources/js/app.js`: Main entry point importing dependencies and initializing page modules.
- `resources/js/map.js`: Leaflet map orchestration, dark CartoDB tiles, origin/destination pins, and quadratic Bezier route curves.
- `resources/js/charts.js`: Chart.js initialization for Status Doughnut (with center total plugin), Carrier bar chart, 8-week delay trend line, and Exception distribution.
- `resources/js/tracking.js`: Public & authenticated tracking lookups, timeline animation, and quick track modal handler.
- `resources/js/ui.js`: Notification menu, modal toggles, currency converter calculator, and toast dismissals.

---

## 5. Honesty & Fallback Policy
- **No False Claims**: All references to real-time satellite AIS or AI/ML predictions are replaced with truthful terminology: "Rule-based ETA & delay evaluation" and "Operator-recorded checkpoint events".
- **Telemetry Provenance**: Any external integration (OpenWeatherMap, ExchangeRate API, Logistics News) that returns fallback or cached data is explicitly badged as `SIMULATED` or `CACHED`.
