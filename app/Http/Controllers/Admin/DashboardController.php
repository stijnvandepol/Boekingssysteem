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
            'recentBookings' => $recentBookings,
        ]);
    }
}
