<x-mail::message>
<p style="text-align:center; margin:0 0 8px;">
    <img src="{{ $message->embed($logoPath) }}" alt="{{ config('app.name') }}" height="48" style="height:48px; border-radius:8px;">
</p>

# Booking Received

Hi {{ $customerName }}, we've received your booking request. Here are the details:

<x-mail::table>
| &nbsp; | &nbsp; |
| :--- | :--- |
| **Reference** | {{ $reference }} |
| **Venue** | {{ $venueName }} |
| **Court** | {{ $courtName }} |
| **Date** | {{ $date }} |
| **Time** | {{ $timeSlots }} |
| **Duration** | {{ $duration }} |
@if($transactionCode)
| **Payment Ref** | {{ $transactionCode }} |
@endif
| **Total** | ₱{{ $total }} |
</x-mail::table>

<p style="text-align:center; margin:24px 0 6px;">
    <img src="{{ $message->embedData($qrPng, 'booking-qr.png', 'image/png') }}" alt="Booking verification QR code" width="200" height="200" style="border:1px solid #e5e7eb; border-radius:8px; background:#ffffff;">
</p>
<p style="text-align:center; font-size:12px; color:#6b7280; margin:0 0 8px;">
    Show this QR to staff to verify your booking.
</p>

<x-mail::panel>
⚠️ **Your slot is not yet guaranteed.** This booking is pending review and staff may cancel it at any time. You'll receive a confirmation email once it's approved — or log in anytime to track your booking status.
</x-mail::panel>

<x-mail::button :url="$trackUrl">
Track My Booking
</x-mail::button>

Thanks for booking with us,<br>
{{ config('app.name') }}
</x-mail::message>
