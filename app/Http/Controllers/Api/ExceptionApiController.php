<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exception\StoreShipmentExceptionRequest;
use App\Http\Requests\Exception\UpdateExceptionStatusRequest;
use App\Http\Resources\ShipmentExceptionResource;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptionApiController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    /**
     * List exceptions logged for a specific shipment.
     */
    public function index(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        $exceptions = $shipment->exceptions()
            ->with(['reporter', 'resolver'])
            ->orderByDesc('detected_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'shipment_tracking_number' => $shipment->tracking_number,
            'exceptions' => ShipmentExceptionResource::collection($exceptions),
        ], Response::HTTP_OK);
    }

    /**
     * Report a new shipment exception (admin and operator only).
     */
    public function store(StoreShipmentExceptionRequest $request, Shipment $shipment): JsonResponse
    {
        $validated = $request->validated();
        $validated['reported_by'] = $request->user()->id;

        $exception = $this->exceptionService->reportException($shipment, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment exception reported successfully.',
            'data' => new ShipmentExceptionResource($exception->load(['reporter', 'resolver'])),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update exception status (admin and operator only).
     */
    public function updateStatus(UpdateExceptionStatusRequest $request, ShipmentException $exception): JsonResponse
    {
        $validated = $request->validated();

        $updated = $this->exceptionService->updateExceptionStatus(
            $exception,
            $validated['status'],
            $validated['resolution_notes'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Exception status updated successfully.',
            'data' => new ShipmentExceptionResource($updated->load(['reporter', 'resolver'])),
        ], Response::HTTP_OK);
    }
}

