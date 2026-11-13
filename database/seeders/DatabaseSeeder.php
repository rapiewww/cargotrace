<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\MaritimeNewsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with pure maritime container shipping data.
     * Orchestrates modular seeders and primes external service caches.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 0. PRIME EXTERNAL SERVICE CACHES (Currency & Maritime News)
        // ---------------------------------------------------------------------
        Cache::forever(CurrencyService::STALE_CACHE_KEY, [
            'base' => 'USD',
            'rates' => [
                'USD' => 1.0, 'EUR' => 0.92, 'CNY' => 7.23, 'IDR' => 15873.0, 'SGD' => 1.34, 'AUD' => 1.52, 'GBP' => 0.79, 'JPY' => 154.5, 'AED' => 3.67, 'CHF' => 0.88, 'CAD' => 1.39,
            ],
            'time_last_update_utc' => 'Wed, 11 Nov 2026 00:00:00 +0000',
            'time_next_update_utc' => 'Thu, 12 Nov 2026 00:00:00 +0000',
            'last_updated' => 'Wed, 11 Nov 2026 00:00:00 +0000',
            'source' => 'ExchangeRate-API (open.er-api.com)',
            'provider_url' => 'https://www.exchangerate-api.com',
            'attribution_text' => 'Rates By ExchangeRate-API',
            'data_source' => 'cached',
        ]);

        Cache::forever(MaritimeNewsService::STALE_CACHE_KEY_PREFIX.md5('gCaptain'), [
            [
                'headline' => 'Panama Canal Increases Daily Transit Booking Slots as Water Levels Recover',
                'title' => 'Panama Canal Increases Daily Transit Booking Slots as Water Levels Recover',
                'source' => 'gCaptain',
                'url' => 'https://gcaptain.com/panama-canal-transit-slots-recovery',
                'thumbnail' => '/images/news-placeholder.svg',
                'category' => 'Waterways',
                'summary' => 'Panama Canal Authority increases daily vessel reservation slots to 36 following sustained precipitation in Gatun Lake basin.',
                'published_at' => Carbon::now()->subHours(4)->toIso8601String(),
                'data_source' => 'cached',
            ],
        ]);

        Cache::forever(MaritimeNewsService::STALE_CACHE_KEY_PREFIX.md5('Maritime Executive'), [
            [
                'headline' => 'Singapore Port Automated Terminal Expands Quay Crane Berths',
                'title' => 'Singapore Port Automated Terminal Expands Quay Crane Berths',
                'source' => 'Maritime Executive',
                'url' => 'https://maritime-executive.com/singapore-automated-berth-expansion',
                'thumbnail' => '/images/hero-port.webp',
                'category' => 'Port Operations',
                'summary' => 'PSA Singapore accelerates deepwater berth crane automation to improve container throughput and turnaround time.',
                'published_at' => Carbon::now()->subHours(8)->toIso8601String(),
                'data_source' => 'cached',
            ],
        ]);

        // ---------------------------------------------------------------------
        // 1-7. CALL MODULAR SEEDERS IN DEPENDENCY ORDER
        // ---------------------------------------------------------------------
        $this->call([
            UserSeeder::class,
            CarrierSeeder::class,
            LocationSeeder::class,
            ShipmentSeeder::class,
            TrackingEventSeeder::class,
            ExceptionSeeder::class,
            NotificationSeeder::class,
        ]);

        // ---------------------------------------------------------------------
        // 8. AUDIT LOGS FOR DEMO ACTIVITIES
        // ---------------------------------------------------------------------
        $admin = User::where('email', 'admin@cargotrace.test')->first();
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $customer1 = User::where('email', 'customer@cargotrace.test')->first();
        $shipment1 = Shipment::where('tracking_number', 'CTG-SEA-2026-00189')->first();
        $shipment2 = Shipment::where('tracking_number', 'CTG-SEA-2026-00244')->first();
        $shanghai = Location::where('unlocode', 'CNSHA')->first();

        $auditLogs = [
            [
                'user_id' => $admin?->id,
                'action' => 'SHIPMENT_CREATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment1?->id,
                'old_values' => null,
                'new_values' => [
                    'tracking_number' => $shipment1?->tracking_number,
                    'customer' => $customer1?->name,
                    'origin' => 'IDJKT',
                    'destination' => 'NLRTM',
                    'declared_value' => $shipment1?->declared_value,
                    'currency_code' => $shipment1?->currency_code,
                ],
                'ip_address' => '10.0.0.1',
                'created_at' => Carbon::now()->subDays(36),
            ],
            [
                'user_id' => $operator?->id,
                'action' => 'SHIPMENT_STATUS_UPDATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment1?->id,
                'old_values' => ['status' => 'IN_TRANSIT'],
                'new_values' => ['status' => 'DELIVERED', 'actual_arrival_at' => $shipment1?->actual_arrival_at],
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::now()->subDays(4),
            ],
            [
                'user_id' => $operator?->id,
                'action' => 'EXCEPTION_RAISED',
                'auditable_type' => ShipmentException::class,
                'auditable_id' => 1,
                'old_values' => null,
                'new_values' => [
                    'shipment_tracking' => $shipment2?->tracking_number,
                    'exception_code' => 'CUSTOMS_HOLD',
                    'severity' => 'HIGH',
                    'status' => 'INVESTIGATING',
                ],
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::now()->subHours(12),
            ],
            [
                'user_id' => null, // Automated External Integration
                'action' => 'WEATHER_SNAPSHOT_SYNCED',
                'auditable_type' => Location::class,
                'auditable_id' => $shanghai?->id,
                'old_values' => ['condition' => 'Clear'],
                'new_values' => ['condition' => 'Overcast & Dense Sea Fog', 'visibility_km' => 4.2],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'user_id' => null, // Automated External Integration
                'action' => 'CURRENCY_RATES_SYNCED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment2?->id,
                'old_values' => ['exchange_rate_to_usd' => 0.1378],
                'new_values' => ['exchange_rate_to_usd' => 0.138312, 'converted_value_usd' => 255877.20],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subDays(16),
            ],
        ];

        foreach ($auditLogs as $log) {
            AuditLog::create($log);
        }
    }
}
