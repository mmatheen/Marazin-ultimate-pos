<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\SalesReturn;
use App\Models\Ledger;

class AnalyzeDataSources extends Command
{
    protected $signature = 'data:analyze-sources';
    protected $description = 'Analyze sales, payments, and sales returns vs ledger entries';

    public function handle()
    {
        $this->info('🔍 ANALYZING DATA SOURCES VS LEDGER ENTRIES...');
        $this->newLine();
        
        // Check Sales Table
        $this->checkSalesTable();
        $this->newLine();
        
        // Check Payments Table
        $this->checkPaymentsTable();
        $this->newLine();
        
        // Check Sales Returns Table
        $this->checkSalesReturnsTable();
        $this->newLine();
        
        // Check Ledger Coverage
        $this->checkLedgerCoverage();
        
        return Command::SUCCESS;
    }
    
    private function checkSalesTable()
    {
        $this->info('📊 SALES TABLE ANALYSIS:');
        $this->line(str_repeat('─', 50));
        
        $salesCount = Sale::withoutGlobalScopes()->count();
        $this->line("Total Sales Records: {$salesCount}");
        
        if ($salesCount > 0) {
            $sales = Sale::withoutGlobalScopes()->with('customer')->get();
            $totalSales = $sales->sum('final_total');
            $totalPaid = $sales->sum('total_paid');
            $totalDue = $sales->sum('total_due');
            
            $this->table([
                'Metric', 'Value'
            ], [
                ['Total Sales Amount', 'Rs. ' . number_format($totalSales, 2)],
                ['Total Paid', 'Rs. ' . number_format($totalPaid, 2)],
                ['Total Due', 'Rs. ' . number_format($totalDue, 2)],
            ]);
            
            $this->line('First 5 Sales Records:');
            foreach ($sales->take(5) as $sale) {
                $customer = $sale->customer ? $sale->customer->name : 'No Customer';
                $this->line("ID: {$sale->id} | Customer: {$customer} | Invoice: {$sale->invoice_no} | Total: Rs.{$sale->final_total} | Status: {$sale->payment_status}");
            }
        } else {
            $this->warn('❌ No sales records found!');
        }
    }
    
    private function checkPaymentsTable()
    {
        $this->info('💰 PAYMENTS TABLE ANALYSIS:');
        $this->line(str_repeat('─', 50));
        
        $paymentsCount = Payment::withoutGlobalScopes()->count();
        $this->line("Total Payment Records: {$paymentsCount}");
        
        if ($paymentsCount > 0) {
            // Group by payment type
            $paymentTypes = Payment::withoutGlobalScopes()
                ->selectRaw('payment_type, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('payment_type')
                ->get();
            
            $this->table([
                'Payment Type', 'Count', 'Total Amount'
            ], $paymentTypes->map(function($type) {
                return [
                    $type->payment_type ?: 'NULL',
                    $type->count,
                    'Rs. ' . number_format($type->total_amount, 2)
                ];
            })->toArray());
            
            $this->line('First 10 Payment Records:');
            $payments = Payment::withoutGlobalScopes()->with(['customer', 'supplier'])->take(10)->get();
            foreach ($payments as $payment) {
                $contact = $payment->customer ? "Customer: {$payment->customer->name}" : 
                          ($payment->supplier ? "Supplier: {$payment->supplier->name}" : 'No Contact');
                $this->line("ID: {$payment->id} | {$contact} | Type: {$payment->payment_type} | Amount: Rs.{$payment->amount} | Ref: {$payment->reference_no}");
            }
        } else {
            $this->warn('❌ No payment records found!');
        }
    }
    
    private function checkSalesReturnsTable()
    {
        $this->info('↩️ SALES RETURNS TABLE ANALYSIS:');
        $this->line(str_repeat('─', 50));
        
        $returnsCount = SalesReturn::withoutGlobalScopes()->count();
        $this->line("Total Sales Return Records: {$returnsCount}");
        
        if ($returnsCount > 0) {
            $returns = SalesReturn::withoutGlobalScopes()->with('customer')->get();
            $totalReturns = $returns->sum('return_total');
            $totalPaid = $returns->sum('total_paid');
            $totalDue = $returns->sum('total_due');
            
            $this->table([
                'Metric', 'Value'
            ], [
                ['Total Returns Amount', 'Rs. ' . number_format($totalReturns, 2)],
                ['Total Paid', 'Rs. ' . number_format($totalPaid, 2)],
                ['Total Due', 'Rs. ' . number_format($totalDue, 2)],
            ]);
            
            $this->line('Sales Return Records:');
            foreach ($returns as $return) {
                $customer = $return->customer ? $return->customer->name : 'No Customer';
                $this->line("ID: {$return->id} | Customer: {$customer} | Invoice: {$return->invoice_number} | Total: Rs.{$return->return_total} | Type: {$return->stock_type}");
            }
        } else {
            $this->warn('❌ No sales return records found!');
        }
    }
    
    private function checkLedgerCoverage()
    {
        $this->info('📋 LEDGER COVERAGE ANALYSIS:');
        $this->line(str_repeat('─', 50));
        
        // Use withoutGlobalScopes to fetch all data
        $ledgerCount = Ledger::withoutGlobalScopes()->count();
        $this->line("Total Ledger Records: {$ledgerCount}");
        
        // Check ledger transaction types
        $ledgerTypes = Ledger::withoutGlobalScopes()
            ->selectRaw('transaction_type, COUNT(*) as count')
            ->groupBy('transaction_type')
            ->get();
        
        $this->table([
            'Transaction Type', 'Count'
        ], $ledgerTypes->map(function($type) {
            return [$type->transaction_type ?: 'NULL', $type->count];
        })->toArray());
        
        $this->newLine();
        $this->info('🔍 MISSING DATA ANALYSIS:');
        $this->line(str_repeat('─', 50));
        
        // Check if sales data should be in ledger
        $salesCount = Sale::withoutGlobalScopes()->count();
        $ledgerSalesCount = Ledger::withoutGlobalScopes()->where('transaction_type', 'sale')->count();
        $this->line("Sales Table: {$salesCount} records");
        $this->line("Ledger Sales: {$ledgerSalesCount} records");
        
        if ($salesCount > $ledgerSalesCount) {
            $this->warn("⚠️ Missing " . ($salesCount - $ledgerSalesCount) . " sales entries in ledger!");
        } elseif ($salesCount < $ledgerSalesCount) {
            $this->warn("⚠️ Extra " . ($ledgerSalesCount - $salesCount) . " sales entries in ledger!");
        } else {
            $this->info("✅ Sales data matches between tables");
        }
        
        // Check payments
        $paymentsCount = Payment::withoutGlobalScopes()->count();
        $ledgerPaymentsCount = Ledger::withoutGlobalScopes()->where('transaction_type', 'payments')->count();
        $this->line("Payments Table: {$paymentsCount} records");
        $this->line("Ledger Payments: {$ledgerPaymentsCount} records");
        
        if ($paymentsCount > $ledgerPaymentsCount) {
            $this->warn("⚠️ Missing " . ($paymentsCount - $ledgerPaymentsCount) . " payment entries in ledger!");
        } elseif ($paymentsCount < $ledgerPaymentsCount) {
            $this->warn("⚠️ Extra " . ($ledgerPaymentsCount - $paymentsCount) . " payment entries in ledger!");
        } else {
            $this->info("✅ Payment data matches between tables");
        }
        
        // Check sales returns
        $returnsCount = SalesReturn::withoutGlobalScopes()->count();
        $ledgerReturnsCount = Ledger::withoutGlobalScopes()->where('transaction_type', 'sale_return_with_bill')->count();
        $this->line("Sales Returns Table: {$returnsCount} records");
        $this->line("Ledger Sales Returns: {$ledgerReturnsCount} records");
        
        if ($returnsCount > $ledgerReturnsCount) {
            $this->warn("⚠️ Missing " . ($returnsCount - $ledgerReturnsCount) . " sales return entries in ledger!");
        } elseif ($returnsCount < $ledgerReturnsCount) {
            $this->warn("⚠️ Extra " . ($ledgerReturnsCount - $returnsCount) . " sales return entries in ledger!");
        } else {
            $this->info("✅ Sales return data matches between tables");
        }
        
        $this->newLine();
        $this->info('📊 DETAILED LEDGER BREAKDOWN:');
        $this->line(str_repeat('─', 50));
        
        // Show all ledger transaction types
        $allTypes = Ledger::withoutGlobalScopes()
            ->selectRaw('transaction_type, contact_type, COUNT(*) as count, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('transaction_type', 'contact_type')
            ->orderBy('transaction_type')
            ->get();
        
        foreach ($allTypes as $type) {
            $contactType = $type->contact_type ?: 'Unknown';
            $transactionType = $type->transaction_type ?: 'NULL';
            $this->line("Type: {$transactionType} ({$contactType}) - Count: {$type->count} | Debit: Rs." . number_format($type->total_debit, 2) . " | Credit: Rs." . number_format($type->total_credit, 2));
        }
    }
}