<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check current prefix in DB
$locations = \App\Models\Location::whereNull('parent_id')->get(['id', 'name', 'invoice_prefix']);
foreach ($locations as $l) {
    echo "ID {$l->id} | {$l->name} | prefix = " . ($l->invoice_prefix ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . "--- Last 5 invoice numbers ---" . PHP_EOL;
\App\Models\Sale::latest()->take(5)->get(['id', 'invoice_no', 'location_id'])->each(function($s) {
    echo "sale_id={$s->id} | invoice={$s->invoice_no} | location_id={$s->location_id}" . PHP_EOL;
});
