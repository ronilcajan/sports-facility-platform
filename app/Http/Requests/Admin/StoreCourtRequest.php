<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use App\Models\Court;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCourtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Court::class) ?? false;
    }

    /**
     * Venue admins always file courts under their own venue, so the form need not send it.
     */
    protected function prepareForValidation(): void
    {
        if ($this->user()?->isVenueScopedAdmin()) {
            $this->merge(['venue_id' => $this->user()->venue_id]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Required: the public site is venue-first, so a venue-less court renders nowhere.
            'venue_id' => ['required', 'exists:venues,id'],
            'name' => ['required', 'string', 'max:255'],
            'sport_type' => ['required', new Enum(SportType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', new Enum(CourtStatus::class)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'slot_prices' => ['nullable', 'array'],
            'slot_prices.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'slot_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:5120'],
        ];
    }
}
