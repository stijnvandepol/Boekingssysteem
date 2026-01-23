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
            $service->createBlock($resource, $request->validated(), $request->user());
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
