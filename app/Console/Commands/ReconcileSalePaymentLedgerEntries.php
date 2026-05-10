<?php

namespace App\Console\Commands;

use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\UnifiedLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileSalePaymentLedgerEntries extends Command
{
    protected $signature = 'ledger:reconcile-sale-payment-entries
                            {--customer= : Limit to payments for this customer ID}
                            {--sale= : Limit to payments linked to this sale ID (reference_id)}
                            {--dry-run : Show actions without saving}';

    protected $description = 'Fix missing/wrong customer ledger payment lines (bulk BLK same-ref dedupe) and rename refs to *-PAY{id}';

    public function handle(UnifiedLedgerService $ledgerService): int
    {
        $customerId = $this->option('customer');
        $saleId = $this->option('sale');
        $dryRun = (bool) $this->option('dry-run');

        if ($customerId === null && $saleId === null) {
            $this->error('Specify --customer=ID and/or --sale=ID.');

            return self::FAILURE;
        }

        $query = Payment::query()
            ->where('status', '!=', 'deleted')
            ->where('customer_id', '!=', 1)
            ->whereIn('payment_type', ['sale', 'advance_credit_usage']);

        if ($customerId !== null) {
            $query->where('customer_id', (int) $customerId);
        }

        if ($saleId !== null) {
            $query->where('reference_id', (int) $saleId);
        }

        /** @var \Illuminate\Support\Collection<int, Payment> $payments */
        $payments = $query->orderBy('id')->get();

        if ($payments->isEmpty()) {
            $this->warn('No matching payments.');

            return self::SUCCESS;
        }

        $legacyKeys = $payments
            ->map(fn (Payment $p) => $this->legacyReferencePoolKey($p->reference_no))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $legacyByRef = collect();
        if ($legacyKeys !== []) {
            $customerIds = $payments->pluck('customer_id')->unique()->all();
            $legacyByRef = Ledger::query()
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->whereIn('contact_id', $customerIds)
                ->whereIn('transaction_type', ['payments', 'discount_given'])
                ->whereIn('reference_no', $legacyKeys)
                ->orderBy('id')
                ->get()
                ->groupBy('reference_no');
        }

        $claimedLedgerIds = [];

        $run = function () use ($payments, $ledgerService, $legacyByRef, &$claimedLedgerIds, $dryRun): void {
            foreach ($payments as $payment) {
                $sale = null;
                if ($payment->reference_id && in_array($payment->payment_type, ['sale', 'advance_credit_usage'], true)) {
                    $sale = Sale::withoutGlobalScopes()
                        ->select(['id', 'invoice_no', 'customer_id'])
                        ->find($payment->reference_id);
                }

                $canonicalRef = $ledgerService->canonicalCustomerPaymentLedgerReference($payment, $sale);
                $txType = $payment->payment_method === 'discount' ? 'discount_given' : 'payments';

                $existingCanon = Ledger::query()
                    ->where('contact_id', $payment->customer_id)
                    ->where('contact_type', 'customer')
                    ->where('status', 'active')
                    ->where('reference_no', $canonicalRef)
                    ->where('transaction_type', $txType)
                    ->first();

                if ($existingCanon && $this->amountMatchesPaymentLine($payment, $existingCanon)) {
                    continue;
                }

                if ($existingCanon && ! $this->amountMatchesPaymentLine($payment, $existingCanon)) {
                    $this->warn("Payment #{$payment->id}: ledger {$existingCanon->id} ref {$canonicalRef} amount mismatch (ledger credit {$existingCanon->credit}, payment {$payment->amount}) — skipped.");

                    continue;
                }

                $legacyKey = $this->legacyReferencePoolKey($payment->reference_no);
                $legacyRow = null;

                if ($legacyKey !== '' && $legacyByRef->has($legacyKey)) {
                    $legacyRow = $legacyByRef->get($legacyKey)
                        ->first(function (Ledger $row) use ($payment, $txType, &$claimedLedgerIds) {
                            if (in_array($row->id, $claimedLedgerIds, true)) {
                                return false;
                            }

                            if ($row->transaction_type !== $txType) {
                                return false;
                            }

                            return $this->amountMatchesPaymentLine($payment, $row);
                        });
                }

                if ($legacyRow) {
                    $this->line("Payment #{$payment->id}: rename ledger #{$legacyRow->id} ref \"{$legacyRow->reference_no}\" → \"{$canonicalRef}\" ({$payment->amount})");
                    $claimedLedgerIds[] = $legacyRow->id;
                    if (! $dryRun) {
                        $legacyRow->reference_no = $canonicalRef;
                        $legacyRow->save();
                    }

                    continue;
                }

                $this->line("Payment #{$payment->id}: create ledger via recordSalePayment ({$canonicalRef}, {$payment->amount})");
                if (! $dryRun) {
                    $ledgerService->recordSalePayment($payment, $sale, $payment->created_by);
                }
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Reconciliation saved.');

        return self::SUCCESS;
    }

    private function legacyReferencePoolKey(?string $referenceNo): string
    {
        $referenceNo = (string) $referenceNo;
        if ($referenceNo === '') {
            return '';
        }

        if (preg_match('/^(.*)-PAY\d+$/', $referenceNo, $m)) {
            return $m[1];
        }

        return $referenceNo;
    }

    private function amountMatchesPaymentLine(Payment $payment, Ledger $ledger): bool
    {
        $credit = (float) $ledger->credit;
        $amount = abs((float) $payment->amount);

        return abs($credit - $amount) < 0.02;
    }
}
