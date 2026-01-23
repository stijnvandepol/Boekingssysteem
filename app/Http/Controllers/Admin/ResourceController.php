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

        $data = $request->validated();
        $data['min_notice_minutes'] = (int) round(((float) $data['min_notice_hours']) * 60);
        unset($data['min_notice_hours']);

        $resource->update($data);

        return redirect()->route('admin.dashboard')->with('status', 'Resource instellingen bijgewerkt.');
    }
}
