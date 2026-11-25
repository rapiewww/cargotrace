<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService
{
    /**
     * Parse and normalize date filter range. Default: last 90 days.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function parseDateRange(?string $fromInput, ?string $toInput): array
    {
        try {
            $to = $toInput ? Carbon::parse($toInput)->endOfDay() : Carbon::now()->endOfDay();
        } catch (\Throwable) {
            $to = Carbon::now()->endOfDay();
        }

        try {
            $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : Carbon::now()->subDays(90)->startOfDay();
        } catch (\Throwable) {
            $from = Carbon::now()->subDays(90)->startOfDay();
        }

        if ($from->greaterThan($to)) {
            $temp = $from;
            $from = $to->copy()->startOfDay();
            $to = $temp->copy()->endOfDay();
        }

        return [$from, $to];
    }

    /**
     * Check whether the user is restricted to customer-scoped data.
     */
    protected function isCustomerScoped(User $user): bool
    {
        return ! $user->isAdmin() && ! $user->isOperator();
    }

    /**
     * High-level KPI summary cards for the analytics dashboard header.
     *
     * @return array<string, mixed>
     */
    public function getSummaryMetrics(User $user, Carbon $from, Carbon $to): array
    {
        $shipmentQuery = DB::table('shipments')
            ->whereBetween('created_at', [$from, $to]);

        if ($this->isCustomerScoped($user)) {
            $shipmentQuery->where('customer_id', $user->id);
        }

        $shipmentStats = (clone $shipmentQuery)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN is_delayed = 0 THEN 1 ELSE 0 END) as on_time_count,
                SUM(CASE WHEN is_delayed = 1 THEN 1 ELSE 0 END) as delayed_count,
                COALESCE(SUM(converted_value_usd), 0) as total_value_usd,
                COALESCE(SUM(total_volume_cbm), 0) as total_volume_cbm,
                COALESCE(AVG(CASE WHEN is_delayed = 1 THEN delay_hours ELSE NULL END), 0) as avg_delayed_hours
            ')
            ->first();

        $totalCount = (int) ($shipmentStats?->total_count ?? 0);
        $onTimeCount = (int) ($shipmentStats?->on_time_count ?? 0);
        $delayedCount = (int) ($shipmentStats?->delayed_count ?? 0);
        $onTimeRate = $totalCount > 0 ? round(($onTimeCount / $totalCount) * 100, 1) : 0.0;
        $avgDelayHours = round((float) ($shipmentStats?->avg_delayed_hours ?? 0), 1);
        $totalValueUsd = (float) ($shipmentStats?->total_value_usd ?? 0);
        $totalVolumeCbm = (float) ($shipmentStats?->total_volume_cbm ?? 0);

        // Exceptions summary
        $exceptionQuery = DB::table('shipment_exceptions')
            ->join('shipments', 'shipment_exceptions.shipment_id', '=', 'shipments.id')
            ->whereBetween('shipment_exceptions.detected_at', [$from, $to]);

        if ($this->isCustomerScoped($user)) {
            $exceptionQuery->where('shipments.customer_id', $user->id);
        }

        $exceptionStats = (clone $exceptionQuery)
            ->selectRaw('
                COUNT(*) as total_exceptions,
                SUM(CASE WHEN shipment_exceptions.status = "OPEN" THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN shipment_exceptions.status = "INVESTIGATING" THEN 1 ELSE 0 END) as investigating_count,
                SUM(CASE WHEN shipment_exceptions.status = "RESOLVED" THEN 1 ELSE 0 END) as resolved_count
            ')
            ->first();

        $totalExceptions = (int) ($exceptionStats?->total_exceptions ?? 0);
        $openExceptions = (int) ($exceptionStats?->open_count ?? 0);
        $investigatingExceptions = (int) ($exceptionStats?->investigating_count ?? 0);
        $resolvedExceptions = (int) ($exceptionStats?->resolved_count ?? 0);

        return [
            'total_shipments' => $totalCount,
            'on_time_count' => $onTimeCount,
            'delayed_count' => $delayedCount,
            'on_time_rate' => $onTimeRate,
            'avg_delay_hours' => $avgDelayHours,
            'total_value_usd' => $totalValueUsd,
            'total_volume_cbm' => $totalVolumeCbm,
            'total_exceptions' => $totalExceptions,
            'open_exceptions' => $openExceptions,
            'investigating_exceptions' => $investigatingExceptions,
            'resolved_exceptions' => $resolvedExceptions,
            'has_data' => $totalCount > 0 || $totalExceptions > 0,
        ];
    }

    /**
     * High-level operational KPIs for the main dashboard, scoped to the user role.
     *
     * @return array<string, mixed>
     */
    public function getDashboardKpis(User $user): array
    {
        $shipmentQuery = DB::table('shipments');

        if ($this->isCustomerScoped($user)) {
            $shipmentQuery->where('customer_id', $user->id);
        }

        $stats = (clone $shipmentQuery)
            ->selectRaw("
                COUNT(*) as total_shipments,
                SUM(CASE WHEN status = 'IN_TRANSIT' THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN is_delayed = 1 AND status != 'DELIVERED' THEN 1 ELSE 0 END) as delayed_count,
                SUM(CASE WHEN status IN ('BOOKED', 'PICKED_UP', 'IN_TRANSIT') THEN 1 ELSE 0 END) as active_shipments,
                SUM(CASE WHEN status = 'DELIVERED' AND is_delayed = 0 THEN 1 ELSE 0 END) as delivered_on_time,
                COALESCE(SUM(converted_value_usd), 0) as total_value_usd,
                COALESCE(SUM(total_volume_cbm), 0) as total_volume_cbm
            ")
            ->first();

        $totalShipments = (int) ($stats?->total_shipments ?? 0);
        $inTransit = (int) ($stats?->in_transit ?? 0);
        $delivered = (int) ($stats?->delivered ?? 0);
        $delayed = (int) ($stats?->delayed_count ?? 0);
        $activeShipments = (int) ($stats?->active_shipments ?? 0);
        $deliveredOnTime = (int) ($stats?->delivered_on_time ?? 0);
        $onTimeRate = $delivered > 0 ? round(($deliveredOnTime / $delivered) * 100, 1) : 100.0;
        $totalValueUsd = (float) ($stats?->total_value_usd ?? 0);
        $totalVolumeCbm = (float) ($stats?->total_volume_cbm ?? 0);

        $exceptionQuery = DB::table('shipment_exceptions')
            ->join('shipments', 'shipment_exceptions.shipment_id', '=', 'shipments.id')
            ->whereIn('shipment_exceptions.status', ['OPEN', 'INVESTIGATING']);

        if ($this->isCustomerScoped($user)) {
            $exceptionQuery->where('shipments.customer_id', $user->id);
        }

        $openExceptions = (int) $exceptionQuery->count();
        $delayRate = $activeShipments > 0 ? round(($delayed / $activeShipments) * 100, 1) : 0.0;
        $exceptionRate = $activeShipments > 0 ? round(($openExceptions / $activeShipments) * 100, 1) : 0.0;

        return [
            'total_shipments' => $totalShipments,
            'in_transit' => $inTransit,
            'delivered' => $delivered,
            'delayed' => $delayed,
            'active_shipments' => $activeShipments,
            'delivered_on_time' => $deliveredOnTime,
            'on_time_rate' => $onTimeRate,
            'total_value_usd' => $totalValueUsd,
            'total_volume_cbm' => $totalVolumeCbm,
            'open_exceptions' => $openExceptions,
            'delay_rate' => $delayRate,
            'exception_rate' => $exceptionRate,
        ];
    }

    /**
     * Shipments grouped by lifecycle status, ordered canonically and role-scoped.
     *
     * @return array{counts: array<string, int>, total: int, rows: list<array{status: string, label: string, count: int, share: float}>}
     */
    public function getShipmentsByStatus(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = DB::table('shipments');

        if ($from !== null && $to !== null) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        if ($this->isCustomerScoped($user)) {
            $query->where('customer_id', $user->id);
        }

        $rawCounts = $query->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->pluck('count', 'status')
            ->map(fn ($c) => (int) $c)
            ->toArray();

        $canonicalOrder = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
        $labels = [
            'CREATED' => 'Created',
            'BOOKED' => 'Booked',
            'PICKED_UP' => 'Picked up',
            'IN_TRANSIT' => 'In transit',
            'DELIVERED' => 'Delivered',
        ];

        $orderedCounts = [];
        foreach ($canonicalOrder as $statusKey) {
            if (($rawCounts[$statusKey] ?? 0) > 0) {
                $orderedCounts[$statusKey] = (int) $rawCounts[$statusKey];
            }
        }
        foreach ($rawCounts as $statusKey => $cnt) {
            if (! isset($orderedCounts[$statusKey]) && (int) $cnt > 0) {
                $orderedCounts[$statusKey] = (int) $cnt;
            }
        }

        $total = array_sum($orderedCounts);
        $rows = [];
        foreach ($orderedCounts as $statusKey => $cnt) {
            $rows[] = [
                'status' => $statusKey,
                'label' => $labels[$statusKey] ?? ucfirst(strtolower(str_replace('_', ' ', (string) $statusKey))),
                'count' => $cnt,
                'share' => $total > 0 ? round(($cnt / $total) * 100, 1) : 0.0,
            ];
        }

        return [
            'counts' => $orderedCounts,
            'total' => $total,
            'rows' => $rows,
        ];
    }

    /**
     * Shipments grouped by carrier (top N plus an aggregated "Others" row), reusing getCarrierPerformance.
     *
     * @return list<array<string, mixed>>
     */
    public function getShipmentsByCarrier(User $user, int $topLimit = 6, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $rows = $this->getCarrierPerformance($user, $from, $to);
        if ($rows->isEmpty()) {
            return [];
        }

        $total = (int) $rows->sum('total_shipments');
        $topRows = $rows->take($topLimit);

        $result = $topRows->map(function ($row) use ($total): array {
            $count = (int) $row->total_shipments;

            return [
                'id' => (int) $row->carrier_id,
                'name' => (string) $row->carrier_name,
                'code' => (string) $row->carrier_code,
                'count' => $count,
                'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                'is_others' => false,
            ];
        })->values()->all();

        if ($rows->count() > $topLimit) {
            $others = $rows->slice($topLimit);
            $othersCount = (int) $others->sum('total_shipments');
            if ($othersCount > 0) {
                $result[] = [
                    'id' => null,
                    'name' => 'Others',
                    'code' => 'OTH',
                    'count' => $othersCount,
                    'carrier_count' => $others->count(),
                    'share' => $total > 0 ? round(($othersCount / $total) * 100, 1) : 0.0,
                    'is_others' => true,
                ];
            }
        }

        return $result;
    }

    /**
     * 1. On-time performance per carrier.
     */
    public function getCarrierPerformance(User $user, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = DB::table('shipments')
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id');

        if ($from !== null && $to !== null) {
            $query->whereBetween('shipments.created_at', [$from, $to]);
        }

        if ($this->isCustomerScoped($user)) {
            $query->where('shipments.customer_id', $user->id);
        }

        return $query->groupBy('carriers.id', 'carriers.name', 'carriers.code')
            ->selectRaw('
                carriers.id as carrier_id,
                carriers.name as carrier_name,
                carriers.code as carrier_code,
                COUNT(*) as total_shipments,
                SUM(CASE WHEN shipments.is_delayed = 0 THEN 1 ELSE 0 END) as on_time_count,
                SUM(CASE WHEN shipments.is_delayed = 1 THEN 1 ELSE 0 END) as delayed_count,
                ROUND(SUM(CASE WHEN shipments.is_delayed = 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as on_time_rate,
                ROUND(COALESCE(AVG(CASE WHEN shipments.is_delayed = 1 THEN shipments.delay_hours ELSE NULL END), 0), 1) as avg_delay_hours
            ')
            ->orderByDesc('total_shipments')
            ->orderByDesc('on_time_rate')
            ->orderBy('carriers.name')
            ->get();
    }

    /**
     * 2. Average delay hours and performance per lane (origin to destination).
     */
    public function getLaneDelays(User $user, Carbon $from, Carbon $to): Collection
    {
        $query = DB::table('shipments')
            ->join('locations as orig', 'shipments.origin_location_id', '=', 'orig.id')
            ->join('locations as dest', 'shipments.destination_location_id', '=', 'dest.id')
            ->whereBetween('shipments.created_at', [$from, $to]);

        if ($this->isCustomerScoped($user)) {
            $query->where('shipments.customer_id', $user->id);
        }

        return $query->groupBy('orig.id', 'dest.id', 'orig.unlocode', 'orig.name', 'dest.unlocode', 'dest.name')
            ->selectRaw('
                orig.unlocode as origin_code,
                orig.name as origin_name,
                dest.unlocode as destination_code,
                dest.name as destination_name,
                COUNT(*) as total_shipments,
                SUM(CASE WHEN shipments.is_delayed = 1 THEN 1 ELSE 0 END) as delayed_count,
                SUM(CASE WHEN shipments.is_delayed = 0 THEN 1 ELSE 0 END) as on_time_count,
                ROUND(SUM(CASE WHEN shipments.is_delayed = 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as on_time_rate,
                ROUND(COALESCE(AVG(shipments.delay_hours), 0), 1) as avg_delay_hours,
                COALESCE(MAX(shipments.delay_hours), 0) as max_delay_hours
            ')
            ->orderByDesc('avg_delay_hours')
            ->orderByDesc('total_shipments')
            ->get()
            ->map(function ($row) {
                $row->lane_label = "{$row->origin_code} → {$row->destination_code}";

                return $row;
            });
    }

    /**
     * 3. Shipments per week for 12 weeks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWeeklyTrend(User $user, Carbon $referenceDate, int $weekCount = 12): array
    {
        $weeks = [];
        $selectClauses = [];

        for ($i = $weekCount - 1; $i >= 0; $i--) {
            $startOfWeek = $referenceDate->copy()->subWeeks($i)->startOfWeek();
            $endOfWeek = $referenceDate->copy()->subWeeks($i)->endOfWeek();
            $label = $startOfWeek->format('d M');

            $weeks[$i] = [
                'index' => $i,
                'label' => $label,
                'start' => $startOfWeek->format('Y-m-d H:i:s'),
                'end' => $endOfWeek->format('Y-m-d H:i:s'),
            ];

            $s = $startOfWeek->format('Y-m-d H:i:s');
            $e = $endOfWeek->format('Y-m-d H:i:s');

            $selectClauses[] = "SUM(CASE WHEN created_at >= '{$s}' AND created_at <= '{$e}' THEN 1 ELSE 0 END) as w_{$i}_total";
            $selectClauses[] = "SUM(CASE WHEN created_at >= '{$s}' AND created_at <= '{$e}' AND is_delayed = 0 THEN 1 ELSE 0 END) as w_{$i}_ontime";
            $selectClauses[] = "SUM(CASE WHEN created_at >= '{$s}' AND created_at <= '{$e}' AND is_delayed = 1 THEN 1 ELSE 0 END) as w_{$i}_delayed";
        }

        $query = DB::table('shipments');
        if ($this->isCustomerScoped($user)) {
            $query->where('customer_id', $user->id);
        }

        $agg = $query->selectRaw(implode(', ', $selectClauses))->first();

        $result = [];
        for ($i = $weekCount - 1; $i >= 0; $i--) {
            $total = (int) ($agg->{"w_{$i}_total"} ?? 0);
            $onTime = (int) ($agg->{"w_{$i}_ontime"} ?? 0);
            $delayed = (int) ($agg->{"w_{$i}_delayed"} ?? 0);
            $rate = $total > 0 ? round(($onTime / $total) * 100, 1) : 100.0;

            $result[] = [
                'week' => $weeks[$i]['label'],
                'start' => $weeks[$i]['start'],
                'end' => $weeks[$i]['end'],
                'total' => $total,
                'on_time' => $onTime,
                'delayed' => $delayed,
                'on_time_rate' => $rate,
            ];
        }

        return $result;
    }

    /**
     * 4. Exceptions by type (code).
     */
    public function getExceptionsByType(User $user, Carbon $from, Carbon $to): Collection
    {
        $query = DB::table('shipment_exceptions')
            ->join('shipments', 'shipment_exceptions.shipment_id', '=', 'shipments.id')
            ->whereBetween('shipment_exceptions.detected_at', [$from, $to]);

        if ($this->isCustomerScoped($user)) {
            $query->where('shipments.customer_id', $user->id);
        }

        $totalExceptions = (clone $query)->count();

        return $query->groupBy('shipment_exceptions.exception_code')
            ->selectRaw('
                shipment_exceptions.exception_code,
                COUNT(*) as count,
                SUM(CASE WHEN shipment_exceptions.severity = "LOW" THEN 1 ELSE 0 END) as low_count,
                SUM(CASE WHEN shipment_exceptions.severity = "MEDIUM" THEN 1 ELSE 0 END) as medium_count,
                SUM(CASE WHEN shipment_exceptions.severity = "HIGH" THEN 1 ELSE 0 END) as high_count,
                SUM(CASE WHEN shipment_exceptions.status = "OPEN" THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN shipment_exceptions.status = "INVESTIGATING" THEN 1 ELSE 0 END) as investigating_count,
                SUM(CASE WHEN shipment_exceptions.status = "RESOLVED" THEN 1 ELSE 0 END) as resolved_count
            ')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row) use ($totalExceptions) {
                $row->label = ucwords(strtolower(str_replace('_', ' ', $row->exception_code)));
                $row->percentage = $totalExceptions > 0 ? round(($row->count / $totalExceptions) * 100, 1) : 0.0;

                return $row;
            });
    }

    /**
     * 5. Average exception resolution time (hours) overall and per exception code.
     *
     * @return array{overall_avg_hours: ?float, resolved_total: int, by_type: Collection}
     */
    public function getAverageResolutionTime(User $user, Carbon $from, Carbon $to): array
    {
        $driver = DB::connection()->getDriverName();
        // Cross-database resolution calculation in hours
        $diffExpr = $driver === 'sqlite'
            ? '((julianday(shipment_exceptions.resolved_at) - julianday(shipment_exceptions.detected_at)) * 24.0)'
            : '(TIMESTAMPDIFF(SECOND, shipment_exceptions.detected_at, shipment_exceptions.resolved_at) / 3600.0)';

        $baseQuery = DB::table('shipment_exceptions')
            ->join('shipments', 'shipment_exceptions.shipment_id', '=', 'shipments.id')
            ->where('shipment_exceptions.status', 'RESOLVED')
            ->whereNotNull('shipment_exceptions.resolved_at')
            ->whereNotNull('shipment_exceptions.detected_at')
            ->whereBetween('shipment_exceptions.detected_at', [$from, $to]);

        if ($this->isCustomerScoped($user)) {
            $baseQuery->where('shipments.customer_id', $user->id);
        }

        $overallRow = (clone $baseQuery)
            ->selectRaw("COUNT(*) as resolved_total, ROUND(AVG({$diffExpr}), 1) as overall_avg_hours")
            ->first();

        $resolvedTotal = (int) ($overallRow?->resolved_total ?? 0);
        $overallAvgHours = $overallRow?->overall_avg_hours !== null ? (float) $overallRow->overall_avg_hours : null;

        $byType = $baseQuery->groupBy('shipment_exceptions.exception_code')
            ->selectRaw("
                shipment_exceptions.exception_code,
                COUNT(*) as resolved_count,
                ROUND(AVG({$diffExpr}), 1) as avg_hours,
                ROUND(MIN({$diffExpr}), 1) as min_hours,
                ROUND(MAX({$diffExpr}), 1) as max_hours
            ")
            ->orderBy('avg_hours')
            ->get()
            ->map(function ($row) {
                $row->label = ucwords(strtolower(str_replace('_', ' ', $row->exception_code)));

                return $row;
            });

        return [
            'overall_avg_hours' => $overallAvgHours,
            'resolved_total' => $resolvedTotal,
            'by_type' => $byType,
        ];
    }

    /**
     * 6. Top 5 delayed lanes.
     */
    public function getTopDelayedLanes(User $user, Carbon $from, Carbon $to, int $limit = 5): Collection
    {
        $query = DB::table('shipments')
            ->join('locations as orig', 'shipments.origin_location_id', '=', 'orig.id')
            ->join('locations as dest', 'shipments.destination_location_id', '=', 'dest.id')
            ->where('shipments.is_delayed', 1)
            ->whereBetween('shipments.created_at', [$from, $to]);

        if ($this->isCustomerScoped($user)) {
            $query->where('shipments.customer_id', $user->id);
        }

        return $query->groupBy('orig.id', 'dest.id', 'orig.unlocode', 'orig.name', 'dest.unlocode', 'dest.name')
            ->selectRaw('
                orig.unlocode as origin_code,
                orig.name as origin_name,
                dest.unlocode as destination_code,
                dest.name as destination_name,
                COUNT(*) as delayed_shipments,
                ROUND(AVG(shipments.delay_hours), 1) as avg_delay_hours,
                MAX(shipments.delay_hours) as max_delay_hours
            ')
            ->orderByDesc('avg_delay_hours')
            ->orderByDesc('delayed_shipments')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->lane_label = "{$row->origin_code} → {$row->destination_code}";

                return $row;
            });
    }

    /**
     * Stream a CSV export for a specific table.
     */
    public function exportCsv(string $table, User $user, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'cargotrace-analytics-'.preg_replace('/[^a-z0-9_-]/i', '', $table).'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($table, $user, $from, $to) {
            $handle = fopen('php://output', 'w');
            // Byte Order Mark for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            switch ($table) {
                case 'carrier_performance':
                case 'carriers':
                    fputcsv($handle, ['Carrier Code', 'Carrier Name', 'Total Shipments', 'On-Time', 'Delayed', 'On-Time Rate (%)', 'Avg Delay Hours']);
                    $rows = $this->getCarrierPerformance($user, $from, $to);
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r->carrier_code, $r->carrier_name, $r->total_shipments, $r->on_time_count, $r->delayed_count, $r->on_time_rate, $r->avg_delay_hours]);
                    }
                    break;

                case 'lane_delays':
                case 'lanes':
                    fputcsv($handle, ['Origin Code', 'Origin Port', 'Destination Code', 'Destination Port', 'Total Shipments', 'On-Time', 'Delayed', 'On-Time Rate (%)', 'Avg Delay Hours', 'Max Delay Hours']);
                    $rows = $this->getLaneDelays($user, $from, $to);
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r->origin_code, $r->origin_name, $r->destination_code, $r->destination_name, $r->total_shipments, $r->on_time_count, $r->delayed_count, $r->on_time_rate, $r->avg_delay_hours, $r->max_delay_hours]);
                    }
                    break;

                case 'weekly_trend':
                case 'weekly':
                    fputcsv($handle, ['Week Starting', 'Week Ending', 'Total Shipments', 'On-Time', 'Delayed', 'On-Time Rate (%)']);
                    $rows = $this->getWeeklyTrend($user, $to);
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r['start'], $r['end'], $r['total'], $r['on_time'], $r['delayed'], $r['on_time_rate']]);
                    }
                    break;

                case 'exceptions_by_type':
                case 'exceptions':
                    fputcsv($handle, ['Exception Code', 'Category Label', 'Total Count', 'Percentage (%)', 'Low Severity', 'Medium Severity', 'High Severity', 'Open', 'Investigating', 'Resolved']);
                    $rows = $this->getExceptionsByType($user, $from, $to);
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r->exception_code, $r->label, $r->count, $r->percentage, $r->low_count, $r->medium_count, $r->high_count, $r->open_count, $r->investigating_count, $r->resolved_count]);
                    }
                    break;

                case 'resolution_time':
                case 'resolution':
                    fputcsv($handle, ['Exception Code', 'Category Label', 'Resolved Count', 'Avg Resolution Hours', 'Min Resolution Hours', 'Max Resolution Hours']);
                    $data = $this->getAverageResolutionTime($user, $from, $to);
                    foreach ($data['by_type'] as $r) {
                        fputcsv($handle, [$r->exception_code, $r->label, $r->resolved_count, $r->avg_hours, $r->min_hours, $r->max_hours]);
                    }
                    break;

                case 'top_delayed':
                case 'top_delayed_lanes':
                    fputcsv($handle, ['Origin Code', 'Origin Port', 'Destination Code', 'Destination Port', 'Delayed Shipments', 'Avg Delay Hours', 'Max Delay Hours']);
                    $rows = $this->getTopDelayedLanes($user, $from, $to, 10);
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r->origin_code, $r->origin_name, $r->destination_code, $r->destination_name, $r->delayed_shipments, $r->avg_delay_hours, $r->max_delay_hours]);
                    }
                    break;

                default:
                    fputcsv($handle, ['Error', 'Unsupported analytics export table specified.']);
                    break;
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
