<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Shipment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class FreeTimeService
{
    /**
     * Compute free days remaining or days over the limit after arrival or delivery.
     * Deterministic, no money calculations, wording strictly "estimated".
     *
     * @param  CarbonInterface|null  $asOf  Reference date for boundary evaluation (defaults to now)
     * @param  int|null  $freeDaysAllowed  Override setting limit (defaults to setting/config)
     * @return array{
     *     has_arrived: bool,
     *     arrival_at: ?Carbon,
     *     free_days_allowed: int,
     *     free_time_ends_at: ?Carbon,
     *     days_elapsed: int,
     *     days_remaining: int,
     *     days_over: int,
     *     is_over_limit: bool,
     *     badge_label: string,
     *     badge_type: string,
     *     description: string
     * }
     */
    public function calculate(Shipment $shipment, ?CarbonInterface $asOf = null, ?int $freeDaysAllowed = null): array
    {
        $now = $asOf ? Carbon::instance($asOf) : Carbon::now();
        $allowedDays = $freeDaysAllowed ?? (int) (Setting::get('free_days_after_arrival') ?? config('cargotrace.free_time.days', 5));

        // Determine arrival event timestamp from shipment actual_arrival_at or delivery event
        $arrivalAt = null;
        if ($shipment->actual_arrival_at) {
            $arrivalAt = Carbon::instance($shipment->actual_arrival_at);
        } elseif ($shipment->status === 'DELIVERED') {
            $deliveryEvent = $shipment->relationLoaded('trackingEvents')
                ? $shipment->trackingEvents->firstWhere('status', 'DELIVERED')
                : $shipment->trackingEvents()->where('status', 'DELIVERED')->first();

            $arrivalAt = $deliveryEvent?->occurred_at
                ? Carbon::instance($deliveryEvent->occurred_at)
                : Carbon::instance($shipment->updated_at);
        }

        if (! $arrivalAt) {
            return [
                'has_arrived' => false,
                'arrival_at' => null,
                'free_days_allowed' => $allowedDays,
                'free_time_ends_at' => null,
                'days_elapsed' => 0,
                'days_remaining' => $allowedDays,
                'days_over' => 0,
                'is_over_limit' => false,
                'badge_label' => __('Pending arrival'),
                'badge_type' => 'neutral',
                'description' => __('Estimated free-time countdown begins upon confirmed port arrival.'),
            ];
        }

        $freeTimeEndsAt = $arrivalAt->copy()->addDays($allowedDays);

        // Deterministic 24-hour day periods
        $elapsedSeconds = $arrivalAt->diffInSeconds($now, false);
        $daysElapsed = $elapsedSeconds > 0 ? (int) floor($elapsedSeconds / 86400) : 0;

        if ($daysElapsed <= $allowedDays) {
            $daysRemaining = max(0, $allowedDays - $daysElapsed);
            $daysOver = 0;
            $isOverLimit = false;

            if ($daysRemaining === 0) {
                $badgeLabel = __('Estimated 0 days remaining (last free day)');
                $badgeType = 'warning';
                $desc = __('Estimated last day of standard terminal free time before detention.');
            } else {
                $badgeLabel = $daysRemaining === 1
                    ? __('Estimated :days day free time remaining', ['days' => $daysRemaining])
                    : __('Estimated :days days free time remaining', ['days' => $daysRemaining]);
                $badgeType = 'safe';
                $desc = $daysRemaining === 1
                    ? __('Estimated :days day remaining within standard terminal dwell allowance.', ['days' => $daysRemaining])
                    : __('Estimated :days days remaining within standard terminal dwell allowance.', ['days' => $daysRemaining]);
            }
        } else {
            $daysRemaining = 0;
            $daysOver = $daysElapsed - $allowedDays;
            $isOverLimit = true;

            $badgeLabel = $daysOver === 1
                ? __('Estimated :days day over free time limit', ['days' => $daysOver])
                : __('Estimated :days days over free time limit', ['days' => $daysOver]);
            $badgeType = 'danger';
            $desc = $daysOver === 1
                ? __('Estimated :days day exceeded past standard terminal free-time allowance.', ['days' => $daysOver])
                : __('Estimated :days days exceeded past standard terminal free-time allowance.', ['days' => $daysOver]);
        }

        return [
            'has_arrived' => true,
            'arrival_at' => $arrivalAt,
            'free_days_allowed' => $allowedDays,
            'free_time_ends_at' => $freeTimeEndsAt,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'days_over' => $daysOver,
            'is_over_limit' => $isOverLimit,
            'badge_label' => $badgeLabel,
            'badge_type' => $badgeType,
            'description' => $desc,
        ];
    }
}
