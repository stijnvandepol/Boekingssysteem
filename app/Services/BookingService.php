<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\SlotInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function book(SlotInstance $slot, array $guest, string $idempotencyKey): Booking
    {
        return DB::transaction(function () use ($slot, $guest, $idempotencyKey) {
            $existing = Booking::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $lockedSlot = SlotInstance::whereKey($slot->id)->lockForUpdate()->firstOrFail();
            if ($lockedSlot->status !== 'open') {
                throw ValidationException::withMessages([
                    'slot_instance_id' => 'Dit slot is niet beschikbaar.',
                ]);
            }

            if ($lockedSlot->booked_count >= $lockedSlot->capacity) {
                throw ValidationException::withMessages([
                    'slot_instance_id' => 'Dit slot is volgeboekt.',
                ]);
            }

            $booking = Booking::create([
                'resource_id' => $lockedSlot->resource_id,
                'slot_instance_id' => $lockedSlot->id,
                'status' => 'confirmed',
                'idempotency_key' => $idempotencyKey,
                'total_guests' => 1,
                'booked_at' => now(),
            ]);

            BookingGuest::create([
                'booking_id' => $booking->id,
                'name' => $guest['name'],
                'email' => $guest['email'],
                'phone' => $guest['phone'] ?? null,
            ]);

            $lockedSlot->increment('booked_count');
            $lockedSlot->refresh();
            if ($lockedSlot->booked_count >= $lockedSlot->capacity) {
                $lockedSlot->update(['status' => 'closed']);
            }

            AuditLog::create([
                'user_id' => null,
                'action' => 'booking.created',
                'auditable_type' => Booking::class,
                'auditable_id' => $booking->id,
                'metadata' => [
                    'slot_instance_id' => $lockedSlot->id,
                ],
                'created_at' => now(),
            ]);

            return $booking;
        });
    }
}
