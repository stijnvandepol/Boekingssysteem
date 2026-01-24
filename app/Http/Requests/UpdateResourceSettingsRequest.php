<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'default_slot_length_minutes' => [
                'required',
                'integer',
                Rule::in(config('booking.allowed_slot_lengths')),
            ],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:50'],
<<<<<<< HEAD
            'min_notice_hours' => ['required', 'numeric', 'min:0', 'max:24'],
=======
>>>>>>> parent of 1568204 (errors fixed)
            'is_active' => ['required', 'boolean'],
        ];
    }
}
