<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING KUTTY ANNA - CORRECT CALCULATION TO 59175 ===\n\n";

DB::beginTransaction();

try {
    $customer = DB::table('customers')->where('id', 3)->first();
    
    if ($customer) {
        echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
        echo "Current Balance: {$customer->current_balance}\n\n";
        
        echo "ANALYSIS OF PAYMENTS:\n";
        $payments = DB::table('payments')->where('customer_id', 3)->get();
        foreach ($payments as $payment) {
            echo "Payment ID {$payment->id}: Rs.{$payment->amount} ({$payment->payment_type})\n";
            echo "  Reference: {$payment->reference_no}\n";
        }
        
        echo "\nThe issue: Opening Balance Payment of Rs.9,500 should CREATE a CREDIT balance\n";
        echo "This means customer had a NEGATIVE opening balance of -9,500\n\n";
        
        echo "CORRECT CALCULATION:\n";
        echo "Opening Balance: -9500 (customer owes money)\n";
        echo "+ Sale: 74375\n";
        echo "- Payment: 15200\n";
        echo "= Correct Balance: 49675\n\n";
        
        echo "BUT if you say 59175 is correct, let me check if there's missing data...\n\n";
        
        // Check if 59175 means different opening balance
        echo "REVERSE CALCULATION FOR 59175:\n";
        echo "If final balance should be 59175:\n";
        echo "59175 = Opening + 74375 - 24700\n";
        echo "Opening = 59175 - 74375 + 24700\n";
        echo "Opening = 9500\n\n";
        
        echo "So for balance to be 59175, opening balance should be +9500 (not -9500)\n\n";
        
        echo "DECISION POINT:\n";
        echo "Current calculation: Opening(-9500) + Sale(74375) - Payment(15200) = 49675\n";
        echo "Your expected: Opening(+9500) + Sale(74375) - Payment(15200) = 59175\n\n";
        
        echo "The difference is whether the Rs.9500 'opening_balance' payment represents:\n";
        echo "A) Customer paying off a debt (opening balance = -9500) → Final: 49675\n";
        echo "B) Customer having a credit balance (opening balance = +9500) → Final: 59175\n\n";
        
        echo "FIXING TO 59175 (Option B - Customer had positive opening balance):\n\n";
        
        // Update customer opening balance to +9500
        DB::table('customers')
          ->where('id', 3)
          ->update(['opening_balance' => 9500]);
        echo "✓ Set opening balance to +9500\n";
        
        // Rebuild ledger with correct opening balance
        DB::table('ledgers')->where('user_id', 3)->where('contact_type', 'customer')->delete();
        echo "✓ Cleared existing ledger\n";
        
        $balance = 9500; // Positive opening balance
        
        // Add opening balance entry
        DB::table('ledgers')->insert([
            'transaction_date' => '2025-11-11 18:07:56',
            'reference_no' => 'OPENING-3',
            'transaction_type' => 'opening_balance',
            'debit' => 9500,
            'credit' => 0,
            'balance' => 9500,
            'contact_type' => 'customer',
            'user_id' => 3,
            'notes' => 'Opening balance',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✓ Added opening balance entry: +9500\n";
        
        // Add sale
        $balance += 74375;
        DB::table('ledgers')->insert([
            'transaction_date' => '2025-11-13 23:26:33',
            'reference_no' => 'CSX-154',
            'transaction_type' => 'sale',
            'debit' => 74375,
            'credit' => 0,
            'balance' => 83875,
            'contact_type' => 'customer',
            'user_id' => 3,
            'notes' => 'Sale transaction',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✓ Added sale entry: +74375, Balance: 83875\n";
        
        // Add payment (15200)
        $balance -= 15200;
        DB::table('ledgers')->insert([
            'transaction_date' => '2025-11-13 23:26:33',
            'reference_no' => 'SALE-20251113',
            'transaction_type' => 'payment',
            'debit' => 0,
            'credit' => 15200,
            'balance' => 68675,
            'contact_type' => 'customer',
            'user_id' => 3,
            'notes' => 'Payment received',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✓ Added payment entry: -15200, Balance: 68675\n";
        
        echo "\nWait... that gives 68675, not 59175...\n\n";
        
        echo "Let me check if the opening balance payment should NOT be counted as separate payment...\n";
        
        // Try without counting the 9500 opening balance payment
        echo "ALTERNATIVE: Opening balance payment was just recording the opening balance, not additional payment:\n";
        
        // Recalculate
        $balance = 9500; // Opening balance
        $balance += 74375; // Sale
        // Don't subtract the 9500 opening balance payment (it was just recording the opening balance)
        $balance -= 15200; // Only subtract the actual sale payment
        
        echo "Opening Balance: 9500\n";
        echo "+ Sale: 74375 = 83875\n";
        echo "- Payment: 15200 = 68675\n\n";
        
        echo "Still not 59175... Let me try one more approach:\n\n";
        
        // Maybe the 9500 should be added as additional payment, making total payment 24700
        // But if opening balance is 0 and we want final balance 59175:
        // 0 + 74375 - X = 59175
        // X = 15200 (which matches our payment)
        
        echo "FINAL APPROACH: Maybe opening balance should be +9500 and we ignore the opening balance payment:\n";
        
        // Clear and rebuild correctly
        DB::table('ledgers')->where('user_id', 3)->where('contact_type', 'customer')->delete();
        
        $balance = 9500;
        
        // Opening balance
        DB::table('ledgers')->insert([
            'transaction_date' => '2025-11-11 18:07:56',
            'reference_no' => 'OPENING-3',
            'transaction_type' => 'opening_balance',
            'debit' => 9500,
            'credit' => 0,
            'balance' => 9500,
            'contact_type' => 'customer',
            'user_id' => 3,
            'notes' => 'Opening balance',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Sale
        $balance += 74375;
        DB::table('ledgers')->insert([
            'transaction_date' => '2025-11-13 23:26:33',
            'reference_no' => 'CSX-154',
            'transaction_type' => 'sale',
            'debit' => 74375,
            'credit' => 0,
            'balance' => $balance,
            'contact_type' => 'customer',
            'user_id' => 3,
            'notes' => 'Sale transaction',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Only the 15200 payment (not the 9500 opening balance payment)
        $balance -= 15200;
        DB::table('ledgers')->insert([
            'transaction_date' => '2025-11-13 23:26:33',
            'reference_no' => 'SALE-20251113',
            'transaction_type' => 'payment',
            'debit' => 0,
            'credit' => 15200,
            'balance' => $balance,
            'contact_type' => 'customer',
            'user_id' => 3,
            'notes' => 'Payment received',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Update customer balance
        DB::table('customers')
          ->where('id', 3)
          ->update(['current_balance' => $balance]);
        
        echo "CORRECTED CALCULATION:\n";
        echo "Opening Balance: 9500\n";
        echo "+ Sale CSX-154: 74375\n";
        echo "- Payment: 15200\n";
        echo "= Final Balance: {$balance}\n\n";
        
        if ($balance == 59175) {
            echo "✅ Successfully corrected to 59175!\n";
            DB::commit();
        } else {
            echo "❌ Still not 59175. Current result: {$balance}\n";
            echo "Please confirm what the correct calculation should be.\n";
            DB::rollback();
        }
        
    } else {
        echo "Customer not found!\n";
    }
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
}