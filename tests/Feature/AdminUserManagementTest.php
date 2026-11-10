<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('admin can view user management list', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertStatus(200);
    $response->assertSee('Role Management');
    $response->assertSee($admin->email);
});

test('operator and customer cannot access user management', function () {
    $operator = User::where('role', 'operator')->first();
    $customer = User::where('role', 'customer')->first();

    $this->actingAs($operator)->get('/admin/users')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/users')->assertStatus(403);
});

test('admin can create a new user with audit log', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New Regional Operator',
        'email' => 'regional.ops@cargotrace.test',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'role' => 'operator',
        'company_name' => 'CargoTrace Regional Hub',
        'phone' => '+62-21-5550123',
        'preferred_currency' => 'IDR',
        'is_active' => '1',
    ]);

    $response->assertRedirect('/admin/users');
    $this->assertDatabaseHas('users', [
        'email' => 'regional.ops@cargotrace.test',
        'role' => 'operator',
    ]);

    // Check audit log
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'USER_CREATED',
        'user_id' => $admin->id,
    ]);
});

test('admin cannot demote their own account role', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->put('/admin/users/'.$admin->id, [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'customer',
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'role' => 'admin',
    ]);
});

test('admin cannot deactivate their own account', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->put('/admin/users/'.$admin->id, [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'admin',
        'is_active' => '0',
    ]);

    $response->assertSessionHasErrors('is_active');
    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'is_active' => true,
    ]);
});
