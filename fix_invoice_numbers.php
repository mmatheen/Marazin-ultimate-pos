<?php

/**
 * Fix Invoice Numbers Script
 * This script fixes invoice numbers that have brackets or special characters in the prefix
 * 
 * Run this script from the project root:
 * php fix_invoice_numbers.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Starting invoice number fix...\n\n";

try {
    DB::beginTransaction();
    
    // Find all sales with invoice numbers containing brackets or special characters
    $sales = DB::table('sales')
        ->where('invoice_no', 'REGEXP', '[^a-zA-Z0-9-]')
        ->get();
    
    echo "Found " . $sales->count() . " invoices with special characters\n\n";
    
    $fixed = 0;
    $errors = 0;
    
    foreach ($sales as $sale) {
        $oldInvoiceNo = $sale->invoice_no;
        
        // Extract the parts: prefix and number
        // Pattern: PREFIX(-NUMBER or PREFIX-NUMBER
        if (preg_match('/^([A-Z]+)\(?-?0*(\d+)$/', $oldInvoiceNo, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];
            
            // Clean the prefix - remove any non-alphabetic characters
            $cleanPrefix = preg_replace('/[^A-Z]/', '', $prefix);
            
            // If prefix is only 2 characters (like "AT"), try to get the full prefix from location
            if (strlen($cleanPrefix) <= 2 && $sale->location_id) {
                $location = DB::table('locations')->find($sale->location_id);
                if ($location) {
                    // Get clean location name and generate proper prefix
                    $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $location->name);
                    $words = preg_split('/\s+/', trim($cleanName));
                    
                    $newPrefix = '';
                    foreach ($words as $word) {
                        if (strlen($newPrefix) >= 3) break;
                        if (!empty($word)) {
                            $newPrefix .= strtoupper(substr($word, 0, 1));
                        }
                    }
                    while (strlen($newPrefix) < 3) {
                        $newPrefix .= 'X';
                    }
                    
                    $cleanPrefix = $newPrefix;
                }
            }
            
            // Generate new invoice number
            $newInvoiceNo = $cleanPrefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            
            // Check if this new invoice number already exists
            $exists = DB::table('sales')
                ->where('invoice_no', $newInvoiceNo)
                ->where('id', '!=', $sale->id)
                ->exists();
            
            if ($exists) {
                echo "⚠️  Skipping: {$oldInvoiceNo} -> {$newInvoiceNo} (already exists)\n";
                $errors++;
                continue;
            }
            
            // Update the invoice number
            DB::table('sales')
                ->where('id', $sale->id)
                ->update(['invoice_no' => $newInvoiceNo]);
            
            // Also update any related records
            // Update payments
            DB::table('payments')
                ->where('reference_no', $oldInvoiceNo)
                ->update(['reference_no' => $newInvoiceNo]);
            
            // Update ledgers
            DB::table('ledgers')
                ->where('reference_no', $oldInvoiceNo)
                ->update(['reference_no' => $newInvoiceNo]);
            
            echo "✅ Fixed: {$oldInvoiceNo} -> {$newInvoiceNo}\n";
            $fixed++;
        } else {
            echo "⚠️  Could not parse: {$oldInvoiceNo}\n";
            $errors++;
        }
    }
    
    DB::commit();
    
    echo "\n========================================\n";
    echo "Fix completed!\n";
    echo "Total invoices processed: " . $sales->count() . "\n";
    echo "Successfully fixed: {$fixed}\n";
    echo "Errors/Skipped: {$errors}\n";
    echo "========================================\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No changes were made.\n";
}
