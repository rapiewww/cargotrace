<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\ExternalIntegrationService;
use App\Services\MaritimeNewsService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShipmentWebController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected TrackingService $trackingService,
        protected ExceptionService $exceptionService,
        protected EtaService $etaService,
        protected ExternalIntegrationService $externalService,
        protected MaritimeNewsService $newsService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'customer']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(fn ($q) => $q->where('tracking_number', 'like', "%{$s}%")->orWhere('reference_number', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }
        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->query('carrier_id'));
        }
        if ($request->filled('origin_location_id')) {
            $query->where('origin_location_id', $request->query('origin_location_id'));
        }
        if ($request->filled('destination_location_id')) {
            $query->where('destination_location_id', $request->query('destination_location_id'));
        }
        if ($request->has('is_delayed') && $request->query('is_delayed') !== '') {
            $query->where('is_delayed', filter_var($request->query('is_delayed'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expected_departure_at', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expected_arrival_at', '<=', $request->query('date_to'));
        }

        $shipments = $query->orderByDesc('created_at')->paginate(12)->withQueryString();
        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('shipments.index', compact('shipments', 'carriers', 'locations', 'user'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation', 'customer']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(fn ($q) => $q->where('tracking_number', 'like', "%{$s}%")->orWhere('reference_number', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }
        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->query('carrier_id'));
        }
        if ($request->filled('origin_location_id')) {
            $query->where('origin_location_id', $request->query('origin_location_id'));
        }
        if ($request->filled('destination_location_id')) {
            $query->where('destination_location_id', $request->query('destination_location_id'));
        }
        if ($request->has('is_delayed') && $request->query('is_delayed') !== '') {
            $query->where('is_delayed', filter_var($request->query('is_delayed'), FILTER_VALIDATE_BOOLEAN));
        }

        $shipments = $query->orderByDesc('created_at')->get();
        $filename = 'cargotrace-shipments-'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($shipments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Tracking Number',
                'Reference Number',
                'Carrier',
                'Carrier Code',
                'Mode',
                'Origin UNLOCODE',
                'Origin Port',
                'Destination UNLOCODE',
                'Destination Port',
                'Status',
                'Is Delayed',
                'Delay Hours',
                'Delay Severity',
                'Declared Value',
                'Currency',
                'Converted Value USD',
                'Expected Departure',
                'Expected Arrival',
                'Estimated Arrival',
                'Created At',
            ]);

            foreach ($shipments as $s) {
                fputcsv($handle, [
                    $s->tracking_number,
                    $s->reference_number ?? '',
                    $s->carrier?->name ?? '',
                    $s->carrier?->code ?? '',
                    $s->carrier?->mode ?? '',
                    $s->originLocation?->unlocode ?? '',
                    $s->originLocation?->name ?? '',
                    $s->destinationLocation?->unlocode ?? '',
                    $s->destinationLocation?->name ?? '',
                    $s->status,
                    $s->is_delayed ? 'YES' : 'NO',
                    $s->delay_hours,
                    $s->delay_severity,
                    $s->declared_value,
                    $s->currency_code,
                    $s->converted_value_usd,
                    $s->expected_departure_at?->toDateTimeString() ?? '',
                    $s->expected_arrival_at?->toDateTimeString() ?? '',
                    $s->estimated_arrival_at?->toDateTimeString() ?? '',
                    $s->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function show(Shipment $shipment): View
    {
        $this->authorize('view', $shipment);

        $shipment->load([
            'carrier',
            'originLocation',
            'destinationLocation',
            'currentLocation',
            'customer',
            'items',
            'trackingEvents.location',
            'trackingEvents.creator',
            'exceptions.reporter',
            'exceptions.resolver',
            'notifications',
        ]);

        $locations = Location::orderBy('name')->get();
        $user = Auth::user();

        // Relevant maritime news for this shipment corridor
        $rawNews = $this->newsService->getMaritimeNews(12);
        $taggedNews = $this->newsService->tagShipmentRelevance($rawNews, [$shipment]);
        $relatedNews = collect($taggedNews)->filter(fn ($item) => ! empty($item['is_relevant']))->take(3)->values()->all();
        if (empty($relatedNews)) {
            $relatedNews = array_slice($rawNews, 0, 3);
        }

        return view('shipments.show', compact('shipment', 'locations', 'user', 'relatedNews'));
    }

    public function create(): View
    {
        $this->authorize('create', Shipment::class);

        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $customers = User::where('role', 'customer')->orderBy('name')->get();

        return view('shipments.create', compact('carriers', 'locations', 'customers'));
    }

    public function edit(Shipment $shipment): View
    {
        $this->authorize('update', $shipment);

        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $customers = User::where('role', 'customer')->orderBy('name')->get();

        return view('shipments.edit', compact('shipment', 'carriers', 'locations', 'customers'));
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'origin_location_id' => 'required|exists:locations,id',
            'destination_location_id' => 'required|exists:locations,id|different:origin_location_id',
            'current_location_id' => 'nullable|exists:locations,id',
            'status' => 'nullable|string|in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED',
            'cargo_type' => 'required|string',
            'currency_code' => 'nullable|string|size:3',
            'declared_value' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'expected_departure_at' => 'required|date',
            'expected_arrival_at' => 'required|date|after_or_equal:expected_departure_at',
            'estimated_arrival_at' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $this->shipmentService->updateShipment($shipment, $validated);

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Shipment::class);

        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'origin_location_id' => 'required|exists:locations,id',
            'destination_location_id' => 'required|exists:locations,id|different:origin_location_id',
            'cargo_type' => 'required|string',
            'currency_code' => 'nullable|string|size:3',
            'declared_value' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'expected_departure_at' => 'required|date',
            'expected_arrival_at' => 'required|date|after_or_equal:expected_departure_at',
            'reference_number' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string',
            'items.*.sku' => 'nullable|string',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_value' => 'nullable|numeric|min:0',
            'items.*.weight_kg' => 'required_with:items|numeric|min:0',
            'items.*.volume_cbm' => 'required_with:items|numeric|min:0',
            'items.*.is_hazardous' => 'nullable|boolean',
        ]);

        $validated['customer_id'] ??= Auth::id();
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $shipment = $this->shipmentService->createShipment($validated, $items);

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment created successfully.');
    }

    public function addTrackingEvent(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('addTrackingEvent', $shipment);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'nullable|string|in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED',
            'event_type' => 'nullable|string|in:NORMAL,EXCEPTION,SYSTEM',
            'location_id' => 'nullable|exists:locations,id',
            'description' => 'nullable|string',
            'occurred_at' => 'nullable|date',
            'estimated_arrival_at' => 'nullable|date',
        ]);

        $this->trackingService->addTrackingEvent($shipment, $validated);

        return redirect()->route('shipments.show', $shipment)->with('success', 'Tracking event added.');
    }

    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'status' => 'required|string|in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED',
            'location_id' => 'nullable|exists:locations,id',
            'description' => 'nullable|string',
        ]);

        $this->shipmentService->updateStatus($shipment, $validated['status'], $validated['location_id'] ?? null, $validated['description'] ?? null);

        if ($request->filled('estimated_arrival_at')) {
            $shipment->estimated_arrival_at = Carbon::parse($request->input('estimated_arrival_at'));
            $this->etaService->evaluateDelay($shipment);
        }

        return redirect()->route('shipments.show', $shipment)->with('success', 'Status updated.');
    }

    public function reportException(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('reportException', $shipment);

        $validated = $request->validate([
            'exception_code' => 'required|string|in:CUSTOMS_HOLD,WEATHER_DELAY,PORT_CONGESTION,DAMAGED,MISROUTED,DOCUMENTATION_ISSUE',
            'severity' => 'required|string|in:LOW,MEDIUM,HIGH',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'related_news_url' => 'nullable|url',
        ]);

        $this->exceptionService->reportException($shipment, $validated);

        return redirect()->route('shipments.show', $shipment)->with('success', 'Exception reported.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $this->authorize('delete', $shipment);
        $shipment->delete();

        return redirect()->route('shipments.index')->with('success', 'Shipment deleted.');
    }
}
