<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class SettingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view settings', ['only' => ['index']]);
        $this->middleware('permission:edit business-settings', ['only' => ['update', 'updatePriceValidation', 'updateFreeQty']]);
        $this->middleware('permission:edit backorder-settings', ['only' => ['updateBackorders']]);
        $this->middleware('permission:edit sms-settings', ['only' => ['updateSmsSettings']]);
        $this->middleware('permission:sms.send', ['only' => ['sendSms']]);
        $this->middleware('permission:backup database', ['only' => ['backupNow']]);
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

        // General form only updates app name, logo, favicon (toggles are saved via their own endpoints)

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

    /**
     * Update only Price Validation toggle (individual card save)
     * 1 = Strict (only users with permission can edit prices/discounts), 0 = Flexible
     */
    public function updatePriceValidation(Request $request)
    {
        $validated = $request->validate([
            'enable_price_validation' => 'required|in:0,1',
        ]);
        $setting = Setting::first();
        $setting->update($validated);
        Cache::forget('active_setting');
        return response()->json([
            'status' => true,
            'message' => 'Price validation setting updated.',
            'data' => ['enable_price_validation' => (int) $validated['enable_price_validation']],
        ]);
    }

    /**
     * Update only Free Qty toggle (individual card save)
     * 1 = Free Qty visible for permitted users, 0 = Hidden for all
     */
    public function updateFreeQty(Request $request)
    {
        $validated = $request->validate([
            'enable_free_qty' => 'required|in:0,1',
        ]);
        $setting = Setting::first();
        $setting->update($validated);
        Cache::forget('active_setting');
        return response()->json([
            'status' => true,
            'message' => 'Free quantity setting updated.',
            'data' => ['enable_free_qty' => (int) $validated['enable_free_qty']],
        ]);
    }

    /**
     * Update only Backorders toggle (individual card save)
     * 1 = Allow sale-order shortage as backorder, 0 = Strict stock only
     */
    public function updateBackorders(Request $request)
    {
        $validated = $request->validate([
            'enable_backorders' => 'required|in:0,1',
        ]);
        $setting = Setting::first();
        $setting->update($validated);
        Cache::forget('active_setting');
        return response()->json([
            'status' => true,
            'message' => 'Backorder setting updated.',
            'data' => ['enable_backorders' => (int) $validated['enable_backorders']],
        ]);
    }

    /**
     * Update SMS gateway settings.
     */
    public function updateSmsSettings(Request $request)
    {
        $validated = $request->validate([
            'sms_user_id' => 'nullable|string|max:255',
            'sms_api_key' => 'nullable|string|max:255',
            'sms_sender_id' => 'nullable|string|max:255',
        ]);

        $setting = Setting::firstOrFail();

        $payload = [
            'sms_user_id' => $validated['sms_user_id'] ?? null,
            'sms_sender_id' => $validated['sms_sender_id'] ?? null,
        ];

        if ($request->filled('sms_api_key')) {
            $payload['sms_api_key'] = $validated['sms_api_key'];
        }

        $setting->update($payload);

        Cache::forget('active_setting');

        return response()->json([
            'status' => true,
            'message' => 'SMS settings updated successfully.',
            'data' => $setting,
        ]);
    }

    /**
     * Send manual SMS to one or many recipients.
     */
    public function sendSms(Request $request)
    {
        $validated = $request->validate([
            'phones' => 'required|string|max:2000',
            'message' => 'required|string|max:1600',
        ]);

        $phones = preg_split('/[\r\n,;]+/', $validated['phones']) ?: [];
        $phones = array_values(array_filter(array_map('trim', $phones)));

        if (empty($phones)) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter at least one valid phone number.',
            ], 422);
        }

        SendSmsJob::dispatch($phones, $validated['message'])->afterCommit();

        return response()->json([
            'status' => true,
            'message' => 'SMS request submitted successfully.',
        ]);
    }

    /**
     * Manual database backup download
     */
    public function backupNow()
    {
        // Run database-only backup using Spatie
        Artisan::call('backup:run', ['--only-db' => true]);

        // Find latest DB backup on local disk (laravel-pos folder)
        $disk = Storage::disk('local');

        $files = collect($disk->files('laravel-pos'))
            ->filter(fn ($path) => str_ends_with($path, '.zip'))
            ->sortByDesc(fn ($path) => $disk->lastModified($path));

        $latest = $files->first();

        if (! $latest) {
            return back()->with('error', 'No backup file found.');
        }

        $fullPath = storage_path('app/' . $latest);
        $downloadName = 'db-backup-' . Carbon::now()->format('Y-m-d-H-i-s') . '.zip';

        return response()->download($fullPath, $downloadName);
    }
}
