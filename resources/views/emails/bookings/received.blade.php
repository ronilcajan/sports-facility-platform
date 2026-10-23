<x-mail::message>
<p style="text-align:center; margin:0 0 8px;">
    <img src="{{ $message->embed($logoPath) }}" alt="{{ config('app.name') }}" height="48" style="height:48px; border-radius:8px;">
</p>

# Booking Received — Pending Approval

Hi {{ $customerName }},

Thank you for your booking! We have received your reservation request and it is currently **pending approval from the Super Admin**.

<x-mail::panel>
⏳ **Important Notice: Your booking is NOT yet approved.**

Your reservation request has been submitted and is currently awaiting review and approval from our Super Admin. **Please wait for official confirmation before visiting the facility.** You will receive a second email as soon as your booking is approved.
</x-mail::panel>

### Reservation Details

<x-mail::table>
| &nbsp; | &nbsp; |
| :--- | :--- |
| **Reference** | {{ $reference }} |
| **Status** | ⏳ Pending Approval |
| **Location** | {{ $venueName }} |
| **Court** | {{ $courtName }} |
| **Date** | {{ $date }} |
| **Time** | {{ $timeSlots }} |
| **Duration** | {{ $duration }} |
@if($transactionCode)
| **Payment Ref** | {{ $transactionCode }} |
@endif
| **Total Amount** | ₱{{ $total }} |
</x-mail::table>

You can track the live status of your booking anytime by visiting your customer dashboard:

<x-mail::button :url="$trackUrl">
Track My Booking
</x-mail::button>

Thanks for choosing {{ config('app.name') }},<br>
{{ config('app.name') }}
</x-mail::message>
