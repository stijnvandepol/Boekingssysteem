<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        return view('admin.settings', [
            'resource' => $resource,
        ]);
    }
}
