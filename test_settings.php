<?php

// Test settings migration and functionality
require_once 'vendor/autoload.php';

use App\Models\Setting;

echo "Testing settings migration...\n";

try {
    $settings = Setting::all();
    echo "Found " . $settings->count() . " settings\n";

    foreach ($settings as $setting) {
        echo "- ID: {$setting->id}, App Name: {$setting->app_name}, Active: " . ($setting->is_active ? 'Yes' : 'No') . "\n";
    }

    $activeSetting = Setting::where('is_active', true)->first();
    if ($activeSetting) {
        echo "\nActive setting: {$activeSetting->app_name}\n";
    } else {
        echo "\nNo active setting found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
