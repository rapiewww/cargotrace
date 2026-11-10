<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipment\StoreShipmentRequest;
use App\Http\Requests\Shipment\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\EtaService;
use App\Services\ShipmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ShipmentApiController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected EtaService $etaService
    ) {}

    /**
     * Display a paginated listing of shipments with filtering and role isolation.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Shipment::query()
            ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'customer']);

        // Customer role isolation
        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($request->filled('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
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

        if ($request->has('is_delayed')) {
            $isDelayed = filter_var($request->query('is_delayed'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_delayed', $isDelayed);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expected_departure_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expected_arrival_at', '<=', $request->query('date_to'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $shipments = $query->orderByDesc('created_at')->paginate($perPage);

        return ShipmentResource::collection($shipments);
    }

    /**
     * Store a newly created shipment with items (admin and operator only).
     */
    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Default customer_id to user if not specified
        $validated['customer_id'] ??= $request->user()->id;
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $shipment = $this->shipmentService->createShipment($validated, $items);

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment created successfully.',
            'data' => new ShipmentResource($shipment),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display shipment detail if authorized by ShipmentPolicy.
     */
    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        $shipment->load([
            'carrier',
            'originLocation',
            'destinationLocation',
            'currentLocation',
            'customer',
            'items',
            'trackingEvents.creator',
            'trackingEvents.location',
            'exceptions.reporter',
            'exceptions.resolver',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new ShipmentResource($shipment),
        ], Response::HTTP_OK);
    }

    /**
     * Update shipment status and parameters (admin and operator only).
     */
    public function update(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['status'])) {
            $this->shipmentService->updateStatus(
                $shipment,
                $validated['status'],
                $validated['location_id'] ?? null,
                $validated['description'] ?? null
            );
        }

        if (! empty($validated['estimated_arrival_at'])) {
            $shipment->estimated_arrival_at = Carbon::parse($validated['estimated_arrival_at']);
            $this->etaService->evaluateDelay($shipment);
        }

        if (isset($validated['delay_reason'])) {
            $shipment->delay_reason = $validated['delay_reason'];
        }

        if (isset($validated['notes'])) {
            $shipment->notes = $validated['notes'];
        }

        $shipment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment updated successfully.',
            'data' => new ShipmentResource($shipment->fresh([
                'carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'customer',
            ])),
        ], Response::HTTP_OK);
    }

    /**
     * Delete shipment record (strictly admin only).
     */
    public function destroy(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('delete', $shipment);

        $shipment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment deleted successfully.',
        ], Response::HTTP_OK);
    }
}
