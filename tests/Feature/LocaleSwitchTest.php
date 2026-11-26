<?php

use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        '*' => Http::response([], 200),
    ]);
    $this->seed(DatabaseSeeder::class);
});

test('default locale is English and landing page renders English navigation and hero text', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    expect(App::getLocale())->toBe('en');
    $response->assertSee('<html lang="en"', false);
    $response->assertSee('Global spice freight by sea, air and road');
    $response->assertSee('Track Shipment');
    $response->assertSee('Sign In / Portal');
    $response->assertSee('href="'.route('locale.switch', 'id').'"', false);
});

test('switching to Indonesian persists in session, redirects back, and renders Indonesian UI copy', function () {
    $switchResponse = $this->from('/login')->get(route('locale.switch', 'id'));

    $switchResponse->assertRedirect('/login');
    $switchResponse->assertSessionHas('locale', 'id');

    $landingResponse = $this->get('/');
    $landingResponse->assertStatus(200);
    expect(App::getLocale())->toBe('id');
    $landingResponse->assertSee('<html lang="id"', false);
    $landingResponse->assertSee('Kargo rempah global melalui laut, udara, dan darat');
    $landingResponse->assertSee('Lacak Pengiriman');
    $landingResponse->assertSee('Masuk / Portal');
    $landingResponse->assertSee('Kargo Laut');
    $landingResponse->assertSee('Kargo Udara');
    $landingResponse->assertSee('Kargo Darat');
});

test('Indonesian locale renders localized navigation, dashboard, shipments manifest, and public tracking', function () {
    $this->get(route('locale.switch', 'id'))
        ->assertRedirect('/');

    $shipment = Shipment::firstOrFail();

    $trackingResponse = $this->get(route('public.track', $shipment->tracking_number));
    $trackingResponse->assertStatus(200);
    $trackingResponse->assertSee('<html lang="id"', false);
    $trackingResponse->assertSee('Tonggak Rute Terverifikasi');
    $trackingResponse->assertSee('Visibilitas Pengiriman Publik');
    $trackingResponse->assertSee('Progres Transit');

    $admin = User::where('role', 'admin')->firstOrFail();

    $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));
    $dashboardResponse->assertStatus(200);
    $dashboardResponse->assertSee('Ringkasan');
    $dashboardResponse->assertSee('Tampilan Global');
    $dashboardResponse->assertSee('Pengiriman');
    $dashboardResponse->assertSee('Pengecualian');
    $dashboardResponse->assertSee('Keluar');

    $shipmentsResponse = $this->actingAs($admin)->get(route('shipments.index'));
    $shipmentsResponse->assertStatus(200);
    $shipmentsResponse->assertSee('Manifes Pengiriman');
    $shipmentsResponse->assertSee('Semua moda');
});

test('switching back to English restores default English UI copy', function () {
    $this->get(route('locale.switch', 'id'))->assertSessionHas('locale', 'id');

    $this->from('/')->get(route('locale.switch', 'en'))
        ->assertRedirect('/')
        ->assertSessionHas('locale', 'en');

    $response = $this->get('/');
    $response->assertStatus(200);
    expect(App::getLocale())->toBe('en');
    $response->assertSee('<html lang="en"', false);
    $response->assertSee('Global spice freight by sea, air and road');
    $response->assertSee('Sign In / Portal');

    $admin = User::where('role', 'admin')->firstOrFail();
    $this->actingAs($admin)->get(route('dashboard'))
        ->assertStatus(200)
        ->assertSee('Overview')
        ->assertSee('Global View')
        ->assertSee('Shipments');

    $this->actingAs($admin)->get(route('shipments.index'))
        ->assertStatus(200)
        ->assertSee('Shipments Manifest');
});

test('invalid locale codes are rejected with 404 and invalid session values fall back safely to English', function () {
    $this->get(route('locale.switch', 'id'))->assertSessionHas('locale', 'id');

    $invalidSwitch = $this->get('/locale/fr');
    $invalidSwitch->assertStatus(404);
    expect(session('locale'))->toBe('id');

    $fallbackResponse = $this->withSession(['locale' => 'es'])->get('/');
    $fallbackResponse->assertStatus(200);
    expect(App::getLocale())->toBe('en');
    $fallbackResponse->assertSee('<html lang="en"', false);
    $fallbackResponse->assertSee('Global spice freight by sea, air and road');
});

test('en and id translation dictionaries have identical keys, matching placeholders, and full coverage', function () {
    $enPath = base_path('lang/en.json');
    $idPath = base_path('lang/id.json');

    expect(file_exists($enPath))->toBeTrue();
    expect(file_exists($idPath))->toBeTrue();

    $en = json_decode(file_get_contents($enPath), true);
    $id = json_decode(file_get_contents($idPath), true);

    expect($en)->toBeArray()->not->toBeEmpty();
    expect($id)->toBeArray()->not->toBeEmpty();
    expect(array_keys($id))->toBe(array_keys($en));

    foreach ($en as $key => $enValue) {
        $idValue = $id[$key] ?? '';
        expect($idValue)->toBeString()->not->toBe('');
        expect($enValue)->not->toContain('—');
        expect($idValue)->not->toContain('—');

        preg_match_all('/:[a-zA-Z_]+/', $key, $expectedPlaceholders);
        preg_match_all('/:[a-zA-Z_]+/', $idValue, $actualPlaceholders);
        $expected = $expectedPlaceholders[0];
        $actual = $actualPlaceholders[0];
        sort($expected);
        sort($actual);
        expect($actual)->toBe($expected);
    }

    $usedKeys = [];
    foreach ([resource_path('views'), app_path()] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (preg_match_all("/__\((['\"])((?:\\\\.|(?!\\1).)*)\\1/s", $content, $matches)) {
                foreach ($matches[2] as $rawKey) {
                    $usedKeys[stripcslashes($rawKey)] = true;
                }
            }
        }
    }

    $missingInEn = array_diff(array_keys($usedKeys), array_keys($en));
    $missingInId = array_diff(array_keys($usedKeys), array_keys($id));

    expect($missingInEn)->toBeEmpty();
    expect($missingInId)->toBeEmpty();
});
