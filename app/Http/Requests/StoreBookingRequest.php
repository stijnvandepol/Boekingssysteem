<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_instance_id' => ['required', 'integer', 'exists:slot_instances,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'turnstile_token' => ['nullable', 'string'],
        ];
    }
}
