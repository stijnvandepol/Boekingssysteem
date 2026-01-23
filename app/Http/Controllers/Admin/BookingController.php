<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        $query = Booking::with(['slotInstance', 'guests'])
            ->where('resource_id', $resource->id)
            ->orderByDesc('booked_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'), $resource->timezone);
            $query->whereBetween('booked_at', [$date->copy()->startOfDay()->utc(), $date->copy()->endOfDay()->utc()]);
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('admin.bookings', [
            'resource' => $resource,
            'bookings' => $bookings,
        ]);
    }
}
