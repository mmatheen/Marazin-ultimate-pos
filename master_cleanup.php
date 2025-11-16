<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MASTER CUSTOMER CLEANUP - ALL CUSTOMERS SAFE FIX ===\n\n";

// Configuration
$dryRun = isset($argv[1]) && $argv[1] === '--dry-run';
$forceRun = isset($argv[1]) && $argv[1] === '--force';

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No actual changes will be made\n\n";
} elseif (!$forceRun) {
    echo "⚠️ PRODUCTION SAFETY - Add --dry-run to preview or --force to execute\n";
    echo "Usage: php master_cleanup.php --dry-run (preview)\n";
    echo "       php master_cleanup.php --force (execute)\n\n";
    exit(1);
}

try {
    // Get all customers except Walk-in Customer (ID 1) and already fixed ones
    $fixedCustomers = [1, 6, 7, 2]; // Walk-in, DUBAIWORLD, ALM RIYATH, SITHIK STORE
    
    $customers = DB::table('customers')
                  ->whereNotIn('id', $fixedCustomers)
                  ->orderBy('id')
                  ->get();
    
    echo "Customers to process: {$customers->count()}\n";
    echo "Already fixed: " . implode(', ', $fixedCustomers) . "\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    $results = [];
    
    foreach ($customers as $customer) {
        echo str_repeat("=", 60) . "\n";
        echo "Processing Customer ID {$customer->id}: " . trim($customer->first_name . ' ' . $customer->last_name) . "\n";
        echo str_repeat("=", 60) . "\n";
        
        if (!$dryRun) {
            DB::beginTransaction();
        }
        
        try {
            // Analyze customer data
            $sales = DB::table('sales')->where('customer_id', $customer->id)->get();
            $payments = DB::table('payments')->where('customer_id', $customer->id)->get();
            $ledgers = DB::table('ledgers')->where('user_id', $customer->id)->where('contact_type', 'customer')->get();
            
            $totalSales = $sales->sum('final_total');
            $totalPayments = $payments->sum('amount');
            $expectedBalance = ($customer->opening_balance ?? 0) + $totalSales - $totalPayments;
            
            echo "Current State:\n";
            echo "  Sales: {$sales->count()} (Rs.{$totalSales})\n";
            echo "  Payments: {$payments->count()} (Rs.{$totalPayments})\n";
            echo "  Ledger Entries: {$ledgers->count()}\n";
            echo "  Current Balance: {$customer->current_balance}\n";
            echo "  Expected Balance: {$expectedBalance}\n\n";
            
            $actions = [];
            $issuesFound = [];
            
            // 1. Check for duplicate ledger entries
            $ledgerGroups = $ledgers->groupBy('reference_no');
            $duplicatesToDelete = [];
            
            foreach ($ledgerGroups as $refNo => $entries) {
                if ($entries->count() > 1) {
                    $issuesFound[] = "Duplicate ledger entries for {$refNo}";
                    // Keep first entry, delete rest
                    foreach ($entries->skip(1) as $duplicate) {
                        $duplicatesToDelete[] = $duplicate->id;
                    }
                }
            }
            
            if (count($duplicatesToDelete) > 0) {
                $actions[] = "Delete " . count($duplicatesToDelete) . " duplicate ledger entries";
                if (!$dryRun) {
                    DB::table('ledgers')->whereIn('id', $duplicatesToDelete)->delete();
                    echo "✓ Deleted " . count($duplicatesToDelete) . " duplicate ledger entries\n";
                }
            }
            
            // 2. Rebuild ledger entries from scratch
            if (!$dryRun) {
                // Delete all existing ledgers for this customer
                DB::table('ledgers')->where('user_id', $customer->id)->where('contact_type', 'customer')->delete();
                echo "✓ Cleared all ledger entries\n";
            }
            
            $actions[] = "Rebuild complete ledger";
            $balance = $customer->opening_balance ?? 0;
            
            // Add opening balance if exists
            if ($balance != 0) {
                if (!$dryRun) {
                    DB::table('ledgers')->insert([
                        'transaction_date' => $customer->created_at ?? now(),
                        'reference_no' => 'OPENING-' . $customer->id,
                        'transaction_type' => 'opening_balance',
                        'debit' => $balance > 0 ? $balance : 0,
                        'credit' => $balance < 0 ? abs($balance) : 0,
                        'balance' => $balance,
                        'contact_type' => 'customer',
                        'user_id' => $customer->id,
                        'notes' => 'Opening balance',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                echo "✓ Added opening balance: {$balance}\n";
            }
            
            // Add sales entries
            $salesForLedger = DB::table('sales')
                               ->where('customer_id', $customer->id)
                               ->whereIn('status', ['final', 'suspend'])
                               ->orderBy('created_at')
                               ->get();
            
            foreach ($salesForLedger as $sale) {
                $balance += $sale->final_total;
                
                if (!$dryRun) {
                    DB::table('ledgers')->insert([
                        'transaction_date' => $sale->created_at,
                        'reference_no' => $sale->invoice_no ?? 'SALE-' . $sale->id,
                        'transaction_type' => 'sale',
                        'debit' => $sale->final_total,
                        'credit' => 0,
                        'balance' => $balance,
                        'contact_type' => 'customer',
                        'user_id' => $customer->id,
                        'notes' => 'Sale transaction',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            echo "✓ Added {$salesForLedger->count()} sale entries\n";
            
            // Add payment entries
            $paymentsForLedger = DB::table('payments')
                                  ->where('customer_id', $customer->id)
                                  ->orderBy('created_at')
                                  ->get();
            
            foreach ($paymentsForLedger as $payment) {
                $balance -= $payment->amount;
                
                if (!$dryRun) {
                    DB::table('ledgers')->insert([
                        'transaction_date' => $payment->created_at,
                        'reference_no' => $payment->reference_no ?? 'PAY-' . $payment->id,
                        'transaction_type' => 'payment',
                        'debit' => 0,
                        'credit' => $payment->amount,
                        'balance' => $balance,
                        'contact_type' => 'customer',
                        'user_id' => $customer->id,
                        'notes' => 'Payment received',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            echo "✓ Added {$paymentsForLedger->count()} payment entries\n";
            
            // 3. Fix sales table payment tracking
            $actions[] = "Fix sales payment tracking";
            
            foreach ($sales as $sale) {
                // Calculate actual payments for this sale
                $salePayments = $payments->where('reference_no', $sale->invoice_no)->sum('amount');
                $correctTotalDue = $sale->final_total - $salePayments;
                $correctStatus = $salePayments >= $sale->final_total ? 'Paid' : ($salePayments > 0 ? 'Partial' : 'Due');
                
                if (!$dryRun) {
                    DB::table('sales')
                      ->where('id', $sale->id)
                      ->update([
                          'total_paid' => $salePayments,
                          'total_due' => $correctTotalDue,
                          'payment_status' => $correctStatus
                      ]);
                }
            }
            echo "✓ Fixed payment tracking for {$sales->count()} sales\n";
            
            // 4. Update customer balance
            $finalBalance = $balance;
            $actions[] = "Update customer balance to {$finalBalance}";
            
            if (!$dryRun) {
                DB::table('customers')
                  ->where('id', $customer->id)
                  ->update(['current_balance' => $finalBalance]);
            }
            echo "✓ Updated customer balance to: {$finalBalance}\n";
            
            // Verification
            echo "\nVerification:\n";
            echo "  Final Balance: {$finalBalance}\n";
            echo "  Expected Balance: {$expectedBalance}\n";
            echo "  Match: " . ($finalBalance == $expectedBalance ? '✅' : '❌') . "\n";
            
            if ($finalBalance == $expectedBalance) {
                if (!$dryRun) {
                    DB::commit();
                }
                echo "✅ Customer successfully cleaned!\n";
                $successCount++;
                $results[] = [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'status' => 'success',
                    'actions' => $actions,
                    'issues_fixed' => count($issuesFound),
                    'final_balance' => $finalBalance
                ];
            } else {
                if (!$dryRun) {
                    DB::rollback();
                }
                echo "❌ Balance verification failed\n";
                $errorCount++;
                $results[] = [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'status' => 'error',
                    'error' => 'Balance mismatch after cleanup'
                ];
            }
            
        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollback();
            }
            echo "❌ Error processing customer: " . $e->getMessage() . "\n";
            $errorCount++;
            $results[] = [
                'id' => $customer->id,
                'name' => trim($customer->first_name . ' ' . $customer->last_name),
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    // Final Summary
    echo str_repeat("=", 80) . "\n";
    echo "MASTER CLEANUP SUMMARY\n";
    echo str_repeat("=", 80) . "\n";
    echo "Total Customers Processed: " . count($customers) . "\n";
    echo "Successfully Fixed: {$successCount}\n";
    echo "Errors: {$errorCount}\n\n";
    
    if ($successCount > 0) {
        echo "✅ SUCCESSFULLY FIXED CUSTOMERS:\n";
        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                echo "• Customer ID {$result['id']}: {$result['name']} (Balance: {$result['final_balance']}, Issues Fixed: {$result['issues_fixed']})\n";
            }
        }
        echo "\n";
    }
    
    if ($errorCount > 0) {
        echo "❌ CUSTOMERS WITH ERRORS:\n";
        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                echo "• Customer ID {$result['id']}: {$result['name']} - {$result['error']}\n";
            }
        }
        echo "\n";
    }
    
    $totalFixed = count($fixedCustomers) + $successCount;
    echo "🎉 OVERALL PROGRESS:\n";
    echo "Total Customers: 25\n";
    echo "Fixed Customers: {$totalFixed}\n";
    echo "Remaining Issues: " . (25 - $totalFixed) . "\n";
    echo "Success Rate: " . round(($totalFixed / 25) * 100, 1) . "%\n";
    
    if ($dryRun) {
        echo "\n📝 This was a DRY RUN - no changes were made\n";
        echo "Run with --force to execute the cleanup\n";
    } else {
        echo "\n🎉 MASTER CLEANUP COMPLETED!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}