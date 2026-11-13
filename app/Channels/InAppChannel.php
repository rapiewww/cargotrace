<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class InAppChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // In-app notifications are persisted via App\Models\Notification in EtaService
    }
}
