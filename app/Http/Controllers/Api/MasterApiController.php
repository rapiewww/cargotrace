<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCarrierRequest;
use App\Http\Requests\Master\StoreLocationRequest;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\LocationResource;
use App\Models\Carrier;
use App\Models\Location;
use App\Services\ExternalIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MasterApiController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService
    ) {}

    /**
     * List all carriers (all authenticated users).
     */
    public function carriers(): AnonymousResourceCollection
    {
        $carriers = Carrier::orderBy('name')->get();

        return CarrierResource::collection($carriers);
    }

    /**
     * Store new carrier (admin only).
     */
    public function storeCarrier(StoreCarrierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] ??= true;

        $carrier = Carrier::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Carrier created successfully.',
            'data' => new CarrierResource($carrier),
        ], Response::HTTP_CREATED);
    }

    /**
     * List all locations (all authenticated users).
     */
    public function locations(): AnonymousResourceCollection
    {
        $locations = Location::orderBy('name')->get();

        return LocationResource::collection($locations);
    }

    /**
     * Store new location with auto-geocoding and weather/news caching (admin only).
     */
    public function storeLocation(StoreLocationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-geocode if coordinates not supplied
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $geo = $this->externalService->geocodeLocation("{$data['name']}, {$data['city']}, {$data['country']}");
            if ($geo) {
                $data['latitude'] = $geo['latitude'];
                $data['longitude'] = $geo['longitude'];
            } else {
                $data['latitude'] = 0.0;
                $data['longitude'] = 0.0;
            }
        }

        $data['timezone'] ??= 'UTC';

        // Fetch initial external snapshots
        $data['weather_snapshot'] = $this->externalService->getWeatherByCoordinates(
            (float) $data['latitude'],
            (float) $data['longitude'],
            $data['name']
        );

        $newsList = $this->externalService->getLogisticsNews("{$data['city']} port logistics");
        $data['news_snapshot'] = $newsList[0] ?? null;

        $location = Location::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Location created successfully with geocoding and external telemetry.',
            'data' => new LocationResource($location),
        ], Response::HTTP_CREATED);
    }

    /**
     * External weather integration on-demand endpoint.
     */
    public function weather(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:255'],
        ]);

        $weather = $this->externalService->getWeatherByCoordinates(
            (float) $request->query('lat'),
            (float) $request->query('lon'),
            $request->query('location_name')
        );

        return response()->json([
            'status' => 'success',
            'data' => $weather,
        ], Response::HTTP_OK);
    }

    /**
     * External currency conversion on-demand endpoint.
     */
    public function currency(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'size:3'],
            'to' => ['nullable', 'string', 'size:3'],
        ]);

        $conversion = $this->externalService->convertCurrency(
            (float) $request->query('amount'),
            (string) $request->query('from'),
            (string) $request->query('to', 'USD')
        );

        return response()->json([
            'status' => 'success',
            'data' => $conversion,
        ], Response::HTTP_OK);
    }

    /**
     * External logistics news feed on-demand endpoint.
     */
    public function news(Request $request): JsonResponse
    {
        $query = (string) $request->query('query', 'global shipping port logistics');
        $news = $this->externalService->getLogisticsNews($query);

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'count' => count($news),
            'data' => $news,
        ], Response::HTTP_OK);
    }
}
