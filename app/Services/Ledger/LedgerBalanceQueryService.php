<?php

namespace App\Services\Ledger;

use App\Helpers\BalanceHelper;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerBalanceQueryService
{
    public function getLedgerExtractionDependencyMap(): array
    {
        return [
            'getCustomerLedger' => [
                'contact_resolver' => 'getCustomerForLedgerOrFail',
                'location_filter' => 'UnifiedLedgerService::applyLocationFilter',
                'audit_sorter' => 'UnifiedLedgerService::sortLedgerTransactionsForAuditTrail',
                'presentation_helpers' => [
                    'UnifiedLedgerService::getLocationForTransaction',
                    'UnifiedLedgerService::getDetailedTransactionType',
                    'UnifiedLedgerService::getEnhancedTransactionDescription',
                    'UnifiedLedgerService::getPaymentStatus',
                    'UnifiedLedgerService::extractPaymentMethod',
                ],
            ],
            'getSupplierLedger' => [
                'contact_resolver' => 'getSupplierForLedgerOrFail',
                'location_filter' => 'UnifiedLedgerService::applyLocationFilter',
                'audit_sorter' => 'UnifiedLedgerService::sortLedgerTransactionsForAuditTrail',
                'presentation_helpers' => [
                    'UnifiedLedgerService::getLocationForTransaction',
                    'UnifiedLedgerService::getDetailedTransactionType',
                    'UnifiedLedgerService::getEnhancedTransactionDescription',
                    'UnifiedLedgerService::getPaymentStatus',
                    'UnifiedLedgerService::extractPaymentMethod',
                ],
            ],
        ];
    }

    public function getCustomerForLedgerOrFail(int $customerId): Customer
    {
        $customer = Customer::withoutGlobalScopes()->find($customerId);
        if (!$customer) {
            throw new \Exception("Customer not found");
        }

        return $customer;
    }

    public function getSupplierForLedgerOrFail(int $supplierId): Supplier
    {
        $supplier = Supplier::withoutGlobalScopes()->find($supplierId);
        if (!$supplier) {
            throw new \Exception("Supplier not found");
        }

        return $supplier;
    }

    private function customerDisplayName(Customer $customer): string
    {
        return trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    }

    private function customerBalanceType($balance): string
    {
        return $balance > 0 ? 'receivable' : 'payable';
    }

    private function supplierBalanceType($balance): string
    {
        return $balance > 0 ? 'payable' : 'receivable';
    }

    public function getSupplierOrFail(int $supplierId): Supplier
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            throw new \Exception("Supplier not found");
        }

        return $supplier;
    }

    public function buildSupplierSummary(Supplier $supplier, $ledgerEntries): array
    {
        $entries = collect($ledgerEntries);

        return [
            'supplier' => $supplier,
            'opening_balance' => $supplier->opening_balance ?? 0,
            'total_purchases' => $entries->where('transaction_type', 'purchase')->sum('debit'),
            'total_returns' => $entries->where('transaction_type', 'purchase_return')->sum('credit'),
            'total_payments' => $entries->where('transaction_type', 'payments')->sum('credit'),
            'current_balance' => BalanceHelper::getSupplierBalance($supplier->id) ?? 0,
            'total_transactions' => $entries->count()
        ];
    }

    public function summarizeSupplierLedgerData(int $supplierId, array $ledgerData): array
    {
        $supplier = $this->getSupplierOrFail($supplierId);
        $entries = $ledgerData['transactions'] ?? [];

        return $this->buildSupplierSummary($supplier, $entries);
    }

    public function getCustomerBalanceSummary($customerId): array
    {
        $currentBalance = BalanceHelper::getCustomerBalance($customerId);

        return [
            'customer_id' => $customerId,
            'current_balance' => $currentBalance,
            'outstanding_amount' => BalanceHelper::getCustomerDue($customerId),
            'advance_amount' => BalanceHelper::getCustomerAdvance($customerId),
            'balance_status' => $currentBalance > 0 ? 'receivable' : ($currentBalance < 0 ? 'payable' : 'cleared'),
            'last_updated' => Carbon::now('Asia/Colombo')->format('Y-m-d H:i:s')
        ];
    }

    public function getCustomerBillWiseBalance($customerId)
    {
        return BalanceHelper::getCustomerBalance($customerId);
    }

    public function getCustomerFloatingBalance($customerId)
    {
        $floatingDebits = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['cheque_bounce', 'bank_charges'])
            ->where('status', 'active')
            ->sum('debit');

        $floatingCredits = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->whereIn('transaction_type', ['bounce_recovery', 'adjustment_credit'])
            ->where('status', 'active')
            ->sum('credit');

        return $floatingDebits - $floatingCredits;
    }

    public function getCustomerBouncedChequesAmount($customerId)
    {
        return Payment::where('customer_id', $customerId)
            ->where('payment_method', 'cheque')
            ->whereHas('chequeStatusHistory', function($query) {
                $query->whereIn('id', function($subQuery) {
                    $subQuery->select(DB::raw('MAX(id)'))
                        ->from('cheque_status_histories')
                        ->groupBy('payment_id');
                })->where('status', 'bounced');
            })
            ->sum('amount');
    }

    public function getBulkBalances($contactIds, $contactType)
    {
        return BalanceHelper::getBulkBalances($contactIds, $contactType);
    }

    public function getBalanceSummary($contactType = null)
    {
        // Keep existing behavior until dedicated summary logic is implemented.
        return collect();
    }

    public function getUnifiedLedgerView($startDate, $endDate, $contactType = null)
    {
        return Ledger::getUnifiedLedger($startDate, $endDate, $contactType);
    }

    public function getCustomerStatementWithRunningBalance($customerId, $fromDate = null, $toDate = null)
    {
        return Ledger::getStatement($customerId, 'customer', $fromDate, $toDate);
    }

    public function getSupplierStatementWithRunningBalance($supplierId, $fromDate = null, $toDate = null)
    {
        return Ledger::getStatement($supplierId, 'supplier', $fromDate, $toDate);
    }

    public function getCustomerStatement($customerId, $fromDate = null, $toDate = null): array
    {
        $openingBalance = 0;
        if ($fromDate) {
            $openingBalance = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_date', '<', $fromDate)
                ->where('status', 'active')
                ->sum(DB::raw('debit - credit'));
        }

        $transactions = Ledger::getStatement($customerId, 'customer', $fromDate, $toDate);

        $closingBalance = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->sum(DB::raw('debit - credit'));

        return [
            'customer_id' => $customerId,
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $closingBalance,
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]
        ];
    }

    public function getAllCustomersWithBalances()
    {
        $customers = Customer::select('id', 'first_name', 'last_name', 'mobile_no')->get();
        $customerIds = $customers->pluck('id')->toArray();
        $balances = BalanceHelper::getBulkCustomerBalances($customerIds);

        return $customers->map(function ($customer) use ($balances) {
            $balance = $balances->get($customer->id, 0);
            return [
                'id' => $customer->id,
                'name' => $this->customerDisplayName($customer),
                'mobile_no' => $customer->mobile_no,
                'current_balance' => $balance,
                'balance_type' => $this->customerBalanceType($balance)
            ];
        });
    }

    public function getAllSuppliersWithBalances()
    {
        $suppliers = Supplier::select('id', 'name', 'phone')->get();

        return $suppliers->map(function ($supplier) {
            $balance = BalanceHelper::getSupplierBalance($supplier->id);
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'current_balance' => $balance,
                'balance_type' => $this->supplierBalanceType($balance)
            ];
        });
    }
}

