<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('login and register pages reuse multimodal hero assets, alignment classes, and left-aligned cards', function () {
    foreach (['/login' => 'login-theme-toggle', '/register' => 'register-theme-toggle'] as $uri => $toggleId) {
        $response = $this->get($uri);

        $response->assertStatus(200)
            ->assertSee('images/backgrounds/hero-multimodal-dark.jpg', false)
            ->assertSee('images/backgrounds/hero-multimodal-light.jpg', false)
            ->assertSee('hero-artwork-frame', false)
            ->assertSee('hero-artwork-stage', false)
            ->assertSee('hero-light-aligned', false)
            ->assertSee('lg:object-contain lg:object-right', false)
            ->assertSee('transition-opacity duration-300 ease-in-out', false)
            ->assertSee('opacity-0 dark:opacity-100', false)
            ->assertSee('opacity-100 dark:opacity-0', false)
            ->assertSee('bg-[#EEF5FB] dark:bg-[#060D1A]', false)
            ->assertSee('mx-auto lg:mx-0', false)
            ->assertSee('text-center lg:text-left', false)
            ->assertSee('Nusa Freight &bull; Global spice freight by sea, air and road', false)
            ->assertSee('id="'.$toggleId.'"', false)
            ->assertDontSee('hero-img-dark', false)
            ->assertDontSee('hero-img-light', false)
            ->assertDontSee('animate-pulse', false)
            ->assertDontSee('—');
    }
});

test('login and register pages respect configured hero image overrides', function () {
    config([
        'cargotrace.hero_images.dark' => 'images/backgrounds/bg-dark.webp',
        'cargotrace.hero_images.light' => 'images/backgrounds/bg-light.webp',
    ]);

    foreach (['/login', '/register'] as $uri) {
        $this->get($uri)
            ->assertStatus(200)
            ->assertSee('images/backgrounds/bg-dark.webp', false)
            ->assertSee('images/backgrounds/bg-light.webp', false);
    }
});

test('login and register pages render cleanly in Indonesian locale with multimodal hero markup', function () {
    $this->from('/login')->get(route('locale.switch', 'id'))->assertRedirect('/login');

    $this->get('/login')
        ->assertStatus(200)
        ->assertSee('<html lang="id"', false)
        ->assertSee('Masuk Operasional')
        ->assertSee('Akses Demo Cepat (Masuk 1-Klik)')
        ->assertSee('images/backgrounds/hero-multimodal-dark.jpg', false)
        ->assertSee('images/backgrounds/hero-multimodal-light.jpg', false)
        ->assertDontSee('—');

    $this->get('/register')
        ->assertStatus(200)
        ->assertSee('<html lang="id"', false)
        ->assertSee('Buat Akun Pengirim')
        ->assertSee('Selesaikan Pendaftaran')
        ->assertSee('images/backgrounds/hero-multimodal-dark.jpg', false)
        ->assertSee('images/backgrounds/hero-multimodal-light.jpg', false)
        ->assertDontSee('—');
});
