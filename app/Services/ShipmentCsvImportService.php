<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Rules\Iso6346ContainerNumber;
use App\Support\Iso6346;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ShipmentCsvImportService
{
    public const MAX_ROWS = 500;

    public const MAX_BYTES = 2097152; // 2 MB

    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    /**
     * Validate the uploaded file size and extension, and parse rows.
     *
     * @return array{
     *     total_rows: int,
     *     valid_count: int,
     *     invalid_count: int,
     *     valid_rows: array<int, array<string, mixed>>,
     *     invalid_rows: array<int, array{row_number: int, data: array<string, string>, errors: array<int, string>}>
     * }
     *
     * @throws ValidationException
     */
    public function parseAndValidate(UploadedFile|string $file, User $currentUser): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded file is inaccessible or unreadable.',
            ]);
        }

        if (filesize($filePath) > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded CSV file exceeds the maximum allowed size of 2 MB.',
            ]);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => 'Unable to read the CSV file stream.',
            ]);
        }

        // Read header row
        $rawHeader = fgetcsv($handle);
        if ($rawHeader === false || empty($rawHeader)) {
            fclose($handle);
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded CSV file is empty or malformed.',
            ]);
        }

        // Clean Byte Order Mark (BOM) and trim/lowercase headers
        $headers = [];
        foreach ($rawHeader as $idx => $h) {
            if ($idx === 0) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            }
            $clean = strtolower(trim((string) $h));
            $clean = str_replace([' ', '-', '.'], '_', $clean);
            $headers[] = $clean;
        }

        // Require minimum identifying columns
        $headerSet = array_flip($headers);
        $hasOrigin = isset($headerSet['origin_unlocode']) || isset($headerSet['origin']) || isset($headerSet['origin_location_id']);
        $hasDestination = isset($headerSet['destination_unlocode']) || isset($headerSet['destination']) || isset($headerSet['destination_location_id']);
        $hasCarrier = isset($headerSet['carrier_code']) || isset($headerSet['carrier']) || isset($headerSet['carrier_id']);

        if (! $hasOrigin || ! $hasDestination || ! $hasCarrier) {
            fclose($handle);
            throw ValidationException::withMessages([
                'csv_file' => 'The CSV header must include columns for carrier, origin, and destination.',
            ]);
        }

        // Preload active lookup tables for fast resolution
        $carriersByCode = Carrier::where('is_active', true)->get()->keyBy(fn ($c) => strtoupper(trim($c->code)));
        $carriersById = Carrier::where('is_active', true)->get()->keyBy('id');
        $locationsByUnlocode = Location::all()->keyBy(fn ($l) => strtoupper(trim($l->unlocode)));
        $locationsByIata = Location::whereNotNull('iata_code')->get()->keyBy(fn ($l) => strtoupper(trim($l->iata_code)));
        $locationsById = Location::all()->keyBy('id');
        $customersByEmail = User::where('role', 'customer')->get()->keyBy(fn ($u) => strtolower(trim($u->email)));
        $customersById = User::where('role', 'customer')->get()->keyBy('id');

        $rows = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;

            // Skip empty rows
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            // Detect malformed line with mismatched column count
            if (count($row) !== count($headers)) {
                // If counts differ significantly, attempt padding or flag
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } else {
                    $row = array_slice($row, 0, count($headers));
                }
            }

            $mapped = [];
            foreach ($headers as $i => $colName) {
                $mapped[$colName] = trim((string) ($row[$i] ?? ''));
            }

            $rows[] = [
                'line_number' => $lineNum,
                'raw' => $mapped,
            ];
        }

        fclose($handle);

        $totalRows = count($rows);

        if ($totalRows === 0) {
            throw ValidationException::withMessages([
                'csv_file' => 'The CSV file contains no data rows.',
            ]);
        }

        if ($totalRows > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'csv_file' => "The CSV file exceeds the maximum limit of 500 rows (found {$totalRows} rows).",
            ]);
        }

        $validRows = [];
        $invalidRows = [];
        $seenTrackingInFile = [];

        foreach ($rows as $item) {
            $line = $item['line_number'];
            $data = $item['raw'];
            $errors = [];

            // 1. Resolve Carrier
            $carrierId = null;
            $carrierName = null;
            $carrierCode = null;

            $rawCarrier = $data['carrier_code'] ?? ($data['carrier'] ?? ($data['carrier_id'] ?? null));
            if ($rawCarrier !== null && $rawCarrier !== '') {
                $carrierUpper = strtoupper($rawCarrier);
                if (isset($carriersByCode[$carrierUpper])) {
                    $c = $carriersByCode[$carrierUpper];
                    $carrierId = $c->id;
                    $carrierName = $c->name;
                    $carrierCode = $c->code;
                } elseif (is_numeric($rawCarrier) && isset($carriersById[(int) $rawCarrier])) {
                    $c = $carriersById[(int) $rawCarrier];
                    $carrierId = $c->id;
                    $carrierName = $c->name;
                    $carrierCode = $c->code;
                } else {
                    $errors[] = "Unknown shipping carrier: '{$rawCarrier}'.";
                }
            } else {
                $errors[] = 'Carrier is required.';
            }

            // 2. Resolve Origin Location
            $originId = null;
            $originName = null;
            $originUnlocode = null;

            $rawOrigin = $data['origin_unlocode'] ?? ($data['origin'] ?? ($data['origin_location_id'] ?? null));
            if ($rawOrigin !== null && $rawOrigin !== '') {
                $originUpper = strtoupper($rawOrigin);
                if (isset($locationsByUnlocode[$originUpper])) {
                    $l = $locationsByUnlocode[$originUpper];
                    $originId = $l->id;
                    $originName = $l->name;
                    $originUnlocode = $l->display_code;
                } elseif (isset($locationsByIata[$originUpper])) {
                    $l = $locationsByIata[$originUpper];
                    $originId = $l->id;
                    $originName = $l->name;
                    $originUnlocode = $l->display_code;
                } elseif (is_numeric($rawOrigin) && isset($locationsById[(int) $rawOrigin])) {
                    $l = $locationsById[(int) $rawOrigin];
                    $originId = $l->id;
                    $originName = $l->name;
                    $originUnlocode = $l->display_code;
                } else {
                    $errors[] = "Unknown origin location: '{$rawOrigin}'.";
                }
            } else {
                $errors[] = 'Origin location is required.';
            }

            // 3. Resolve Destination Location
            $destId = null;
            $destName = null;
            $destUnlocode = null;

            $rawDest = $data['destination_unlocode'] ?? ($data['destination'] ?? ($data['destination_location_id'] ?? null));
            if ($rawDest !== null && $rawDest !== '') {
                $destUpper = strtoupper($rawDest);
                if (isset($locationsByUnlocode[$destUpper])) {
                    $l = $locationsByUnlocode[$destUpper];
                    $destId = $l->id;
                    $destName = $l->name;
                    $destUnlocode = $l->display_code;
                } elseif (isset($locationsByIata[$destUpper])) {
                    $l = $locationsByIata[$destUpper];
                    $destId = $l->id;
                    $destName = $l->name;
                    $destUnlocode = $l->display_code;
                } elseif (is_numeric($rawDest) && isset($locationsById[(int) $rawDest])) {
                    $l = $locationsById[(int) $rawDest];
                    $destId = $l->id;
                    $destName = $l->name;
                    $destUnlocode = $l->display_code;
                } else {
                    $errors[] = "Unknown destination location: '{$rawDest}'.";
                }
            } else {
                $errors[] = 'Destination location is required.';
            }

            if ($originId !== null && $destId !== null && $originId === $destId) {
                $errors[] = 'Destination location must be different from origin location.';
            }

            // 4. Resolve Customer
            $customerId = null;
            $customerEmail = null;

            $rawCustomer = $data['customer_email'] ?? ($data['customer'] ?? ($data['customer_id'] ?? null));
            if ($rawCustomer !== null && $rawCustomer !== '') {
                $customerLower = strtolower($rawCustomer);
                if (isset($customersByEmail[$customerLower])) {
                    $u = $customersByEmail[$customerLower];
                    $customerId = $u->id;
                    $customerEmail = $u->email;
                } elseif (is_numeric($rawCustomer) && isset($customersById[(int) $rawCustomer])) {
                    $u = $customersById[(int) $rawCustomer];
                    $customerId = $u->id;
                    $customerEmail = $u->email;
                } else {
                    $errors[] = "Customer account '{$rawCustomer}' not found in platform.";
                }
            } else {
                // If not provided, assign to default customer or operator
                $defaultCustomer = $customersByEmail->first() ?? $currentUser;
                $customerId = $defaultCustomer->id;
                $customerEmail = $defaultCustomer->email;
            }

            // 5. Container Number Validation (ISO 6346)
            $containerNum = $data['container_number'] ?? null;
            $normalizedContainer = null;
            if ($containerNum !== null && $containerNum !== '') {
                $validator = Validator::make(
                    ['container_number' => $containerNum],
                    ['container_number' => [new Iso6346ContainerNumber]]
                );

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $msg) {
                        $errors[] = $msg;
                    }
                } else {
                    $normalizedContainer = Iso6346::format(Iso6346::normalize($containerNum));
                }
            }

            // 6. Tracking Number Uniqueness & Duplication check
            $trackingNumber = $data['tracking_number'] ?? null;
            if ($trackingNumber !== null && $trackingNumber !== '') {
                $trackingUpper = strtoupper($trackingNumber);

                if (in_array($trackingUpper, $seenTrackingInFile, true)) {
                    $errors[] = "Duplicate tracking number '{$trackingUpper}' found across multiple rows in this CSV file.";
                } else {
                    $seenTrackingInFile[] = $trackingUpper;
                }

                if (Shipment::where('tracking_number', $trackingUpper)->exists()) {
                    $errors[] = "Tracking number '{$trackingUpper}' is already registered in the system.";
                }
            }

            // 7. General Attribute Validation
            $cargoType = strtoupper($data['cargo_type'] ?? 'FCL');
            if (! in_array($cargoType, ['FCL', 'LCL', 'REEFER', 'HAZARDOUS'], true)) {
                $errors[] = "Cargo type must be one of FCL, LCL, REEFER, HAZARDOUS (found '{$cargoType}').";
            }

            $departureAt = $data['expected_departure_at'] ?? null;
            $arrivalAt = $data['expected_arrival_at'] ?? null;
            $estimatedAt = $data['estimated_arrival_at'] ?? null;

            if (! $departureAt || ! strtotime($departureAt)) {
                $errors[] = 'Expected departure date is invalid or missing.';
            }

            if (! $arrivalAt || ! strtotime($arrivalAt)) {
                $errors[] = 'Expected arrival date is invalid or missing.';
            } elseif ($departureAt && strtotime($departureAt) && strtotime($arrivalAt) < strtotime($departureAt)) {
                $errors[] = 'Expected arrival date must be on or after expected departure date.';
            }

            $declaredValue = (float) ($data['declared_value'] ?? 0);
            if ($declaredValue < 0) {
                $errors[] = 'Declared value must be a positive number.';
            }

            $shippingCost = (float) ($data['shipping_cost'] ?? 0);
            if ($shippingCost < 0) {
                $errors[] = 'Shipping cost must be a positive number.';
            }

            $currencyCode = strtoupper($data['currency_code'] ?? 'USD');
            if (strlen($currencyCode) !== 3) {
                $errors[] = "Currency code must be 3 characters (e.g. USD, EUR, SGD). Found '{$currencyCode}'.";
            }

            if (! empty($errors)) {
                $invalidRows[] = [
                    'row_number' => $line,
                    'data' => $data,
                    'errors' => $errors,
                ];
            } else {
                $itemDescription = $data['item_description'] ?? 'Containerized freight cargo';
                $itemWeight = (float) ($data['item_weight_kg'] ?? 15000);
                $itemVolume = (float) ($data['item_volume_cbm'] ?? 40);

                $validRows[] = [
                    'row_number' => $line,
                    'tracking_number' => $trackingNumber ? strtoupper($trackingNumber) : null,
                    'reference_number' => $data['reference_number'] ?? null,
                    'container_number' => $normalizedContainer,
                    'carrier_id' => $carrierId,
                    'carrier_code' => $carrierCode,
                    'carrier_name' => $carrierName,
                    'origin_location_id' => $originId,
                    'origin_unlocode' => $originUnlocode,
                    'origin_name' => $originName,
                    'destination_location_id' => $destId,
                    'destination_unlocode' => $destUnlocode,
                    'destination_name' => $destName,
                    'customer_id' => $customerId,
                    'customer_email' => $customerEmail,
                    'cargo_type' => $cargoType,
                    'declared_value' => $declaredValue,
                    'currency_code' => $currencyCode,
                    'shipping_cost' => $shippingCost,
                    'expected_departure_at' => date('Y-m-d H:i:s', strtotime($departureAt)),
                    'expected_arrival_at' => date('Y-m-d H:i:s', strtotime($arrivalAt)),
                    'estimated_arrival_at' => $estimatedAt && strtotime($estimatedAt) ? date('Y-m-d H:i:s', strtotime($estimatedAt)) : date('Y-m-d H:i:s', strtotime($arrivalAt)),
                    'notes' => $data['notes'] ?? null,
                    'items' => [
                        [
                            'container_number' => $normalizedContainer ? Iso6346::normalize($normalizedContainer) : null,
                            'description' => $itemDescription,
                            'quantity' => 1,
                            'unit_value' => $declaredValue,
                            'weight_kg' => $itemWeight,
                            'volume_cbm' => $itemVolume,
                            'currency_code' => $currencyCode,
                            'is_hazardous' => $cargoType === 'HAZARDOUS',
                        ],
                    ],
                ];
            }
        }

        return [
            'total_rows' => $totalRows,
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];
    }

    /**
     * Commit the valid rows in a single database transaction and write an audit log entry.
     *
     * @param  array<int, array<string, mixed>>  $validRows
     * @return array{imported_count: int, tracking_numbers: array<int, string>}
     */
    public function commit(array $validRows, User $actor): array
    {
        return DB::transaction(function () use ($validRows, $actor) {
            $createdTrackingNumbers = [];
            $firstShipmentId = 0;

            foreach ($validRows as $row) {
                $shipmentData = [
                    'tracking_number' => $row['tracking_number'] ?? null,
                    'reference_number' => $row['reference_number'] ?? null,
                    'customer_id' => $row['customer_id'],
                    'carrier_id' => $row['carrier_id'],
                    'origin_location_id' => $row['origin_location_id'],
                    'destination_location_id' => $row['destination_location_id'],
                    'cargo_type' => $row['cargo_type'],
                    'declared_value' => $row['declared_value'],
                    'currency_code' => $row['currency_code'],
                    'shipping_cost' => $row['shipping_cost'],
                    'expected_departure_at' => $row['expected_departure_at'],
                    'expected_arrival_at' => $row['expected_arrival_at'],
                    'estimated_arrival_at' => $row['estimated_arrival_at'],
                    'notes' => $row['notes'],
                    'container_number' => $row['container_number'] ? Iso6346::normalize($row['container_number']) : null,
                ];

                $items = $row['items'] ?? [];

                $shipment = $this->shipmentService->createShipment($shipmentData, $items);
                $createdTrackingNumbers[] = $shipment->tracking_number;
                if ($firstShipmentId === 0) {
                    $firstShipmentId = $shipment->id;
                }
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SHIPMENTS_BULK_IMPORTED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $firstShipmentId,
                'old_values' => null,
                'new_values' => [
                    'imported_count' => count($createdTrackingNumbers),
                    'tracking_numbers' => $createdTrackingNumbers,
                ],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'created_at' => now(),
            ]);

            return [
                'imported_count' => count($createdTrackingNumbers),
                'tracking_numbers' => $createdTrackingNumbers,
            ];
        });
    }

    /**
     * Generate downloadable sample CSV content with real carriers and UN/LOCODEs.
     */
    public function generateSampleCsv(): string
    {
        $headers = [
            'tracking_number',
            'reference_number',
            'container_number',
            'carrier_code',
            'origin_unlocode',
            'destination_unlocode',
            'customer_email',
            'cargo_type',
            'declared_value',
            'currency_code',
            'shipping_cost',
            'expected_departure_at',
            'expected_arrival_at',
            'estimated_arrival_at',
            'item_description',
            'item_weight_kg',
            'item_volume_cbm',
            'notes',
        ];

        $samples = [
            [
                'CTG-SEA-2026-90001',
                'B/L-MSCU-2026-90001',
                'MSCU 123456 6',
                'MSCU',
                'SGSIN',
                'NLRTM',
                'customer@cargotrace.test',
                'FCL',
                '65000.00',
                'USD',
                '4200.00',
                '2026-11-20 08:00:00',
                '2026-12-10 16:00:00',
                '2026-12-10 16:00:00',
                'Whole Nutmeg ABCD Grade (HS 0908.11)',
                '18500.00',
                '55.00',
                'Stow in dry ventilated container away from heat',
            ],
            [
                'CTG-SEA-2026-90002',
                'B/L-MAEU-2026-90002',
                Iso6346::format(Iso6346::generate('MAEU', 987654)),
                'MAEU',
                'IDBLW',
                'DEHAM',
                'customer2@cargotrace.test',
                'FCL',
                '92000.00',
                'EUR',
                '5600.00',
                '2026-11-22 10:00:00',
                '2026-12-14 18:00:00',
                '2026-12-14 18:00:00',
                'Sumatra Cassia Cinnamon Quills Grade A (HS 0906.11)',
                '14200.00',
                '48.00',
                'Phytosanitary certificate and silica desiccant packs inside',
            ],
            [
                'CTG-SEA-2026-90003',
                'B/L-HLCU-2026-90003',
                Iso6346::format(Iso6346::generate('HLCU', 456789)),
                'HLCU',
                'IDJKT',
                'USNYC',
                'customer4@cargotrace.test',
                'FCL',
                '78000.00',
                'USD',
                '4850.00',
                '2026-11-25 06:00:00',
                '2026-12-22 14:00:00',
                '2026-12-22 14:00:00',
                'Lampung Black Pepper Whole ASTA 570 (HS 0904.11)',
                '21000.00',
                '60.00',
                'Fumigation certificate verified at origin',
            ],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($samples as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content ?: '';
    }
}
