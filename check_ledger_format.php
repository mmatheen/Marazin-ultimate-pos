<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;

$oldCount = Ledger::where('contact_id', 1058)
    ->where('reference_no', 'BLK-S0075')
    ->where('status', 'active')
    ->count();

$newCount = Ledger::where('contact_id', 1058)
    ->where('reference_no', 'LIKE', 'BLK-S0075-PAY%')
    ->where('status', 'active')
    ->count();

echo "Old format (BLK-S0075): {$oldCount} ledgers\n";
echo "New format (BLK-S0075-PAY*): {$newCount} ledgers\n";
echo "\nTotal should be: 18\n";

if ($oldCount == 17 && $newCount == 0) {
    echo "\n✅ Need to migrate: Delete old 17 entries, create new 18 entries with unique references\n";
}
