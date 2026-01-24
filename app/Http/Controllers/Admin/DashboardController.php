<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\SlotInstance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        $weekStart = Carbon::now($resource->timezone)->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(7)->endOfDay();

        $blocks = AvailabilityBlock::where('resource_id', $resource->id)
            ->whereBetween('starts_at', [$weekStart->copy()->utc(), $weekEnd->copy()->utc()])
            ->orderBy('starts_at')
            ->get();

        $slots = SlotInstance::where('resource_id', $resource->id)
            ->whereBetween('starts_at', [$weekStart->copy()->utc(), $weekEnd->copy()->utc()])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($slot) => $slot->starts_at->setTimezone($resource->timezone)->toDateString());

<<<<<<< HEAD
        $blocksByDate = $blocks->groupBy(fn ($block) => $block->starts_at->setTimezone($resource->timezone)->toDateString());

        $weekDays = collect(range(0, 6))
            ->map(fn ($offset) => $weekStart->copy()->addDays($offset));

        $calendarStartHour = 8;
        $calendarEndHour = 20;
        $calendarHours = collect(range($calendarStartHour, $calendarEndHour - 1));

        $now = Carbon::now($resource->timezone);
        $minute = (int) (ceil($now->minute / 15) * 15);
        if ($minute === 60) {
            $now->addHour()->minute(0);
        } else {
            $now->minute($minute);
        }
        $defaultStart = $now->copy()->second(0);
        $defaultEnd = $defaultStart->copy()->addHour();

=======
>>>>>>> parent of 1568204 (errors fixed)
        $recentBookings = Booking::with('slotInstance')
            ->where('resource_id', $resource->id)
            ->orderByDesc('booked_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'resource' => $resource,
            'weekStart' => $weekStart,
            'blocks' => $blocks,
            'slotsByDate' => $slots,
<<<<<<< HEAD
            'weekDays' => $weekDays,
            'calendarHours' => $calendarHours,
            'calendarStartHour' => $calendarStartHour,
            'calendarEndHour' => $calendarEndHour,
            'defaultStart' => $defaultStart,
            'defaultEnd' => $defaultEnd,
=======
>>>>>>> parent of 1568204 (errors fixed)
            'recentBookings' => $recentBookings,
        ]);
    }
}
