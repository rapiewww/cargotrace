<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shipment\UpdateShipmentRequest;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Rules\AwbRule;
use App\Rules\Iso6346Rule;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\ExternalIntegrationService;
use App\Services\FreeTimeService;
use App\Services\MaritimeNewsService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use App\Services\WeatherService;
use App\Support\Iso6346;
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
        protected MaritimeNewsService $newsService,
        protected WeatherService $weatherService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'customer', 'items']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        // Mode counts for filter chips
        $countQuery = Shipment::query();
        if ($user->role === 'customer') {
            $countQuery->where('customer_id', $user->id);
        }
        $modeCounts = [
            'ALL' => (clone $countQuery)->count(),
            'SEA' => (clone $countQuery)->where('transport_mode', 'SEA')->count(),
            'AIR' => (clone $countQuery)->where('transport_mode', 'AIR')->count(),
            'ROAD' => (clone $countQuery)->where('transport_mode', 'ROAD')->count(),
        ];

        // Mode filter
        if ($request->filled('mode') && in_array(strtoupper($request->query('mode')), ['SEA', 'AIR', 'ROAD'])) {
            $query->where('transport_mode', strtoupper($request->query('mode')));
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $cleanContainer = strtoupper(preg_replace('/\s+/', '', $s));
            $cleanDigits = preg_replace('/[^\d]/', '', $s);
            $query->where(function ($q) use ($s, $cleanContainer, $cleanDigits) {
                $q->where('tracking_number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhere('container_number', 'like', "%{$s}%")
                    ->orWhere('container_number', 'like', "%{$cleanContainer}%")
                    ->orWhere('bill_of_lading_number', 'like', "%{$s}%")
                    ->orWhere('awb_number', 'like', "%{$s}%")
                    ->when($cleanDigits !== '', fn ($sub) => $sub->orWhere('awb_number', 'like', "%{$cleanDigits}%"))
                    ->orWhere('vehicle_plate', 'like', "%{$s}%")
                    ->orWhere('cmr_number', 'like', "%{$s}%")
                    ->orWhereHas('items', function ($iq) use ($s, $cleanContainer) {
                        $iq->where('container_number', 'like', "%{$s}%")
                            ->orWhere('container_number', 'like', "%{$cleanContainer}%")
                            ->orWhere('sku', 'like', "%{$s}%")
                            ->orWhere('sku', 'like', "%{$cleanContainer}%");
                    });
            });
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

        return view('shipments.index', compact('shipments', 'carriers', 'locations', 'user', 'modeCounts'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation', 'customer']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $cleanContainer = strtoupper(preg_replace('/\s+/', '', $s));
            $query->where(function ($q) use ($s, $cleanContainer) {
                $q->where('tracking_number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhereHas('items', function ($iq) use ($s, $cleanContainer) {
                        $iq->where('container_number', 'like', "%{$s}%")
                            ->orWhere('container_number', 'like', "%{$cleanContainer}%")
                            ->orWhere('sku', 'like', "%{$s}%")
                            ->orWhere('sku', 'like', "%{$cleanContainer}%");
                    });
            });
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

        // Real weather at latest tracking location
        $latestLocation = $shipment->currentLocation
            ?? $shipment->trackingEvents->first(fn ($e) => $e->location !== null)?->location
            ?? $shipment->originLocation;

        $locationWeather = null;
        $locationMarine = null;
        $craneAdvisory = null;
        $beaufortScale = null;

        if ($latestLocation && $latestLocation->latitude && $latestLocation->longitude) {
            $locationWeather = $this->weatherService->getPortWeather(
                (float) $latestLocation->latitude,
                (float) $latestLocation->longitude,
                $latestLocation->name
            );
            $locationMarine = $this->weatherService->getMarineWeather(
                (float) $latestLocation->latitude,
                (float) $latestLocation->longitude
            );
            $windKph = (float) ($locationWeather['wind_kph'] ?? 0);
            $craneAdvisory = $this->weatherService->getCraneAdvisory($windKph);
            $beaufortScale = $this->weatherService->getBeaufortScale($windKph);
        }

        $freeTime = app(FreeTimeService::class)->calculate($shipment);

        return view('shipments.show', compact(
            'shipment',
            'locations',
            'user',
            'freeTime',
            'relatedNews',
            'latestLocation',
            'locationWeather',
            'locationMarine',
            'craneAdvisory',
            'beaufortScale'
        ));
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

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validated();

        $this->shipmentService->updateShipment($shipment, $validated);

        if ($request->filled('container_number')) {
            $cont = Iso6346::normalize($request->input('container_number'));
            $item = $shipment->items()->first();
            if ($item) {
                $item->update(['container_number' => $cont]);
            } else {
                $shipment->items()->create([
                    'container_number' => $cont,
                    'sku' => $cont,
                    'description' => 'Container cargo',
                    'quantity' => 1,
                    'weight_kg' => (float) ($shipment->total_weight_kg ?? 1000),
                    'volume_cbm' => (float) ($shipment->total_volume_cbm ?? 10),
                ]);
            }
        }

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Shipment::class);

        // If request is from multimodal web form or has transport_mode, validate with rules
        $rules = [
            'transport_mode' => ['nullable', 'string', 'in:SEA,AIR,ROAD'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'exists:locations,id'],
            'destination_location_id' => ['required', 'exists:locations,id', 'different:origin_location_id'],
            'cargo_type' => ['required', 'string', 'max:50'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'expected_departure_at' => ['required', 'date'],
            'expected_arrival_at' => ['required', 'date', 'after_or_equal:expected_departure_at'],
            'estimated_arrival_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'container_number' => ['nullable', 'string'],
            'bill_of_lading_number' => ['nullable', 'string', 'max:100'],
            'awb_number' => ['nullable', 'string'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'cmr_number' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ];

        // Mode specific validation
        $mode = strtoupper($request->input('transport_mode') ?? 'SEA');
        if ($mode === 'SEA') {
            $rules['container_number'] = ['nullable', 'string', new Iso6346Rule];
            $rules['items.*.container_number'] = ['nullable', 'string', new Iso6346Rule];
        } elseif ($mode === 'AIR') {
            $rules['awb_number'] = ['required', 'string', new AwbRule];
        } elseif ($mode === 'ROAD') {
            $rules['vehicle_plate'] = ['required', 'string', 'max:50'];
            $rules['cmr_number'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($rules);
        $validated['customer_id'] ??= Auth::id();
        $validated['transport_mode'] = $mode;

        $items = $request->input('items', []);

        // If container_number was provided on top level for SEA and no items, auto-seed a line item
        if (empty($items) && ! empty($validated['container_number'])) {
            $items[] = [
                'container_number' => $validated['container_number'],
                'description' => 'Spice Cargo Consignment',
                'quantity' => 1,
                'weight_kg' => 1000,
                'volume_cbm' => 10,
            ];
        }

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
            'exception_code' => 'required|string|in:CUSTOMS_HOLD,WEATHER_DELAY,PORT_CONGESTION,DAMAGED,MISROUTED,DOCUMENTATION_ISSUE,FLIGHT_OFFLOAD,BORDER_QUEUE,VEHICLE_BREAKDOWN',
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
