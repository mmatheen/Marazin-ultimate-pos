<?php

namespace App\Services\Ledger;

use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Payment;
use Carbon\Carbon;

class CustomerAdvanceBalanceService
{
    public function calculateAdvanceFromLedger(int $customerId): float
    {
        if ($customerId <= 1) {
            return 0.0;
        }

        // Manual-only advance pool:
        // 1) Credits explicitly stored as customer advances
        // 2) Minus explicit manual usage allocations to invoices
        // Credit sales must NOT auto-reduce this value.
        $earnedAdvance = (float) Payment::where('customer_id', $customerId)
            ->where('payment_type', 'advance')
            ->where('status', '!=', 'deleted')
            ->sum('amount');

        $usedAdvance = (float) Payment::where('customer_id', $customerId)
            ->where('payment_type', 'advance_credit_usage')
            ->where('status', '!=', 'deleted')
            ->sum('amount');

        // Backward compatibility:
        // Some historical datasets contain ledger advance entries without proper
        // payment.customer_id linkage. In that case, derive from ledger by
        // explicit advance transaction types only (still manual-only safe).
        if ($earnedAdvance <= 0.0001 && $usedAdvance <= 0.0001) {
            $ledgerEarnedAdvance = (float) Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->where('transaction_type', 'advance_payment')
                ->sum('credit');

            $ledgerUsedAdvance = (float) Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->where('transaction_type', 'advance_credit_usage')
                ->sum('debit');

            return round(max(0.0, $ledgerEarnedAdvance - $ledgerUsedAdvance), 2);
        }

        return round(max(0.0, $earnedAdvance - $usedAdvance), 2);
    }

    public function syncCustomer(int $customerId): float
    {
        if ($customerId <= 1) {
            return 0.0;
        }

        $advance = $this->calculateAdvanceFromLedger($customerId);

        Customer::where('id', $customerId)->update([
            'advance_balance' => $advance,
            'advance_updated_at' => Carbon::now('Asia/Colombo'),
        ]);

        return $advance;
    }
}

