<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyWebController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Display the dedicated Maritime Currency FX & Route Freight Rate Hub.
     */
    public function index(Request $request): View
    {
        $baseCurrency = strtoupper($request->query('base', 'USD'));
        $supportedCurrencies = ['USD', 'EUR', 'IDR', 'CNY', 'SGD', 'AUD', 'GBP', 'JPY', 'AED'];

        if (! in_array($baseCurrency, $supportedCurrencies)) {
            $baseCurrency = 'USD';
        }

        // 1. Fetch live rates matrix
        $fxData = $this->currencyService->getRates($baseCurrency);
        $rates = $fxData['rates'] ?? [];
        $dataSource = $fxData['data_source'] ?? 'live';
        $sourceName = $fxData['source'] ?? 'ECB / Frankfurter API';

        // 2. Matrix cross-rates for prominent container currencies
        $matrixCurrencies = ['USD', 'EUR', 'CNY', 'SGD', 'IDR'];
        $crossMatrix = [];
        foreach ($matrixCurrencies as $rowCur) {
            $crossMatrix[$rowCur] = [];
            $rowToUsdRate = $rates[$rowCur] ?? 1.0;
            foreach ($matrixCurrencies as $colCur) {
                if ($rowCur === $colCur) {
                    $crossMatrix[$rowCur][$colCur] = 1.0;
                } else {
                    $colToUsdRate = $rates[$colCur] ?? 1.0;
                    $crossMatrix[$rowCur][$colCur] = round($colToUsdRate / $rowToUsdRate, 4);
                }
            }
        }

        // 3. Global Maritime Freight Rate Benchmarks (Ocean TEU / FEU Spot Indices)
        $routeBenchmarks = [
            [
                'route_name' => 'Transpacific Eastbound',
                'corridor' => 'Shanghai (CNSHA) &rarr; Los Angeles (USLAX)',
                'mode' => '40ft FEU Container',
                'index_usd' => 2850,
                'change_pct' => '+2.4%',
                'trend' => 'up',
                'lead_time_days' => '14 - 18 Days',
            ],
            [
                'route_name' => 'Asia to North Europe',
                'corridor' => 'Shanghai (CNSHA) &rarr; Rotterdam (NLRTM)',
                'mode' => '40ft FEU Container',
                'index_usd' => 2140,
                'change_pct' => '-1.2%',
                'trend' => 'down',
                'lead_time_days' => '28 - 34 Days',
            ],
            [
                'route_name' => 'Transatlantic Westbound',
                'corridor' => 'Rotterdam (NLRTM) &rarr; New York (USNYC)',
                'mode' => '40ft FEU Container',
                'index_usd' => 1620,
                'change_pct' => '+0.8%',
                'trend' => 'up',
                'lead_time_days' => '10 - 12 Days',
            ],
            [
                'route_name' => 'Intra-Asia Feeder Loop',
                'corridor' => 'Singapore (SGSIN) &rarr; Tanjung Priok (IDJKT)',
                'mode' => '20ft TEU Container',
                'index_usd' => 420,
                'change_pct' => '0.0%',
                'trend' => 'neutral',
                'lead_time_days' => '2 - 3 Days',
            ],
            [
                'route_name' => 'Middle East to Far East Loop',
                'corridor' => 'Jebel Ali (AEJEA) &rarr; Ningbo (CNNBG)',
                'mode' => '40ft FEU Container',
                'index_usd' => 1890,
                'change_pct' => '+1.5%',
                'trend' => 'up',
                'lead_time_days' => '18 - 22 Days',
            ],
        ];

        // 4. Vessel Bunker Fuel Benchmark Estimates (Global 20 Ports Average)
        $bunkerPrices = [
            'vlsfo' => ['name' => 'VLSFO 0.5% Marine Fuel', 'price_mt' => 618.50, 'unit' => 'USD / MT', 'change' => '+1.8%'],
            'mgo' => ['name' => 'LSMGO 0.1% Marine Gasoil', 'price_mt' => 742.00, 'unit' => 'USD / MT', 'change' => '-0.6%'],
            'hsfo' => ['name' => 'IFO 380 High Sulfur Fuel', 'price_mt' => 455.00, 'unit' => 'USD / MT', 'change' => '+0.4%'],
        ];

        return view('currency.index', compact(
            'baseCurrency',
            'supportedCurrencies',
            'rates',
            'dataSource',
            'sourceName',
            'crossMatrix',
            'matrixCurrencies',
            'routeBenchmarks',
            'bunkerPrices'
        ));
    }
}

