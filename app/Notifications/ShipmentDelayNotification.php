<?php

namespace App\Notifications;

use App\Channels\InAppChannel;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentDelayNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Shipment $shipment,
        public string $severity,
        public float $delayHours,
        public ?string $previousSeverity = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, class-string|string>
     */
    public function via(object $notifiable): array
    {
        $channels = [InAppChannel::class];

        if (config('cargotrace.notifications.email', false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("CargoTrace: Shipment {$this->shipment->tracking_number} Delay Alert ({$this->severity})")
            ->greeting("Hello {$notifiable->name},")
            ->line("An operational delay has been recorded for ocean container consignment {$this->shipment->tracking_number}.")
            ->line("Current delay severity: {$this->severity} (+{$this->delayHours} hours).")
            ->line("Updated estimated arrival: {$this->shipment->estimated_arrival_at?->format('Y-m-d H:i')} UTC.")
            ->action('View shipment details', url("/shipments/{$this->shipment->id}"))
            ->line('This is an automated operational advisory from CargoTrace.');
    }
}
