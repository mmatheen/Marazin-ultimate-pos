<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Display the settings index page (for admin)
     */
    public function index()
    {
        $settings = Setting::all();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Fetch all settings (API)
     */
    public function getAllSettings()
    {
        $settings = Setting::all();
        return response()->json([
            'status' => true,
            'message' => 'Settings fetched successfully.',
            'data' => $settings
        ]);
    }

    /**
     * Show a specific setting
     */
    public function show($id)
    {
        $setting = Setting::findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $setting
        ]);
    }

    /**
     * Store a new setting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:2048',
            'favicon' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $validated;

        // Handle is_active logic
        if (isset($validated['is_active']) && $validated['is_active']) {
            // If this setting is being set as active, deactivate all others
            Setting::where('is_active', true)->update(['is_active' => false]);
            $data['is_active'] = true;
        } else {
            // If no active setting exists, make this one active
            if (!Setting::where('is_active', true)->exists()) {
                $data['is_active'] = true;
            } else {
                $data['is_active'] = false;
            }
        }

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('settings', 'public');
            $data['logo'] = basename($logoPath);
        }

        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->store('settings', 'public');
            $data['favicon'] = basename($faviconPath);
        }

        $setting = Setting::create($data);

        // Clear the active setting cache
        Cache::forget('active_setting');

        return response()->json([
            'status' => true,
            'message' => 'Settings created successfully.',
            'data' => $setting
        ]);
    }

    /**
     * Update existing setting
     */
    public function update(Request $request, $id = null)
    {
        $setting = $id ? Setting::findOrFail($id) : Setting::first();

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:2048',
            'favicon' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting not found.'
            ], 404);
        }

        // Handle is_active logic
        if (isset($validated['is_active']) && $validated['is_active']) {
            // If this setting is being set as active, deactivate all others
            Setting::where('id', '!=', $setting->id)->update(['is_active' => false]);
            $validated['is_active'] = true;
        } elseif (isset($validated['is_active']) && !$validated['is_active']) {
            // If trying to deactivate and this is the only active setting, don't allow it
            $activeCount = Setting::where('is_active', true)->count();
            if ($activeCount <= 1 && $setting->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'At least one setting must be active.'
                ], 422);
            }
            $validated['is_active'] = false;
        }

        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete('settings/' . $setting->logo);
            }
            $logoPath = $request->file('logo')->store('settings', 'public');
            $validated['logo'] = basename($logoPath);
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon) {
                Storage::disk('public')->delete('settings/' . $setting->favicon);
            }
            $faviconPath = $request->file('favicon')->store('settings', 'public');
            $validated['favicon'] = basename($faviconPath);
        }

        $setting->fill($validated);
        $setting->save();

        // Clear the active setting cache
        Cache::forget('active_setting');

        return response()->json([
            'status' => true,
            'message' => 'Settings updated successfully.',
            'data' => $setting
        ]);
    }

    /**
     * Delete setting
     */
    public function destroy($id = null)
    {
        $setting = $id ? Setting::findOrFail($id) : Setting::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting not found.'
            ], 404);
        }

        // Check if this is the only active setting
        if ($setting->is_active) {
            $activeCount = Setting::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete the only active setting. At least one setting must be active.'
                ], 422);
            }
        }

        // Delete logo
        if ($setting->logo) {
            Storage::disk('public')->delete('settings/' . $setting->logo);
        }

        // Delete favicon
        if ($setting->favicon) {
            Storage::disk('public')->delete('settings/' . $setting->favicon);
        }

        $setting->delete();

        // Clear the active setting cache
        Cache::forget('active_setting');

        return response()->json([
            'status' => true,
            'message' => 'Settings deleted successfully.'
        ]);
    }

    /**
     * Activate a setting
     */
    public function activate($id)
    {
        $setting = Setting::findOrFail($id);

        // Deactivate all other settings
        Setting::where('id', '!=', $id)->update(['is_active' => false]);

        // Activate this setting
        $setting->update(['is_active' => true]);

        // Clear the active setting cache
        Cache::forget('active_setting');

        return response()->json([
            'status' => true,
            'message' => 'Setting activated successfully.',
            'data' => $setting
        ]);
    }

    /**
     * Get the active setting
     */
    public function getActiveSetting()
    {
        $setting = Setting::where('is_active', true)->first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'No active setting found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $setting
        ]);
    }
}
