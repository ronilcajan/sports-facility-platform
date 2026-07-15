<?php

namespace App\Http\Requests\Site;

use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'court_id' => ['required', 'exists:courts,id'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'array', 'min:1'],
            'time.*' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'transaction_code' => ['nullable', 'string', 'max:100'], // payment reference no. for staff to verify — optional but preferred
            'receipt' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB, optional — payment receipt is preferred but not required
        ];
    }

    /**
     * Configure the validator instance with double-booking checks.
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $courtId = $this->input('court_id');
            $date = $this->input('date');
            $requestedSlots = $this->input('time', []);

            if (! $courtId || ! $date || empty($requestedSlots)) {
                return;
            }

            // Find all existing bookings for this court and date
            $existingBookings = Booking::query()
                ->where('court_id', $courtId)
                ->where('date', $date)
                ->where('status', '!=', 'cancelled')
                ->get();

            // Check if any requested slot is already booked
            foreach ($existingBookings as $booking) {
                $bookedSlots = $booking->time_slots ?? [];
                foreach ($requestedSlots as $slot) {
                    if (in_array($slot, $bookedSlots)) {
                        $validator->errors()->add('time', "The slot '{$slot}' is already booked.");

                        return;
                    }
                }
            }
        });
    }
}
