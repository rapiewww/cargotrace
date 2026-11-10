<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('user can view notifications list', function () {
    $user = User::first();

    Notification::create([
        'user_id' => $user->id,
        'title' => 'Test Notification Alert',
        'message' => 'This is a test notification payload',
        'type' => 'SHIPMENT_DELAY_ALERT',
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/notifications');

    $response->assertStatus(200);
    $response->assertSee('Test Notification Alert');
});

test('user can mark notification as read', function () {
    $user = User::first();

    $notification = Notification::create([
        'user_id' => $user->id,
        'title' => 'Unread Alert',
        'message' => 'Unread message',
        'type' => 'SHIPMENT_DELAY_ALERT',
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)->post('/notifications/' . $notification->id . '/read');

    $response->assertSessionHas('success');
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user can mark all notifications as read', function () {
    $user = User::first();

    Notification::create([
        'user_id' => $user->id,
        'title' => 'Alert 1',
        'message' => 'Message 1',
        'type' => 'SHIPMENT_DELAY_ALERT',
        'read_at' => null,
    ]);

    Notification::create([
        'user_id' => $user->id,
        'title' => 'Alert 2',
        'message' => 'Message 2',
        'type' => 'SHIPMENT_DELAY_ALERT',
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)->post('/notifications/read-all');

    $response->assertSessionHas('success');
    $unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    expect($unreadCount)->toBe(0);
});
