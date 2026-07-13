<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $action = 'created'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $courtName = $this->booking->court?->name ?? 'Court';

        return [
            'booking_id' => $this->booking->id,
            'court_id' => $this->booking->court_id,
            'court_name' => $courtName,
            'customer_name' => $this->booking->name,
            'action' => $this->action,
            'status' => $this->booking->status,
            'message' => "Booking #{$this->booking->id} for {$courtName} by {$this->booking->name} was {$this->action}.",
        ];
    }
}
