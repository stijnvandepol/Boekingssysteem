<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvailabilityBlockRequest;
use App\Http\Requests\UpdateAvailabilityBlockRequest;
use App\Models\AvailabilityBlock;
use App\Models\Resource;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    public function store(StoreAvailabilityBlockRequest $request, AvailabilityService $service)
    {
        $resource = Resource::where('user_id', $request->user()->id)->findOrFail($request->input('resource_id'));
        $this->authorize('update', $resource);

        try {
            $data = $request->validated();
            $rangesInput = $request->input('ranges');
            if ($rangesInput) {
                $ranges = json_decode($rangesInput, true);
                if (! is_array($ranges) || count($ranges) === 0) {
                    return back()->withErrors(['ranges' => 'Selecteer geldige blokken.'])->withInput();
                }

                $service->createBlocks($resource, $ranges, [
                    'slot_length_minutes' => $data['slot_length_minutes'],
                    'capacity' => $data['capacity'],
                ], $request->user());
            } else {
                $service->createBlock($resource, $data, $request->user());
            }
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('admin.dashboard')->with('status', 'Beschikbaarheid toegevoegd.');
    }

    public function destroy(Request $request, AvailabilityBlock $block, AvailabilityService $service)
    {
        $this->authorize('delete', $block);

        try {
            $service->deleteBlock($block, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('admin.dashboard')->with('status', 'Beschikbaarheid verwijderd.');
    }

    public function edit(Request $request, AvailabilityBlock $block)
    {
        $this->authorize('update', $block);
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        return view('admin.availability_edit', [
            'resource' => $resource,
            'block' => $block,
        ]);
    }

    public function update(UpdateAvailabilityBlockRequest $request, AvailabilityBlock $block, AvailabilityService $service)
    {
        $this->authorize('update', $block);

        try {
            $service->updateBlock($block, $request->validated(), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('admin.dashboard')->with('status', 'Beschikbaarheid bijgewerkt.');
    }
}
