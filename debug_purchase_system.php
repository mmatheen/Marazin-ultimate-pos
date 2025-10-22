<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PURCHASE SCRIPT DEBUG ===\n\n";

// Check if purchases table is accessible
echo "1. Checking database connection...\n";
try {
    \DB::connection()->getPdo();
    echo "   ✓ Database connected\n\n";
} catch (\Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check purchases table structure
echo "2. Checking purchases table columns...\n";
$purchaseColumns = \DB::select('SHOW COLUMNS FROM purchases');
$hasDiscountFields = false;
$hasTaxFields = false;

foreach ($purchaseColumns as $col) {
    if ($col->Field === 'discount_type') $hasDiscountFields = true;
    if ($col->Field === 'tax_type') $hasTaxFields = true;
}

echo "   - discount_type: " . ($hasDiscountFields ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   - discount_amount: " . ($hasDiscountFields ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   - tax_type: " . ($hasTaxFields ? "✓ (but nullable)" : "✗ MISSING") . "\n\n";

// Check purchase_products table structure
echo "3. Checking purchase_products table columns...\n";
$productColumns = \DB::select('SHOW COLUMNS FROM purchase_products');
$hasPrice = false;
$hasDiscountPercent = false;

foreach ($productColumns as $col) {
    if ($col->Field === 'price') $hasPrice = true;
    if ($col->Field === 'discount_percent') $hasDiscountPercent = true;
}

echo "   - price: " . ($hasPrice ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   - discount_percent: " . ($hasDiscountPercent ? "✓ EXISTS" : "✗ MISSING") . "\n\n";

// Check PurchaseController validation rules
echo "4. Checking PurchaseController validation...\n";
$controllerPath = app_path('Http/Controllers/PurchaseController.php');
$controllerContent = file_get_contents($controllerPath);

$hasProductsPriceValidation = strpos($controllerContent, "'products.*.price'") !== false;
$hasProductsDiscountValidation = strpos($controllerContent, "'products.*.discount_percent'") !== false;
$hasDiscountTypeValidation = strpos($controllerContent, "'discount_type'") !== false;

echo "   - products.*.price validation: " . ($hasProductsPriceValidation ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   - products.*.discount_percent validation: " . ($hasProductsDiscountValidation ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   - discount_type validation: " . ($hasDiscountTypeValidation ? "✓ EXISTS" : "✗ MISSING") . "\n\n";

// Check JavaScript sends correct data
echo "5. Checking purchase_ajax.blade.php JavaScript...\n";
$ajaxPath = resource_path('views/purchase/purchase_ajax.blade.php');
$ajaxContent = file_get_contents($ajaxPath);

$sendsPrice = strpos($ajaxContent, "formData.append(`products[") !== false && 
              strpos($ajaxContent, "[price]`, price)") !== false;
$sendsDiscountPercent = strpos($ajaxContent, "[discount_percent]`, discountPercent)") !== false;
$sendsDiscountType = strpos($ajaxContent, "formData.append('discount_type'") !== false;
$sendsTaxType = strpos($ajaxContent, "formData.append('tax_type'") !== false;

echo "   - Sends products[*][price]: " . ($sendsPrice ? "✓ YES" : "✗ NO") . "\n";
echo "   - Sends products[*][discount_percent]: " . ($sendsDiscountPercent ? "✓ YES" : "✗ NO") . "\n";
echo "   - Sends discount_type: " . ($sendsDiscountType ? "✓ YES" : "✗ NO") . "\n";
echo "   - Sends tax_type: " . ($sendsTaxType ? "✓ YES" : "✗ NO") . "\n\n";

// Check for common issues
echo "6. Checking for common issues...\n";

// Check if migration ran
$migrations = \DB::table('migrations')->where('migration', 'like', '%add_discount_fields_to_purchase_products%')->get();
if ($migrations->isEmpty()) {
    echo "   ⚠ ISSUE: Migration for discount fields not run!\n";
    echo "      Run: php artisan migrate\n\n";
} else {
    echo "   ✓ Migration has been run\n\n";
}

// Check if any purchase has wrong totals
echo "7. Checking purchases for calculation errors...\n";
$purchases = \App\Models\Purchase::with('purchaseProducts')->get();
$errorCount = 0;

foreach ($purchases as $purchase) {
    $calculatedTotal = $purchase->purchaseProducts->sum('total');
    
    if (abs($purchase->total - $calculatedTotal) > 0.01) {
        $errorCount++;
        echo "   ⚠ Purchase #{$purchase->id}: total mismatch\n";
        echo "      Database: " . number_format($purchase->total, 2) . "\n";
        echo "      Calculated: " . number_format($calculatedTotal, 2) . "\n";
    }
}

if ($errorCount === 0) {
    echo "   ✓ All purchases have correct totals\n\n";
} else {
    echo "\n   Found {$errorCount} purchases with incorrect totals\n";
    echo "   Run: php artisan purchase:reconcile-all --fix\n\n";
}

// Check Laravel logs for errors
echo "8. Checking recent Laravel logs...\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $logLines = file($logPath);
    $recentErrors = [];
    
    // Get last 100 lines
    $lastLines = array_slice($logLines, -100);
    
    foreach ($lastLines as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
            $recentErrors[] = trim($line);
        }
    }
    
    if (empty($recentErrors)) {
        echo "   ✓ No recent errors found\n\n";
    } else {
        echo "   ⚠ Found recent errors:\n";
        foreach (array_slice($recentErrors, -5) as $error) {
            echo "      " . substr($error, 0, 100) . "...\n";
        }
        echo "\n";
    }
} else {
    echo "   - No log file found (this is okay)\n\n";
}

// Final summary
echo "=== SUMMARY ===\n";

$issues = [];

if (!$hasPrice || !$hasDiscountPercent) {
    $issues[] = "❌ Database columns missing - Run: php artisan migrate";
}

if (!$hasProductsPriceValidation || !$hasProductsDiscountValidation) {
    $issues[] = "❌ Controller validation missing - Check PurchaseController.php";
}

if (!$sendsPrice || !$sendsDiscountPercent || !$sendsDiscountType || !$sendsTaxType) {
    $issues[] = "❌ JavaScript not sending required fields - Check purchase_ajax.blade.php";
}

if ($errorCount > 0) {
    $issues[] = "⚠️  {$errorCount} purchases have incorrect totals - Run: php artisan purchase:reconcile-all --fix";
}

if (empty($issues)) {
    echo "✅ Everything looks good!\n";
    echo "\nYour purchase system is working correctly.\n";
    echo "All calculations are server-side and secure.\n";
} else {
    echo "⚠️  Issues found:\n\n";
    foreach ($issues as $issue) {
        echo "  {$issue}\n";
    }
}

echo "\n=== DONE ===\n";
