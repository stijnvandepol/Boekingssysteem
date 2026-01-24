<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'slot_length_minutes' => [
                'required',
                'integer',
                Rule::in(config('booking.allowed_slot_lengths')),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
