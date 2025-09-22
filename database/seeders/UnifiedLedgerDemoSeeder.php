<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;

class UnifiedLedgerDemoSeeder extends Seeder
{
    private $unifiedLedgerService;

    public function __construct()
    {
        $this->unifiedLedgerService = new UnifiedLedgerService();
    }

    /**
     * Run the database seeds.
     * This creates the realistic scenario described in the user request
     */
    public function run()
    {
        // Clear existing demo data
        $this->clearDemoData();

        // Create customers and suppliers
        $customerA = $this->createCustomerA();
        $customerB = $this->createCustomerB();
        $supplierX = $this->createSupplierX();
        $supplierY = $this->createSupplierY();
        $supplierZ = $this->createSupplierZ();

        // Create the unified ledger scenario step by step
        $this->createUnifiedLedgerScenario($customerA, $customerB, $supplierX, $supplierY, $supplierZ);

        $this->command->info('Unified Ledger Demo Data Created Successfully!');
        $this->command->info('Scenario includes:');
        $this->command->info('- Opening balances for customers and suppliers');
        $this->command->info('- Sales and purchases');
        $this->command->info('- Payments and returns');
        $this->command->info('- Return payments');
        $this->command->info('- All with proper debit/credit logic and running balances');
    }

    private function clearDemoData()
    {
        // Clean up any existing demo data
        DB::table('ledgers')->where('reference_no', 'like', 'DEMO-%')->delete();
        DB::table('customers')->where('first_name', 'like', 'Demo Customer%')->delete();
        DB::table('suppliers')->where('first_name', 'like', 'Demo Supplier%')->delete();
    }

    private function createCustomerA()
    {
        $customer = Customer::create([
            'first_name' => 'Demo Customer',
            'last_name' => 'A',
            'mobile_no' => '1234567890',
            'email' => 'customer.a@demo.com',
            'address' => 'Customer A Address',
            'opening_balance' => 5000.00, // Customer owes us 5000
            'current_balance' => 0,
        ]);

        // Record opening balance in ledger
        $this->unifiedLedgerService->recordOpeningBalance(
            $customer->id,
            'customer',
            5000.00,
            'Customer A opening balance - owes us'
        );

        return $customer;
    }

    private function createCustomerB()
    {
        $customer = Customer::create([
            'first_name' => 'Demo Customer',
            'last_name' => 'B',
            'mobile_no' => '1234567891',
            'email' => 'customer.b@demo.com',
            'address' => 'Customer B Address',
            'opening_balance' => 2000.00, // Customer owes us 2000
            'current_balance' => 0,
        ]);

        return $customer;
    }

    private function createSupplierX()
    {
        $supplier = Supplier::create([
            'first_name' => 'Demo Supplier',
            'last_name' => 'X',
            'mobile_no' => '2234567890',
            'email' => 'supplier.x@demo.com',
            'address' => 'Supplier X Address',
            'opening_balance' => 3000.00, // We owe supplier 3000
            'current_balance' => 0,
        ]);

        // Record opening balance in ledger
        $this->unifiedLedgerService->recordOpeningBalance(
            $supplier->id,
            'supplier',
            3000.00,
            'Supplier X opening balance - we owe them'
        );

        return $supplier;
    }

    private function createSupplierY()
    {
        $supplier = Supplier::create([
            'first_name' => 'Demo Supplier',
            'last_name' => 'Y',
            'mobile_no' => '2234567891',
            'email' => 'supplier.y@demo.com',
            'address' => 'Supplier Y Address',
            'opening_balance' => 1000.00, // We owe supplier 1000
            'current_balance' => 0,
        ]);

        return $supplier;
    }

    private function createSupplierZ()
    {
        $supplier = Supplier::create([
            'first_name' => 'Demo Supplier',
            'last_name' => 'Z',
            'mobile_no' => '2234567892',
            'email' => 'supplier.z@demo.com',
            'address' => 'Supplier Z Address',
            'opening_balance' => 0.00, // No opening balance
            'current_balance' => 0,
        ]);

        return $supplier;
    }

    private function createUnifiedLedgerScenario($customerA, $customerB, $supplierX, $supplierY, $supplierZ)
    {
        $baseDate = Carbon::parse('2025-09-21');

        // Entry 1: Customer A Opening Balance (already created above)
        // Debit: 5000, Credit: 0, Running Balance: 5000

        // Entry 2: Supplier X Opening Balance (already created above)
        // Debit: 0, Credit: 3000, Running Balance: 2000 (5000 - 3000)

        // Entry 3: Customer A Sale - 10:00
        $saleA1 = $this->createSale($customerA, 'DEMO-CUSTA-SALE1', 9000.00, $baseDate->copy()->setTime(10, 0));
        // Debit: 9000, Credit: 0, Running Balance: 14000

        // Entry 4: Supplier X Purchase - 10:15
        $purchaseX1 = $this->createPurchase($supplierX, 'DEMO-SUPX-PUR1', 8000.00, $baseDate->copy()->setTime(10, 15));
        // Debit: 0, Credit: 8000, Running Balance: 11000 (14000 - 8000)

        // Entry 5: Customer A Payment - 11:00
        $this->createPayment($customerA, null, 'DEMO-CUSTA-PAY1', 3000.00, $baseDate->copy()->setTime(11, 0), 'sale');
        // Debit: 0, Credit: 3000, Running Balance: 11000 (11000 - 3000)

        // Entry 6: Supplier X Payment - 11:15
        $this->createPayment(null, $supplierX, 'DEMO-SUPX-PAY1', 5000.00, $baseDate->copy()->setTime(11, 15), 'purchase');
        // Debit: 5000, Credit: 0, Running Balance: 6000 (11000 + 5000 - 0)

        // Entry 7: Customer A Return Payment - 12:00
        $this->createReturnPayment($customerA, null, 'DEMO-CUSTA-RET1', 4200.00, $baseDate->copy()->setTime(12, 0), 'customer');
        // Debit: 4200, Credit: 0, Running Balance: 15200 (6000 + 4200)

        // Entry 8: Supplier X Purchase Return - 12:15
        $this->createPurchaseReturn($supplierX, 'DEMO-SUPX-PR1', 2000.00, $baseDate->copy()->setTime(12, 15));
        // Debit: 2000, Credit: 0, Running Balance: 4000 (15200 + 2000 - 0)

        // Entry 9: Customer B Opening Balance - 13:00
        $this->unifiedLedgerService->recordOpeningBalance(
            $customerB->id,
            'customer',
            2000.00,
            'Customer B opening balance'
        );
        // This goes to customer B's ledger

        // Entry 10: Customer B Sale - 13:15
        $saleB1 = $this->createSale($customerB, 'DEMO-CUSTB-SALE1', 5000.00, $baseDate->copy()->setTime(13, 15));

        // Entry 11: Supplier X Return Payment - 13:30
        $this->createReturnPayment(null, $supplierX, 'DEMO-SUPX-RP1', 1500.00, $baseDate->copy()->setTime(13, 30), 'supplier');
        // Debit: 0, Credit: 1500, Running Balance: 5500 (4000 - 1500)

        // Entry 12: Supplier Y Opening Balance - 14:00
        $this->unifiedLedgerService->recordOpeningBalance(
            $supplierY->id,
            'supplier',
            1000.00,
            'Supplier Y opening balance'
        );

        // Entry 13: Supplier Y Purchase - 14:15
        $purchaseY1 = $this->createPurchase($supplierY, 'DEMO-SUPY-PUR1', 4000.00, $baseDate->copy()->setTime(14, 15));

        // Entry 14: Supplier Y Payment - 14:45
        $this->createPayment(null, $supplierY, 'DEMO-SUPY-PAY1', 2000.00, $baseDate->copy()->setTime(14, 45), 'purchase');

        // Entry 15: Supplier Z Opening Balance - 15:00
        $this->unifiedLedgerService->recordOpeningBalance(
            $supplierZ->id,
            'supplier',
            0.00,
            'Supplier Z opening balance'
        );

        // Entry 16: Supplier Z Purchase - 15:15
        $purchaseZ1 = $this->createPurchase($supplierZ, 'DEMO-SUPZ-PUR1', 2500.00, $baseDate->copy()->setTime(15, 15));
    }

    private function createSale($customer, $invoiceNo, $amount, $date)
    {
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_no' => $invoiceNo,
            'sales_date' => $date->format('Y-m-d'),
            'final_total' => $amount,
            'total_paid' => 0,
            'payment_status' => 'Due',
            'location_id' => 1, // Assuming location 1 exists
        ]);

        // Record in unified ledger
        $this->unifiedLedgerService->recordSale($sale);

        return $sale;
    }

    private function createPurchase($supplier, $referenceNo, $amount, $date)
    {
        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'reference_no' => $referenceNo,
            'purchase_date' => $date->format('Y-m-d'),
            'final_total' => $amount,
            'total_paid' => 0,
            'payment_status' => 'Due',
            'location_id' => 1, // Assuming location 1 exists
        ]);

        // Record in unified ledger
        $this->unifiedLedgerService->recordPurchase($purchase);

        return $purchase;
    }

    private function createPayment($customer, $supplier, $referenceNo, $amount, $date, $type)
    {
        $payment = Payment::create([
            'customer_id' => $customer?->id,
            'supplier_id' => $supplier?->id,
            'reference_no' => $referenceNo,
            'payment_date' => $date->format('Y-m-d'),
            'amount' => $amount,
            'payment_method' => 'cash',
            'payment_type' => $type,
            'notes' => "Demo payment - {$referenceNo}",
        ]);

        // Record in unified ledger
        if ($type === 'sale' && $customer) {
            $this->unifiedLedgerService->recordSalePayment($payment);
        } elseif ($type === 'purchase' && $supplier) {
            $this->unifiedLedgerService->recordPurchasePayment($payment);
        }

        return $payment;
    }

    private function createReturnPayment($customer, $supplier, $referenceNo, $amount, $date, $contactType)
    {
        $payment = Payment::create([
            'customer_id' => $customer?->id,
            'supplier_id' => $supplier?->id,
            'reference_no' => $referenceNo,
            'payment_date' => $date->format('Y-m-d'),
            'amount' => $amount,
            'payment_method' => 'cash',
            'payment_type' => 'return_payment',
            'notes' => "Demo return payment - {$referenceNo}",
        ]);

        // Record in unified ledger
        $this->unifiedLedgerService->recordReturnPayment($payment, $contactType);

        return $payment;
    }

    private function createPurchaseReturn($supplier, $returnNo, $amount, $date)
    {
        $purchaseReturn = PurchaseReturn::create([
            'supplier_id' => $supplier->id,
            'return_no' => $returnNo,
            'return_date' => $date->format('Y-m-d'),
            'return_total' => $amount,
            'total_paid' => 0,
            'payment_status' => 'Due',
            'location_id' => 1,
        ]);

        // Record in unified ledger
        $this->unifiedLedgerService->recordPurchaseReturn($purchaseReturn);

        return $purchaseReturn;
    }
}