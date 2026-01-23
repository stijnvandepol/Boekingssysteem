<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\SlotInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class BookingService
{
    public function book(SlotInstance $slot, array $guest, string $idempotencyKey, ?int $durationMinutes = null): Booking
    {
        return DB::transaction(function () use ($slot, $guest, $idempotencyKey, $durationMinutes) {
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

            $resource = $slot->resource ?? $lockedSlot->resource;
            $minNotice = (int) ($resource?->min_notice_minutes ?? 0);
            $nowUtc = now()->utc()->addMinutes($minNotice);
            if ($lockedSlot->starts_at->lt($nowUtc)) {
                throw ValidationException::withMessages([
                    'slot_instance_id' => 'Dit slot is niet meer beschikbaar.',
                ]);
            }

            if ($lockedSlot->booked_count >= $lockedSlot->capacity) {
                throw ValidationException::withMessages([
                    'slot_instance_id' => 'Dit slot is volgeboekt.',
                ]);
            }

            $slotLength = $lockedSlot->starts_at->diffInMinutes($lockedSlot->ends_at);
            $finalDuration = $durationMinutes ?? $slotLength;
            $allowedDurations = config('booking.allowed_durations', config('booking.allowed_slot_lengths'));
            if (! in_array($finalDuration, $allowedDurations, true)) {
                throw ValidationException::withMessages([
                    'duration_minutes' => 'De gekozen duur is ongeldig.',
                ]);
            }
            if ($finalDuration > $slotLength) {
                throw ValidationException::withMessages([
                    'duration_minutes' => 'De gekozen duur past niet in dit slot.',
                ]);
            }

            $booking = Booking::create([
                'resource_id' => $lockedSlot->resource_id,
                'slot_instance_id' => $lockedSlot->id,
                'status' => 'confirmed',
                'idempotency_key' => $idempotencyKey,
                'total_guests' => 1,
                'booked_at' => now(),
                'duration_minutes' => $finalDuration,
            ]);

            BookingGuest::create([
                'booking_id' => $booking->id,
                'name' => $guest['name'],
                'email' => $guest['email'] ?? '',
                'phone' => $guest['phone'] ?? '',
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

    public function cancel(Booking $booking, User $user): Booking
    {
        return DB::transaction(function () use ($booking, $user) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($lockedBooking->status === 'cancelled') {
                return $lockedBooking;
            }

            $lockedSlot = SlotInstance::whereKey($lockedBooking->slot_instance_id)->lockForUpdate()->firstOrFail();
            if ($lockedSlot->booked_count > 0) {
                $lockedSlot->decrement('booked_count');
            }

            if ($lockedSlot->status === 'closed' && $lockedSlot->booked_count < $lockedSlot->capacity) {
                $lockedSlot->update(['status' => 'open']);
            }

            $lockedBooking->update([
                'status' => 'cancelled',
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'booking.cancelled',
                'auditable_type' => Booking::class,
                'auditable_id' => $lockedBooking->id,
                'metadata' => [
                    'slot_instance_id' => $lockedSlot->id,
                ],
                'created_at' => now(),
            ]);

            return $lockedBooking;
        });
    }
}
