<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\ShipmentDelayNotification;

class EtaService
{
    /**
     * Severity hierarchy ranks for escalation detection.
     *
     * @var array<string, int>
     */
    protected const SEVERITY_RANKS = [
        'NONE' => 0,
        'LOW' => 1,
        'MEDIUM' => 2,
        'HIGH' => 3,
    ];

    /**
     * Evaluate and update delay metrics for a given shipment.
     */
    public function evaluateDelay(Shipment $shipment): Shipment
    {
        $lowThreshold = (float) (Setting::get('delay_low_hours') ?? config('cargotrace.delay.low_hours', 6));
        $mediumThreshold = (float) (Setting::get('delay_medium_hours') ?? config('cargotrace.delay.medium_hours', 24));

        $wasDelayed = (bool) $shipment->is_delayed;
        $previousSeverity = $shipment->delay_severity ?? 'NONE';
        $previousDelayHours = (float) ($shipment->delay_hours ?? 0);

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

        // Check if shipment became delayed or its severity increased
        $prevRank = self::SEVERITY_RANKS[$previousSeverity] ?? 0;
        $newRank = self::SEVERITY_RANKS[$shipment->delay_severity] ?? 0;

        $becameDelayed = ! $wasDelayed && $shipment->is_delayed;
        $severityEscalated = $wasDelayed && $shipment->is_delayed && ($newRank > $prevRank);

        // Never send delay notifications for delivered shipments
        if ($shipment->status !== 'DELIVERED' && ($becameDelayed || $severityEscalated)) {
            $this->notifyRelevantUsersAndAudit($shipment, $becameDelayed, $previousSeverity, $previousDelayHours, $wasDelayed);
        }

        return $shipment;
    }

    /**
     * Dispatch in-app and optional email notifications idempotently to relevant users,
     * and write a system actor audit log entry.
     */
    protected function notifyRelevantUsersAndAudit(
        Shipment $shipment,
        bool $becameDelayed,
        string $previousSeverity,
        float $previousDelayHours,
        bool $wasDelayed
    ): void {
        // Collect relevant recipients: customer owner and operators
        $recipients = collect();

        if ($shipment->customer_id) {
            $customer = $shipment->customer ?? User::find($shipment->customer_id);
            if ($customer && $customer->is_active) {
                $recipients->push($customer);
            }
        }

        $operators = User::where('role', 'operator')->where('is_active', true)->get();
        $recipients = $recipients->merge($operators)->unique('id')->values();

        $title = "Shipment {$shipment->tracking_number} Delayed ({$shipment->delay_severity})";
        $estimatedFormatted = $shipment->estimated_arrival_at?->format('Y-m-d H:i') ?? 'N/A';
        $message = "Estimated arrival has been delayed by {$shipment->delay_hours} hours. New estimated arrival: {$estimatedFormatted}.";

        $anyCreated = false;

        foreach ($recipients as $recipient) {
            $notif = Notification::firstOrCreate(
                [
                    'user_id' => $recipient->id,
                    'shipment_id' => $shipment->id,
                    'type' => 'SHIPMENT_DELAY_ALERT',
                    'title' => $title,
                ],
                [
                    'message' => $message,
                    'read_at' => null,
                ]
            );

            if ($notif->wasRecentlyCreated) {
                $anyCreated = true;
                $recipient->notify(new ShipmentDelayNotification(
                    $shipment,
                    $shipment->delay_severity,
                    (float) $shipment->delay_hours,
                    $previousSeverity
                ));
            }
        }

        // Only log audit trail once when notifications are newly issued or escalated
        if ($anyCreated) {
            AuditLog::create([
                'user_id' => null, // "system" actor
                'action' => $becameDelayed ? 'SHIPMENT_DELAY_DETECTED' : 'SHIPMENT_DELAY_ESCALATED',
                'auditable_type' => Shipment::class,
                'auditable_id' => $shipment->id,
                'old_values' => [
                    'is_delayed' => $wasDelayed,
                    'delay_severity' => $previousSeverity,
                    'delay_hours' => $previousDelayHours,
                ],
                'new_values' => [
                    'is_delayed' => true,
                    'delay_severity' => $shipment->delay_severity,
                    'delay_hours' => (float) $shipment->delay_hours,
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
            ]);
        }
    }
}
