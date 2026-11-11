# CargoTrace — UI Architecture & Design System Notes

This document provides a comprehensive overview of the design system, component hierarchy, client-side modules, and responsive navigation architecture for CargoTrace.

---

## 1. Visual Language & Dual-Theme System

CargoTrace implements a fully synchronized, zero-flicker dual-theme system driven by CSS custom properties and `data-theme="dark"` / `data-theme="light"` attributes on `<html>`.

### Color Palette Tokens
| Token | Dark Mode (`[data-theme="dark"]`) | Light Mode (`[data-theme="light"]`) | Usage |
|---|---|---|---|
| `--bg-page` | `#050811` (Abyssal Navy) | `#F1F5F9` (Slate 100) | Main background canvas |
| `--bg-surface` | `#0B1222` | `#FFFFFF` | Standard card surface |
| `--bg-surface-elevated` | `#111A2E` | `#F8FAFC` | Modals, active states, sub-cards |
| `--bg-sidebar` | `#080D1A` | `#FFFFFF` | Global navigation sidebar |
| `--border-color` | `#1E293B` | `#E2E8F0` | Structural dividers & borders |
| `--text-heading` | `#FFFFFF` | `#0F172A` | Primary titles and high-contrast labels |
| `--text-primary` | `#F8FAFC` | `#1E293B` | High-contrast body text |
| `--text-secondary` | `#94A3B8` | `#475569` | Secondary descriptive captions |
| `--accent-blue` | `#2563EB` | `#2563EB` | Interactive links and primary actions |
| `--accent-cyan` | `#38BDF8` | `#0284C7` | Telemetry badges and nautical highlights |

### Typography
- **Primary Body & Headings**: `Inter` (weights 400, 500, 600, 700, 800) bundled via Vite.
- **Monospace Identifiers**: `JetBrains Mono` for tracking numbers (`CTG-SEA-2026-...`), UN/LOCODEs, coordinates, and ISO currency codes.

---

## 2. Navigation Architecture & Responsive Sidebar

CargoTrace features an enterprise maritime navigation hierarchy grouped strictly into four domain areas:

### Navigation Groups & Role Scoping
1. **Operations**:
   - `Global View` (`/dashboard`): 3D Earth Globe / 2D Leaflet interactive overview and KPIs.
   - `Shipments` (`/shipments`): Ocean waybill directory, lifecycle progression, and filters.
   - `Exceptions` (`/exceptions`): Incident logging and resolution (scoped to `admin` and `operator`). Displays live badge of open exception count.
2. **Intelligence**:
   - `Seaport Weather` (`/weather`): Live Open-Meteo atmospheric & marine wave telemetry, rule-based STS crane advisory, and comparison matrix.
   - `Currency FX` (`/currency`): Global reference rates via ExchangeRate-API, 30-day ECB historical trend chart, combobox, and portfolio value breakdown.
   - `Maritime News` (`/news`): Multi-feed RSS parser (gCaptain & Maritime Executive) with OpenGraph thumbnails, search, and shipment corridor match tags.
3. **Master Data** (`admin` & `operator` only):
   - `Carriers` (`/carriers`): Pure maritime shipping lines (Maersk, MSC, CMA CGM, COSCO, Hapag-Lloyd, ONE).
   - `Locations` (`/locations`): Seaport container terminals with UN/LOCODEs and coordinates.
4. **Administration** (`admin` only):
   - `Audit Logs` (`/audit-logs`): Immutable operational ledger of all updates.
   - `Users` (`/admin/users`): Multi-tenant role management (`admin`, `operator`, `customer`).
   - `Delay Thresholds` (`/admin/settings`): Runtime configurable hours for LOW and MEDIUM delay severity.

### Responsive Behaviors
- **Desktop Expanded**: Fixed `w-64` sidebar with full section headings, brand title, and icon labels.
- **Desktop Collapsed (Icon Rail)**: Collapses to `w-20` icon rail with centered icons, tooltips, and compact badges. State is preserved in `localStorage('cargotrace_sidebar_collapsed')` and applied before DOM paint to prevent layout shifts.
- **Mobile Drawer**: Slide-out drawer with backdrop overlay triggered by the header hamburger button.
- **Header Bar**: Minimalist sticky header with theme switch pill, desktop sidebar rail toggle, user profile badge, and unread notification popover.

---

## 3. Geospatial & Visual Modules (`resources/js/`)

1. **`map.js`**: Leaflet.js map orchestration using OpenStreetMap tile infrastructure (`https://tile.openstreetmap.org/{z}/{x}/{y}.png`). Applies CSS inversion and hue-rotation filter to tile panes in dark mode without altering marker or geodesic arc rendering.
2. **`globe.js`**: Interactive 3D WebGL Earth globe rendered with Three.js / Three-Globe. Textures are self-hosted locally under `/images/globe/` (`earth-blue-marble.jpg`, `earth-night.jpg`, `earth-topology.png`).
3. **`charts.js`**: Responsive Chart.js visualizers for container status distribution, carrier volume breakdowns, and 30-day currency historical trends.
4. **`app.js`**: Central application bootstrap providing `window.CargoTrace` namespace, theme synchronization, and sidebar state handlers.

---

## 4. Reusable Blade Components (`resources/views/components/`)

- `<x-card>`: Surface card with dual-theme border and responsive elevation.
- `<x-data-source-badge>`: Truthful provenance indicator (`LIVE`, `CACHED`, or `SIMULATED`).
- `<x-status-stepper>`: Visual horizontal/vertical milestone stepper (`CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED`).
- `<x-empty-state>`: Standardized empty state container with icon and call to action.
