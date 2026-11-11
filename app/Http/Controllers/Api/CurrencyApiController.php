<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CurrencyApiController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Get all supported world currencies.
     */
    public function currencies(): JsonResponse
    {
        try {
            $currencies = $this->currencyService->getCurrencies();

            return response()->json([
                'status' => 'success',
                'data' => $currencies,
            ], Response::HTTP_OK);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * Convert currency using exchange rates service.
     */
    public function convert(Request $request): JsonResponse
    {
        $amount = (float) $request->input('amount', $request->query('amount', 1));
        $from = (string) $request->input('from', $request->query('from', 'USD'));
        $to = (string) $request->input('to', $request->query('to', 'IDR'));

        if ($amount < 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Amount must be greater than or equal to 0.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->currencyService->convert($amount, $from, $to);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], Response::HTTP_OK);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * Get historical 30-day rates (Frankfurter API).
     */
    public function history(Request $request): JsonResponse
    {
        $from = (string) $request->query('from', 'USD');
        $to = (string) $request->query('to', 'EUR');
        $days = (int) $request->query('days', 30);

        $history = $this->currencyService->getHistory($from, $to, $days);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ], Response::HTTP_OK);
    }
}
