<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view settings', ['only' => ['index']]);
        $this->middleware('permission:edit business-settings', ['only' => ['update']]);
    }

    /**
     * Show the settings form
     */
    public function index()
    {
        $setting = Setting::firstOrFail(); // Only one setting
        return view('admin.settings.index', compact('setting'));
    }

    /**
     * Update the single setting
     */
    public function update(Request $request)
    {
        $setting = Setting::first();

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:2048',
            'favicon' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:2048',
            'primary_color' => 'nullable|string|max:7', // e.g., #4F46E5
            'font_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete('settings/' . $setting->logo);
            }
            $path = $request->file('logo')->store('settings', 'public');
            $validated['logo'] = basename($path);
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            if ($setting->favicon) {
                Storage::disk('public')->delete('settings/' . $setting->favicon);
            }
            $path = $request->file('favicon')->store('settings', 'public');
            $validated['favicon'] = basename($path);
        }

        $setting->update($validated);

        // Clear cache if used
        Cache::forget('active_setting');

        return response()->json([
            'status' => true,
            'message' => 'Settings updated successfully.',
            'data' => $setting
        ]);
    }
}