<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 CHECKING FOR CUSTOMER CHANGE ISSUES IN EXISTING DATA...\n\n";

// Check for potential customer change issues
// Look for sales where ledger entries might be pointing to wrong customers
// Since Sale model doesn't have ledgerEntries relationship, we'll use direct queries
$problematicSales = \App\Models\Sale::select('id', 'invoice_no', 'customer_id', 'final_total', 'created_at')
    ->where('customer_id', '!=', 1) // Skip Walk-In customers
    ->whereNotNull('invoice_no') // Only check sales with invoice numbers
    ->get();

echo "Found " . $problematicSales->count() . " sales to check...\n\n";

$issuesFound = [];
foreach ($problematicSales as $sale) {
    // Get ledger entries for this sale's invoice number
    $ledgerEntries = \App\Models\Ledger::where('reference_no', $sale->invoice_no)
        ->where('contact_type', 'customer')
        ->whereIn('transaction_type', ['sale', 'payments'])
        ->get();
    
    if ($ledgerEntries->isEmpty()) {
        continue; // Skip if no ledger entries found
    }
    
    $ledgerCustomerIds = $ledgerEntries->pluck('user_id')->unique();
    
    // If ledger entries point to different customer than sale's current customer
    if ($ledgerCustomerIds->count() > 1 || 
        ($ledgerCustomerIds->count() == 1 && !$ledgerCustomerIds->contains($sale->customer_id))) {
        
        // Get detailed ledger info
        $ledgerDetails = $ledgerEntries->map(function($entry) {
            return [
                'id' => $entry->id,
                'user_id' => $entry->user_id,
                'transaction_type' => $entry->transaction_type,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'reference_no' => $entry->reference_no,
                'notes' => $entry->notes,
                'created_at' => $entry->created_at->format('Y-m-d H:i:s')
            ];
        });
        
        $issuesFound[] = [
            'sale_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'current_customer_id' => $sale->customer_id,
            'ledger_customer_ids' => $ledgerCustomerIds->toArray(),
            'final_total' => $sale->final_total,
            'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
            'ledger_details' => $ledgerDetails->toArray()
        ];
    }
}

if (empty($issuesFound)) {
    echo "✅ NO CUSTOMER CHANGE ISSUES FOUND!\n";
    echo "Your ledger system appears to be consistent.\n";
} else {
    echo "❌ FOUND " . count($issuesFound) . " POTENTIAL ISSUES:\n\n";
    
    foreach ($issuesFound as $issue) {
        echo "=" . str_repeat("=", 50) . "\n";
        echo "Sale ID: {$issue['sale_id']}\n";
        echo "Invoice: {$issue['invoice_no']}\n";
        echo "Current Customer: {$issue['current_customer_id']}\n";
        echo "Ledger Points To: " . implode(', ', $issue['ledger_customer_ids']) . "\n";
        echo "Amount: Rs " . number_format($issue['final_total'], 2) . "\n";
        echo "Date: {$issue['created_at']}\n";
        echo "\nLedger Details:\n";
        
        foreach ($issue['ledger_details'] as $ledger) {
            echo "  - ID: {$ledger['id']}, Customer: {$ledger['user_id']}, Type: {$ledger['transaction_type']}\n";
            echo "    Debit: Rs " . number_format($ledger['debit'], 2) . ", Credit: Rs " . number_format($ledger['credit'], 2) . "\n";
            echo "    Ref: {$ledger['reference_no']}\n";
            echo "    Notes: {$ledger['notes']}\n";
        }
        echo "\n";
    }
    
    // Check customer balances for affected customers
    $affectedCustomers = collect($issuesFound)->pluck('ledger_customer_ids')->flatten()->unique();
    $currentCustomers = collect($issuesFound)->pluck('current_customer_id')->unique();
    $allAffectedCustomers = $affectedCustomers->merge($currentCustomers)->unique();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "AFFECTED CUSTOMER BALANCES:\n\n";
    
    foreach ($allAffectedCustomers as $customerId) {
        $customer = \App\Models\Customer::find($customerId);
        if ($customer) {
            $ledgerBalance = \App\Models\Ledger::getLatestBalance($customerId, 'customer');
            echo "Customer ID: $customerId ({$customer->full_name})\n";
            echo "Current Ledger Balance: Rs " . number_format($ledgerBalance, 2) . "\n";
            echo "Model Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
            if (abs($ledgerBalance - $customer->current_balance) > 0.01) {
                echo "⚠️ BALANCE MISMATCH!\n";
            }
            echo "\n";
        }
    }
}

// Also check for duplicate ledger entries with same reference but different customers
echo "\n" . str_repeat("=", 60) . "\n";
echo "CHECKING FOR DUPLICATE REFERENCES WITH DIFFERENT CUSTOMERS:\n\n";

$duplicateRefs = \App\Models\Ledger::select('reference_no')
    ->where('contact_type', 'customer')
    ->whereIn('transaction_type', ['sale', 'payments'])
    ->groupBy('reference_no')
    ->havingRaw('COUNT(DISTINCT user_id) > 1')
    ->pluck('reference_no');

if ($duplicateRefs->isEmpty()) {
    echo "✅ NO DUPLICATE REFERENCE ISSUES FOUND!\n";
} else {
    echo "❌ FOUND " . $duplicateRefs->count() . " REFERENCES WITH MULTIPLE CUSTOMERS:\n\n";
    
    foreach ($duplicateRefs as $refNo) {
        $entries = \App\Models\Ledger::where('reference_no', $refNo)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['sale', 'payments'])
            ->get();
        
        echo "Reference: $refNo\n";
        foreach ($entries as $entry) {
            echo "  - Customer: {$entry->user_id}, Type: {$entry->transaction_type}\n";
            echo "    Debit: Rs " . number_format($entry->debit, 2) . ", Credit: Rs " . number_format($entry->credit, 2) . "\n";
            echo "    Date: " . $entry->created_at->format('Y-m-d H:i:s') . "\n";
        }
        echo "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "ANALYSIS COMPLETE!\n";