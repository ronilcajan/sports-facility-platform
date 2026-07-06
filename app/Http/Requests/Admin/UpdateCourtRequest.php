<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCourtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Route-model-bound court authorization is handled by the controller's
     * `authorizeResource`; this guards the validated payload.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('court')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sport_type' => ['required', new Enum(SportType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', new Enum(CourtStatus::class)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'slot_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
