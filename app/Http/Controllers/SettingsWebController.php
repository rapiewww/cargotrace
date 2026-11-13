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
        protected AuditService $auditService
    ) {}

    public function index(): View
    {
        $lowThreshold = (float) (Setting::get('delay_low_hours') ?? config('cargotrace.delay.low_hours', 6));
        $mediumThreshold = (float) (Setting::get('delay_medium_hours') ?? config('cargotrace.delay.medium_hours', 24));
        $freeDays = (int) (Setting::get('free_days_after_arrival') ?? config('cargotrace.free_time.days', 5));

        $activeShipmentsCount = Shipment::where('status', '!=', 'DELIVERED')->count();
        $delayedShipmentsCount = Shipment::where('status', '!=', 'DELIVERED')->where('is_delayed', true)->count();

        return view('admin.settings', compact(
            'lowThreshold',
            'mediumThreshold',
            'freeDays',
            'activeShipmentsCount',
            'delayedShipmentsCount'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delay_low_hours' => 'required|numeric|min:1',
            'delay_medium_hours' => 'required|numeric|gt:delay_low_hours',
            'free_days_after_arrival' => 'nullable|integer|min:0',
        ]);

        $oldValues = [
            'delay_low_hours' => Setting::get('delay_low_hours', 6),
            'delay_medium_hours' => Setting::get('delay_medium_hours', 24),
            'free_days_after_arrival' => Setting::get('free_days_after_arrival', 5),
        ];

        Setting::set('delay_low_hours', $validated['delay_low_hours']);
        Setting::set('delay_medium_hours', $validated['delay_medium_hours']);

        if (array_key_exists('free_days_after_arrival', $validated) && $validated['free_days_after_arrival'] !== null) {
            Setting::set('free_days_after_arrival', (int) $validated['free_days_after_arrival']);
        }

        $newValues = [
            'delay_low_hours' => Setting::get('delay_low_hours'),
            'delay_medium_hours' => Setting::get('delay_medium_hours'),
            'free_days_after_arrival' => Setting::get('free_days_after_arrival', 5),
        ];

        $targetSetting = Setting::where('key', 'free_days_after_arrival')->first()
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
