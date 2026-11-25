<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CurrencyWebController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Display the dedicated Freight Currency FX & Valuation Hub.
     */
    public function index(Request $request): View
    {
        $baseCurrency = strtoupper(trim((string) $request->query('base', 'USD')));
        $error = null;
        $rates = [];
        $dataSource = 'live';
        $sourceName = 'ExchangeRate-API (open.er-api.com)';
        $timeLastUpdateUtc = null;
        $providerUrl = 'https://www.exchangerate-api.com';
        $attributionText = 'Rates By ExchangeRate-API';
        $allCurrencies = [];
        $pinnedCurrencies = CurrencyService::PINNED_CURRENCIES;
        $frankfurterCurrencies = [];
        $initialHistory = null;
        $crossMatrix = [];
        $defaultMatrix = CurrencyService::SEEDED_CURRENCIES;

        try {
            $fxData = $this->currencyService->getRates($baseCurrency);
            $rates = $fxData['rates'] ?? [];
            $dataSource = $fxData['data_source'] ?? 'live';
            $sourceName = $fxData['source'] ?? 'ExchangeRate-API';
            $timeLastUpdateUtc = $fxData['time_last_update_utc'] ?? null;
            $providerUrl = $fxData['provider_url'] ?? 'https://www.exchangerate-api.com';
            $attributionText = $fxData['attribution_text'] ?? 'Rates By ExchangeRate-API';
            $allCurrencies = array_keys($rates);
            sort($allCurrencies);

            $frankfurterCurrencies = $this->currencyService->getFrankfurterCurrencies();
            $initialHistory = $this->currencyService->getHistory('USD', 'EUR', 30);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        // Cross-rate matrix selection (user picks 3 - 8 currencies)
        $matrixParam = $request->query('matrix');
        if (! empty($matrixParam)) {
            $picked = array_unique(array_filter(array_map('trim', explode(',', strtoupper($matrixParam)))));
            $validPicked = array_values(array_filter($picked, fn ($c) => isset($rates[$c])));
            if (count($validPicked) >= 3 && count($validPicked) <= 8) {
                $matrixCurrencies = $validPicked;
            } else {
                $matrixCurrencies = $defaultMatrix;
            }
        } else {
            $matrixCurrencies = $defaultMatrix;
        }

        if (! empty($rates)) {
            foreach ($matrixCurrencies as $rowCur) {
                $crossMatrix[$rowCur] = [];
                $rowRate = (float) ($rates[$rowCur] ?? 1.0);
                foreach ($matrixCurrencies as $colCur) {
                    if ($rowCur === $colCur) {
                        $crossMatrix[$rowCur][$colCur] = 1.0;
                    } else {
                        $colRate = (float) ($rates[$colCur] ?? 1.0);
                        $crossMatrix[$rowCur][$colCur] = $rowRate > 0 ? round($colRate / $rowRate, 4) : 1.0;
                    }
                }
            }
        }

        // Portfolio value by currency (role-scoped to user's shipments)
        $user = $request->user();
        $shipmentQuery = Shipment::query();
        if ($user && $user->role === 'customer') {
            $shipmentQuery->where('customer_id', $user->id);
        }

        $portfolioByCurrency = (clone $shipmentQuery)
            ->selectRaw('currency_code, COUNT(*) as shipments_count, SUM(declared_value) as total_declared, SUM(converted_value_usd) as total_usd')
            ->groupBy('currency_code')
            ->orderByDesc('total_usd')
            ->get();

        $totalPortfolioUsd = (float) (clone $shipmentQuery)->sum('converted_value_usd');
        $totalShipmentsCount = (int) (clone $shipmentQuery)->count();

        return view('currency.index', compact(
            'baseCurrency',
            'rates',
            'dataSource',
            'sourceName',
            'timeLastUpdateUtc',
            'providerUrl',
            'attributionText',
            'allCurrencies',
            'pinnedCurrencies',
            'frankfurterCurrencies',
            'initialHistory',
            'crossMatrix',
            'matrixCurrencies',
            'portfolioByCurrency',
            'totalPortfolioUsd',
            'totalShipmentsCount',
            'error'
        ));
    }
}
