<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierLedgerService
{
    /**
     * Record a purchase transaction in the supplier ledger
     * 
     * @param Purchase $purchase
     * @param Carbon|null $transactionDate
     * @return Ledger
     */
    public function recordPurchase(Purchase $purchase, Carbon $transactionDate = null): Ledger
    {
        $transactionDate = $transactionDate ?? Carbon::parse($purchase->purchase_date);
        
        return $this->createLedgerEntry([
            'transaction_date' => $transactionDate,
            'reference_no' => $purchase->reference_no,
            'transaction_type' => 'purchase',
            'debit' => $purchase->final_total,
            'credit' => 0,
            'contact_type' => 'supplier',
            'user_id' => $purchase->supplier_id,
            'notes' => "Purchase: {$purchase->reference_no}"
        ]);
    }

    /**
     * Record a purchase return transaction in the supplier ledger
     * 
     * @param PurchaseReturn $purchaseReturn
     * @param Carbon|null $transactionDate
     * @return Ledger
     */
    public function recordPurchaseReturn(PurchaseReturn $purchaseReturn, Carbon $transactionDate = null): Ledger
    {
        $transactionDate = $transactionDate ?? Carbon::parse($purchaseReturn->return_date);
        
        return $this->createLedgerEntry([
            'transaction_date' => $transactionDate,
            'reference_no' => $purchaseReturn->reference_no,
            'transaction_type' => 'purchase_return',
            'debit' => 0,
            'credit' => $purchaseReturn->return_total,
            'contact_type' => 'supplier',
            'user_id' => $purchaseReturn->supplier_id,
            'notes' => "Purchase Return: {$purchaseReturn->reference_no}"
        ]);
    }

    /**
     * Record a payment transaction in the supplier ledger
     * 
     * @param Payment $payment
     * @param string $referenceNo
     * @param Carbon|null $transactionDate
     * @return Ledger
     */
    public function recordPayment(Payment $payment, string $referenceNo, Carbon $transactionDate = null): Ledger
    {
        $transactionDate = $transactionDate ?? Carbon::parse($payment->payment_date);
        
        // Determine if this is a return payment or purchase payment
        $isReturnPayment = ($payment->payment_type === 'purchase_return');
        
        if ($isReturnPayment) {
            // Return payment: Supplier pays us back, reduces our debt (CREDIT)
            $transactionType = 'payments';
            $debit = 0;
            $credit = $payment->amount;
            $notes = "Return Payment: {$payment->payment_method} - {$payment->notes}";
        } else {
            // Purchase payment: We pay supplier, reduces our debt (CREDIT)
            $transactionType = 'payments';
            $debit = 0;
            $credit = $payment->amount;
            $notes = "Purchase Payment: {$payment->payment_method} - {$payment->notes}";
        }
        
        return $this->createLedgerEntry([
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => $transactionType,
            'debit' => $debit,
            'credit' => $credit,
            'contact_type' => 'supplier',
            'user_id' => $payment->supplier_id,
            'notes' => $notes
        ]);
    }

    /**
     * Record opening balance for a supplier
     * 
     * @param int $supplierId
     * @param float $openingBalance
     * @param Carbon|null $transactionDate
     * @return Ledger|null
     */
    public function recordOpeningBalance(int $supplierId, float $openingBalance, Carbon $transactionDate = null): ?Ledger
    {
        if ($openingBalance == 0) {
            return null;
        }

        $transactionDate = $transactionDate ?? now();
        
        return $this->createLedgerEntry([
            'transaction_date' => $transactionDate,
            'reference_no' => "OB-{$supplierId}",
            'transaction_type' => 'opening_balance',
            'debit' => $openingBalance > 0 ? $openingBalance : 0,
            'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
            'contact_type' => 'supplier',
            'user_id' => $supplierId,
            'notes' => 'Opening Balance'
        ]);
    }

    /**
     * Create a ledger entry with balance calculation
     * 
     * @param array $data
     * @return Ledger
     */
    private function createLedgerEntry(array $data): Ledger
    {
        // Calculate the new balance based on previous entries
        $previousBalance = $this->getLastBalance($data['user_id']);
        $newBalance = $previousBalance + $data['debit'] - $data['credit'];
        
        $data['balance'] = $newBalance;
        
        return Ledger::create($data);
    }

    /**
     * Get the last balance for a supplier
     * 
     * @param int $supplierId
     * @return float
     */
    public function getLastBalance(int $supplierId): float
    {
        $lastEntry = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastEntry ? $lastEntry->balance : 0;
    }

    /**
     * Calculate current balance for a supplier
     * 
     * @param int $supplierId
     * @return float
     */
    public function calculateCurrentBalance(int $supplierId): float
    {
        $entries = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $balance = 0;
        foreach ($entries as $entry) {
            $balance += $entry->debit - $entry->credit;
        }

        return $balance;
    }

    /**
     * Recalculate all balances for a supplier from scratch
     * 
     * @param int $supplierId
     * @return void
     */
    public function recalculateSupplierBalance(int $supplierId): void
    {
        $entries = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        
        foreach ($entries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->update(['balance' => $runningBalance]);
        }

        // Update supplier's current_balance
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $supplier->update(['current_balance' => $runningBalance]);
        }
    }

    /**
     * Delete ledger entries for a specific reference
     * 
     * @param string $referenceNo
     * @param int $supplierId
     * @return void
     */
    public function deleteLedgerEntries(string $referenceNo, int $supplierId): void
    {
        Ledger::where('reference_no', $referenceNo)
            ->where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->delete();
    }

    /**
     * Get supplier ledger entries with filters
     * 
     * @param int $supplierId
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @param string|null $transactionType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSupplierLedger(int $supplierId, Carbon $fromDate = null, Carbon $toDate = null, string $transactionType = null)
    {
        $query = Ledger::where('user_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate) {
            $query->where('transaction_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('transaction_date', '<=', $toDate);
        }

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        return $query->get();
    }

    /**
     * Validate ledger consistency for a supplier
     * 
     * @param int $supplierId
     * @return array
     */
    public function validateSupplierLedger(int $supplierId): array
    {
        $entries = $this->getSupplierLedger($supplierId);
        $errors = [];
        $runningBalance = 0;

        foreach ($entries as $entry) {
            $expectedBalance = $runningBalance + $entry->debit - $entry->credit;
            
            if (abs($expectedBalance - $entry->balance) > 0.01) {
                $errors[] = [
                    'id' => $entry->id,
                    'reference_no' => $entry->reference_no,
                    'expected_balance' => $expectedBalance,
                    'actual_balance' => $entry->balance,
                    'difference' => $entry->balance - $expectedBalance
                ];
            }
            
            $runningBalance = $expectedBalance;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'final_balance' => $runningBalance
        ];
    }

    /**
     * Update purchase in ledger
     * 
     * @param Purchase $purchase
     * @param Purchase $oldPurchase
     * @return void
     */
    public function updatePurchaseInLedger(Purchase $purchase, Purchase $oldPurchase = null): void
    {
        // Delete existing ledger entries for this purchase
        $this->deleteLedgerEntries($purchase->reference_no, $purchase->supplier_id);

        // Record the updated purchase
        $this->recordPurchase($purchase);

        // Recalculate balances
        $this->recalculateSupplierBalance($purchase->supplier_id);
    }

    /**
     * Update purchase return in ledger
     * 
     * @param PurchaseReturn $purchaseReturn
     * @param PurchaseReturn $oldPurchaseReturn
     * @return void
     */
    public function updatePurchaseReturnInLedger(PurchaseReturn $purchaseReturn, PurchaseReturn $oldPurchaseReturn = null): void
    {
        // Delete existing ledger entries for this purchase return
        $this->deleteLedgerEntries($purchaseReturn->reference_no, $purchaseReturn->supplier_id);

        // Record the updated purchase return
        $this->recordPurchaseReturn($purchaseReturn);

        // Recalculate balances
        $this->recalculateSupplierBalance($purchaseReturn->supplier_id);
    }

    /**
     * Record payment for purchase
     * 
     * @param Payment $payment
     * @param Purchase $purchase
     * @return void
     */
    public function recordPurchasePayment(Payment $payment, Purchase $purchase): void
    {
        $this->recordPayment($payment, $purchase->reference_no);
        $this->recalculateSupplierBalance($payment->supplier_id);
    }

    /**
     * Record payment for purchase return
     * 
     * @param Payment $payment
     * @param PurchaseReturn $purchaseReturn
     * @return void
     */
    public function recordPurchaseReturnPayment(Payment $payment, PurchaseReturn $purchaseReturn): void
    {
        $this->recordPayment($payment, $purchaseReturn->reference_no);
        $this->recalculateSupplierBalance($payment->supplier_id);
    }

    /**
     * Get supplier summary
     * 
     * @param int $supplierId
     * @return array
     */
    public function getSupplierSummary(int $supplierId): array
    {
        $supplier = Supplier::find($supplierId);
        
        if (!$supplier) {
            throw new \Exception("Supplier not found");
        }

        $ledgerEntries = $this->getSupplierLedger($supplierId);
        
        $summary = [
            'supplier' => $supplier,
            'opening_balance' => $supplier->opening_balance ?? 0,
            'total_purchases' => $ledgerEntries->where('transaction_type', 'purchase')->sum('debit'),
            'total_returns' => $ledgerEntries->where('transaction_type', 'purchase_return')->sum('credit'),
            'total_payments' => $ledgerEntries->where('transaction_type', 'payments')->sum('credit'),
            'current_balance' => $this->getLastBalance($supplierId),
            'total_transactions' => $ledgerEntries->count()
        ];

        return $summary;
    }
}