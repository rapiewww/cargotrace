<?php

namespace Tests\Feature;

use App\Channels\InAppChannel;
use App\Models\AuditLog;
use App\Models\Notification as InAppNotification;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\ShipmentDelayNotification;
use App\Services\EtaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('cargotrace:evaluate-delays evaluates active shipments and dispatches notifications', function () {
    Notification::fake();
    Carbon::setTestNow('2026-11-13 12:00:00');

    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
    $operator = User::where('role', 'operator')->where('is_active', true)->first();

    $shipment = Shipment::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'IN_TRANSIT',
        'expected_arrival_at' => Carbon::parse('2026-11-13 14:00:00'),
        'estimated_arrival_at' => Carbon::parse('2026-11-13 22:00:00'), // 8 hours delay => LOW (<= 6 is NONE/LOW or 6-24 MEDIUM depending on thresholds)
        'is_delayed' => false,
        'delay_hours' => 0,
        'delay_severity' => 'NONE',
    ]);

    $this->artisan('cargotrace:evaluate-delays')
        ->assertSuccessful();

    $shipment->refresh();
    expect($shipment->is_delayed)->toBeTrue();
    expect((float) $shipment->delay_hours)->toBe(8.0);

    // Customer receives notification
    Notification::assertSentTo(
        $customer,
        ShipmentDelayNotification::class,
        function (ShipmentDelayNotification $notification) use ($shipment) {
            return $notification->shipment->id === $shipment->id;
        }
    );

    // Operator receives notification
    Notification::assertSentTo(
        $operator,
        ShipmentDelayNotification::class,
        function (ShipmentDelayNotification $notification) use ($shipment) {
            return $notification->shipment->id === $shipment->id;
        }
    );

    // In-app notifications stored in database
    expect(InAppNotification::where('user_id', $customer->id)->where('shipment_id', $shipment->id)->count())->toBe(1);

    // System actor audit log created
    $audit = AuditLog::where('auditable_type', Shipment::class)
        ->where('auditable_id', $shipment->id)
        ->where('action', 'SHIPMENT_DELAY_DETECTED')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->user_id)->toBeNull(); // "system" actor
});

test('delay evaluation is idempotent and does not create duplicate notifications or audit logs', function () {
    Notification::fake();
    Carbon::setTestNow('2026-11-13 12:00:00');

    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'IN_TRANSIT',
        'expected_arrival_at' => Carbon::parse('2026-11-13 14:00:00'),
        'estimated_arrival_at' => Carbon::parse('2026-11-13 22:00:00'),
        'is_delayed' => false,
        'delay_hours' => 0,
        'delay_severity' => 'NONE',
    ]);

    // First run
    $this->artisan('cargotrace:evaluate-delays')->assertSuccessful();

    $customerNotifCount1 = InAppNotification::where('user_id', $customer->id)->where('shipment_id', $shipment->id)->count();
    $auditCount1 = AuditLog::where('auditable_type', Shipment::class)->where('auditable_id', $shipment->id)->count();
    expect($customerNotifCount1)->toBe(1);
    expect($auditCount1)->toBe(1);

    // Second run with frozen clock
    $this->artisan('cargotrace:evaluate-delays')->assertSuccessful();

    $customerNotifCount2 = InAppNotification::where('user_id', $customer->id)->where('shipment_id', $shipment->id)->count();
    $auditCount2 = AuditLog::where('auditable_type', Shipment::class)->where('auditable_id', $shipment->id)->count();

    expect($customerNotifCount2)->toBe(1); // No duplicate
    expect($auditCount2)->toBe(1); // No duplicate audit entry
    Notification::assertSentToTimes($customer, ShipmentDelayNotification::class, 1);
});

test('severity escalation sends new notification and logs audit trail', function () {
    Notification::fake();
    Carbon::setTestNow('2026-11-13 12:00:00');

    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'IN_TRANSIT',
        'expected_arrival_at' => Carbon::parse('2026-11-13 14:00:00'),
        'estimated_arrival_at' => Carbon::parse('2026-11-13 18:00:00'), // 4 hours -> LOW
        'is_delayed' => false,
        'delay_hours' => 0,
        'delay_severity' => 'NONE',
    ]);

    $etaService = app(EtaService::class);
    $etaService->evaluateDelay($shipment);

    $shipment->refresh();
    expect($shipment->delay_severity)->toBe('LOW');
    expect(InAppNotification::where('user_id', $customer->id)->count())->toBe(1);

    // Now escalate delay to 30 hours -> HIGH (> 24h)
    $shipment->estimated_arrival_at = Carbon::parse('2026-11-14 20:00:00');
    $shipment->save();

    $etaService->evaluateDelay($shipment);
    $shipment->refresh();
    expect($shipment->delay_severity)->toBe('HIGH');

    // Customer receives escalated notification
    expect(InAppNotification::where('user_id', $customer->id)->count())->toBe(2);

    $auditEscalated = AuditLog::where('auditable_type', Shipment::class)
        ->where('auditable_id', $shipment->id)
        ->where('action', 'SHIPMENT_DELAY_ESCALATED')
        ->first();

    expect($auditEscalated)->not->toBeNull();
    expect($auditEscalated->user_id)->toBeNull();
    expect($auditEscalated->old_values['delay_severity'])->toBe('LOW');
    expect($auditEscalated->new_values['delay_severity'])->toBe('HIGH');
});

test('delivered shipments never generate delay notifications or logs', function () {
    Notification::fake();
    Carbon::setTestNow('2026-11-13 12:00:00');

    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'DELIVERED',
        'expected_arrival_at' => Carbon::parse('2026-11-10 12:00:00'),
        'estimated_arrival_at' => Carbon::parse('2026-11-11 12:00:00'),
        'is_delayed' => false,
        'delay_hours' => 0,
        'delay_severity' => 'NONE',
    ]);

    $etaService = app(EtaService::class);
    $etaService->evaluateDelay($shipment);

    Notification::assertNothingSent();
    expect(InAppNotification::where('shipment_id', $shipment->id)->count())->toBe(0);
    expect(AuditLog::where('auditable_id', $shipment->id)->where('action', 'SHIPMENT_DELAY_DETECTED')->count())->toBe(0);
});

test('customer only receives notifications for their own shipment', function () {
    Notification::fake();
    Carbon::setTestNow('2026-11-13 12:00:00');

    $customerA = User::factory()->create(['role' => 'customer', 'is_active' => true]);
    $customerB = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $shipmentA = Shipment::factory()->create([
        'customer_id' => $customerA->id,
        'status' => 'IN_TRANSIT',
        'expected_arrival_at' => Carbon::parse('2026-11-13 14:00:00'),
        'estimated_arrival_at' => Carbon::parse('2026-11-13 22:00:00'),
        'is_delayed' => false,
        'delay_hours' => 0,
        'delay_severity' => 'NONE',
    ]);

    $etaService = app(EtaService::class);
    $etaService->evaluateDelay($shipmentA);

    Notification::assertSentTo($customerA, ShipmentDelayNotification::class);
    Notification::assertNotSentTo($customerB, ShipmentDelayNotification::class);

    expect(InAppNotification::where('user_id', $customerA->id)->where('shipment_id', $shipmentA->id)->count())->toBe(1);
    expect(InAppNotification::where('user_id', $customerB->id)->where('shipment_id', $shipmentA->id)->count())->toBe(0);
});

test('notification email channel is optional and disabled by default', function () {
    $shipment = Shipment::factory()->make();
    $notification = new ShipmentDelayNotification($shipment, 'HIGH', 36.0, 'LOW');
    $user = User::factory()->make();

    // Default configuration (disabled)
    config(['cargotrace.notifications.email' => false]);
    expect($notification->via($user))->toBe([InAppChannel::class]);

    // Enabled configuration
    config(['cargotrace.notifications.email' => true]);
    expect($notification->via($user))->toContain('mail');
    expect($notification->via($user))->toContain(InAppChannel::class);
});
