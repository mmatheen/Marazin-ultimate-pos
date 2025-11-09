<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING FOR NEGATIVE VALUES (MINUS VALUES) ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "🔍 SCANNING ALL TABLES FOR NEGATIVE VALUES...\n";
    echo "=============================================\n\n";
    
    // 1. Check location_batches for negative quantities
    echo "1. LOCATION_BATCHES TABLE:\n";
    echo "=========================\n";
    
    $negativeStock = DB::table('location_batches')
        ->where('qty', '<', 0)
        ->orderBy('qty', 'asc')
        ->get();
    
    if (count($negativeStock) > 0) {
        echo "❌ Found " . count($negativeStock) . " negative stock quantities:\n";
        foreach ($negativeStock as $stock) {
            echo "- Batch {$stock->batch_id}, Location {$stock->location_id}: {$stock->qty} units\n";
        }
    } else {
        echo "✅ No negative quantities found in location_batches\n";
    }
    
    echo "\n";
    
    // 2. Check sales table for negative amounts
    echo "2. SALES TABLE:\n";
    echo "===============\n";
    
    $negativeSales = DB::table('sales')
        ->where(function($query) {
            $query->where('subtotal', '<', 0)
                  ->orWhere('discount_amount', '<', 0)
                  ->orWhere('final_total', '<', 0)
                  ->orWhere('total_paid', '<', 0)
                  ->orWhere('total_due', '<', 0);
        })
        ->get();
    
    if (count($negativeSales) > 0) {
        echo "❌ Found " . count($negativeSales) . " sales with negative amounts:\n";
        foreach ($negativeSales as $sale) {
            echo "- Sale ID: {$sale->id}, Invoice: {$sale->invoice_no}\n";
            if ($sale->subtotal < 0) echo "  * Subtotal: {$sale->subtotal}\n";
            if ($sale->discount_amount < 0) echo "  * Discount: {$sale->discount_amount}\n";
            if ($sale->final_total < 0) echo "  * Final Total: {$sale->final_total}\n";
            if ($sale->total_paid < 0) echo "  * Total Paid: {$sale->total_paid}\n";
            if ($sale->total_due < 0) echo "  * Total Due: {$sale->total_due}\n";
        }
    } else {
        echo "✅ No negative amounts found in sales\n";
    }
    
    echo "\n";
    
    // 3. Check payments table for negative amounts
    echo "3. PAYMENTS TABLE:\n";
    echo "=================\n";
    
    $negativePayments = DB::table('payments')
        ->where('amount', '<', 0)
        ->get();
    
    if (count($negativePayments) > 0) {
        echo "❌ Found " . count($negativePayments) . " negative payments:\n";
        foreach ($negativePayments as $payment) {
            echo "- Payment ID: {$payment->id}, Amount: {$payment->amount}, Date: {$payment->payment_date}\n";
        }
    } else {
        echo "✅ No negative amounts found in payments\n";
    }
    
    echo "\n";
    
    // 4. Check ledgers table for negative balances
    echo "4. LEDGERS TABLE:\n";
    echo "================\n";
    
    $negativeBalances = DB::table('ledgers')
        ->where('balance', '<', 0)
        ->orderBy('balance', 'asc')
        ->get();
    
    if (count($negativeBalances) > 0) {
        echo "❌ Found " . count($negativeBalances) . " negative balances:\n";
        foreach ($negativeBalances as $ledger) {
            echo "- Ledger ID: {$ledger->id}, Balance: {$ledger->balance}, Type: {$ledger->transaction_type}, Contact: {$ledger->contact_type}, User ID: {$ledger->user_id}\n";
        }
        
        echo "\nNote: Some negative balances might be normal (e.g., customer credits from returns)\n";
    } else {
        echo "✅ No negative balances found in ledgers\n";
    }
    
    echo "\n";
    
    // 5. Check customers for negative balances
    echo "5. CUSTOMERS TABLE:\n";
    echo "==================\n";
    
    $negativeCustomerBalances = DB::table('customers')
        ->where(function($query) {
            $query->where('opening_balance', '<', 0)
                  ->orWhere('current_balance', '<', 0);
        })
        ->get();
    
    if (count($negativeCustomerBalances) > 0) {
        echo "❌ Found " . count($negativeCustomerBalances) . " customers with negative balances:\n";
        foreach ($negativeCustomerBalances as $customer) {
            $customerName = trim($customer->first_name . ' ' . ($customer->last_name ?? ''));
            echo "- Customer ID: {$customer->id}, Name: {$customerName}, Mobile: {$customer->mobile_no}\n";
            if ($customer->opening_balance < 0) echo "  * Opening Balance: {$customer->opening_balance}\n";
            if ($customer->current_balance < 0) echo "  * Current Balance: {$customer->current_balance}\n";
        }
        
        echo "\nNote: Negative customer balances usually mean customer credits or refunds due\n";
    } else {
        echo "✅ No negative balances found in customers\n";
    }
    
    echo "\n";
    
    // 6. Check purchases for negative amounts
    echo "6. PURCHASES TABLE:\n";
    echo "==================\n";
    
    $negativePurchases = DB::table('purchases')
        ->where(function($query) {
            $query->where('subtotal', '<', 0)
                  ->orWhere('discount_amount', '<', 0)
                  ->orWhere('final_total', '<', 0)
                  ->orWhere('total_paid', '<', 0)
                  ->orWhere('total_due', '<', 0);
        })
        ->get();
    
    if (count($negativePurchases) > 0) {
        echo "❌ Found " . count($negativePurchases) . " purchases with negative amounts:\n";
        foreach ($negativePurchases as $purchase) {
            echo "- Purchase ID: {$purchase->id}, Reference: {$purchase->reference_no}\n";
            if ($purchase->subtotal < 0) echo "  * Subtotal: {$purchase->subtotal}\n";
            if ($purchase->discount_amount < 0) echo "  * Discount: {$purchase->discount_amount}\n";
            if ($purchase->final_total < 0) echo "  * Final Total: {$purchase->final_total}\n";
            if ($purchase->total_paid < 0) echo "  * Total Paid: {$purchase->total_paid}\n";
            if ($purchase->total_due < 0) echo "  * Total Due: {$purchase->total_due}\n";
        }
    } else {
        echo "✅ No negative amounts found in purchases\n";
    }
    
    echo "\n";
    
    // 7. Summary
    echo "📊 SUMMARY OF NEGATIVE VALUE SCAN:\n";
    echo "==================================\n";
    
    $totalIssues = count($negativeStock) + count($negativeSales) + count($negativePayments) + 
                   count($negativeBalances) + count($negativeCustomerBalances) + count($negativePurchases);
    
    if ($totalIssues == 0) {
        echo "🎉 EXCELLENT! No problematic negative values found!\n";
        echo "✅ Your database is clean and ready for business.\n\n";
    } else {
        echo "⚠️ Found {$totalIssues} records with negative values:\n";
        echo "- Negative stock quantities: " . count($negativeStock) . "\n";
        echo "- Negative sales amounts: " . count($negativeSales) . "\n";
        echo "- Negative payments: " . count($negativePayments) . "\n";
        echo "- Negative ledger balances: " . count($negativeBalances) . "\n";
        echo "- Negative customer balances: " . count($negativeCustomerBalances) . "\n";
        echo "- Negative purchase amounts: " . count($negativePurchases) . "\n\n";
        
        echo "🛠️ RECOMMENDATIONS:\n";
        echo "- Stock quantities: Should be fixed (critical for POS)\n";
        echo "- Sales/Purchase amounts: Should be investigated\n";
        echo "- Customer/Ledger balances: May be normal (credits/refunds)\n\n";
    }
    
    // 8. Quick fix option for negative stock
    if (count($negativeStock) > 0) {
        echo "🔧 QUICK FIX FOR NEGATIVE STOCK:\n";
        echo "===============================\n";
        echo "Command to fix: UPDATE location_batches SET qty = 0 WHERE qty < 0;\n";
        echo "Or run: php -r \"require 'bootstrap/app.php'; \$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); DB::table('location_batches')->where('qty', '<', 0)->update(['qty' => 0]);\"\n\n";
    }
    
    echo "Scan completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error during scan: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}