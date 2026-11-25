<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    /**
     * Seed audit logs consistent with seeded shipments, exceptions, and offline syncs.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@cargotrace.test')->first();
        $operator = User::where('email', 'operator@cargotrace.test')->first();
        $now = Carbon::now();

        $s189 = Shipment::where('tracking_number', 'CTG-SEA-2026-00189')->first();
        $s244 = Shipment::where('tracking_number', 'CTG-SEA-2026-00244')->first();
        $s678 = Shipment::where('tracking_number', 'CTG-AIR-2026-00678')->first();
        $s408 = Shipment::where('tracking_number', 'CTG-ROAD-2026-00408')->first();
        $belawan = Location::where('unlocode', 'IDBLW')->first();

        $exCustoms244 = $s244 ? ShipmentException::where('shipment_id', $s244->id)->first() : null;
        $exWeather189 = $s189 ? ShipmentException::where('shipment_id', $s189->id)->first() : null;
        $exWeather678 = $s678 ? ShipmentException::where('shipment_id', $s678->id)->first() : null;
        $exBorder408 = $s408 ? ShipmentException::where('shipment_id', $s408->id)->first() : null;

        $logs = [];

        if ($s189) {
            $logs[] = [
                'user_id' => $admin?->id,
                'action' => 'SHIPMENT_CREATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $s189->id,
                'old_values' => null,
                'new_values' => [
                    'tracking_number' => $s189->tracking_number,
                    'transport_mode' => 'SEA',
                    'origin' => 'IDJKT',
                    'destination' => 'NLRTM',
                    'declared_value' => (float) $s189->declared_value,
                    'currency_code' => $s189->currency_code,
                ],
                'ip_address' => '10.0.0.10',
                'created_at' => $s189->created_at ?? $now->copy()->subDays(36),
            ];
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'SHIPMENT_STATUS_UPDATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $s189->id,
                'old_values' => ['status' => 'IN_TRANSIT'],
                'new_values' => [
                    'status' => 'DELIVERED',
                    'actual_arrival_at' => $s189->actual_arrival_at?->toIso8601String(),
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $s189->actual_arrival_at ?? $now->copy()->subDays(4),
            ];
        }

        if ($s244) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'SHIPMENT_CREATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $s244->id,
                'old_values' => null,
                'new_values' => [
                    'tracking_number' => $s244->tracking_number,
                    'transport_mode' => 'SEA',
                    'origin' => 'IDBLW',
                    'destination' => 'USNYC',
                    'declared_value' => (float) $s244->declared_value,
                    'currency_code' => $s244->currency_code,
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $s244->created_at ?? $now->copy()->subDays(28),
            ];
            $logs[] = [
                'user_id' => null,
                'action' => 'CURRENCY_RATES_SYNCED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $s244->id,
                'old_values' => ['exchange_rate_to_usd' => 1.0],
                'new_values' => [
                    'exchange_rate_to_usd' => 1.0,
                    'converted_value_usd' => (float) $s244->converted_value_usd,
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subDays(1),
            ];
        }

        if ($s678) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'SHIPMENT_CREATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $s678->id,
                'old_values' => null,
                'new_values' => [
                    'tracking_number' => $s678->tracking_number,
                    'transport_mode' => 'AIR',
                    'origin' => 'IDKNO',
                    'destination' => 'FRCDG',
                    'awb_number' => $s678->awb_number,
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $s678->created_at ?? $now->copy()->subDays(2),
            ];
        }

        if ($s408) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'SHIPMENT_CREATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $s408->id,
                'old_values' => null,
                'new_values' => [
                    'tracking_number' => $s408->tracking_number,
                    'transport_mode' => 'ROAD',
                    'origin' => 'NLRDD',
                    'destination' => 'DEHAM',
                    'cmr_number' => $s408->cmr_number,
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $s408->created_at ?? $now->copy()->subDays(4),
            ];
        }

        if ($exCustoms244) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'EXCEPTION_REPORTED',
                'auditable_type' => ShipmentException::class,
                'auditable_id' => $exCustoms244->id,
                'old_values' => null,
                'new_values' => [
                    'shipment_tracking' => 'CTG-SEA-2026-00244',
                    'exception_code' => $exCustoms244->exception_code,
                    'severity' => $exCustoms244->severity,
                    'status' => $exCustoms244->status,
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $exCustoms244->detected_at,
            ];
        }

        if ($exWeather189) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'EXCEPTION_STATUS_UPDATED',
                'auditable_type' => ShipmentException::class,
                'auditable_id' => $exWeather189->id,
                'old_values' => ['status' => 'OPEN'],
                'new_values' => [
                    'status' => 'RESOLVED',
                    'resolved_at' => $exWeather189->resolved_at?->toIso8601String(),
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $exWeather189->resolved_at ?? $now->copy()->subDays(5),
            ];
        }

        if ($exWeather678) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'EXCEPTION_REPORTED',
                'auditable_type' => ShipmentException::class,
                'auditable_id' => $exWeather678->id,
                'old_values' => null,
                'new_values' => [
                    'shipment_tracking' => 'CTG-AIR-2026-00678',
                    'exception_code' => $exWeather678->exception_code,
                    'severity' => $exWeather678->severity,
                    'status' => $exWeather678->status,
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $exWeather678->detected_at,
            ];
        }

        if ($exBorder408) {
            $logs[] = [
                'user_id' => $operator?->id,
                'action' => 'EXCEPTION_REPORTED',
                'auditable_type' => ShipmentException::class,
                'auditable_id' => $exBorder408->id,
                'old_values' => null,
                'new_values' => [
                    'shipment_tracking' => 'CTG-ROAD-2026-00408',
                    'exception_code' => $exBorder408->exception_code,
                    'severity' => $exBorder408->severity,
                    'status' => $exBorder408->status,
                ],
                'ip_address' => '192.168.10.22',
                'created_at' => $exBorder408->detected_at,
            ];
        }

        if ($belawan) {
            $logs[] = [
                'user_id' => null,
                'action' => 'WEATHER_SNAPSHOT_SYNCED',
                'auditable_type' => Location::class,
                'auditable_id' => $belawan->id,
                'old_values' => ['condition' => 'Clear'],
                'new_values' => ['condition' => 'Humid & Partly Cloudy', 'visibility_km' => 9.5],
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subHours(2),
            ];
        }

        foreach ($logs as $log) {
            AuditLog::firstOrCreate(
                [
                    'action' => $log['action'],
                    'auditable_type' => $log['auditable_type'],
                    'auditable_id' => $log['auditable_id'],
                ],
                $log
            );
        }
    }
}
