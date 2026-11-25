<?php

namespace Database\Seeders;

use App\Services\CurrencyService;
use App\Services\MaritimeNewsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with synthetic multimodal spice freight data
     * for Nusa Spice Freight across SEA, AIR, and ROAD transport modes.
     */
    public function run(): void
    {
        mt_srand(20261125);
        fake()->seed(20261125);

        $now = Carbon::now();

        // Prime offline stale caches (no network calls during seeding)
        Cache::forever(CurrencyService::STALE_CACHE_KEY, [
            'base' => 'USD',
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'INR' => 84.0,
                'IDR' => 15873.0,
                'SGD' => 1.34,
                'AED' => 3.67,
                'MYR' => 4.45,
                'GBP' => 0.79,
                'JPY' => 154.5,
                'AUD' => 1.52,
                'CHF' => 0.88,
                'CAD' => 1.39,
                'CNY' => 7.23,
            ],
            'time_last_update_utc' => $now->copy()->subHours(6)->toRfc7231String(),
            'time_next_update_utc' => $now->copy()->addHours(18)->toRfc7231String(),
            'last_updated' => $now->copy()->subHours(6)->toRfc7231String(),
            'source' => 'ExchangeRate-API (open.er-api.com)',
            'provider_url' => 'https://www.exchangerate-api.com',
            'attribution_text' => 'Rates By ExchangeRate-API',
            'data_source' => 'cached',
        ]);

        Cache::forever(MaritimeNewsService::STALE_CACHE_KEY_PREFIX.md5('gCaptain'), [
            [
                'headline' => 'Malacca Strait Feeder Schedule Tightens Ahead of Peak Spice Export Season',
                'title' => 'Malacca Strait Feeder Schedule Tightens Ahead of Peak Spice Export Season',
                'source' => 'gCaptain',
                'url' => 'https://gcaptain.com/malacca-strait-feeder-schedule-update',
                'thumbnail' => '/images/news-placeholder.svg',
                'category' => 'Waterways',
                'summary' => 'Regional container feeders connecting Belawan and Singapore adjust berth windows to reduce transshipment dwell.',
                'published_at' => $now->copy()->subHours(4)->toIso8601String(),
                'data_source' => 'cached',
            ],
        ]);

        Cache::forever(MaritimeNewsService::STALE_CACHE_KEY_PREFIX.md5('Maritime Executive'), [
            [
                'headline' => 'Rotterdam Maasvlakte Expands Phytosanitary Inspection Bays for Agricultural Imports',
                'title' => 'Rotterdam Maasvlakte Expands Phytosanitary Inspection Bays for Agricultural Imports',
                'source' => 'Maritime Executive',
                'url' => 'https://maritime-executive.com/rotterdam-phytosanitary-inspection-bays',
                'thumbnail' => '/images/news-placeholder.svg',
                'category' => 'Port Operations',
                'summary' => 'New temperature-controlled inspection docks speed up EU customs release for botanicals and whole spices.',
                'published_at' => $now->copy()->subHours(8)->toIso8601String(),
                'data_source' => 'cached',
            ],
        ]);

        // Call all eight modular seeders in strict dependency order
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            CarrierSeeder::class,
            ShipmentSeeder::class,
            TrackingEventSeeder::class,
            ExceptionSeeder::class,
            NotificationSeeder::class,
            AuditSeeder::class,
        ]);
    }
}
