<?php

namespace App\Services\Ledger;

use App\Models\Ledger;

class LedgerMaintenanceService
{
    public function recalculateSupplierBalance(int $supplierId): void
    {
        $entries = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;

        foreach ($entries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            // Balance column removed from ledgers table - calculated dynamically.
        }
    }

    public function validateSupplierLedger(int $supplierId): array
    {
        $entries = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

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

    public function deleteLedgerEntries(string $referenceNo, int $contactId, string $contactType): void
    {
        Ledger::where('reference_no', $referenceNo)
            ->where('contact_id', $contactId)
            ->where('contact_type', $contactType)
            ->delete();
    }
}

