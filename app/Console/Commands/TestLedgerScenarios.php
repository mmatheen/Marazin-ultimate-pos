<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnifiedLedgerService;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use Carbon\Carbon;

class TestLedgerScenarios extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:ledger-scenarios';

    /**
     * The console command description.
     */
    protected $description = 'Test all ledger scenarios to verify UnifiedLedgerService works correctly';

    private $unifiedLedgerService;
    private $testResults = [];
    private $testCustomerId;
    private $testSupplierId;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔬 Starting Comprehensive Ledger Testing...');
        $this->newLine();
        
        $this->unifiedLedgerService = app(UnifiedLedgerService::class);
        
        $this->setupTestEnvironment();
        $this->runAllTests();
        $this->showTestResults();
        $this->cleanup();
        
        return 0;
    }

    private function setupTestEnvironment()
    {
        $this->info('📋 Setting up test environment...');
        
        // Clear any existing test data
        $this->cleanupTestData();
        
        // Create test customer and supplier
        $this->createTestCustomer();
        $this->createTestSupplier();
        
        $this->info('✅ Test environment ready!');
        $this->newLine();
    }

    private function cleanupTestData()
    {
        $this->info('🧹 Cleaning up existing test data...');
        
        // Remove test ledger entries
        Ledger::where('notes', 'LIKE', '%TEST%')->delete();
        
        // Remove test customers/suppliers if they exist
        Customer::where('first_name', 'LIKE', '%TEST%')->delete();
        Supplier::where('first_name', 'LIKE', '%TEST%')->delete();
    }

    private function createTestCustomer()
    {
        $customer = Customer::create([
            'first_name' => 'TEST Customer',
            'last_name' => 'Test',
            'email' => 'test.customer@example.com',
            'mobile_no' => '1234567890',
            'address' => 'Test Address',
            'opening_balance' => 1000.00,
            'current_balance' => 0,
            'location_id' => 1
        ]);
        
        $this->testCustomerId = $customer->id;
        $this->info("👤 Created test customer ID: {$this->testCustomerId}");
    }

    private function createTestSupplier()
    {
        $supplier = Supplier::create([
            'first_name' => 'TEST Supplier',
            'last_name' => 'Test',
            'email' => 'test.supplier@example.com',
            'mobile_no' => '0987654321',
            'address' => 'Supplier Test Address',
            'opening_balance' => 2000.00,
            'current_balance' => 0,
            'location_id' => 1
        ]);
        
        $this->testSupplierId = $supplier->id;
        $this->info("🏭 Created test supplier ID: {$this->testSupplierId}");
    }

    private function runAllTests()
    {
        $this->info('🚀 Running comprehensive ledger tests...');
        $this->newLine();
        
        $this->testOpeningBalances();
        $this->testSaleScenarios();
        $this->testPurchaseScenarios();
        $this->testPaymentScenarios();
        $this->testReturnScenarios();
        $this->testUpdateScenarios();
        $this->verifyDataIntegrity();
    }

    private function testOpeningBalances()
    {
        $this->info('📊 Testing Opening Balances...');
        
        try {
            // Test customer opening balance
            $customerResult = $this->unifiedLedgerService->recordOpeningBalance(
                $this->testCustomerId, 
                'customer', 
                1000.00, 
                'TEST: Customer opening balance'
            );
            
            // Test supplier opening balance
            $supplierResult = $this->unifiedLedgerService->recordOpeningBalance(
                $this->testSupplierId, 
                'supplier', 
                2000.00, 
                'TEST: Supplier opening balance'
            );
            
            $this->addTestResult('Opening Balances', true, 'Customer and supplier opening balances recorded');
            $this->info('   ✅ Opening balances recorded successfully');
            
        } catch (\Exception $e) {
            $this->addTestResult('Opening Balances', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testSaleScenarios()
    {
        $this->info('🛒 Testing Sale Scenarios...');
        
        try {
            // Create a test sale
            $sale = Sale::create([
                'customer_id' => $this->testCustomerId,
                'invoice_no' => 'TEST-INV-001',
                'reference_no' => 'TEST-REF-001',
                'sales_date' => Carbon::now(),
                'subtotal' => 500.00,
                'final_total' => 500.00,
                'total_paid' => 300.00,
                'status' => 'final',
                'location_id' => 1,
                'user_id' => 1
            ]);
            
            // Test sale recording
            $this->unifiedLedgerService->recordSale($sale);
            
            // Verify ledger entry was created
            $ledgerEntry = Ledger::where('reference_no', 'TEST-INV-001')
                ->where('transaction_type', 'sale')
                ->where('user_id', $this->testCustomerId)
                ->first();
                
            if ($ledgerEntry && $ledgerEntry->debit == 500.00) {
                $this->addTestResult('Sale Recording', true, 'Sale ledger entry created with correct debit amount');
                $this->info('   ✅ Sale recorded correctly: ₹500.00 (debit)');
            } else {
                $this->addTestResult('Sale Recording', false, 'Sale ledger entry not found or incorrect amount');
                $this->error('   ❌ Sale recording failed');
            }
            
        } catch (\Exception $e) {
            $this->addTestResult('Sale Recording', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testPurchaseScenarios()
    {
        $this->info('📦 Testing Purchase Scenarios...');
        
        try {
            // Create a test purchase
            $purchase = Purchase::create([
                'supplier_id' => $this->testSupplierId,
                'reference_no' => 'TEST-PUR-001',
                'purchase_date' => Carbon::now(),
                'total' => 800.00,
                'final_total' => 800.00,
                'total_paid' => 600.00,
                'purchasing_status' => 'Received',
                'location_id' => 1,
                'user_id' => 1
            ]);
            
            // Test purchase recording
            $this->unifiedLedgerService->recordPurchase($purchase);
            
            // The service will use the purchase reference_no, so check for that
            $expectedRef = $purchase->reference_no ?: 'PUR-' . $purchase->id;
            
            // Verify ledger entry was created
            $ledgerEntry = Ledger::where('reference_no', $expectedRef)
                ->where('transaction_type', 'purchase')
                ->where('user_id', $this->testSupplierId)
                ->first();
                
            if ($ledgerEntry && $ledgerEntry->credit == 800.00) {
                $this->addTestResult('Purchase Recording', true, 'Purchase ledger entry created with correct credit amount');
                $this->info('   ✅ Purchase recorded correctly: ₹800.00 (credit) with ref: ' . $expectedRef);
            } else {
                $this->addTestResult('Purchase Recording', false, 'Purchase ledger entry not found or incorrect amount');
                $this->error('   ❌ Purchase recording failed. Expected ref: ' . $expectedRef);
                if ($ledgerEntry) {
                    $this->error('   Found credit: ₹' . $ledgerEntry->credit);
                }
            }
            
        } catch (\Exception $e) {
            $this->addTestResult('Purchase Recording', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testPaymentScenarios()
    {
        $this->info('💰 Testing Payment Scenarios...');
        
        try {
            // Test customer payment
            $customerPayment = Payment::create([
                'customer_id' => $this->testCustomerId,
                'reference_no' => 'TEST-PAY-001',
                'amount' => 300.00,
                'payment_date' => Carbon::now(),
                'payment_type' => 'sale',
                'payment_method' => 'cash',
                'user_id' => 1
            ]);
            
            $this->unifiedLedgerService->recordSalePayment($customerPayment);
            
            // Verify customer payment ledger entry
            $customerLedger = Ledger::where('reference_no', 'TEST-PAY-001')
                ->where('transaction_type', 'payments')
                ->where('user_id', $this->testCustomerId)
                ->first();
                
            if ($customerLedger && $customerLedger->credit == 300.00) {
                $this->addTestResult('Customer Payment', true, 'Customer payment recorded correctly');
                $this->info('   ✅ Customer payment recorded: ₹300.00 (credit)');
            } else {
                $this->addTestResult('Customer Payment', false, 'Customer payment not recorded correctly');
                $this->error('   ❌ Customer payment failed');
            }
            
            // Test supplier payment
            $supplierPayment = Payment::create([
                'supplier_id' => $this->testSupplierId,
                'reference_no' => 'TEST-PAY-002',
                'amount' => 600.00,
                'payment_date' => Carbon::now(),
                'payment_type' => 'purchase',
                'payment_method' => 'bank_transfer',
                'user_id' => 1
            ]);
            
            $this->unifiedLedgerService->recordPurchasePayment($supplierPayment);
            
            // Verify supplier payment ledger entry
            $supplierLedger = Ledger::where('reference_no', 'TEST-PAY-002')
                ->where('transaction_type', 'payments')
                ->where('user_id', $this->testSupplierId)
                ->first();
                
            if ($supplierLedger && $supplierLedger->debit == 600.00) {
                $this->addTestResult('Supplier Payment', true, 'Supplier payment recorded correctly');
                $this->info('   ✅ Supplier payment recorded: ₹600.00 (debit)');
            } else {
                $this->addTestResult('Supplier Payment', false, 'Supplier payment not recorded correctly');
                $this->error('   ❌ Supplier payment failed');
            }
            
        } catch (\Exception $e) {
            $this->addTestResult('Payment Scenarios', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testReturnScenarios()
    {
        $this->info('↩️ Testing Return Scenarios...');
        
        try {
            // Test sale return
            $saleReturn = SalesReturn::create([
                'customer_id' => $this->testCustomerId,
                'return_date' => Carbon::now(),
                'return_total' => 100.00,
                'total_paid' => 0,
                'notes' => 'TEST: Sale return',
                'location_id' => 1,
                'user_id' => 1
            ]);
            
            $this->unifiedLedgerService->recordSaleReturn($saleReturn);
            
            // Verify sale return ledger entry
            $saleReturnLedger = Ledger::where('reference_no', 'SR-' . $saleReturn->id)
                ->where('transaction_type', 'sale_return')
                ->where('user_id', $this->testCustomerId)
                ->first();
                
            if ($saleReturnLedger && $saleReturnLedger->credit == 100.00) {
                $this->addTestResult('Sale Return', true, 'Sale return recorded correctly');
                $this->info('   ✅ Sale return recorded: ₹100.00 (credit)');
            } else {
                $this->addTestResult('Sale Return', false, 'Sale return not recorded correctly');
                $this->error('   ❌ Sale return failed');
            }
            
            // Test purchase return
            $purchaseReturn = PurchaseReturn::create([
                'supplier_id' => $this->testSupplierId,
                'return_date' => Carbon::now(),
                'return_total' => 150.00,
                'notes' => 'TEST: Purchase return',
                'location_id' => 1,
                'user_id' => 1
            ]);
            
            $this->unifiedLedgerService->recordPurchaseReturn($purchaseReturn);
            
            // Verify purchase return ledger entry
            $purchaseReturnLedger = Ledger::where('reference_no', 'PR-' . $purchaseReturn->id)
                ->where('transaction_type', 'purchase_return')
                ->where('user_id', $this->testSupplierId)
                ->first();
                
            if ($purchaseReturnLedger && $purchaseReturnLedger->debit == 150.00) {
                $this->addTestResult('Purchase Return', true, 'Purchase return recorded correctly');
                $this->info('   ✅ Purchase return recorded: ₹150.00 (debit)');
            } else {
                $this->addTestResult('Purchase Return', false, 'Purchase return not recorded correctly');
                $this->error('   ❌ Purchase return failed');
            }
            
        } catch (\Exception $e) {
            $this->addTestResult('Return Scenarios', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testUpdateScenarios()
    {
        $this->info('🔄 Testing Update Scenarios...');
        
        try {
            // Get the test sale we created earlier
            $sale = Sale::where('invoice_no', 'TEST-INV-001')->first();
            
            if (!$sale) {
                $this->addTestResult('Sale Update', false, 'Test sale not found for update');
                $this->error('   ❌ Test sale not found');
                return;
            }
            
            // Count ledger entries before update
            $beforeCount = Ledger::where('reference_no', 'TEST-INV-001')
                ->where('transaction_type', 'sale')
                ->count();
            
            // Update the sale amount
            $sale->final_total = 750.00;
            $sale->save();
            
            // Perform update using the service
            $this->unifiedLedgerService->updateSale($sale, 'TEST-INV-001');
            
            // Count ledger entries after update
            $afterCount = Ledger::where('reference_no', 'TEST-INV-001')
                ->where('transaction_type', 'sale')
                ->count();
            
            // Verify updated amount
            $updatedEntry = Ledger::where('reference_no', 'TEST-INV-001')
                ->where('transaction_type', 'sale')
                ->where('user_id', $this->testCustomerId)
                ->first();
                
            if ($updatedEntry && $updatedEntry->debit == 750.00 && $afterCount == 1) {
                $this->addTestResult('Sale Update', true, 'Sale updated correctly with cleanup');
                $this->info('   ✅ Sale updated: ₹500.00 → ₹750.00 (debit)');
            } else {
                $this->addTestResult('Sale Update', false, 'Sale update failed or created duplicates');
                $this->error('   ❌ Sale update failed');
            }
            
        } catch (\Exception $e) {
            $this->addTestResult('Update Scenarios', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function verifyDataIntegrity()
    {
        $this->info('🔍 Verifying Data Integrity...');
        
        try {
            // Check for duplicate entries
            $duplicates = Ledger::select('reference_no', 'transaction_type', 'user_id')
                ->where('notes', 'LIKE', '%TEST%')
                ->groupBy('reference_no', 'transaction_type', 'user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();
                
            if ($duplicates->isEmpty()) {
                $this->addTestResult('No Duplicates', true, 'No duplicate ledger entries found');
                $this->info('   ✅ No duplicate entries found');
            } else {
                $this->addTestResult('No Duplicates', false, 'Duplicate entries detected');
                $this->error('   ❌ Duplicate entries found: ' . $duplicates->count());
            }
            
            // Check running balances
            $customerLedger = $this->unifiedLedgerService->getCustomerLedger(
                $this->testCustomerId, 
                Carbon::now()->subDays(1), 
                Carbon::now()->addDays(1)
            );
            
            $supplierLedger = $this->unifiedLedgerService->getSupplierLedger(
                $this->testSupplierId, 
                Carbon::now()->subDays(1), 
                Carbon::now()->addDays(1)
            );
            
            if ($customerLedger && $supplierLedger) {
                $this->addTestResult('Ledger Generation', true, 'Customer and supplier ledgers generated successfully');
                $this->info('   ✅ Ledger generation working');
                $this->info('   📊 Customer entries: ' . count($customerLedger));
                $this->info('   📊 Supplier entries: ' . count($supplierLedger));
            } else {
                $this->addTestResult('Ledger Generation', false, 'Failed to generate ledgers');
                $this->error('   ❌ Ledger generation failed');
            }
            
        } catch (\Exception $e) {
            $this->addTestResult('Data Integrity', false, $e->getMessage());
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function addTestResult($testName, $passed, $message)
    {
        $this->testResults[] = [
            'test' => $testName,
            'passed' => $passed,
            'message' => $message
        ];
    }

    private function showTestResults()
    {
        $this->info('📋 TEST RESULTS SUMMARY');
        $this->line(str_repeat('=', 60));
        $this->newLine();
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $this->line(sprintf('%-20s %s - %s', $result['test'], $status, $result['message']));
            
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('Total Tests: ' . count($this->testResults));
        $this->info('✅ Passed: ' . $passed);
        $this->info('❌ Failed: ' . $failed);
        
        if ($failed == 0) {
            $this->newLine();
            $this->info('🎉 ALL TESTS PASSED! UnifiedLedgerService is working perfectly!');
        } else {
            $this->newLine();
            $this->warn('⚠️  Some tests failed. Please review the implementation.');
        }
        
        // Show actual ledger data for verification
        $this->showActualLedgerData();
    }

    private function showActualLedgerData()
    {
        $this->newLine();
        $this->info('📊 ACTUAL LEDGER DATA');
        $this->line(str_repeat('=', 100));
        
        $testEntries = Ledger::where('notes', 'LIKE', '%TEST%')
            ->orWhere('reference_no', 'LIKE', 'TEST-%')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
            
        if ($testEntries->isEmpty()) {
            $this->warn('No test ledger entries found.');
            return;
        }
        
        $headers = ['Reference', 'Contact Type', 'Transaction', 'Debit', 'Credit', 'Balance'];
        $rows = [];
        
        foreach ($testEntries as $entry) {
            $rows[] = [
                substr($entry->reference_no, 0, 14),
                $entry->contact_type,
                $entry->transaction_type,
                '₹' . number_format($entry->debit, 2),
                '₹' . number_format($entry->credit, 2),
                '₹' . number_format($entry->balance, 2)
            ];
        }
        
        $this->table($headers, $rows);
        $this->line(str_repeat('=', 100));
    }

    private function cleanup()
    {
        $this->newLine();
        $this->info('🧹 Cleaning up test data...');
        $this->cleanupTestData();
        $this->info('✅ Cleanup complete!');
    }
}