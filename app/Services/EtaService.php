<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Shipment;

class EtaService
{
    /**
     * Evaluate and update delay metrics for a given shipment.
     */
    public function evaluateDelay(Shipment $shipment): Shipment
    {
        $lowThreshold = (float) config('cargotrace.delay.low_hours', 6);
        $mediumThreshold = (float) config('cargotrace.delay.medium_hours', 24);

        $wasDelayed = (bool) $shipment->is_delayed;
        $previousSeverity = $shipment->delay_severity;

        if (! $shipment->estimated_arrival_at || ! $shipment->expected_arrival_at) {
            $shipment->is_delayed = false;
            $shipment->delay_hours = 0;
            $shipment->delay_severity = 'NONE';
            $shipment->save();

            return $shipment;
        }

        $estimated = $shipment->estimated_arrival_at;
        $expected = $shipment->expected_arrival_at;

        if ($estimated->gt($expected)) {
            // Calculate delay hours
            $diffInSeconds = $expected->diffInSeconds($estimated, false);
            $delayHours = round(abs($diffInSeconds) / 3600, 2);

            $severity = match (true) {
                $delayHours <= 0 => 'NONE',
                $delayHours <= $lowThreshold => 'LOW',
                $delayHours <= $mediumThreshold => 'MEDIUM',
                default => 'HIGH',
            };

            $shipment->is_delayed = $delayHours > 0;
            $shipment->delay_hours = $delayHours;
            $shipment->delay_severity = $severity;
        } else {
            $shipment->is_delayed = false;
            $shipment->delay_hours = 0;
            $shipment->delay_severity = 'NONE';
        }

        $shipment->save();

        // Send customer notification when shipment becomes delayed or severity escalates
        if ($shipment->is_delayed && (! $wasDelayed || $previousSeverity !== $shipment->delay_severity)) {
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'type' => 'SHIPMENT_DELAY_ALERT',
                'title' => "Shipment {$shipment->tracking_number} Delayed ({$shipment->delay_severity})",
                'message' => "Estimated arrival has been delayed by {$shipment->delay_hours} hours. New estimated arrival: {$shipment->estimated_arrival_at?->format('Y-m-d H:i')}.",
                'read_at' => null,
            ]);
        }

        return $shipment;
    }
}

