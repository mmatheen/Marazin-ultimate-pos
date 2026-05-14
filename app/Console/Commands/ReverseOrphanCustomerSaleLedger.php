<?php

namespace App\Console\Commands;

use App\Helpers\BalanceHelper;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Illuminate\Console\Command;

/**
 * Clears a stray active customer sale debit when the invoice moved/renamed but ledger still shows the old ref.
 */
class ReverseOrphanCustomerSaleLedger extends Command
{
    protected $signature = 'ledger:reverse-orphan-customer-sale
                            {customer_id : Customer contact_id (ledgers.contact_id)}
                            {reference_no : Ledger reference_no to reverse (e.g. MLX-205)}
                            {--dry-run : Show what would happen without writing}
                            {--reason=Orphan sale ledger cleanup : Note on reversed rows}';

    protected $description = 'Reverse an active customer sale ledger line by reference (fixes ledger vs open bills mismatch)';

    public function handle(UnifiedLedgerService $ledgerService): int
    {
        $customerId = (int) $this->argument('customer_id');
        $referenceNo = trim((string) $this->argument('reference_no'));
        $dryRun = (bool) $this->option('dry-run');
        $reason = (string) $this->option('reason');

        if ($customerId <= 1) {
            $this->error('customer_id must be greater than 1.');

            return self::FAILURE;
        }

        if ($referenceNo === '') {
            $this->error('reference_no is required.');

            return self::FAILURE;
        }

        $before = (float) BalanceHelper::getCustomerBalance($customerId);
        $this->info("Customer {$customerId} ledger balance before: {$before}");

        if ($dryRun) {
            $entry = Ledger::query()
                ->where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'sale')
                ->where('reference_no', $referenceNo)
                ->where('status', 'active')
                ->where('debit', '>', 0)
                ->orderByDesc('id')
                ->first();

            if (!$entry) {
                $this->warn('No matching active sale ledger row (nothing to do).');

                return self::SUCCESS;
            }

            $this->table(
                ['id', 'reference_no', 'debit', 'credit', 'notes'],
                [[$entry->id, $entry->reference_no, $entry->debit, $entry->credit, mb_substr((string) $entry->notes, 0, 80)]]
            );
            $this->info('Dry run: would reverse this row and post a matching reversal entry.');
            $this->info('Estimated balance after: ' . ($before - (float) $entry->debit));

            return self::SUCCESS;
        }

        $reversal = $ledgerService->reverseOrphanCustomerSaleLedgerEntry($customerId, $referenceNo, $reason, 1);
        if (!$reversal) {
            $this->warn('No matching active sale ledger row (nothing to do).');

            return self::SUCCESS;
        }

        $after = (float) BalanceHelper::getCustomerBalance($customerId);
        $this->info('Reversal ledger id: ' . $reversal->id);
        $this->info("Customer {$customerId} ledger balance after: {$after}");

        return self::SUCCESS;
    }
}
