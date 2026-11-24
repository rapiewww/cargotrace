<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Shipment;
use App\Services\AuditService;
use App\Services\EtaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsWebController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected EtaService $etaService
    ) {}

    public function index(): View
    {
        $airThresholds = $this->etaService->getThresholdsForMode('AIR');
        $roadThresholds = $this->etaService->getThresholdsForMode('ROAD');
        $seaThresholds = $this->etaService->getThresholdsForMode('SEA');

        $lowThreshold = $seaThresholds['low'];
        $mediumThreshold = $seaThresholds['medium'];
        $freeDays = (int) (Setting::get('free_days_after_arrival') ?? config('cargotrace.free_time.days', 5));

        $activeShipmentsCount = Shipment::where('status', '!=', 'DELIVERED')->count();
        $delayedShipmentsCount = Shipment::where('status', '!=', 'DELIVERED')->where('is_delayed', true)->count();

        return view('admin.settings', compact(
            'lowThreshold',
            'mediumThreshold',
            'airThresholds',
            'roadThresholds',
            'seaThresholds',
            'freeDays',
            'activeShipmentsCount',
            'delayedShipmentsCount'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Mode-specific delay thresholds
            'delay_air_low_hours' => 'nullable|numeric|min:0.5',
            'delay_air_medium_hours' => 'nullable|numeric|gt:delay_air_low_hours',
            'delay_road_low_hours' => 'nullable|numeric|min:0.5',
            'delay_road_medium_hours' => 'nullable|numeric|gt:delay_road_low_hours',
            'delay_sea_low_hours' => 'nullable|numeric|min:0.5',
            'delay_sea_medium_hours' => 'nullable|numeric|gt:delay_sea_low_hours',

            // Legacy fallback support
            'delay_low_hours' => 'nullable|numeric|min:0.5',
            'delay_medium_hours' => 'nullable|numeric',
            'free_days_after_arrival' => 'nullable|integer|min:0',
        ]);

        $oldValues = [
            'delay.air.low_hours' => Setting::get('delay.air.low_hours', Setting::get('delay_air_low_hours', 3)),
            'delay.air.medium_hours' => Setting::get('delay.air.medium_hours', Setting::get('delay_air_medium_hours', 12)),
            'delay.road.low_hours' => Setting::get('delay.road.low_hours', Setting::get('delay_road_low_hours', 6)),
            'delay.road.medium_hours' => Setting::get('delay.road.medium_hours', Setting::get('delay_road_medium_hours', 24)),
            'delay.sea.low_hours' => Setting::get('delay.sea.low_hours', Setting::get('delay_sea_low_hours', Setting::get('delay_low_hours', 12))),
            'delay.sea.medium_hours' => Setting::get('delay.sea.medium_hours', Setting::get('delay_sea_medium_hours', Setting::get('delay_medium_hours', 48))),
            'free_days_after_arrival' => Setting::get('free_days_after_arrival', 5),
        ];

        // Update AIR
        if (isset($validated['delay_air_low_hours'])) {
            Setting::set('delay.air.low_hours', $validated['delay_air_low_hours']);
            Setting::set('delay_air_low_hours', $validated['delay_air_low_hours']);
        }
        if (isset($validated['delay_air_medium_hours'])) {
            Setting::set('delay.air.medium_hours', $validated['delay_air_medium_hours']);
            Setting::set('delay_air_medium_hours', $validated['delay_air_medium_hours']);
        }

        // Update ROAD
        if (isset($validated['delay_road_low_hours'])) {
            Setting::set('delay.road.low_hours', $validated['delay_road_low_hours']);
            Setting::set('delay_road_low_hours', $validated['delay_road_low_hours']);
        }
        if (isset($validated['delay_road_medium_hours'])) {
            Setting::set('delay.road.medium_hours', $validated['delay_road_medium_hours']);
            Setting::set('delay_road_medium_hours', $validated['delay_road_medium_hours']);
        }

        // Update SEA (and sync with delay_low_hours / delay_medium_hours)
        $seaLow = $validated['delay_sea_low_hours'] ?? ($validated['delay_low_hours'] ?? null);
        $seaMedium = $validated['delay_sea_medium_hours'] ?? ($validated['delay_medium_hours'] ?? null);

        if ($seaLow !== null) {
            Setting::set('delay.sea.low_hours', $seaLow);
            Setting::set('delay_sea_low_hours', $seaLow);
            Setting::set('delay_low_hours', $seaLow);
        }
        if ($seaMedium !== null) {
            Setting::set('delay.sea.medium_hours', $seaMedium);
            Setting::set('delay_sea_medium_hours', $seaMedium);
            Setting::set('delay_medium_hours', $seaMedium);
        }

        if (array_key_exists('free_days_after_arrival', $validated) && $validated['free_days_after_arrival'] !== null) {
            Setting::set('free_days_after_arrival', (int) $validated['free_days_after_arrival']);
        }

        $newValues = [
            'delay.air.low_hours' => Setting::get('delay.air.low_hours'),
            'delay.air.medium_hours' => Setting::get('delay.air.medium_hours'),
            'delay.road.low_hours' => Setting::get('delay.road.low_hours'),
            'delay.road.medium_hours' => Setting::get('delay.road.medium_hours'),
            'delay.sea.low_hours' => Setting::get('delay.sea.low_hours'),
            'delay.sea.medium_hours' => Setting::get('delay.sea.medium_hours'),
            'free_days_after_arrival' => Setting::get('free_days_after_arrival', 5),
        ];

        $targetSetting = Setting::where('key', 'free_days_after_arrival')->first()
            ?? Setting::where('key', 'delay.sea.low_hours')->first()
            ?? Setting::where('key', 'delay_low_hours')->first()
            ?? Setting::first();

        $this->auditService->log('SETTINGS_UPDATED', $targetSetting, $oldValues, $newValues);

        return back()->with('success', 'Delay thresholds and terminal free time updated successfully.');
    }

    public function reevaluate(EtaService $etaService): RedirectResponse
    {
        $shipments = Shipment::where('status', '!=', 'DELIVERED')->get();
        $count = 0;

        foreach ($shipments as $shipment) {
            $etaService->evaluateDelay($shipment);
            $count++;
        }

        return back()->with('success', "Evaluated {$count} active shipments against current thresholds.");
    }
}
