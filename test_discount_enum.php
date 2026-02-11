<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Testing if database enum accepts 'discount_given' value...\n\n";

try {
    // Try to insert a test record with discount_given
    DB::statement('INSERT INTO ledgers (contact_id, contact_type, transaction_date, reference_no, transaction_type, debit, credit, status, created_at, updated_at) VALUES (1, "customer", NOW(), "TEST-ENUM-' . time() . '", "discount_given", 100, 0, "active", NOW(), NOW())');

    echo "✅ SUCCESS - Database enum already accepts 'discount_given'\n";
    echo "No migration needed!\n\n";

    // Clean up test record
    DB::statement('DELETE FROM ledgers WHERE reference_no LIKE "TEST-ENUM-%"');

} catch (\Exception $e) {
    echo "❌ FAILED - Database enum does NOT accept 'discount_given'\n";
    echo "Error: " . $e->getMessage() . "\n\n";

    // Check if it's an enum error
    if (strpos($e->getMessage(), 'Data truncated') !== false || strpos($e->getMessage(), 'enum') !== false) {
        echo "⚠️  MIGRATION IS REQUIRED to add 'discount_given' to the transaction_type enum!\n\n";
    } else {
        echo "ℹ️  Error might not be related to enum. Full message above.\n\n";
    }
}
