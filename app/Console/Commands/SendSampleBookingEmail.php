<?php

namespace App\Console\Commands;

use App\Mail\BookingReceivedMail;
use App\Models\Booking;
use App\Support\QrCodePng;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-sample-booking-email {email} {--booking=} {--preview=}')]
#[Description('Send a sample booking-received email (with QR) to an address, and optionally write a browser-viewable HTML preview.')]
class SendSampleBookingEmail extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $booking = $this->option('booking')
            ? Booking::with('court.venue')->find($this->option('booking'))
            : Booking::with('court.venue')->first();

        if (! $booking) {
            $this->error('No booking found to build the sample email. Seed the database first.');

            return self::FAILURE;
        }

        Mail::to($email)->send(new BookingReceivedMail($booking));
        $this->info("Sample booking email dispatched to {$email} via the '".config('mail.default')."' mailer (booking #{$booking->id}).");

        if (config('mail.default') === 'log') {
            $this->warn('Mailer is "log" — the email was written to storage/logs, NOT delivered. Configure SMTP to deliver to a real inbox.');
        }

        // Optionally write a browser-viewable preview (CID QR swapped for an inline data URI).
        if ($previewPath = $this->option('preview')) {
            $html = (new BookingReceivedMail($booking))->render();
            $dataUri = 'data:image/png;base64,'.base64_encode(
                QrCodePng::generate(url('/staff/bookings/'.$booking->id), 320)
            );
            $html = preg_replace('/src="cid:[^"]+"/', 'src="'.$dataUri.'"', $html);
            file_put_contents($previewPath, $html);
            $this->info("Preview written to {$previewPath}");
        }

        return self::SUCCESS;
    }
}
