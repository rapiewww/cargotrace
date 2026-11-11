<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurrencyApiController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Convert currency live using exchange rates service.
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

        $result = $this->currencyService->convert($amount, $from, $to);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ], Response::HTTP_OK);
    }
}

