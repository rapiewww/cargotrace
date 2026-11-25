<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Iso6346;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('customer is strictly forbidden from shipment CSV import endpoints', function () {
    $customer = User::where('role', 'customer')->first();

    // Index
    $this->actingAs($customer)
        ->get('/shipments/import')
        ->assertForbidden();

    // Sample
    $this->actingAs($customer)
        ->get('/shipments/import/sample')
        ->assertForbidden();

    // Preview
    $file = UploadedFile::fake()->create('shipments.csv', 10, 'text/csv');
    $this->actingAs($customer)
        ->post('/shipments/import/preview', ['csv_file' => $file])
        ->assertForbidden();

    // Confirm
    $this->actingAs($customer)
        ->post('/shipments/import/confirm')
        ->assertForbidden();
});

test('operator and admin can access import upload page and download sample CSV', function () {
    $operator = User::where('role', 'operator')->first();
    $admin = User::where('role', 'admin')->first();

    // Operator
    $this->actingAs($operator)
        ->get('/shipments/import')
        ->assertOk()
        ->assertSee('Bulk CSV shipment import');

    $response = $this->actingAs($operator)
        ->get('/shipments/import/sample')
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
    $csvContent = $response->getContent();
    expect($csvContent)->toContain('carrier_code,origin_unlocode,destination_unlocode');
    expect($csvContent)->toContain('MSCU 123456 6');

    // Admin
    $this->actingAs($admin)
        ->get('/shipments/import')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/shipments/import/sample')
        ->assertOk();
});

test('oversize file exceeding 2 MB is rejected with validation error', function () {
    $operator = User::where('role', 'operator')->first();

    // 2.5 MB file (2560 KB > 2048 KB)
    $oversizeFile = UploadedFile::fake()->create('large.csv', 2560, 'text/csv');

    $response = $this->actingAs($operator)
        ->post('/shipments/import/preview', [
            'csv_file' => $oversizeFile,
        ]);

    $response->assertSessionHasErrors('csv_file');
});

test('malformed or empty CSV file is rejected', function () {
    $operator = User::where('role', 'operator')->first();

    // Empty file
    $emptyFile = UploadedFile::fake()->createWithContent('empty.csv', '');

    $response1 = $this->actingAs($operator)
        ->post('/shipments/import/preview', [
            'csv_file' => $emptyFile,
        ]);

    $response1->assertSessionHasErrors('csv_file');

    // Header only (no data rows)
    $headerOnly = UploadedFile::fake()->createWithContent(
        'header_only.csv',
        "carrier_code,origin_unlocode,destination_unlocode,expected_departure_at,expected_arrival_at\n"
    );

    $response2 = $this->actingAs($operator)
        ->post('/shipments/import/preview', [
            'csv_file' => $headerOnly,
        ]);

    $response2->assertSessionHasErrors('csv_file');
});

test('file exceeding 500 rows is rejected with validation error', function () {
    $operator = User::where('role', 'operator')->first();

    $lines = [
        'carrier_code,origin_unlocode,destination_unlocode,expected_departure_at,expected_arrival_at',
    ];

    for ($i = 1; $i <= 501; $i++) {
        $lines[] = 'MSCU,SGSIN,NLRTM,2026-11-20,2026-12-10';
    }

    $file = UploadedFile::fake()->createWithContent('too_many_rows.csv', implode("\n", $lines));

    $response = $this->actingAs($operator)
        ->post('/shipments/import/preview', [
            'csv_file' => $file,
        ]);

    $response->assertSessionHasErrors('csv_file');
});

test('duplicate shipment numbers within CSV and against database are flagged as invalid rows', function () {
    $operator = User::where('role', 'operator')->first();
    $existing = Shipment::whereNotNull('tracking_number')->first();

    $csv = implode("\n", [
        'tracking_number,carrier_code,origin_unlocode,destination_unlocode,container_number,expected_departure_at,expected_arrival_at',
        // Row 2: duplicates existing DB shipment
        "{$existing->tracking_number},MSCU,SGSIN,NLRTM,MSCU 123456 6,2026-11-20,2026-12-10",
        // Row 3: new tracking number
        'CTG-SEA-2026-88001,MAEU,IDBLW,DEHAM,MAEU 987654 2,2026-11-22,2026-12-14',
        // Row 4: duplicate of row 3 tracking number
        'CTG-SEA-2026-88001,HLCU,IDJKT,USNYC,'.Iso6346::format(Iso6346::generate('HLCU', 456789)).',2026-11-25,2026-12-12',
    ]);

    $file = UploadedFile::fake()->createWithContent('duplicates.csv', $csv);

    $response = $this->actingAs($operator)
        ->post('/shipments/import/preview', [
            'csv_file' => $file,
        ]);

    $response->assertOk();
    $response->assertSee('1 of 3 valid');
    $response->assertSee('Flagged rows with validation errors');
    $response->assertSee('is already registered in the system');
    $response->assertSee('Duplicate tracking number');
});

test('partial errors: preview separates valid and invalid rows, confirm commits only valid rows in one transaction with audit log', function () {
    $operator = User::where('role', 'operator')->first();

    $initialCount = Shipment::count();

    $csv = implode("\n", [
        'tracking_number,carrier_code,origin_unlocode,destination_unlocode,container_number,cargo_type,declared_value,currency_code,expected_departure_at,expected_arrival_at,item_description',
        // Row 2: Valid
        'CTG-SEA-2026-99001,MSCU,SGSIN,NLRTM,MSCU 123456 6,FCL,50000.00,USD,2026-11-20,2026-12-10,Machinery equipment',
        // Row 3: Invalid ISO 6346 container number (check digit 9 is wrong, should be 6)
        'CTG-SEA-2026-99002,MSCU,SGSIN,NLRTM,MSCU 123456 9,FCL,20000.00,USD,2026-11-20,2026-12-10,Auto parts',
        // Row 4: Invalid (same origin and destination)
        'CTG-SEA-2026-99003,MAEU,SGSIN,SGSIN,MAEU 987654 2,FCL,30000.00,USD,2026-11-20,2026-12-10,Textiles',
        // Row 5: Valid
        'CTG-SEA-2026-99004,HLCU,IDJKT,USNYC,'.Iso6346::format(Iso6346::generate('HLCU', 456789)).',FCL,75000.00,USD,2026-11-22,2026-12-15,Lampung Black Pepper Whole',
    ]);

    $file = UploadedFile::fake()->createWithContent('partial.csv', $csv);

    // 1. Preview
    $previewResponse = $this->actingAs($operator)
        ->post('/shipments/import/preview', [
            'csv_file' => $file,
        ]);

    $previewResponse->assertOk();
    $previewResponse->assertSee('2 of 4 valid');
    $previewResponse->assertSee('CTG-SEA-2026-99001');
    $previewResponse->assertSee('CTG-SEA-2026-99004');
    $previewResponse->assertSee('Flagged rows with validation errors (2)');
    $previewResponse->assertSee('Destination location must be different from origin location');

    expect(session('shipment_import_valid_rows'))->toHaveCount(2);

    // 2. Confirm Commit
    $confirmResponse = $this->actingAs($operator)
        ->post('/shipments/import/confirm');

    $confirmResponse->assertRedirect('/shipments');
    $confirmResponse->assertSessionHas('success', 'Successfully imported 2 ocean container shipments.');

    // Verify DB additions
    expect(Shipment::count())->toBe($initialCount + 2);
    expect(Shipment::where('tracking_number', 'CTG-SEA-2026-99001')->exists())->toBeTrue();
    expect(Shipment::where('tracking_number', 'CTG-SEA-2026-99004')->exists())->toBeTrue();
    expect(Shipment::where('tracking_number', 'CTG-SEA-2026-99002')->exists())->toBeFalse();
    expect(Shipment::where('tracking_number', 'CTG-SEA-2026-99003')->exists())->toBeFalse();

    // Verify Audit Log
    $audit = AuditLog::where('action', 'SHIPMENTS_BULK_IMPORTED')->latest()->first();
    expect($audit)->not->toBeNull();
    expect($audit->user_id)->toBe($operator->id);
    expect($audit->new_values['imported_count'])->toBe(2);
    expect($audit->new_values['tracking_numbers'])->toContain('CTG-SEA-2026-99001', 'CTG-SEA-2026-99004');

    // Verify session was cleared
    expect(session('shipment_import_valid_rows'))->toBeNull();
});
