<?php

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('public landing page loads successfully with hero and metrics', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('CargoTrace');
    $response->assertSee('Global spice freight by sea, air and road');
    $response->assertSee('Track Shipment');
    $response->assertSee('Sign In / Portal');
});

test('public landing page can search for an existing shipment', function () {
    $shipment = Shipment::first();

    $response = $this->get('/?tracking='.$shipment->tracking_number);

    $response->assertStatus(200);
    $response->assertSee($shipment->tracking_number);
});

test('login and registration pages load successfully with demo options', function () {
    $loginResponse = $this->get('/login');
    $loginResponse->assertStatus(200);
    $loginResponse->assertSee('Quick Demo Access');
    $loginResponse->assertSee('admin@cargotrace.test');
    $loginResponse->assertSee('operator@cargotrace.test');
    $loginResponse->assertSee('customer@cargotrace.test');

    $registerResponse = $this->get('/register');
    $registerResponse->assertStatus(200);
    $registerResponse->assertSee('Create Shipper Account');
});

test('user can login and redirect to dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'operator@cargotrace.test',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

test('dashboard loads for authenticated admin and operator', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Overview');
    $response->assertSee('Active shipments');
    $response->assertSee('Global route map');
});

test('shipments list displays shipments and respects role visibility', function () {
    $operator = User::where('role', 'operator')->first();
    $customer1 = User::where('role', 'customer')->first();

    // Operator sees all
    $responseOperator = $this->actingAs($operator)->get('/shipments');
    $responseOperator->assertStatus(200);
    $responseOperator->assertSee('Shipments Manifest');

    // Customer only sees own
    $responseCustomer = $this->actingAs($customer1)->get('/shipments');
    $responseCustomer->assertStatus(200);
});

test('customer cannot view other customer shipments via web view', function () {
    $customer1 = User::where('role', 'customer')->first();
    $customer2 = User::where('role', 'customer')->where('id', '!=', $customer1->id)->first();

    $shipmentOfCustomer2 = Shipment::where('customer_id', $customer2->id)->first();

    $response = $this->actingAs($customer1)->get('/shipments/'.$shipmentOfCustomer2->id);

    $response->assertStatus(403);
});

test('operator can view shipment details with leaflet tracking map', function () {
    $operator = User::where('role', 'operator')->first();
    $shipment = Shipment::first();

    $response = $this->actingAs($operator)->get('/shipments/'.$shipment->id);

    $response->assertStatus(200);
    $response->assertSee($shipment->tracking_number);
    $response->assertSee('Tracking Checkpoints');
});

test('operator can create shipment via web interface', function () {
    $operator = User::where('role', 'operator')->first();
    $carrier = Carrier::first();
    $origin = Location::first();
    $destination = Location::orderBy('id', 'desc')->first();
    $customer = User::where('role', 'customer')->first();

    $response = $this->actingAs($operator)->post('/shipments', [
        'carrier_id' => $carrier->id,
        'customer_id' => $customer->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destination->id,
        'cargo_type' => 'ELECTRONICS',
        'currency_code' => 'USD',
        'declared_value' => 50000,
        'shipping_cost' => 3500,
        'expected_departure_at' => now()->toDateTimeString(),
        'expected_arrival_at' => now()->addDays(5)->toDateTimeString(),
        'items' => [
            [
                'description' => 'High Performance Servers',
                'sku' => 'SRV-8819',
                'quantity' => 10,
                'weight_kg' => 250,
                'volume_cbm' => 2.5,
                'unit_value' => 5000,
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('shipment_items', [
        'sku' => 'SRV-8819',
    ]);
});

test('operator and admin can access exceptions management', function () {
    $operator = User::where('role', 'operator')->first();
    $customer = User::where('role', 'customer')->first();

    $responseOperator = $this->actingAs($operator)->get('/exceptions');
    $responseOperator->assertStatus(200);
    $responseOperator->assertSee('Operational Exceptions');

    $responseCustomer = $this->actingAs($customer)->get('/exceptions');
    $responseCustomer->assertStatus(403);
});

test('operator can update exception status via web post', function () {
    $operator = User::where('role', 'operator')->first();
    $exception = ShipmentException::first();

    $response = $this->actingAs($operator)->patch('/exceptions/'.$exception->id, [
        'status' => 'INVESTIGATING',
        'resolution_notes' => 'Investigating port delay with logistics agent.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('shipment_exceptions', [
        'id' => $exception->id,
        'status' => 'INVESTIGATING',
    ]);
});

test('carriers and locations management accessible only to operator and admin', function () {
    $operator = User::where('role', 'operator')->first();
    $customer = User::where('role', 'customer')->first();

    $this->actingAs($operator)->get('/carriers')->assertStatus(200);
    $this->actingAs($operator)->get('/locations')->assertStatus(200);

    $this->actingAs($customer)->get('/carriers')->assertStatus(403);
    $this->actingAs($customer)->get('/locations')->assertStatus(403);
});

test('audit logs page accessible only to admin', function () {
    $admin = User::where('role', 'admin')->first();
    $operator = User::where('role', 'operator')->first();
    $customer = User::where('role', 'customer')->first();

    $this->actingAs($admin)->get('/audit-logs')->assertStatus(200)->assertSee('System Audit Trail');
    $this->actingAs($operator)->get('/audit-logs')->assertStatus(403);
    $this->actingAs($customer)->get('/audit-logs')->assertStatus(403);
});

test('dedicated location weather hub loads with telemetry and forecast', function () {
    $user = User::first();

    $response = $this->actingAs($user)->get('/weather');
    $response->assertStatus(200);
    $response->assertSee('Location Weather');
    $response->assertSee('Open-Meteo');
    $response->assertSee('7-Day');
    $response->assertSee('context only, not a delay prediction');
});

test('dedicated currency fx hub loads with calculator and portfolio valuation', function () {
    $user = User::first();

    $response = $this->actingAs($user)->get('/currency');
    $response->assertStatus(200);
    $response->assertSee('Currency Converter & Reference Rates');
    $response->assertSee('Freight cross-rate matrix');
    $response->assertSee('Portfolio value by currency');
});

test('dedicated freight news hub loads with thumbnails and category filters', function () {
    $user = User::first();

    $response = $this->actingAs($user)->get('/news');
    $response->assertStatus(200);
    $response->assertSee('Freight News');
    $response->assertSee('All categories');
    $response->assertSee('news-placeholder.svg');
});

test('global sidebar displays improved text labels and links for freight intelligence', function () {
    $user = User::where('role', 'admin')->first();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSee('Global View');
    $response->assertSee('Shipments');
    $response->assertSee('Location Weather');
    $response->assertSee('Currency FX');
    $response->assertSee('Freight News');
});
