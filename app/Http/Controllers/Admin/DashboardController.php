<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\SlotInstance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        $weekOffset = (int) $request->query('week', 0);
        $now = $this->currentTime($resource->timezone);
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset);
        // Inclusief maandag t/m zondag van de gekozen week
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $blocks = AvailabilityBlock::where('resource_id', $resource->id)
            ->whereBetween('starts_at', [$weekStart->copy()->utc(), $weekEnd->copy()->utc()])
            ->orderBy('starts_at')
            ->get();

        $slots = SlotInstance::where('resource_id', $resource->id)
            ->whereBetween('starts_at', [$weekStart->copy()->utc(), $weekEnd->copy()->utc()])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($slot) => $slot->starts_at->setTimezone($resource->timezone)->toDateString());

        $blocksByDate = $blocks->groupBy(fn ($block) => $block->starts_at->setTimezone($resource->timezone)->toDateString());

        $weekDays = collect(range(0, 6))
            ->map(fn ($offset) => $weekStart->copy()->addDays($offset));

        $calendarStartHour = 8;
        $calendarEndHour = 22;
        $calendarHours = collect(range($calendarStartHour, $calendarEndHour - 1));

        $minute = (int) (ceil($now->minute / 15) * 15);
        if ($minute === 60) {
            $now->addHour()->minute(0);
        } else {
            $now->minute($minute);
        }
        $defaultStart = $now->copy()->second(0);
        $defaultEnd = $defaultStart->copy()->addHour();

        return view('admin.dashboard', [
            'resource' => $resource,
            'weekStart' => $weekStart,
            'weekOffset' => $weekOffset,
            'blocks' => $blocks,
            'blocksByDate' => $blocksByDate,
            'slotsByDate' => $slots,
            'weekDays' => $weekDays,
            'calendarHours' => $calendarHours,
            'calendarStartHour' => $calendarStartHour,
            'calendarEndHour' => $calendarEndHour,
            'defaultStart' => $defaultStart,
            'defaultEnd' => $defaultEnd,
        ]);
    }

    private function currentTime(string $timezone): Carbon
    {
        try {
            $response = Http::timeout(3)->get("https://worldtimeapi.org/api/timezone/{$timezone}");
            if ($response->ok() && ($dt = $response->json('datetime'))) {
                return Carbon::parse($dt)->setTimezone($timezone);
            }
        } catch (\Throwable $e) {
            // fall back to server time
        }

        return Carbon::now($timezone);
    }
}
