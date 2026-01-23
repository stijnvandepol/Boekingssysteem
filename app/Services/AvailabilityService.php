<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AvailabilityBlock;
use App\Models\Resource;
use App\Models\SlotInstance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    public function createBlock(Resource $resource, array $data, User $user): AvailabilityBlock
    {
        return DB::transaction(function () use ($resource, $data, $user) {
            $startsAt = Carbon::parse($data['starts_at'], $resource->timezone)->utc();
            $endsAt = Carbon::parse($data['ends_at'], $resource->timezone)->utc();
            $slotLength = (int) $data['slot_length_minutes'];
            $capacity = (int) $data['capacity'];

            $overlap = SlotInstance::where('resource_id', $resource->id)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Er bestaan al slots in dit tijdsbereik.',
                ]);
            }

            $block = AvailabilityBlock::create([
                'resource_id' => $resource->id,
                'created_by' => $user->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'slot_length_minutes' => $slotLength,
                'capacity' => $capacity,
            ]);

            $slots = [];
            $cursor = $startsAt->copy();
            while ($cursor->copy()->addMinutes($slotLength)->lte($endsAt)) {
                $slotEnd = $cursor->copy()->addMinutes($slotLength);
                $slots[] = [
                    'resource_id' => $resource->id,
                    'availability_block_id' => $block->id,
                    'starts_at' => $cursor->copy(),
                    'ends_at' => $slotEnd,
                    'capacity' => $capacity,
                    'booked_count' => 0,
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $cursor = $slotEnd;
            }

            if (count($slots) === 0) {
                throw ValidationException::withMessages([
                    'ends_at' => 'De eindtijd levert geen volledige slots op.',
                ]);
            }

            foreach (array_chunk($slots, 500) as $chunk) {
                SlotInstance::insert($chunk);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'availability.created',
                'auditable_type' => AvailabilityBlock::class,
                'auditable_id' => $block->id,
                'metadata' => [
                    'resource_id' => $resource->id,
                    'slot_length_minutes' => $slotLength,
                    'capacity' => $capacity,
                ],
                'created_at' => now(),
            ]);

            return $block;
        });
    }

    public function deleteBlock(AvailabilityBlock $block, User $user): void
    {
        DB::transaction(function () use ($block, $user) {
            $locked = AvailabilityBlock::whereKey($block->id)->lockForUpdate()->firstOrFail();

            $hasBookings = $locked->slotInstances()
                ->where('booked_count', '>', 0)
                ->lockForUpdate()
                ->exists();

            if ($hasBookings) {
                throw ValidationException::withMessages([
                    'block' => 'Dit blok heeft al boekingen en kan niet worden verwijderd.',
                ]);
            }

            $locked->slotInstances()->delete();
            $locked->delete();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'availability.deleted',
                'auditable_type' => AvailabilityBlock::class,
                'auditable_id' => $block->id,
                'metadata' => [
                    'resource_id' => $block->resource_id,
                ],
                'created_at' => now(),
            ]);
        });
    }
}
