<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test all locations: raw attribute vs accessor
$locations = App\Models\Location::all();
foreach ($locations as $loc) {
    $raw = $loc->getAttributes()['invoice_prefix'] ?? 'NULL';
    $accessor = $loc->invoice_prefix;
    echo "Location {$loc->id} ({$loc->name}): Raw=[{$raw}] Accessor=[{$accessor}]\n";
}

// Test what generateInvoiceNo would use
echo "\n=== What generateInvoiceNo sees ===\n";
foreach ($locations as $loc) {
    $prefix = !empty($loc->invoice_prefix) ? strtoupper($loc->invoice_prefix) : 'INV';
    echo "Location {$loc->id}: prefix={$prefix}\n";
}

// Check for prefix collisions
echo "\n=== Prefix Collision Check ===\n";
$prefixMap = [];
foreach ($locations as $loc) {
    $prefix = !empty($loc->invoice_prefix) ? strtoupper($loc->invoice_prefix) : 'INV';
    $prefixMap[$prefix][] = "Location {$loc->id} ({$loc->name})";
}
foreach ($prefixMap as $prefix => $locs) {
    if (count($locs) > 1) {
        echo "COLLISION! Prefix '{$prefix}' used by:\n";
        foreach ($locs as $l) echo "  - {$l}\n";
    }
}
