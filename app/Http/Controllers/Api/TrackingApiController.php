<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tracking\StoreTrackingEventRequest;
use App\Http\Resources\TrackingEventResource;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingApiController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    /**
     * Retrieve chronological tracking timeline for a shipment.
     */
    public function index(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        $events = $shipment->trackingEvents()
            ->with(['creator', 'location'])
            ->orderBy('occurred_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'shipment_tracking_number' => $shipment->tracking_number,
            'current_status' => $shipment->status,
            'events' => TrackingEventResource::collection($events),
        ], Response::HTTP_OK);
    }

    /**
     * Add a verified tracking event (admin and operator only).
     */
    public function store(StoreTrackingEventRequest $request, Shipment $shipment): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $event = $this->trackingService->addTrackingEvent($shipment, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tracking event logged successfully.',
            'data' => new TrackingEventResource($event->load(['creator', 'location'])),
        ], Response::HTTP_CREATED);
    }
}
