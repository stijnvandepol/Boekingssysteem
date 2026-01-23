<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $durations = config('booking.allowed_durations', config('booking.allowed_slot_lengths'));

        return [
            'slot_instance_id' => ['required', 'integer', 'exists:slot_instances,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'duration_minutes' => ['required', 'integer', Rule::in($durations)],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'turnstile_token' => ['nullable', 'string'],
        ];
    }
}
