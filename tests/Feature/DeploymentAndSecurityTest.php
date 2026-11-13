<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

test('security headers are present on application responses', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('sensitive files and skill paths return 404', function (string $sensitivePath) {
    $response = $this->get($sensitivePath);

    $response->assertNotFound();
})->with([
    '/.env',
    '/.env.production',
    '/.env.local',
    '/.git/config',
    '/.claude/skills/anti-slop/antislop.md',
    '/.gemini/antigravity',
]);

test('hsts header is sent over secure connections', function () {
    $response = $this->get('https://localhost/');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('application healthcheck endpoint up returns 200', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});

test('osm map tile url is configurable via config and rendered in layout meta', function () {
    Config::set('cargotrace.map.tile_url', 'https://custom-tiles.example.com/{z}/{x}/{y}.png');

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('https://custom-tiles.example.com/{z}/{x}/{y}.png', false);
});

test('login requests are rate limited', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/login', [
            'email' => 'throttletest@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'throttletest@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
});
