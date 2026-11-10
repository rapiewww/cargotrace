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

        $activeShipmentsCount = Shipment::where('status', '!=', 'DELIVERED')->count();
        $delayedShipmentsCount = Shipment::where('status', '!=', 'DELIVERED')->where('is_delayed', true)->count();

        return view('admin.settings', compact(
            'lowThreshold',
            'mediumThreshold',
            'activeShipmentsCount',
            'delayedShipmentsCount'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delay_low_hours' => 'required|numeric|min:1',
            'delay_medium_hours' => 'required|numeric|gt:delay_low_hours',
        ]);

        $oldValues = [
            'delay_low_hours' => Setting::get('delay_low_hours', 6),
            'delay_medium_hours' => Setting::get('delay_medium_hours', 24),
        ];

        Setting::set('delay_low_hours', $validated['delay_low_hours']);
        Setting::set('delay_medium_hours', $validated['delay_medium_hours']);

        $this->auditService->log('SETTINGS_UPDATED', Setting::where('key', 'delay_low_hours')->first(), $oldValues, $validated);

        return back()->with('success', 'Delay thresholds updated successfully.');
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
