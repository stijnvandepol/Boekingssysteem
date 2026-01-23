<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateResourceSettingsRequest;
use App\Models\Resource;

class ResourceController extends Controller
{
    public function update(UpdateResourceSettingsRequest $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('update', $resource);

        $resource->update($request->validated());

        return redirect()->route('admin.dashboard')->with('status', 'Resource instellingen bijgewerkt.');
    }
}
