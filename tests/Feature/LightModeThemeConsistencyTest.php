<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Http::fake([
        '*' => Http::response([], 200),
    ]);
});

test('admin settings and user management pages use adaptive theme classes without hardcoded dark-only surfaces', function () {
    $admin = User::where('role', 'admin')->first();
    $targetUser = User::where('role', 'customer')->first();

    $routes = [
        '/admin/settings',
        '/admin/users',
        '/admin/users/create',
        '/admin/users/'.$targetUser->id.'/edit',
    ];

    foreach ($routes as $uri) {
        $response = $this->actingAs($admin)->get($uri);
        $response->assertStatus(200);

        $html = $response->getContent();
        expect($html)
            ->toContain('theme-heading')
            ->toContain('theme-input')
            ->toContain('theme-card-elevated')
            ->not->toContain(' bg-slate-950 ')
            ->not->toContain(' bg-slate-950"')
            ->not->toContain(' bg-slate-950/40')
            ->not->toContain(' bg-slate-950/60 border')
            ->not->toContain(' bg-slate-950/60 flex')
            ->not->toContain(' bg-slate-950/70 border')
            ->not->toContain(' bg-slate-950/70 text-')
            ->not->toContain(' bg-slate-950/80')
            ->not->toContain(' border-slate-800')
            ->not->toContain(' divide-white/5')
            ->not->toContain(' text-slate-200')
            ->not->toContain(' tracking-tight text-white');
    }
});

test('all secondary and internal pages render cleanly without dark-only contrast regressions', function () {
    $admin = User::where('role', 'admin')->first();
    $shipment = Shipment::first();

    $routes = [
        '/audit-logs',
        '/carriers',
        '/locations',
        '/exceptions',
        '/notifications',
        '/weather',
        '/currency',
        '/news',
        '/analytics',
        '/shipments/create',
        '/shipments/'.$shipment->id,
        '/shipments/'.$shipment->id.'/edit',
        '/shipments/import',
        '/track/'.$shipment->tracking_number,
    ];

    foreach ($routes as $uri) {
        $response = $this->actingAs($admin)->get($uri);
        $response->assertStatus(200);

        $html = $response->getContent();
        expect($html)
            ->toContain('theme-heading')
            ->not->toContain(' bg-slate-950 ')
            ->not->toContain(' bg-slate-950"')
            ->not->toContain(' bg-slate-950/40')
            ->not->toContain(' bg-slate-950/60 border')
            ->not->toContain(' bg-slate-950/60 flex')
            ->not->toContain(' bg-slate-950/70 border')
            ->not->toContain(' bg-slate-950/70 text-')
            ->not->toContain(' bg-slate-950/80')
            ->not->toContain(' border-slate-800')
            ->not->toContain(' divide-white/5')
            ->not->toContain(' text-slate-200')
            ->not->toContain(' tracking-tight text-white');
    }
});
