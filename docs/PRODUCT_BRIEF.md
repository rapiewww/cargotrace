# CargoTrace product brief

## Story
CargoTrace is the shipment-tracking portal of a fictional freight forwarder (working name "Nusa Spice Freight", fictional) that moves Indonesian spices and essential oils (for example nutmeg, cloves, cinnamon, pepper and patchouli oil) from Sumatra and Java to buyers in Europe, the Middle East, South Asia and North America. All data in this project is synthetic and documented in docs/DEMO_DATA.md.

## Users
- Admin: the forwarder's director. Manages users, carriers, locations and delay thresholds, reads the audit log.
- Operator: the operations desk. Creates shipments, records checkpoints, manages exceptions.
- Customer: a buyer such as a spice wholesaler or a fragrance house. Sees only their own shipments.

## Transport modes (one primary mode per shipment)
- SEA: containers (20ft, 40ft). References: ISO 6346 container number and bill of lading number. Locations: seaports (UN/LOCODE). Typical use: bulk spices.
- AIR: references: air waybill (AWB), 11 digits = 3-digit airline prefix + 7-digit serial + 1 check digit (serial modulo 7). Locations: airports (IATA code). Typical use: high-value essential oils, samples, urgent orders.
- ROAD: references: vehicle plate and CMR or consignment note number. Locations: depots, warehouses, border crossings, and ports or airports as handover points. Typical use: first and last mile and regional hauls (for example Aceh to Medan, Marseille to Grasse).
Phase 1 supports one primary mode per shipment. A chained door-to-door journey with several legs is future work.

## Cargo fields
Commodity name, HS code (examples to verify before the final report: 0908 nutmeg, 3301 essential oils), gross and net weight, volume, packaging, declared value and currency, Incoterm.

## Exceptions
Keep the six types from the course brief: CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION (shown as terminal, airport or border congestion depending on the mode), DAMAGED, MISROUTED, DOCUMENTATION_ISSUE. Add three mode-specific types: FLIGHT_OFFLOAD (air), BORDER_QUEUE (road), VEHICLE_BREAKDOWN (road). Severity and the OPEN, INVESTIGATING, RESOLVED statuses stay as they are.

## Delay rules
The same deterministic rule for all modes: delay hours versus the expected arrival, severity LOW up to the low threshold, MEDIUM up to the medium threshold, HIGH above. Thresholds become per mode and are edited by the admin. Suggested defaults in hours (low/medium): AIR 3/12, ROAD 6/24, SEA 12/48.

## Out of scope
No GPS or AIS vessel tracking, no machine-learning ETA, no payments, no real carrier integrations. Weather, news and currency are context only and never claimed to predict delays.
