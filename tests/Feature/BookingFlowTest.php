<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\SlotInstance;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_capacity_is_enforced(): void
    {
        $resource = Resource::factory()->create();
        $slot = SlotInstance::create([
            'resource_id' => $resource->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'capacity' => 1,
            'booked_count' => 0,
            'status' => 'open',
        ]);

        $service = app(BookingService::class);
        $service->book($slot, ['name' => 'A', 'email' => 'a@example.com', 'phone' => '0612345678'], 'key-1', 30);

        $this->expectException(ValidationException::class);
        $service->book($slot->fresh(), ['name' => 'B', 'email' => 'b@example.com', 'phone' => '0612345678'], 'key-2', 30);
    }

    public function test_idempotency_returns_same_booking(): void
    {
        $resource = Resource::factory()->create();
        $slot = SlotInstance::create([
            'resource_id' => $resource->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'capacity' => 2,
            'booked_count' => 0,
            'status' => 'open',
        ]);

        $service = app(BookingService::class);
        $first = $service->book($slot, ['name' => 'A', 'email' => 'a@example.com', 'phone' => '0612345678'], 'idempotent-1', 30);
        $second = $service->book($slot->fresh(), ['name' => 'A', 'email' => 'a@example.com', 'phone' => '0612345678'], 'idempotent-1', 30);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Booking::count());
    }
}
