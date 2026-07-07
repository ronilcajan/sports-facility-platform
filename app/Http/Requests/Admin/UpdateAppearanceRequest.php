<?php

namespace App\Http\Requests\Admin;

use App\Enums\SiteTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppearanceRequest extends FormRequest
{
    /**
     * The admin route group already gates access by role, so authorization
     * here is unconditional.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::enum(SiteTheme::class)],
        ];
    }
}
