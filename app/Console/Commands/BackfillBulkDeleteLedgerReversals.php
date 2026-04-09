<?php

namespace App\Console\Commands;

use App\Models\Ledger;
use App\Models\Payment;
use App\Services\UnifiedLedgerService;
use Illuminate\Console\Command;

class BackfillBulkDeleteLedgerReversals extends Command
{
    /**
     * Examples:
     * php artisan ledger:backfill-bulk-delete-reversals --dry-run
     * php artisan ledger:backfill-bulk-delete-reversals --reference=BLK-S0356
     * php artisan ledger:backfill-bulk-delete-reversals --payment-id=1484
     */
    protected $signature = 'ledger:backfill-bulk-delete-reversals
        {--dry-run : Show what would be fixed without writing changes}
        {--reference= : Process only one bulk reference (example: BLK-S0356)}
        {--payment-id=* : Process only specific payment IDs}
        {--limit=0 : Max rows to process (0 = no limit)}
        {--user-id=1 : User ID to stamp in reversal reason}';

    protected $description = 'Backfill missing ledger reversals for previously deleted bulk payments where active ledger rows were left behind.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $reference = $this->option('reference');
        $paymentIds = collect($this->option('payment-id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $limit = max(0, (int) $this->option('limit'));
        $userId = max(1, (int) $this->option('user-id'));

        $this->info('Ledger backfill for deleted bulk payments');
        $this->line('----------------------------------------');
        if ($dryRun) {
            $this->warn('Running in dry-run mode. No database changes will be made.');
        }

        $query = Payment::withoutGlobalScope('excludeDeleted')
            ->where('status', 'deleted')
            ->whereNotNull('reference_no')
            ->where('reference_no', 'like', 'BLK-%')
            ->orderBy('id');

        if (!empty($reference)) {
            $query->where('reference_no', $reference);
        }

        if (!empty($paymentIds)) {
            $query->whereIn('id', $paymentIds);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            $this->info('No deleted bulk payments matched your filters.');
            return self::SUCCESS;
        }

        $rowsToFix = collect();
        foreach ($payments as $payment) {
            $contactId = $payment->customer_id ?: $payment->supplier_id;
            $contactType = $payment->customer_id ? 'customer' : 'supplier';

            if (!$contactId) {
                continue;
            }

            $canonicalReference = $this->resolvePaymentLedgerReference($payment);

            $activeLedger = Ledger::where('reference_no', $canonicalReference)
                ->where('contact_id', $contactId)
                ->where('contact_type', $contactType)
                ->whereIn('transaction_type', ['payments', 'purchase_payment', 'discount_given'])
                ->where('status', 'active')
                ->count();

            if ($activeLedger > 0) {
                $rowsToFix->push([
                    'payment_id' => $payment->id,
                    'payment_ref' => $payment->reference_no,
                    'ledger_ref' => $canonicalReference,
                    'contact' => $contactType . ':' . $contactId,
                    'amount' => (float) $payment->amount,
                    'active_ledger_rows' => $activeLedger,
                ]);
            }
        }

        if ($rowsToFix->isEmpty()) {
            $this->info('No mismatched rows found. Ledger appears consistent for the selected scope.');
            return self::SUCCESS;
        }

        $this->table(
            ['payment_id', 'payment_ref', 'ledger_ref', 'contact', 'amount', 'active_ledger_rows'],
            $rowsToFix->map(function ($r) {
                return [
                    $r['payment_id'],
                    $r['payment_ref'],
                    $r['ledger_ref'],
                    $r['contact'],
                    number_format($r['amount'], 2),
                    $r['active_ledger_rows'],
                ];
            })->all()
        );

        if ($dryRun) {
            $this->info('Dry-run complete. Re-run without --dry-run to apply backfill.');
            return self::SUCCESS;
        }

        $service = app(UnifiedLedgerService::class);
        $fixed = 0;
        $failed = 0;

        foreach ($rowsToFix as $row) {
            try {
                $payment = Payment::withoutGlobalScope('excludeDeleted')->find($row['payment_id']);
                if (!$payment) {
                    $failed++;
                    $this->error('Payment not found: ' . $row['payment_id']);
                    continue;
                }

                $service->deletePayment(
                    $payment,
                    'Backfill ledger reversal after historical bulk-delete reference mismatch',
                    $userId
                );

                $fixed++;
                $this->line('Fixed payment #' . $row['payment_id'] . ' (' . $row['ledger_ref'] . ')');
            } catch (\Throwable $e) {
                $failed++;
                $this->error('Failed payment #' . $row['payment_id'] . ': ' . $e->getMessage());
            }
        }

        $this->line('----------------------------------------');
        $this->info('Backfill finished.');
        $this->info('Fixed: ' . $fixed);
        $this->info('Failed: ' . $failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolvePaymentLedgerReference(Payment $payment): string
    {
        $baseReferenceNo = $payment->reference_no ?: ('PAY-' . $payment->id);

        if (strpos($baseReferenceNo, 'BLK-') === 0 && $payment->id) {
            $suffix = '-PAY' . $payment->id;
            if (substr($baseReferenceNo, -strlen($suffix)) !== $suffix) {
                return $baseReferenceNo . $suffix;
            }
        }

        return $baseReferenceNo;
    }
}
