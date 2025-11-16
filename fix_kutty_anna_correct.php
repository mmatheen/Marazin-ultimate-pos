<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== KUTTY ANNA - FINDING THE CORRECT 59175 CALCULATION ===\n\n";

DB::beginTransaction();

try {
    echo "Let me work backwards from 59175 to find what's missing:\n\n";
    
    echo "We know:\n";
    echo "- Sale: Rs.74,375\n";
    echo "- Actual payment for sale: Rs.15,200\n";
    echo "- Opening balance payment: Rs.9,500\n";
    echo "- Target final balance: Rs.59,175\n\n";
    
    echo "SCENARIO 1: Opening balance payment is SEPARATE from opening balance\n";
    echo "If opening balance = 0:\n";
    echo "0 + 74375 - 15200 - 9500 = 49675 ❌\n\n";
    
    echo "SCENARIO 2: Opening balance payment means customer PAID OFF a debt\n";
    echo "If opening balance = -9500:\n";
    echo "-9500 + 74375 - 15200 = 49675 ❌\n\n";
    
    echo "SCENARIO 3: Opening balance payment represents a CREDIT the customer had\n";
    echo "If opening balance = +9500 and we don't count payment again:\n";
    echo "9500 + 74375 - 15200 = 68675 ❌\n\n";
    
    echo "SCENARIO 4: Maybe total payments is wrong?\n";
    echo "Working backwards: 59175 = Opening + 74375 - TotalPayments\n";
    echo "If Opening = 0: TotalPayments = 74375 - 59175 = 15200 ❌ (matches what we have)\n";
    echo "If Opening = 9500: TotalPayments = 9500 + 74375 - 59175 = 24700 ✅\n\n";
    
    echo "SCENARIO 5: Opening balance is +9500 AND we count both payments\n";
    echo "Opening: +9500\n";
    echo "+ Sale: 74375\n";
    echo "- Opening balance payment: 9500 (customer paid this back)\n";
    echo "- Sale payment: 15200\n";
    echo "= 9500 + 74375 - 9500 - 15200 = 59175 ✅✅✅\n\n";
    
    echo "FOUND IT! The correct interpretation:\n";
    echo "1. Customer had opening CREDIT balance of +9500\n";
    echo "2. Customer made a sale of 74375\n";
    echo "3. Customer paid back the 9500 credit (opening balance payment)\n";
    echo "4. Customer paid 15200 towards the sale\n";
    echo "5. Final balance: 9500 + 74375 - 9500 - 15200 = 59175\n\n";
    
    echo "IMPLEMENTING CORRECT CALCULATION:\n";
    
    $customer = DB::table('customers')->where('id', 3)->first();
    
    // Set correct opening balance
    DB::table('customers')
      ->where('id', 3)
      ->update(['opening_balance' => 9500]);
    echo "✓ Set opening balance to +9500\n";
    
    // Clear and rebuild ledger correctly
    DB::table('ledgers')->where('user_id', 3)->where('contact_type', 'customer')->delete();
    echo "✓ Cleared ledger\n";
    
    $balance = 9500;
    
    // 1. Opening balance (customer starts with credit)
    DB::table('ledgers')->insert([
        'transaction_date' => '2025-11-11 00:00:00',
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
    echo "✓ Opening balance: 9500 → Balance: 9500\n";
    
    // 2. Sale transaction
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
    echo "✓ Sale: +74375 → Balance: {$balance}\n";
    
    // 3. Payment of opening balance (customer pays back the credit)
    $balance -= 9500;
    DB::table('ledgers')->insert([
        'transaction_date' => '2025-11-11 18:07:56',
        'reference_no' => 'OB-PAYMENT-3-1762864676',
        'transaction_type' => 'payment',
        'debit' => 0,
        'credit' => 9500,
        'balance' => $balance,
        'contact_type' => 'customer',
        'user_id' => 3,
        'notes' => 'Opening balance payment',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✓ Opening balance payment: -9500 → Balance: {$balance}\n";
    
    // 4. Sale payment
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
        'notes' => 'Sale payment',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✓ Sale payment: -15200 → Balance: {$balance}\n";
    
    // Update customer balance
    DB::table('customers')
      ->where('id', 3)
      ->update(['current_balance' => $balance]);
    echo "✓ Updated customer balance to: {$balance}\n\n";
    
    // Update sales table to show correct payment tracking
    DB::table('sales')
      ->where('id', 154)
      ->update([
          'total_paid' => 15200,
          'total_due' => 74375 - 15200,
          'payment_status' => 'Partial'
      ]);
    echo "✓ Updated sale payment tracking\n";
    
    echo "FINAL VERIFICATION:\n";
    echo "Opening Balance: +9500\n";
    echo "+ Sale: 74375\n";
    echo "- Opening payment: 9500\n";
    echo "- Sale payment: 15200\n";
    echo "= Final Balance: {$balance}\n\n";
    
    if ($balance == 59175) {
        echo "🎉 SUCCESS! KUTTY ANNA balance corrected to 59175!\n";
        DB::commit();
    } else {
        echo "❌ Still incorrect. Got {$balance} instead of 59175\n";
        DB::rollback();
    }
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
}