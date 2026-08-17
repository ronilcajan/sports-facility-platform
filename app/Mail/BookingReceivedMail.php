<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\SiteSetting;
use App\Support\QrCodePng;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Deliberately NOT ShouldQueue. This application is hosted on shared hosting
 * with no queue worker, so a queued mailable would sit in the jobs table and
 * never reach the customer. The caller wraps the send in a try/catch, so an
 * SMTP failure degrades to a logged warning rather than a failed booking.
 */
class BookingReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing('court.venue');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Received — '.$this->referenceCode(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $court = $this->booking->court;
        $slotCount = count($this->booking->time_slots ?? []);
        $minutes = $slotCount * ($court?->slot_duration_minutes ?? 60);
        $hours = $minutes / 60;

        return new Content(
            markdown: 'emails.bookings.received',
            with: [
                'reference' => $this->referenceCode(),
                'customerName' => $this->booking->name,
                'venueName' => $court?->venue?->name ?? 'Sports Facility',
                'courtName' => $court?->name ?? 'Court',
                'date' => $this->booking->date->toDateString(),
                'timeSlots' => implode(', ', $this->booking->time_slots ?? []),
                'duration' => ($hours === floor($hours) ? (int) $hours : $hours).' '.($hours === 1.0 ? 'hour' : 'hours'),
                'total' => number_format((float) $this->booking->total_price, 2),
                'transactionCode' => $this->booking->transaction_code,
                'trackUrl' => route('dashboard'),
                'qrPng' => QrCodePng::generate(url('/staff/bookings/'.$this->booking->id)),
                'logoPath' => $this->logoPath(),
            ],
        );
    }

    /**
     * Human-friendly booking reference code.
     */
    private function referenceCode(): string
    {
        return 'DY-RESRV-'.str_pad((string) $this->booking->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Filesystem path to the current site logo (uploaded, or the bundled default).
     */
    private function logoPath(): string
    {
        $path = SiteSetting::get('site_logo');

        return $path ? storage_path('app/public/'.$path) : public_path('logo.jpg');
    }
}
