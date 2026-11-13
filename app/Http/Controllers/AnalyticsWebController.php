<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsWebController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Display the analytics and performance intelligence dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        [$from, $to] = $this->analyticsService->parseDateRange(
            $request->query('from'),
            $request->query('to')
        );

        $summary = $this->analyticsService->getSummaryMetrics($user, $from, $to);
        $carrierPerformance = $this->analyticsService->getCarrierPerformance($user, $from, $to);
        $laneDelays = $this->analyticsService->getLaneDelays($user, $from, $to);
        $weeklyTrend = $this->analyticsService->getWeeklyTrend($user, $to);
        $exceptionsByType = $this->analyticsService->getExceptionsByType($user, $from, $to);
        $resolutionTime = $this->analyticsService->getAverageResolutionTime($user, $from, $to);
        $topDelayedLanes = $this->analyticsService->getTopDelayedLanes($user, $from, $to, 5);

        $isCustomer = ! $user->isAdmin() && ! $user->isOperator();

        // Chart.js data packages
        $carrierChartData = [
            'labels' => $carrierPerformance->pluck('carrier_name')->map(fn ($n) => strlen($n) > 22 ? substr($n, 0, 20).'…' : $n)->toArray(),
            'rates' => $carrierPerformance->pluck('on_time_rate')->toArray(),
            'totals' => $carrierPerformance->pluck('total_shipments')->toArray(),
        ];

        $weeklyChartData = [
            'labels' => array_column($weeklyTrend, 'week'),
            'totals' => array_column($weeklyTrend, 'total'),
            'on_time' => array_column($weeklyTrend, 'on_time'),
            'delayed' => array_column($weeklyTrend, 'delayed'),
        ];

        $exceptionChartData = [
            'labels' => $exceptionsByType->pluck('label')->toArray(),
            'counts' => $exceptionsByType->pluck('count')->toArray(),
        ];

        $laneChartData = [
            'labels' => $topDelayedLanes->pluck('lane_label')->toArray(),
            'delays' => $topDelayedLanes->pluck('avg_delay_hours')->toArray(),
            'shipments' => $topDelayedLanes->pluck('delayed_shipments')->toArray(),
        ];

        return view('analytics.index', compact(
            'user',
            'isCustomer',
            'from',
            'to',
            'summary',
            'carrierPerformance',
            'laneDelays',
            'weeklyTrend',
            'exceptionsByType',
            'resolutionTime',
            'topDelayedLanes',
            'carrierChartData',
            'weeklyChartData',
            'exceptionChartData',
            'laneChartData'
        ));
    }

    /**
     * Download CSV export of a specific analytics table.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        [$from, $to] = $this->analyticsService->parseDateRange(
            $request->query('from'),
            $request->query('to')
        );

        $table = (string) $request->query('table', 'carriers');

        return $this->analyticsService->exportCsv($table, $user, $from, $to);
    }
}

