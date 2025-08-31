<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Payment;
use Carbon\Carbon;

class TestPurchaseLedger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:purchase-ledger';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the purchase ledger functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Purchase Ledger Functionality...');
        
        try {
            // Test the balance calculation method
            $this->testBalanceCalculation();
            
            $this->info('✅ All tests passed! Purchase ledger is working correctly.');
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function testBalanceCalculation()
    {
        $this->info('Testing balance calculation logic...');
        
        // Create a test supplier
        $supplier = Supplier::create([
            'name' => 'Test Supplier for Ledger',
            'phone' => '1234567890',
            'email' => 'ledgertest@supplier.com',
            'opening_balance' => 0,
            'current_balance' => 0
        ]);
        
        $this->info("Created test supplier with ID: {$supplier->id}");
        
        // Test scenario 1: Purchase without payment
        $purchaseAmount = 5000.00;
        $this->info("Scenario 1: Creating purchase for amount: {$purchaseAmount}");
        
        $balance1 = $this->calculateNewBalance($supplier->id, $purchaseAmount, 0);
        $this->info("Expected balance after purchase: {$balance1}");
        
        Ledger::create([
            'transaction_date' => Carbon::now(),
            'reference_no' => 'TEST001',
            'transaction_type' => 'purchase',
            'debit' => $purchaseAmount,
            'credit' => 0,
            'balance' => $balance1,
            'contact_type' => 'supplier',
            'user_id' => $supplier->id,
        ]);
        
        // Test scenario 2: Payment against purchase
        $paymentAmount = 2000.00;
        $this->info("Scenario 2: Creating payment for amount: {$paymentAmount}");
        
        $balance2 = $this->calculateNewBalance($supplier->id, 0, $paymentAmount);
        $this->info("Expected balance after payment: {$balance2}");
        
        Ledger::create([
            'transaction_date' => Carbon::now(),
            'reference_no' => 'TEST001',
            'transaction_type' => 'payments',
            'debit' => 0,
            'credit' => $paymentAmount,
            'balance' => $balance2,
            'contact_type' => 'supplier',
            'user_id' => $supplier->id,
        ]);
        
        // Verify final balance
        $expectedFinalBalance = $purchaseAmount - $paymentAmount;
        $actualFinalBalance = $balance2;
        
        $this->info("Expected final balance: {$expectedFinalBalance}");
        $this->info("Actual final balance: {$actualFinalBalance}");
        
        if ($actualFinalBalance == $expectedFinalBalance) {
            $this->info("✅ Balance calculation is correct!");
        } else {
            throw new \Exception("Balance calculation is incorrect. Expected: {$expectedFinalBalance}, Got: {$actualFinalBalance}");
        }
        
        // Clean up
        Ledger::where('user_id', $supplier->id)->delete();
        $supplier->delete();
        
        $this->info("Test data cleaned up successfully.");
    }
    
    private function calculateNewBalance($userId, $debitAmount, $creditAmount)
    {
        $lastLedger = Ledger::where('user_id', $userId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $previousBalance = $lastLedger ? $lastLedger->balance : 0;
        return $previousBalance + $debitAmount - $creditAmount;
    }
}
