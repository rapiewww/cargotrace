<?php

use App\Models\Shipment;
use App\Services\FreeTimeService;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('FreeTimeService evaluates day 0 boundary (day of arrival)', function () {
    $service = new FreeTimeService;
    $arrival = Carbon::parse('2026-11-01 08:00:00');

    $shipment = new Shipment([
        'status' => 'DELIVERED',
        'actual_arrival_at' => $arrival,
    ]);

    // Evaluated at exact moment of arrival
    $result = $service->calculate($shipment, $arrival, 5);

    expect($result['has_arrived'])->toBeTrue()
        ->and($result['days_elapsed'])->toBe(0)
        ->and($result['days_remaining'])->toBe(5)
        ->and($result['days_over'])->toBe(0)
        ->and($result['is_over_limit'])->toBeFalse()
        ->and($result['badge_label'])->toBe('Estimated 5 days free time remaining')
        ->and($result['badge_type'])->toBe('safe');

    // Evaluated 6 hours after arrival (still Day 0)
    $result6h = $service->calculate($shipment, $arrival->copy()->addHours(6), 5);
    expect($result6h['days_elapsed'])->toBe(0)
        ->and($result6h['days_remaining'])->toBe(5)
        ->and($result6h['is_over_limit'])->toBeFalse();
});

test('FreeTimeService evaluates intermediate active free time', function () {
    $service = new FreeTimeService;
    $arrival = Carbon::parse('2026-11-01 08:00:00');

    $shipment = new Shipment([
        'status' => 'DELIVERED',
        'actual_arrival_at' => $arrival,
    ]);

    // Evaluated at day 2 (48 hours later)
    $result = $service->calculate($shipment, $arrival->copy()->addDays(2), 5);

    expect($result['days_elapsed'])->toBe(2)
        ->and($result['days_remaining'])->toBe(3)
        ->and($result['days_over'])->toBe(0)
        ->and($result['is_over_limit'])->toBeFalse()
        ->and($result['badge_label'])->toBe('Estimated 3 days free time remaining');
});

test('FreeTimeService evaluates last free day boundary', function () {
    $service = new FreeTimeService;
    $arrival = Carbon::parse('2026-11-01 08:00:00');

    $shipment = new Shipment([
        'status' => 'DELIVERED',
        'actual_arrival_at' => $arrival,
    ]);

    // Evaluated at exactly 5 days (120 hours) - the 5th and final allowed free day
    $result = $service->calculate($shipment, $arrival->copy()->addDays(5), 5);

    expect($result['has_arrived'])->toBeTrue()
        ->and($result['days_elapsed'])->toBe(5)
        ->and($result['days_remaining'])->toBe(0)
        ->and($result['days_over'])->toBe(0)
        ->and($result['is_over_limit'])->toBeFalse()
        ->and($result['badge_label'])->toBe('Estimated 0 days remaining (last free day)')
        ->and($result['badge_type'])->toBe('warning');
});

test('FreeTimeService evaluates first day over limit boundary', function () {
    $service = new FreeTimeService;
    $arrival = Carbon::parse('2026-11-01 08:00:00');

    $shipment = new Shipment([
        'status' => 'DELIVERED',
        'actual_arrival_at' => $arrival,
    ]);

    // Evaluated at day 6 (144 hours) - first full day over limit
    $result = $service->calculate($shipment, $arrival->copy()->addDays(6), 5);

    expect($result['has_arrived'])->toBeTrue()
        ->and($result['days_elapsed'])->toBe(6)
        ->and($result['days_remaining'])->toBe(0)
        ->and($result['days_over'])->toBe(1)
        ->and($result['is_over_limit'])->toBeTrue()
        ->and($result['badge_label'])->toBe('Estimated 1 day over free time limit')
        ->and($result['badge_type'])->toBe('danger');
});

test('FreeTimeService evaluates multiple days over limit', function () {
    $service = new FreeTimeService;
    $arrival = Carbon::parse('2026-11-01 08:00:00');

    $shipment = new Shipment([
        'status' => 'DELIVERED',
        'actual_arrival_at' => $arrival,
    ]);

    // Evaluated at day 9 (216 hours) - 4 days over 5-day limit
    $result = $service->calculate($shipment, $arrival->copy()->addDays(9), 5);

    expect($result['days_elapsed'])->toBe(9)
        ->and($result['days_over'])->toBe(4)
        ->and($result['is_over_limit'])->toBeTrue()
        ->and($result['badge_label'])->toBe('Estimated 4 days over free time limit');
});

test('FreeTimeService handles pending arrival when shipment is not yet arrived', function () {
    $service = new FreeTimeService;

    $shipment = new Shipment([
        'status' => 'IN_TRANSIT',
        'actual_arrival_at' => null,
    ]);

    $result = $service->calculate($shipment, Carbon::now(), 5);

    expect($result['has_arrived'])->toBeFalse()
        ->and($result['is_over_limit'])->toBeFalse()
        ->and($result['badge_label'])->toBe('Pending arrival');
});
