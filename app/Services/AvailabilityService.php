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
    public function createBlock(Resource $resource, array $data, User $user): array
    {
        return DB::transaction(function () use ($resource, $data, $user) {
            return $this->createBlocksForRange($resource, $data, $user);
        });
    }

    public function createBlocks(Resource $resource, array $ranges, array $data, User $user): array
    {
        return DB::transaction(function () use ($resource, $ranges, $data, $user) {
            $created = [];
            foreach ($ranges as $range) {
                $payload = array_merge($data, [
                    'starts_at' => $range['start'] ?? null,
                    'ends_at' => $range['end'] ?? null,
                ]);
                $created = array_merge($created, $this->createBlocksForRange($resource, $payload, $user));
            }

            return $created;
        });
    }

    private function createBlocksForRange(Resource $resource, array $data, User $user): array
    {
        $startsAt = Carbon::parse($data['starts_at'], $resource->timezone)->utc();
        $endsAt = Carbon::parse($data['ends_at'], $resource->timezone)->utc();
        $slotLength = (int) $data['slot_length_minutes'];
        $capacity = (int) $data['capacity'];
        $totalMinutes = $startsAt->diffInMinutes($endsAt);
        if ($totalMinutes % $slotLength !== 0) {
            throw ValidationException::withMessages([
                'ends_at' => 'De eindtijd moet precies op een veelvoud van de slotlengte uitkomen.',
            ]);
        }

        $existingSlots = SlotInstance::where('resource_id', $resource->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->lockForUpdate()
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        $cursor = $startsAt->copy();
        while ($cursor->copy()->addMinutes($slotLength)->lte($endsAt)) {
            $slotEnd = $cursor->copy()->addMinutes($slotLength);
            $overlaps = $existingSlots->first(function ($existing) use ($cursor, $slotEnd) {
                return $existing->starts_at->lt($slotEnd) && $existing->ends_at->gt($cursor);
            });

            if (! $overlaps) {
                $slots[] = [
                    'starts_at' => $cursor->copy(),
                    'ends_at' => $slotEnd,
                ];
            }
            $cursor = $slotEnd;
        }

        if (count($slots) === 0) {
            throw ValidationException::withMessages([
                'starts_at' => 'Er bestaan al slots in dit tijdsbereik.',
            ]);
        }

        $blocks = [];
        $current = [$slots[0]];
        for ($i = 1; $i < count($slots); $i++) {
            $prev = $slots[$i - 1];
            $next = $slots[$i];
            if ($prev['ends_at']->equalTo($next['starts_at'])) {
                $current[] = $next;
            } else {
                $blocks[] = $current;
                $current = [$next];
            }
        }
        $blocks[] = $current;

        $createdBlocks = [];
        foreach ($blocks as $group) {
            $blockStartsAt = $group[0]['starts_at']->copy();
            $blockEndsAt = $group[count($group) - 1]['ends_at']->copy();

            $block = AvailabilityBlock::create([
                'resource_id' => $resource->id,
                'created_by' => $user->id,
                'starts_at' => $blockStartsAt,
                'ends_at' => $blockEndsAt,
                'slot_length_minutes' => $slotLength,
                'capacity' => $capacity,
            ]);

            $payload = [];
            foreach ($group as $slot) {
                $payload[] = [
                    'resource_id' => $resource->id,
                    'availability_block_id' => $block->id,
                    'starts_at' => $slot['starts_at'],
                    'ends_at' => $slot['ends_at'],
                    'capacity' => $capacity,
                    'booked_count' => 0,
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($payload, 500) as $chunk) {
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

            $createdBlocks[] = $block;
        }

        return $createdBlocks;
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
    public function updateBlock(AvailabilityBlock $block, array $data, User $user): AvailabilityBlock
    {
        return DB::transaction(function () use ($block, $data, $user) {
            $locked = AvailabilityBlock::with('resource')->whereKey($block->id)->lockForUpdate()->firstOrFail();

            $hasBookings = $locked->slotInstances()
                ->where('booked_count', '>', 0)
                ->lockForUpdate()
                ->exists();

            if ($hasBookings) {
                throw ValidationException::withMessages([
                    'block' => 'Dit blok heeft al boekingen en kan niet worden gewijzigd.',
                ]);
            }

            $startsAt = Carbon::parse($data['starts_at'], $locked->resource->timezone)->utc();
            $endsAt = Carbon::parse($data['ends_at'], $locked->resource->timezone)->utc();
            $slotLength = (int) $data['slot_length_minutes'];
            $capacity = (int) $data['capacity'];
            $totalMinutes = $startsAt->diffInMinutes($endsAt);
            if ($totalMinutes % $slotLength !== 0) {
                throw ValidationException::withMessages([
                    'ends_at' => 'De eindtijd moet precies op een veelvoud van de slotlengte uitkomen.',
                ]);
            }

            $overlap = SlotInstance::where('resource_id', $locked->resource_id)
                ->where('availability_block_id', '!=', $locked->id)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Er bestaan al slots in dit tijdsbereik.',
                ]);
            }

            $locked->slotInstances()->delete();

            $locked->update([
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
                    'resource_id' => $locked->resource_id,
                    'availability_block_id' => $locked->id,
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
                'action' => 'availability.updated',
                'auditable_type' => AvailabilityBlock::class,
                'auditable_id' => $locked->id,
                'metadata' => [
                    'resource_id' => $locked->resource_id,
                    'slot_length_minutes' => $slotLength,
                    'capacity' => $capacity,
                ],
                'created_at' => now(),
            ]);

            return $locked;
        });
    }
}
