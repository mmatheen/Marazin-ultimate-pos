<?php

namespace App\Services;

use App\Services\Ledger\LedgerBalanceQueryService;
use App\Services\Ledger\LedgerMaintenanceService;
use App\Services\Ledger\CustomerAdvanceBalanceService;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

class UnifiedLedgerService
{
    /** @var list<string> */
    private const SALE_RETURN_LEDGER_TYPES = ['sale_return', 'sale_return_with_bill', 'sale_return_without_bill'];

    private function balanceQueries(): LedgerBalanceQueryService
    {
        return app(LedgerBalanceQueryService::class);
    }

    private function maintenance(): LedgerMaintenanceService
    {
        return app(LedgerMaintenanceService::class);
    }

    private function resolvePaymentContactType($payment): string
    {
        return $payment->customer_id ? 'customer' : 'supplier';
    }

    private function resolvePaymentContactId($payment, ?string $contactType = null): int
    {
        $resolvedType = $contactType ?? $this->resolvePaymentContactType($payment);

        return (int) ($resolvedType === 'customer'
            ? ($payment->customer_id ?? 0)
            : ($payment->supplier_id ?? 0));
    }


    /**
     * Build the canonical ledger reference for payment entries.
     * Bulk payment references are stored as BLK-...-PAY{id} for uniqueness.
     */
    private function resolvePaymentLedgerReference($payment, $fallbackBaseReference = null)
    {
        $baseReferenceNo = $payment->reference_no ?: ($fallbackBaseReference ?: 'PAY-' . $payment->id);

        if (strpos($baseReferenceNo, 'BLK-') === 0 && $payment->id) {
            $expectedSuffix = '-PAY' . $payment->id;
            if (substr($baseReferenceNo, -strlen($expectedSuffix)) !== $expectedSuffix) {
                return $baseReferenceNo . $expectedSuffix;
            }
        }

        // Split tender (cash + bank transfer, etc.): several Payment rows share the same invoice
        // reference_no. Ledger::createEntry dedupes (reference_no + payments + contact) within 5s,
        // so the second line was incorrectly treated as a duplicate and skipped.
        // advance_credit_usage lines can share the same bulk/invoice reference — same rule as sale.
        if ($payment->id && in_array($payment->payment_type ?? '', ['sale', 'advance_credit_usage'], true)) {
            $suffix = '-PAY' . $payment->id;
            if (substr($baseReferenceNo, -strlen($suffix)) !== $suffix && ! preg_match('/-PAY\d+$/', $baseReferenceNo)) {
                return $baseReferenceNo . $suffix;
            }
        }

        return $baseReferenceNo;
    }

    /**
     * Canonical ledger reference_no for a customer sale / advance-credit payment row (used for reconciliation).
     */
    public function canonicalCustomerPaymentLedgerReference(Payment $payment, ?Sale $sale = null): string
    {
        if ($sale === null && $payment->reference_id && in_array($payment->payment_type, ['sale', 'advance_credit_usage'], true)) {
            $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->select(['id', 'invoice_no', 'customer_id'])
                ->find($payment->reference_id);
        }

        return $this->resolvePaymentLedgerReference($payment, $sale ? $sale->invoice_no : null);
    }

    /**
     * Resolve a payment entity from mixed ledger reference formats.
     */
    private function findPaymentByReference($referenceNo): ?Payment
    {
        $reference = (string) $referenceNo;

        $payment = Payment::where('reference_no', $reference)->first();

        // Bulk payments often use BLK-...-PAY{id}
        if (!$payment && preg_match('/-PAY(\d+)$/', $reference, $m)) {
            $payment = Payment::find((int) $m[1]);
        }

        // Direct fallback format: PAY-{id}
        if (!$payment && strpos($reference, 'PAY-') === 0) {
            $payment = Payment::find((int) str_replace('PAY-', '', $reference));
        }

        return $payment;
    }

    private function resolveSaleByLedgerReference(string $referenceNo): ?Sale
    {
        $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
            ->where('invoice_no', $referenceNo)
            ->with('location')
            ->first();

        if (!$sale && strpos($referenceNo, 'MLX') === 0) {
            $saleId = str_replace('MLX', '', $referenceNo);
            $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->where('id', $saleId)
                ->with('location')
                ->first();
        }

        if (!$sale && strpos($referenceNo, 'INV-') === 0) {
            $saleId = str_replace('INV-', '', $referenceNo);
            $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->where('id', $saleId)
                ->with('location')
                ->first();
        }

        return $sale;
    }

    private function resolvePurchaseByLedgerReference(string $referenceNo): ?Purchase
    {
        $purchase = Purchase::where('reference_no', $referenceNo)->with('location')->first();

        if (!$purchase && strpos($referenceNo, 'PUR-') === 0) {
            $purchaseId = str_replace('PUR-', '', $referenceNo);
            $purchase = Purchase::where('id', $purchaseId)->with('location')->first();
        }

        return $purchase;
    }

    private function resolveSaleReturnByLedgerReference(string $referenceNo): ?SalesReturn
    {
        return SalesReturn::where('invoice_number', $referenceNo)
            ->orWhere('id', str_replace('SR-', '', $referenceNo))
            ->with(['sale.location'])
            ->first();
    }

    private function resolvePurchaseReturnByLedgerReference(string $referenceNo): ?PurchaseReturn
    {
        return PurchaseReturn::where('reference_no', $referenceNo)
            ->orWhere('id', str_replace('PR-', '', $referenceNo))
            ->with(['purchase.location'])
            ->first();
    }

    private function getDefaultLocationName(): ?string
    {
        $defaultLocation = \App\Models\Location::first();

        return $defaultLocation ? $defaultLocation->name : null;
    }

    private function resolveOpeningBalanceLocationName($ledger): ?string
    {
        if ($ledger->contact_type === 'customer') {
            $customer = Customer::find($ledger->contact_id);
            if ($customer && $customer->location_id) {
                $location = \App\Models\Location::find($customer->location_id);
                if ($location) {
                    return $location->name;
                }
            }
        } elseif ($ledger->contact_type === 'supplier') {
            $supplier = Supplier::find($ledger->contact_id);
            if ($supplier && $supplier->location_id) {
                $location = \App\Models\Location::find($supplier->location_id);
                if ($location) {
                    return $location->name;
                }
            }
        }

        return $this->getDefaultLocationName();
    }

    private function resolveLocationNameFromPayment(Payment $payment): ?string
    {
        if ($payment->payment_type === 'sale' && $payment->reference_id) {
            $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                ->where('id', $payment->reference_id)
                ->with('location')
                ->first();
            if ($sale && $sale->location) {
                return $sale->location->name;
            }
        }

        if ($payment->payment_type === 'purchase' && $payment->reference_id) {
            $purchase = Purchase::where('id', $payment->reference_id)->with('location')->first();
            if ($purchase && $purchase->location) {
                return $purchase->location->name;
            }
        }

        return null;
    }

    /**
     * Resolve expected payment ledger transaction type.
     */
    private function resolvePaymentLedgerTransactionType($payment, $fallbackType = null)
    {
        if (!empty($fallbackType)) {
            return $fallbackType;
        }

        if (!empty($payment->supplier_id)) {
            return 'purchase_payment';
        }

        if (($payment->payment_method ?? null) === 'discount') {
            return 'discount_given';
        }

        return 'payments';
    }

    /**
     * Transaction types that may represent payment ledger lines.
     */
    private function getPaymentLedgerTransactionTypes()
    {
        return ['payments', 'purchase_payment', 'discount_given'];
    }

    /**
     * Keep created_by assignment consistent across ledger writes.
     */
    private function resolveCreatedBy($createdBy = null): int
    {
        return (int) ($createdBy ?? auth()->id() ?? 1);
    }

    private function nowColombo(): Carbon
    {
        return Carbon::now('Asia/Colombo');
    }

    private function withTimedSuffix(string $referenceNo, string $suffix): string
    {
        return $referenceNo . '-' . $suffix . '-' . time();
    }

    private function reversedTag(string $message): string
    {
        return '[REVERSED: ' . $message . ' on ' . $this->nowColombo()->format('Y-m-d H:i:s') . ']';
    }

    private function appendNote(?string $baseNotes, string $suffix, string $separator = ' '): string
    {
        $base = (string) ($baseNotes ?? '');
        if ($base === '') {
            return $suffix;
        }

        return $base . $separator . $suffix;
    }

    private function markEntryReversed(Ledger $entry, string $message): void
    {
        $entry->update([
            'status' => 'reversed',
            'notes' => $this->appendNote($entry->notes, $this->reversedTag($message)),
        ]);

        if ($entry->contact_type === 'customer') {
            app(CustomerAdvanceBalanceService::class)->syncCustomer((int) $entry->contact_id);
        }
    }

    private function detectPaymentMethodFromNotes(?string $notes): string
    {
        $notesLower = strtolower((string) ($notes ?? ''));

        if (stripos($notesLower, 'cash') !== false) {
            return 'Cash';
        }
        if (stripos($notesLower, 'card') !== false || stripos($notesLower, 'credit') !== false || stripos($notesLower, 'debit') !== false) {
            return 'Card';
        }
        if (stripos($notesLower, 'bank') !== false || stripos($notesLower, 'transfer') !== false || stripos($notesLower, 'neft') !== false || stripos($notesLower, 'rtgs') !== false) {
            return 'Bank Transfer';
        }
        if (stripos($notesLower, 'cheque') !== false || stripos($notesLower, 'check') !== false) {
            return 'Cheque';
        }
        if (stripos($notesLower, 'upi') !== false || stripos($notesLower, 'gpay') !== false || stripos($notesLower, 'paytm') !== false || stripos($notesLower, 'phonepe') !== false) {
            return 'UPI';
        }

        return $notes ? 'Other' : 'N/A';
    }

    /**
     * Centralized ledger writer (phase 2 foundation, no behavior change).
     */
    private function postLedgerEntry(
        int $contactId,
        string $contactType,
        $transactionDate,
        string $referenceNo,
        string $transactionType,
        $amount,
        string $notes = '',
        $createdBy = null,
        ?string $status = null,
        array $extra = []
    ) {
        $payload = [
            'contact_id' => $contactId,
            'contact_type' => $contactType,
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'notes' => $notes,
            'created_by' => $this->resolveCreatedBy($createdBy),
        ];

        if ($status !== null) {
            $payload['status'] = $status;
        }

        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        return Ledger::createEntry($payload);
    }

    /**
     * Reduce opening balance without triggering model events.
     */
    private function applyOpeningBalancePaymentQuietly(int $contactId, string $contactType, float $paymentAmount): void
    {
        if ($contactType === 'customer') {
            $customer = Customer::withoutGlobalScopes()->find($contactId);
            if (!$customer) {
                return;
            }

            $oldOpeningBalance = (float) $customer->opening_balance;
            $newOpeningBalance = max(0, $oldOpeningBalance - $paymentAmount);
            $customer->updateQuietly(['opening_balance' => $newOpeningBalance]);

            Log::info("Customer opening balance updated via payment (no reversals)", [
                'customer_id' => $contactId,
                'old_opening_balance' => $oldOpeningBalance,
                'payment_amount' => $paymentAmount,
                'new_opening_balance' => $newOpeningBalance,
                'method' => 'updateQuietly'
            ]);

            return;
        }

        $supplier = Supplier::withoutGlobalScopes()->find($contactId);
        if (!$supplier) {
            return;
        }

        $oldOpeningBalance = (float) $supplier->opening_balance;
        $newOpeningBalance = max(0, $oldOpeningBalance - $paymentAmount);
        $supplier->updateQuietly(['opening_balance' => $newOpeningBalance]);

        Log::info("Supplier opening balance updated via payment (no reversals)", [
            'supplier_id' => $contactId,
            'old_opening_balance' => $oldOpeningBalance,
            'payment_amount' => $paymentAmount,
            'new_opening_balance' => $newOpeningBalance,
            'method' => 'updateQuietly'
        ]);
    }

    /**
     * Record opening balance for customer or supplier
     */
    public function recordOpeningBalance($contactId, $contactType, $amount, $notes = '', $createdBy = null)
    {
        return $this->postLedgerEntry(
            (int) $contactId,
            (string) $contactType,
            $this->nowColombo(),
            'OB-' . strtoupper($contactType) . '-' . $contactId,
            'opening_balance',
            $amount,
            $notes ?: "Opening balance for {$contactType}",
            $createdBy
        );
    }

    /**
     * Record sale transaction
     */
    public function recordSale($sale, $createdBy = null, $customTransactionDate = null, $forceCreate = false)
    {

        if (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order') {

            return null; // Don't create ledger entry
        }

        // ✅ FIX: Skip draft/quotation check if forceCreate is true (for updates from draft to final)
        if (!$forceCreate && isset($sale->status) && in_array($sale->status, ['draft', 'quotation'])) {
            Log::warning('⚠️ Attempted to create ledger entry for Draft/Quotation - skipping', [
                'sale_id' => $sale->id ?? 'N/A',
                'invoice_no' => $sale->invoice_no ?? 'N/A',
                'status' => $sale->status,
                'forceCreate' => 'NO'
            ]);
            return null; // Don't create ledger entry
        }

        Log::info('✅ RecordSale proceeding - checks passed', [
            'sale_id' => $sale->id ?? 'N/A',
            'invoice_no' => $sale->invoice_no ?? 'N/A',
            'status' => $sale->status ?? 'N/A'
        ]);

        // ✅ FIX: Validate that sale has customer_id before proceeding
        if (empty($sale->customer_id)) {
            Log::error('RecordSale called with empty customer_id', [
                'sale_id' => $sale->id ?? 'N/A',
                'customer_id' => $sale->customer_id ?? 'NULL',
                'invoice_no' => $sale->invoice_no ?? 'N/A'
            ]);
            throw new \Exception("Cannot record sale in ledger: customer_id is missing or empty. Sale ID: " . ($sale->id ?? 'unknown'));
        }

        // ✅ ADDITIONAL VALIDATION: Check for required sale properties
        if (!isset($sale->id) || !isset($sale->final_total)) {
            Log::error('RecordSale called with incomplete sale data', [
                'sale_id' => $sale->id ?? 'N/A',
                'has_final_total' => isset($sale->final_total) ? 'yes' : 'no',
                'final_total' => $sale->final_total ?? 'N/A'
            ]);
            throw new \Exception("Cannot record sale in ledger: Sale object is incomplete or missing required fields.");
        }

        // Generate a proper reference number for the sale
        $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;

        // ✅ FIX: Use custom transaction date if provided (for updates), otherwise use original creation time
        $transactionDate = $customTransactionDate ?:
            ($sale->created_at ?
                Carbon::parse($sale->created_at)->setTimezone('Asia/Colombo') :
                $this->nowColombo());

        return $this->postLedgerEntry(
            (int) $sale->customer_id,
            'customer',
            $transactionDate,
            $referenceNo,
            'sale',
            $sale->final_total,
            "Sale invoice #{$referenceNo}",
            $createdBy
        );
    }

    /**
     * Record purchase transaction
     */
    public function recordPurchase($purchase, $createdBy = null)
    {
        // ✅ VALIDATION: Ensure purchase has required fields
        if (empty($purchase->supplier_id)) {
            Log::error('RecordPurchase called with empty supplier_id', [
                'purchase_id' => $purchase->id ?? 'N/A',
                'supplier_id' => $purchase->supplier_id ?? 'NULL'
            ]);
            throw new \Exception("Cannot record purchase in ledger: supplier_id is missing. Purchase ID: " . ($purchase->id ?? 'unknown'));
        }

        if (!isset($purchase->grand_total) && !isset($purchase->final_total)) {
            Log::error('RecordPurchase called without total amount', [
                'purchase_id' => $purchase->id ?? 'N/A',
                'has_grand_total' => isset($purchase->grand_total) ? 'yes' : 'no',
                'has_final_total' => isset($purchase->final_total) ? 'yes' : 'no'
            ]);
            throw new \Exception("Cannot record purchase in ledger: total amount is missing. Purchase ID: " . ($purchase->id ?? 'unknown'));
        }

        if (!isset($purchase->id)) {
            Log::error('RecordPurchase called without purchase id');
            throw new \Exception("Cannot record purchase in ledger: purchase id is missing.");
        }

        $purchaseAmount = $purchase->final_total ?? $purchase->grand_total;

        // Generate a proper reference number for the purchase
        $referenceNo = $purchase->reference_no ?: 'PUR-' . $purchase->id;

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $purchase->created_at ?
            Carbon::parse($purchase->created_at)->setTimezone('Asia/Colombo') :
            $this->nowColombo();

        return $this->postLedgerEntry(
            (int) $purchase->supplier_id,
            'supplier',
            $transactionDate,
            $referenceNo,
            'purchase',
            $purchaseAmount,
            "Purchase invoice #{$referenceNo}",
            $createdBy
        );
    }

    /**
     * Record sale payment
     */
    public function recordSalePayment($payment, $sale = null, $createdBy = null)
    {
        // ✅ PERFORMANCE FIX: Skip ledger entries for Walk-In customers (customer_id = 1)
        // Walk-In customers don't need credit tracking, so no ledger entries needed
        if ($payment->customer_id == 1) {
            Log::info('Skipping ledger entry for Walk-In customer payment', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]);
            return null;
        }

        // ✅ CRITICAL FIX: For bulk payments, use actual invoice number in notes instead of bulk reference
        // If a sale object is provided, use its invoice_no for clarity in ledger notes
        // Otherwise, use the payment reference_no (e.g., BLK-S1422 for bulk payments)
        $invoiceNumberForNotes = $sale ? $sale->invoice_no : $payment->reference_no;
        $baseReferenceNo = $payment->reference_no ?: ($sale ? $sale->invoice_no : 'PAY-' . $payment->id);
        $referenceNo = $this->resolvePaymentLedgerReference($payment, $sale ? $sale->invoice_no : null);

        // Use the actual creation timestamp so ledger ordering reflects the real edit/create time.
        $transactionDate = $payment->created_at
            ? Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo')
            : $this->nowColombo();

        // ✅ SPECIAL HANDLING: Discount payment method should be recorded as 'discount_given', not 'payments'
        // This allows proper reporting and audit trail while still being a CREDIT entry (reduces customer debt)
        $transactionType = 'payments';
        if ($payment->payment_method === 'discount') {
            $transactionType = 'discount_given';
        }

        return $this->postLedgerEntry(
            (int) $payment->customer_id,
            'customer',
            $transactionDate,
            $referenceNo,
            $transactionType,
            $payment->amount,
            $payment->notes ?: "Payment for sale #{$invoiceNumberForNotes}",
            $createdBy
        );
    }

    /**
     * Record purchase payment
     */
    public function recordPurchasePayment($payment, $purchase = null, $createdBy = null)
    {
        // ✅ VALIDATION: Ensure payment has required fields
        if (empty($payment->supplier_id)) {
            Log::error('RecordPurchasePayment called with empty supplier_id', [
                'payment_id' => $payment->id ?? 'N/A',
                'supplier_id' => $payment->supplier_id ?? 'NULL'
            ]);
            throw new \Exception("Cannot record payment in ledger: supplier_id is missing. Payment ID: " . ($payment->id ?? 'unknown'));
        }

        if (!isset($payment->amount) || $payment->amount === null) {
            Log::error('RecordPurchasePayment called with null amount', [
                'payment_id' => $payment->id ?? 'N/A',
                'amount' => $payment->amount ?? 'NULL'
            ]);
            throw new \Exception("Cannot record payment in ledger: amount is missing. Payment ID: " . ($payment->id ?? 'unknown'));
        }

        // ✅ CRITICAL FIX: For bulk payments, append payment ID to reference to ensure unique ledger entries
        $baseReferenceNo = $payment->reference_no ?: ($purchase ? $purchase->reference_no : 'PAY-' . $payment->id);
        $referenceNo = $this->resolvePaymentLedgerReference($payment, $purchase ? $purchase->reference_no : null);

        // Use the user-entered payment_date (not system created_at).
        // payment_date is cast as 'date' — a plain Y-m-d already in Asia/Colombo time.
        // Use Carbon::parse($date, $tz) to INTERPRET (no shift), NOT ->setTimezone() which CONVERTS from UTC.
        $transactionDate = $payment->payment_date
            ? Carbon::parse($payment->payment_date, 'Asia/Colombo')
            : $this->nowColombo();

        return $this->postLedgerEntry(
            (int) $payment->supplier_id,
            'supplier',
            $transactionDate,
            $referenceNo,
            'purchase_payment',
            $payment->amount,
            $payment->notes ?: "Payment for purchase #{$baseReferenceNo}",
            $createdBy
        );
    }

    /**
     * Customer for ledger: direct link or parent sale (fixes null/wrong customer_id on returns).
     */
    public function resolveSaleReturnCustomerId($saleReturn): int
    {
        if (! $saleReturn instanceof SalesReturn) {
            return 0;
        }
        $saleReturn->loadMissing('sale:id,customer_id');

        return (int) ($saleReturn->customer_id ?: $saleReturn->sale?->customer_id);
    }

    /**
     * Ledger label: linked sale / with_bill stock → with bill; otherwise walk-in without bill.
     */
    private function saleReturnLedgerTransactionType(SalesReturn $saleReturn): string
    {
        $withBill = $saleReturn->sale_id !== null
            || strtolower((string) ($saleReturn->stock_type ?? '')) === 'with_bill';

        return $withBill ? 'sale_return_with_bill' : 'sale_return_without_bill';
    }

    private function buildSaleReturnLedgerNotes(SalesReturn $saleReturn, string $referenceNo): string
    {
        $saleReturn->loadMissing('sale:id,invoice_no');
        $ledgerType = $this->saleReturnLedgerTransactionType($saleReturn);
        $isWithBill = $ledgerType === 'sale_return_with_bill';
        $segments = ['Sale return ' . $referenceNo, $isWithBill ? 'With bill' : 'Without bill'];
        if ($isWithBill) {
            $inv = $saleReturn->sale?->invoice_no;
            if ($inv) {
                $segments[] = 'Against invoice ' . $inv;
            }
        } else {
            $segments[] = 'No parent sales invoice';
        }

        return implode(' · ', $segments);
    }

    /**
     * Record sale return
     */
    public function recordSaleReturn($saleReturn, $createdBy = null)
    {
        $contactId = $this->resolveSaleReturnCustomerId($saleReturn);
        if ($contactId <= 0) {
            Log::error('recordSaleReturn: cannot post ledger without customer', [
                'sales_return_id' => $saleReturn->id ?? null,
                'sale_id'         => $saleReturn->sale_id ?? null,
            ]);
            throw new \RuntimeException(
                'Sale return cannot be posted to the ledger: missing customer. Ensure the return is linked to a customer or a sale with a customer.'
            );
        }

        if (! $saleReturn->customer_id) {
            $saleReturn->forceFill(['customer_id' => $contactId])->saveQuietly();
        }

        // Generate a proper reference number for the sale return
        $referenceNo = $saleReturn->invoice_number ?: 'SR-' . $saleReturn->id;

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $saleReturn->created_at ?
            Carbon::parse($saleReturn->created_at)->setTimezone('Asia/Colombo') :
            $this->nowColombo();

        $ledgerTxType = $this->saleReturnLedgerTransactionType($saleReturn);
        $notes = $this->buildSaleReturnLedgerNotes($saleReturn, $referenceNo);

        return $this->postLedgerEntry(
            $contactId,
            'customer',
            $transactionDate,
            $referenceNo,
            $ledgerTxType,
            $saleReturn->return_total,
            $notes,
            $createdBy
        );
    }

    /**
     * Record purchase return
     */
    public function recordPurchaseReturn($purchaseReturn)
    {
        // Generate a proper reference number for the purchase return
        $referenceNo = $purchaseReturn->reference_no ?: 'PR-' . $purchaseReturn->id;

        // Use the actual creation time converted to Asia/Colombo timezone
        $transactionDate = $purchaseReturn->created_at ?
            Carbon::parse($purchaseReturn->created_at)->setTimezone('Asia/Colombo') :
            $this->nowColombo();

        return $this->postLedgerEntry(
            (int) $purchaseReturn->supplier_id,
            'supplier',
            $transactionDate,
            $referenceNo,
            'purchase_return',
            $purchaseReturn->return_total,
            "Purchase return #{$referenceNo}"
        );
    }

    /**
     * Record return payment (money paid back to customer or received from supplier)
     */
    public function recordReturnPayment($payment, $contactType)
    {
        return $this->createReturnLedgerEntry(
            $payment,
            $contactType,
            'payments',
            'Return payment - ' . ($payment->notes ?: 'Payment for returned items')
        );
    }

    /**
     * Record return credit application (return credit applied to reduce customer's sales due)
     */
    public function recordReturnCreditApplication($payment, $contactType)
    {
        return $this->createReturnLedgerEntry(
            $payment,
            $contactType,
            'payments',
            $payment->notes ?: 'Credit adjustment applied to outstanding sales'
        );
    }

    /**
     * Build the shared ledger entry used by return payment flows.
     */
    private function createReturnLedgerEntry($payment, $contactType, $transactionType, $notes)
    {
        // Use the edit timestamp so reversal/new entries are ordered where the edit happened.
        $transactionDate = $this->nowColombo();

        return $this->postLedgerEntry(
            $this->resolvePaymentContactId($payment, $contactType),
            (string) $contactType,
            $transactionDate,
            (string) $payment->reference_no,
            (string) $transactionType,
            $payment->amount,
            (string) $notes
        );
    }

    /**
     * Record cash refund for return (money paid back to customer)
     */
    public function recordReturnRefund($payment, $contactType)
    {
        // Use the user-entered payment_date (cast as 'date', already Asia/Colombo — interpret not convert)
        $transactionDate = $payment->payment_date
            ? Carbon::parse($payment->payment_date, 'Asia/Colombo')
            : $this->nowColombo();

        return $this->postLedgerEntry(
            $this->resolvePaymentContactId($payment, $contactType),
            (string) $contactType,
            $transactionDate,
            (string) $payment->reference_no,
            'return_payment',
            $payment->amount,
            $payment->notes ?: 'Cash refund processed'
        );
    }

    /**
     * Record cheque bounce
     */
    public function recordChequeBounce($payment, $bounceDate, $bounceReason, $createdBy = null)
    {
        // $bounceDate is user-entered — already Asia/Colombo. Interpret not convert.
        $transactionDate = Carbon::parse($bounceDate, 'Asia/Colombo');
        $referenceNo = 'BOUNCE-' . $payment->cheque_number . '-' . $payment->id;

        return $this->postLedgerEntry(
            $this->resolvePaymentContactId($payment),
            $this->resolvePaymentContactType($payment),
            $transactionDate,
            $referenceNo,
            'cheque_bounce',
            $payment->amount,
            "Cheque bounce: {$payment->cheque_number} - {$bounceReason}",
            $createdBy
        );
    }

    /**
     * Record advance payment (customer credit / overpayment)
     */
    public function recordAdvancePayment($payment, $contactType = 'customer', $createdBy = null)
    {
        // Use the user-entered payment_date (cast as 'date', already Asia/Colombo — interpret not convert)
        $transactionDate = $payment->payment_date
            ? Carbon::parse($payment->payment_date, 'Asia/Colombo')
            : $this->nowColombo();

        $contactId = $this->resolvePaymentContactId($payment, $contactType);

        return $this->postLedgerEntry(
            (int) $contactId,
            (string) $contactType,
            $transactionDate,
            $payment->reference_no ?: 'ADV-' . $payment->id,
            'advance_payment',
            $payment->amount,
            $payment->notes ?: "Advance payment - customer credit",
            $createdBy
        );
    }

    /**
     * Record advance credit usage as a ledger line (debit against customer advance).
     *
     * @deprecated Not invoked anywhere in this codebase. Applying advance to invoices uses
     *             Payment rows with payment_type advance_credit_usage without a second ledger post
     *             (credit already exists from the original advance).
     *             Do not call unless a future design explicitly adds ledger rows for usage.
     */
    public function recordAdvanceCreditUsage($payment, $contactType = 'customer', $createdBy = null)
    {
        // Use the user-entered payment_date (cast as 'date', already Asia/Colombo — interpret not convert)
        $transactionDate = $payment->payment_date
            ? Carbon::parse($payment->payment_date, 'Asia/Colombo')
            : $this->nowColombo();

        $contactId = $this->resolvePaymentContactId($payment, $contactType);

        return $this->postLedgerEntry(
            (int) $contactId,
            (string) $contactType,
            $transactionDate,
            $payment->reference_no ?: 'ADV-USE-' . $payment->id,
            'advance_credit_usage',
            $payment->amount,
            $payment->notes ?: "Advance credit applied to bills",
            $createdBy,
            null,
            // Preserved to avoid behavioral drift for downstream consumers.
            ['amount_type' => 'debit']
        );
    }

    /**
     * Legacy sale amount change (reversal + new sale lines).
     *
     * @deprecated Not used by the application. POS/API sale edits route through {@see SaleLedgerManager}
     *             which calls {@see self::updateSale()} (reverse + {@see self::recordNewSaleEntry()}).
     *             Retained only for possible external callers; remove after one release if unused.
     */
    public function editSale($sale, $oldFinalTotal, $editReason = null)
    {
        Log::info('🔧 editSale() called', [
            'sale_id'     => $sale->id ?? 'N/A',
            'invoice_no'  => $sale->invoice_no ?? 'N/A',
            'customer_id' => $sale->customer_id ?? 'N/A',
            'old_amount'  => $oldFinalTotal,
            'new_amount'  => $sale->final_total ?? 'N/A',
            'edit_reason' => $editReason,
        ]);

        return DB::transaction(function () use ($sale, $oldFinalTotal, $editReason) {
            $newFinalTotal = (float) ($sale->final_total ?? 0);
            $difference    = $newFinalTotal - $oldFinalTotal;
            $referenceNo   = $sale->invoice_no ?: 'INV-' . $sale->id;

            if ($sale->customer_id == 1 || $difference == 0) {
                Log::info('⏭️ editSale skipped', [
                    'sale_id' => $sale->id,
                    'reason'  => $sale->customer_id == 1 ? 'Walk-In Customer' : 'No amount change',
                ]);

                return null;
            }

            Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $sale->customer_id)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'sale')
                ->where('status', 'active')
                ->update([
                    'status' => 'reversed',
                    'notes'  => DB::raw("CONCAT(COALESCE(notes, ''), ' [REVERSED: Sale edited on " .
                        $this->nowColombo()->format('Y-m-d H:i:s') . "]')"),
                ]);

            $reversalEntry = $this->postLedgerEntry(
                (int) $sale->customer_id,
                'customer',
                $this->nowColombo(),
                $this->withTimedSuffix($referenceNo, 'REV'),
                'sale',
                -$oldFinalTotal,
                'REVERSAL: Sale Edit - Cancel previous amount Rs.' .
                number_format($oldFinalTotal, 2) .
                ($editReason ? ' | Reason: ' . $editReason : ''),
                null,
                'reversed'
            );

            $newSaleEntry = $this->postLedgerEntry(
                (int) $sale->customer_id,
                'customer',
                $sale->created_at
                    ? Carbon::parse($sale->created_at, 'Asia/Colombo')
                    : $this->nowColombo(),
                $referenceNo,
                'sale',
                $newFinalTotal,
                'Sale invoice #' . $referenceNo .
                ' (Edited) - New Amount Rs.' . number_format($newFinalTotal, 2) .
                ($editReason ? ' | Reason: ' . $editReason : ''),
                null,
                'active'
            );

            Log::info('✅ editSale completed with clean reversal accounting', [
                'sale_id'           => $sale->id,
                'invoice_no'        => $referenceNo,
                'old_amount'        => $oldFinalTotal,
                'new_amount'        => $newFinalTotal,
                'difference'        => $difference,
                'reversal_entry_id' => $reversalEntry->id ?? null,
                'new_entry_id'      => $newSaleEntry->id,
            ]);

            return [
                'reversal_entry' => $reversalEntry,
                'new_entry'      => $newSaleEntry,
                'old_amount'     => $oldFinalTotal,
                'new_amount'     => $newFinalTotal,
                'difference'     => $difference,
                'method'         => 'clean_reversal_accounting_v2',
            ];
        });
    }

    /**
     * Customer balance summary for reporting (delegates to LedgerBalanceQueryService).
     */
    public function getCustomerBalanceSummary($customerId)
    {
        return $this->balanceQueries()->getCustomerBalanceSummary($customerId);
    }

    /**
     * @deprecated Use BalanceHelper::getCustomerBalance() instead
     */
    public function getCustomerBillWiseBalance($customerId)
    {
        return $this->balanceQueries()->getCustomerBillWiseBalance($customerId);
    }

    /**
     * @deprecated Prefer BalanceHelper once floating-balance logic is moved there (Phase 3).
     *             Cheque bounces, bank charges, etc.
     */
    public function getCustomerFloatingBalance($customerId)
    {
        return $this->balanceQueries()->getCustomerFloatingBalance($customerId);
    }

    /**
     * Get total bounced cheques amount for customer
     */
    public function getCustomerBouncedChequesAmount($customerId)
    {
        return $this->balanceQueries()->getCustomerBouncedChequesAmount($customerId);
    }

    /**
     * Record floating balance recovery payment
     */
    public function recordFloatingBalanceRecovery($customerId, $amount, $paymentMethod = 'cash', $notes = '', $referenceNo = null)
    {
        $referenceNo = $referenceNo ?: ('RECOVERY-' . $customerId . '-' . time());

        return $this->postLedgerEntry(
            (int) $customerId,
            'customer',
            $this->nowColombo(),
            $referenceNo,
            'bounce_recovery',
            $amount,
            $notes ?: "Recovery payment for floating balance via {$paymentMethod}"
        );
    }

    /**
     * Get customer ledger statement for a date range
     */
    public function getCustomerStatement($customerId, $fromDate = null, $toDate = null)
    {
        return $this->balanceQueries()->getCustomerStatement($customerId, $fromDate, $toDate);
    }

    /**
     * Record opening balance payment and update customer/supplier opening balance
     * Business Logic: When opening balance is paid, reduce the opening balance in customer/supplier table
     * CLEAN VERSION: Only creates payment entry and updates table, no reversal entries
     */
    public function recordOpeningBalancePayment($payment, $contactType)
    {
        return DB::transaction(function () use ($payment, $contactType) {
            // Use the actual creation time converted to Asia/Colombo timezone
            $transactionDate = $payment->created_at ?
                Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo') :
                $this->nowColombo();

            $contactId = $contactType === 'customer' ? $payment->customer_id : $payment->supplier_id;

            // 1. Create ledger entry for the payment
            $ledgerEntry = $this->postLedgerEntry(
                (int) $contactId,
                (string) $contactType,
                $transactionDate,
                (string) $payment->reference_no,
                'opening_balance_payment',
                $payment->amount,
                $payment->notes ?: "Opening balance payment"
            );

            // 2. Update opening balance in customer/supplier table (Business Logic)
            // IMPORTANT: Use updateQuietly to prevent triggering model events that create reversal entries
            $this->applyOpeningBalancePaymentQuietly((int) $contactId, (string) $contactType, (float) $payment->amount);

            return $ledgerEntry;
        });
    }

    /**
     * Record opening balance adjustment (when customer/supplier opening balance is updated)
     * Creates proper balancing entries for clean audit trail
     */
    public function recordOpeningBalanceAdjustment($contactId, $contactType, $oldAmount, $newAmount, $notes = '')
    {
        return DB::transaction(function () use ($contactId, $contactType, $oldAmount, $newAmount, $notes) {
            // Only create ledger entries if there's an actual change
            if ($oldAmount == $newAmount) {
                return null;
            }

            $referenceBase = 'OB-' . strtoupper($contactType) . '-' . $contactId;

            // 🔥 THREE-RECORD REVERSAL ACCOUNTING:
            // Step 1: Find and mark existing opening balance as 'reversed'
            $oldEntry = null;
            if ($oldAmount != 0) {
                $oldEntry = Ledger::where('contact_id', $contactId)
                    ->where('contact_type', $contactType)
                    ->whereIn('transaction_type', ['opening_balance', 'opening_balance_adjustment']) // ✅ Check both types
                    ->where('status', 'active')
                    ->orderBy('id', 'desc') // Use id for reliable ordering when created_at is same
                    ->first();

                // Mark the old entry as reversed (for audit trail)
                if ($oldEntry) {
                    $this->markEntryReversed($oldEntry, 'Opening balance edited');
                }
            }

            // Step 2: Create REVERSAL entry to mathematically cancel the old amount
            $reversalEntry = null;
            if ($oldAmount != 0) {
                $reversalReferenceNo = $this->withTimedSuffix($referenceBase, 'REV');

                if ($contactType === 'customer') {
                    // For customers: Old opening was DEBIT, so create CREDIT to reverse it
                    $reversalEntry = $this->postLedgerEntry(
                        (int) $contactId,
                        (string) $contactType,
                        $this->nowColombo(),
                        $reversalReferenceNo,
                        'opening_balance_adjustment',
                        -$oldAmount, // Negative amount creates CREDIT to reverse old DEBIT
                        'REVERSAL: Opening Balance Edit - Cancel previous amount Rs.' . number_format($oldAmount, 2) . ($oldEntry ? ' [Cancels Entry ID: ' . $oldEntry->id . ']' : ''),
                        null,
                        'reversed'
                    );
                } else {
                    // For suppliers: Old opening was CREDIT, so create DEBIT to reverse it
                    $reversalEntry = $this->postLedgerEntry(
                        (int) $contactId,
                        (string) $contactType,
                        $this->nowColombo(),
                        $reversalReferenceNo,
                        'opening_balance_adjustment',
                        $oldAmount, // Positive amount creates DEBIT to reverse old CREDIT
                        'REVERSAL: Opening Balance Edit - Cancel previous amount Rs.' . number_format($oldAmount, 2) . ($oldEntry ? ' [Cancels Entry ID: ' . $oldEntry->id . ']' : ''),
                        null,
                        'reversed'
                    );
                }
            }

            // Step 3: Create NEW opening balance entry with the correct amount
            $newOpeningEntry = null;
            if ($newAmount != 0) {
                $newReferenceNo = $referenceBase;

                $newOpeningEntry = $this->postLedgerEntry(
                    (int) $contactId,
                    (string) $contactType,
                    $this->nowColombo(),
                    $newReferenceNo,
                    'opening_balance',
                    $newAmount,
                    $notes ?: 'Opening Balance for ' . ucfirst($contactType) . ': ' . ($contactType === 'customer' ? Customer::find($contactId)->name ?? 'Unknown' : Supplier::find($contactId)->name ?? 'Unknown')
                );
            }

            // **IMPORTANT: Update the customer/supplier table to keep opening_balance field in sync**
            // This ensures UI consistency between customer info panel and ledger calculations
            if ($contactType === 'customer') {
                Customer::where('id', $contactId)->update(['opening_balance' => $newAmount]);
            } elseif ($contactType === 'supplier') {
                \App\Models\Supplier::where('id', $contactId)->update(['opening_balance' => $newAmount]);
            }

            Log::info("Three-record reversal accounting completed for opening balance", [
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'old_entry_id' => $oldEntry ? $oldEntry->id : null,
                'reversal_entry_id' => $reversalEntry ? $reversalEntry->id : null,
                'new_entry_id' => $newOpeningEntry ? $newOpeningEntry->id : null
            ]);

            return [
                'old_entry' => $oldEntry,
                'reversal_entry' => $reversalEntry,
                'new_entry' => $newOpeningEntry,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'method' => 'three_record_reversal'
            ];
        });
    }

    /**
     * Get customer ledger with proper unified logic
     * @param bool $showFullHistory - true to show all transactions including edit history
     */
    public function getCustomerLedger($customerId, $startDate, $endDate, $locationId = null, $showFullHistory = false)
    {
        // Use withoutGlobalScopes to bypass LocationScope filtering
        // This is necessary because LocationScope filters customers by user's location permissions
        // but for ledger reports, we need to access all customers regardless of location
        $customer = $this->balanceQueries()->getCustomerForLedgerOrFail((int) $customerId);

        // Get ledger transactions for the customer within the date range
        $ledgerQuery = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->byDateRange($startDate, $endDate);

        // Apply location filtering if specified
        if ($locationId) {
            $ledgerQuery = $this->applyLocationFilter($ledgerQuery, $locationId, 'customer');
        }

        // Get all ledger transactions and calculate running balance properly based on view mode
        $ledgerQuery = DB::table('ledgers')
            ->where('contact_id', $customerId)
            ->where('contact_type', 'customer');

        // Apply date filtering - start date is optional
        if ($startDate) {
            $ledgerQuery->where('transaction_date', '>=', Carbon::parse($startDate)->startOfDay());
        }
        $ledgerQuery->where('transaction_date', '<=', Carbon::parse($endDate)->endOfDay())
            ->select('*')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        // Apply status filtering based on view mode
        if (!$showFullHistory) {
            // NORMAL VIEW: Only show active entries
            $ledgerQuery->where('status', 'active');
        }
        // FULL AUDIT TRAIL: Show all entries (no status filter)

        $ledgerTransactions = $ledgerQuery->get()
            ->map(function($row) {
                // Convert stdClass to object with proper properties for compatibility
                $ledger = new \stdClass();
                foreach ($row as $key => $value) {
                    $ledger->$key = $value;
                }
                return $ledger;
            });

        if ($showFullHistory) {
            $ledgerTransactions = $this->sortLedgerTransactionsForAuditTrail($ledgerTransactions);
        }

        // Calculate running balance properly based on what entries are included
        $runningBalance = 0;

        $ledgerTransactions = $ledgerTransactions->map(function($ledger) use (&$runningBalance, $showFullHistory) {
            // Calculate running balance based on entry status and view mode
            if ($showFullHistory) {
                // Full audit trail: Include all entries in running balance
                $runningBalance += ($ledger->debit - $ledger->credit);
            } else {
                // Normal view: Only active entries contribute to running balance
                if ($ledger->status === 'active') {
                    $runningBalance += ($ledger->debit - $ledger->credit);
                }
            }

            $ledger->calculated_running_balance = $runningBalance;
            $ledger->is_reversal_entry = ($ledger->status !== 'active');

            return $ledger;
        });

        // For normal view, entries are already filtered by status in query
        // For full audit trail, show everything
        $transactionsToProcess = $ledgerTransactions;

        // Transform ledger data for frontend display
        $transactions = $transactionsToProcess->map(function ($ledger) use ($showFullHistory) {
            // Use the calculated running balance from above
            $displayBalance = (float) $ledger->calculated_running_balance;

            // Use created_at converted to Asia/Colombo timezone for display
            $displayDate = $ledger->created_at ?
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') :
                'N/A';

            // Get location and transaction details
            $locationName = $this->getLocationForTransaction($ledger);

            if ($showFullHistory) {
                $transactionType = $this->getDetailedTransactionType($ledger);
                $enhancedNotes = $this->getEnhancedTransactionDescription($ledger);
            } else {
                $transactionType = Ledger::formatTransactionType($ledger->transaction_type);
                $enhancedNotes = $ledger->notes ?: '';
            }

            return [
                'date' => $displayDate,
                'reference_no' => $ledger->reference_no,
                'type' => $transactionType,
                'location' => $locationName,
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $displayBalance, // Display balance only
                'payment_method' => $this->extractPaymentMethod($ledger),
                'notes' => $enhancedNotes,
                'others' => $enhancedNotes,
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // SIMPLIFIED TOTALS CALCULATION - Use BalanceHelper for consistency

        // Use active transactions only for totals (consistent with BalanceHelper logic)
        $activeTransactions = $ledgerTransactions->where('status', 'active');
        $totalDebits = $activeTransactions->sum('debit');
        $totalCredits = $activeTransactions->sum('credit');

        // Calculate specific totals from ledger entries
        // Total Transactions: Only sale amounts (not opening balance)
        $totalInvoices = $activeTransactions->whereIn('transaction_type', ['sale'])->sum('debit');

        // Total Paid: Only sale payments (not opening balance payments or advance payments)
        $totalPayments = $activeTransactions->whereIn('transaction_type', [
            'payment',                  // Generic payment for sales
            'payments',                 // Original payment type for sales
            'sale_payment',            // Sale-specific payment only
            'discount_given',         // Reduces receivable like a payment
        ])->sum('credit');

        $totalReturns = $activeTransactions->whereIn('transaction_type', self::SALE_RETURN_LEDGER_TYPES)->sum('credit');

        // Get current balance using BalanceHelper (SINGLE SOURCE OF TRUTH)
        $currentBalance = BalanceHelper::getCustomerBalance($customerId);

        // Opening balance for the period = net of active ledger rows strictly before start date
        if ($startDate) {
            $openingBalance = (float) Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_date', '<', Carbon::parse($startDate)->startOfDay())
                ->where('status', 'active')
                ->sum(DB::raw('debit - credit'));
        } else {
            // If no start date, use customer's original opening balance
            $openingBalance = $customer->opening_balance;
        }

        // CRITICAL FIX: When showing full audit trail, the effective due should match the final running balance
        // This ensures consistency between what's displayed and the effective due amount
        if ($showFullHistory && !$transactionsToProcess->isEmpty()) {
            // Use the final running balance from the displayed transactions
            $finalTransaction = $transactionsToProcess->last();
            if ($finalTransaction && isset($finalTransaction->calculated_running_balance)) {
                // For full audit trail, the effective due should match the displayed running balance
                $auditTrailFinalBalance = (float) $finalTransaction->calculated_running_balance;

                // IMPORTANT: For advance calculation, we should still use business logic
                // Even in full audit mode, advances should be calculated correctly
                // The customer should have 0 advance since they owe Rs. 8,000

                $effectiveDue = max(0, $auditTrailFinalBalance);
                $advanceAmount = $auditTrailFinalBalance < 0 ? abs($auditTrailFinalBalance) : 0;

                // However, if the audit trail shows a positive balance (customer owes money),
                // then advance amount should be 0
                if ($auditTrailFinalBalance > 0) {
                    $advanceAmount = 0; // No advance when customer owes money
                }

                // Store both values for comparison
                $currentBalanceForDisplay = $auditTrailFinalBalance; // Use audit trail balance for display
                $auditTrailBalance = $auditTrailFinalBalance; // Audit trail balance
            } else {
                // Fallback to BalanceHelper calculation
                $effectiveDue = max(0, $currentBalance);
                $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;
                $currentBalanceForDisplay = $currentBalance;
                $auditTrailBalance = $currentBalance;
            }
        } else {
            // For normal view, ALWAYS use BalanceHelper for consistency
            // This ensures POS, ledger, and all other places show the same balance
            $filteredBalance = BalanceHelper::getCustomerBalance($customerId);

            $effectiveDue = max(0, $filteredBalance);
            $advanceAmount = $filteredBalance < 0 ? abs($filteredBalance) : 0;
            $currentBalanceForDisplay = $filteredBalance;
            $auditTrailBalance = $filteredBalance;
        }

        // Outstanding Due should match Effective Due for consistency in reports
        $totalOutstandingDue = $effectiveDue;

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'mobile' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'opening_balance' => $customer->opening_balance,
                'current_balance' => isset($currentBalanceForDisplay) ? $currentBalanceForDisplay : $currentBalance,
            ],
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $totalInvoices, // Total sale transactions for display
                'total_invoices' => $totalInvoices, // Only actual sales/invoices
                'total_paid' => $totalPayments, // Only actual payments
                'total_returns' => $totalReturns, // Only actual returns
                'balance_due' => $totalOutstandingDue,
                'advance_amount' => $advanceAmount,
                'effective_due' => $effectiveDue,
                'outstanding_due' => $totalOutstandingDue,
                'opening_balance' => $openingBalance,
                // Debug information for troubleshooting
                'current_balance_from_helper' => $currentBalance, // From BalanceHelper (business logic)
                'audit_trail_final_balance' => isset($auditTrailBalance) ? $auditTrailBalance : null, // From audit trail
                'show_full_history' => $showFullHistory, // Mode indicator
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'advance_application' => [
                'available_advance' => $advanceAmount,
                'applied_to_outstanding' => 0,
                'remaining_advance' => $advanceAmount,
            ]
        ];
    }

    /**
     * Get supplier ledger with proper unified logic
     * @param bool $showFullHistory - true to show all transactions including edit history
     */
    public function getSupplierLedger($supplierId, $startDate, $endDate, $locationId = null, $showFullHistory = false)
    {
        // Use withoutGlobalScopes to bypass LocationScope filtering
        // This is necessary because LocationScope filters suppliers by user's location permissions
        // but for ledger reports, we need to access all suppliers regardless of location
        $supplier = $this->balanceQueries()->getSupplierForLedgerOrFail((int) $supplierId);

        // Get ledger transactions with proper status filtering based on view mode
        $ledgerQuery = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->byDateRange($startDate, $endDate);

        // Apply location filtering if specified
        if ($locationId) {
            $ledgerQuery = $this->applyLocationFilter($ledgerQuery, $locationId, 'supplier');
        }

        // Apply status filtering based on view mode
        if (!$showFullHistory) {
            // NORMAL VIEW: Only show active entries
            $ledgerQuery->where('status', 'active');
        }
        // FULL AUDIT TRAIL: Show all entries (no status filter)

        $ledgerTransactions = $ledgerQuery
            ->orderBy('created_at', 'asc') // Order by created_at ascending (chronological order)
            ->orderBy('id', 'asc') // Secondary sort by ID for same-date transactions (chronological)
            ->get();

        if ($showFullHistory) {
            $ledgerTransactions = $this->sortLedgerTransactionsForAuditTrail($ledgerTransactions);
        }

        // For normal view, entries are already filtered by status in query
        // For full audit trail, show everything
        $transactionsToProcess = $ledgerTransactions;

        // Calculate running balance properly based on view mode
        $runningBalance = 0;
        $transactions = $transactionsToProcess->map(function ($ledger) use ($showFullHistory, &$runningBalance) {
            // Calculate running balance based on view mode
            if ($showFullHistory) {
                // Full audit trail: Include all entries in running balance
                $runningBalance += ($ledger->credit - $ledger->debit); // For suppliers: credit increases balance
            } else {
                // Normal view: Only active entries contribute to running balance
                if ($ledger->status === 'active') {
                    $runningBalance += ($ledger->credit - $ledger->debit); // For suppliers: credit increases balance
                }
            }

            // Use created_at converted to Asia/Colombo timezone for display
            $displayDate = $ledger->created_at ?
                Carbon::parse($ledger->created_at)->setTimezone('Asia/Colombo')->format('d/m/Y H:i:s') :
                'N/A';

            // Get location and transaction details
            $locationName = $this->getLocationForTransaction($ledger);

            if ($showFullHistory) {
                $transactionType = $this->getDetailedTransactionType($ledger);
                $enhancedNotes = $this->getEnhancedTransactionDescription($ledger);
            } else {
                $transactionType = Ledger::formatTransactionType($ledger->transaction_type);
                $enhancedNotes = $ledger->notes ?: '';
            }

            return [
                'date' => $displayDate,
                'reference_no' => $ledger->reference_no,
                'type' => $transactionType,
                'location' => $locationName,
                'payment_status' => $this->getPaymentStatus($ledger),
                'debit' => $ledger->debit,
                'credit' => $ledger->credit,
                'running_balance' => $runningBalance,
                'payment_method' => $this->extractPaymentMethod($ledger),
                'notes' => $enhancedNotes,
                'others' => $enhancedNotes,
                'created_at' => $ledger->created_at,
                'transaction_type' => $ledger->transaction_type
            ];
        });

        // SIMPLIFIED TOTALS - Use active transactions only (consistent with BalanceHelper)
        $activeTransactions = $ledgerTransactions->where('status', 'active');
        $totalDebits = $activeTransactions->sum('debit');
        $totalCredits = $activeTransactions->sum('credit');

        // Calculate specific totals for account summary
        // Total Transactions: Only purchase amounts (not opening balance)
        $totalPurchases = $activeTransactions->whereIn('transaction_type', ['purchase'])->sum('credit');

        // Total Paid: Only purchase payments (not opening balance payments or advance payments)
        $totalPayments = $activeTransactions->whereIn('transaction_type', [
            'payment',                  // Generic payment for purchases
            'payments',                 // Original payment type for purchases
            'purchase_payment',        // Purchase-specific payment only
        ])->sum('debit');

        $totalReturns = $activeTransactions->whereIn('transaction_type', ['purchase_return'])->sum('debit');

        // Get current balance using BalanceHelper (SINGLE SOURCE OF TRUTH)
        $currentBalance = BalanceHelper::getSupplierBalance($supplierId);

        // SIMPLIFIED balance calculations
        $totalOutstandingDue = max(0, $currentBalance);
        $advanceAmount = $currentBalance < 0 ? abs($currentBalance) : 0;
        $effectiveDue = $totalOutstandingDue;
        $openingBalance = $supplier->opening_balance ?? 0;

        return [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->first_name . ' ' . $supplier->last_name,
                'mobile' => $supplier->mobile_no,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'opening_balance' => $supplier->opening_balance,
                'current_balance' => $currentBalance,
            ],
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $totalPurchases, // Total purchase transactions for display
                'total_purchases' => $totalPurchases, // Only actual purchases
                'total_paid' => $totalPayments, // Only actual payments
                'total_returns' => $totalReturns, // Only actual returns
                'balance_due' => $totalOutstandingDue,
                'advance_amount' => $advanceAmount,
                'effective_due' => $effectiveDue,
                'outstanding_due' => $totalOutstandingDue,
                'opening_balance' => $openingBalance,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'advance_application' => [
                'available_advance' => $advanceAmount,
                'applied_to_outstanding' => 0,
                'remaining_advance' => $advanceAmount,
            ]
        ];
    }

    /**
     * Get unified ledger view (both customers and suppliers)
     */
    public function getUnifiedLedgerView($startDate, $endDate, $contactType = null)
    {
        return $this->balanceQueries()->getUnifiedLedgerView($startDate, $endDate, $contactType);
    }

    /**
     * Get payment status based on ledger transaction type
     */
    private function getPaymentStatus($ledger)
    {
        return match($ledger->transaction_type) {
            'sale', 'purchase' => 'Due',
            'sale_payment', 'opening_balance_payment', 'payments', 'purchase_payment', 'discount_given' => 'Paid',
            'sale_return', 'purchase_return' => 'Returned',
            'return_payment' => 'Refunded',
            'opening_balance' => 'Due',
            default => 'N/A'
        };
    }

    /**
     * Extract payment method from actual Payment record or ledger notes
     */
    private function extractPaymentMethod($ledger)
    {
        if ($ledger->transaction_type === 'discount_given') {
            return 'Discount';
        }

        // For payment transactions, try to get actual payment method from Payment table first
        if (in_array($ledger->transaction_type, ['payments', 'sale_payment', 'purchase_payment'])) {
            try {
                // Try to find the actual Payment record by reference number
                $referenceNo = $ledger->reference_no;

                // Look for payment by reference number or extract payment ID
                $payment = null;

                // Try direct reference match first
                $payment = $this->findPaymentByReference($referenceNo);

                // If found payment record, use its payment method
                if ($payment && $payment->payment_method) {
                    return ucfirst($payment->payment_method);
                }
            } catch (\Exception $e) {
                // Fall back to notes extraction if Payment lookup fails
                Log::warning("Could not fetch payment method from Payment table: " . $e->getMessage());
            }

            // Fallback: Extract from notes if Payment record not found
            return $this->detectPaymentMethodFromNotes($ledger->notes ?? null);
        }

        return 'N/A';
    }

    /**
     * Get location information for a ledger transaction
     */
    private function getLocationForTransaction($ledger)
    {
        try {
            // Extract invoice/reference numbers to find related records
            $referenceNo = $ledger->reference_no;

            // For opening balance transactions, get customer/supplier's location_id
            if ($ledger->transaction_type === 'opening_balance') {
                $openingBalanceLocation = $this->resolveOpeningBalanceLocationName($ledger);
                if ($openingBalanceLocation !== null) {
                    return $openingBalanceLocation;
                }
            }

            // For sale transactions, find the sale and get its location
            if (in_array($ledger->transaction_type, ['sale', 'sale_payment'])) {
                $sale = $this->resolveSaleByLedgerReference($referenceNo);

                if ($sale && $sale->location) {
                    return $sale->location->name;
                }
            }

            // For purchase transactions, find the purchase and get its location
            if (in_array($ledger->transaction_type, ['purchase', 'payments'])) {
                $purchase = $this->resolvePurchaseByLedgerReference($referenceNo);

                if ($purchase && $purchase->location) {
                    return $purchase->location->name;
                }
            }

            // For sale return transactions
            if (in_array($ledger->transaction_type, ['sale_return', 'sale_return_with_bill', 'sale_return_without_bill'])) {
                $saleReturn = $this->resolveSaleReturnByLedgerReference($referenceNo);

                if ($saleReturn && $saleReturn->sale && $saleReturn->sale->location) {
                    return $saleReturn->sale->location->name;
                }
            }

            // For purchase return transactions
            if (in_array($ledger->transaction_type, ['purchase_return'])) {
                $purchaseReturn = $this->resolvePurchaseReturnByLedgerReference($referenceNo);

                if ($purchaseReturn && $purchaseReturn->purchase && $purchaseReturn->purchase->location) {
                    return $purchaseReturn->purchase->location->name;
                }
            }

            // For payment transactions, try to find the related sale/purchase through payment table
            if (in_array($ledger->transaction_type, ['payments', 'purchase_payment'])) {
                $payment = $this->findPaymentByReference($referenceNo);

                if ($payment) {
                    $paymentLocationName = $this->resolveLocationNameFromPayment($payment);
                    if ($paymentLocationName !== null) {
                        return $paymentLocationName;
                    }
                }
            }

        } catch (\Exception $e) {
            // Log error if needed, but don't break the flow
            Log::warning("Error getting location for transaction {$ledger->id}: " . $e->getMessage());
        }

        // If we still can't find location, try to get default location
        try {
            $defaultLocationName = $this->getDefaultLocationName();
            if ($defaultLocationName !== null) {
                return $defaultLocationName;
            }
        } catch (\Exception $e) {
            Log::warning("Error getting default location: " . $e->getMessage());
        }

        return 'N/A';
    }

    /**
     * Apply location filter to ledger query by joining with related transaction tables
     */
    private function applyLocationFilter($ledgerQuery, $locationId, $contactType)
    {
        // Get reference numbers for transactions that belong to the specified location
        $saleReferences = DB::table('sales')
            ->where('location_id', $locationId)
            ->pluck('invoice_no')
            ->merge(DB::table('sales')->where('location_id', $locationId)->pluck('id')->map(function($id) {
                return "INV-{$id}";
            }))
            ->merge(DB::table('sales')->where('location_id', $locationId)->pluck('id')->map(function($id) {
                return "MLX{$id}";
            }))
            ->filter()
            ->toArray();

        // Get payment references for sales at this location
        $paymentReferences = DB::table('payments')
            ->join('sales', 'sales.id', '=', 'payments.reference_id')
            ->where('sales.location_id', $locationId)
            ->where('payments.payment_type', 'sale')
            ->pluck('payments.reference_no')
            ->filter()
            ->toArray();

        // Get sale return references for sales at this location
        $saleReturnReferences = DB::table('sales_returns')
            ->join('sales', 'sales.id', '=', 'sales_returns.sale_id')
            ->where('sales.location_id', $locationId)
            ->pluck('sales_returns.invoice_number')
            ->merge(DB::table('sales_returns')
                ->join('sales', 'sales.id', '=', 'sales_returns.sale_id')
                ->where('sales.location_id', $locationId)
                ->pluck('sales_returns.id')->map(function($id) {
                    return "SR-{$id}";
                }))
            ->filter()
            ->toArray();

        // Get return payment references (for returned items at this location)
        $returnPaymentReferences = DB::table('payments')
            ->join('sales_returns', 'sales_returns.invoice_number', '=', 'payments.reference_no')
            ->join('sales', 'sales.id', '=', 'sales_returns.sale_id')
            ->where('sales.location_id', $locationId)
            ->where('payments.payment_type', 'sale_return_with_bill')
            ->pluck('payments.reference_no')
            ->filter()
            ->toArray();

        $allReferences = array_merge($saleReferences, $paymentReferences, $saleReturnReferences, $returnPaymentReferences);

        if ($contactType === 'supplier') {
            $purchaseReferences = DB::table('purchases')
                ->where('location_id', $locationId)
                ->pluck('reference_no')
                ->merge(DB::table('purchases')->where('location_id', $locationId)->pluck('id')->map(function($id) {
                    return "PUR-{$id}";
                }))
                ->filter()
                ->toArray();

            $purchasePaymentReferences = DB::table('payments')
                ->join('purchases', 'purchases.id', '=', 'payments.reference_id')
                ->where('purchases.location_id', $locationId)
                ->where('payments.payment_type', 'purchase')
                ->pluck('payments.reference_no')
                ->filter()
                ->toArray();

            $purchaseReturnReferences = DB::table('purchase_returns')
                ->join('purchases', 'purchases.id', '=', 'purchase_returns.purchase_id')
                ->where('purchases.location_id', $locationId)
                ->pluck('purchase_returns.reference_no')
                ->merge(DB::table('purchase_returns')
                    ->join('purchases', 'purchases.id', '=', 'purchase_returns.purchase_id')
                    ->where('purchases.location_id', $locationId)
                    ->pluck('purchase_returns.id')->map(function($id) {
                        return "PR-{$id}";
                    }))
                ->filter()
                ->toArray();

            $allReferences = array_merge($allReferences, $purchaseReferences, $purchasePaymentReferences, $purchaseReturnReferences);
        }

        // Always include opening balance transactions
        $ledgerQuery->where(function ($query) use ($allReferences) {
            if (!empty($allReferences)) {
                $query->whereIn('reference_no', $allReferences);
            }
            $query->orWhere('transaction_type', 'opening_balance');
        });

        return $ledgerQuery;
    }

    /**
     * Sync existing data to ledger (migration helper)
     */
    public function syncExistingDataToLedger()
    {
        // This method can be used to migrate existing sales, purchases, payments etc. to the unified ledger
        // Implementation would depend on your specific migration needs
    }

    /**
     * Handle sale edit with customer change - properly manages ledger transfers
     *
     * @param  string|null  $oldInvoiceNo  Invoice number before save (ledger rows may still use this ref)
     */
    public function editSaleWithCustomerChange($sale, $oldCustomerId, $newCustomerId, $oldFinalTotal, $editReason = null, ?string $oldInvoiceNo = null)
    {
        // ✅ FIX: Validate customer IDs before proceeding
        if (empty($newCustomerId) || $newCustomerId === null) {
            Log::error('EditSaleWithCustomerChange called with empty newCustomerId', [
                'sale_id' => $sale->id,
                'old_customer_id' => $oldCustomerId,
                'new_customer_id' => $newCustomerId,
                'sale_customer_id' => $sale->customer_id ?? 'N/A'
            ]);
            throw new \Exception("Cannot edit sale: new customer_id is missing or empty. Sale ID: {$sale->id}");
        }

        return DB::transaction(function () use ($sale, $oldCustomerId, $newCustomerId, $oldFinalTotal, $editReason, $oldInvoiceNo) {
            $inv = trim((string) ($sale->invoice_no ?? ''));
            $newReferenceNo = ($inv !== '' && $inv !== '-') ? $inv : 'INV-' . (int) $sale->id;
            $newFinalTotal = $sale->final_total;

            $refsForOldCustomer = array_values(array_unique(array_filter([
                ($oldInvoiceNo !== null && trim((string) $oldInvoiceNo) !== '' && trim((string) $oldInvoiceNo) !== '-')
                    ? trim((string) $oldInvoiceNo) : null,
                'INV-' . (int) $sale->id,
            ], static fn ($r) => $r !== null && $r !== '' && $r !== '-')));

            if ($refsForOldCustomer === []) {
                $refsForOldCustomer = [$newReferenceNo];
            }

            // Skip if both customers are Walk-In (no ledger impact)
            if ($oldCustomerId == 1 && $newCustomerId == 1) {
                return null;
            }

            Log::info("Processing sale edit with customer change", [
                'sale_id' => $sale->id,
                'new_reference_no' => $newReferenceNo,
                'refs_for_old_customer' => $refsForOldCustomer,
                'old_invoice_no' => $oldInvoiceNo,
                'old_customer_id' => $oldCustomerId,
                'new_customer_id' => $newCustomerId,
                'old_amount' => $oldFinalTotal,
                'new_amount' => $newFinalTotal
            ]);

            // STEP 1: Remove/reverse entries from old customer (if not Walk-In)
            if ($oldCustomerId != 1) {
                foreach ($refsForOldCustomer as $ledgerRef) {
                    $oldSaleEntries = Ledger::where('reference_no', $ledgerRef)
                        ->where('contact_id', $oldCustomerId)
                        ->where('contact_type', 'customer')
                        ->where('transaction_type', 'sale')
                        ->where('debit', '>', 0)
                        ->where('status', '!=', 'reversed')
                        ->get();

                    foreach ($oldSaleEntries as $entry) {
                        $this->markEntryReversed($entry, 'Customer changed');

                        $this->postLedgerEntry(
                            (int) $oldCustomerId,
                            'customer',
                            $this->nowColombo(),
                            $this->withTimedSuffix('EDIT-CUST-REV-' . $entry->reference_no, (string) $entry->id),
                            'sale',
                            -$entry->debit,
                            'Sale Customer Change - Removed from Customer #' . $oldCustomerId . ' (Rs' . number_format($entry->debit, 2) . ')' .
                            ($editReason ? ' | Reason: ' . $editReason : ''),
                            null,
                            'reversed'
                        );
                    }

                    $oldPaymentEntries = Ledger::where('reference_no', $ledgerRef)
                        ->where('contact_id', $oldCustomerId)
                        ->where('contact_type', 'customer')
                        ->where('transaction_type', 'payments')
                        ->where('credit', '>', 0)
                        ->where('status', '!=', 'reversed')
                        ->get();

                    foreach ($oldPaymentEntries as $entry) {
                        $this->markEntryReversed($entry, 'Customer changed');

                        $this->postLedgerEntry(
                            (int) $oldCustomerId,
                            'customer',
                            $this->nowColombo(),
                            $this->withTimedSuffix('EDIT-PAY-REV-' . $entry->reference_no, (string) $entry->id),
                            'payments',
                            $entry->credit,
                            'Payment Customer Change - Removed from Customer #' . $oldCustomerId . ' (Rs' . number_format($entry->credit, 2) . ')' .
                            ($editReason ? ' | Reason: ' . $editReason : ''),
                            null,
                            'reversed'
                        );
                    }
                }
            }

            // STEP 2: Add entries to new customer (if not Walk-In)
            if ($newCustomerId != 1) {
                $this->postLedgerEntry(
                    (int) $newCustomerId,
                    'customer',
                    $this->nowColombo(),
                    $newReferenceNo,
                    'sale',
                    $newFinalTotal,
                    "Sale Customer Change - Added to Customer #{$newCustomerId} (Rs{$newFinalTotal})" .
                    ($editReason ? " | Reason: {$editReason}" : '')
                );

                // Note: Payment entries will be recreated by the payment processing in controller
            }

            return [
                'old_customer_id' => $oldCustomerId,
                'new_customer_id' => $newCustomerId,
                'amount_transferred' => $newFinalTotal,
                'status' => 'customer_change_completed'
            ];
        });
    }

    /**
     * Update sale transaction - creates proper reversal entries for audit trail
     */
    /**
     * Reverse old sale ledger entry (Step 1 of sale edit)
     * This creates sale reversal entries but does NOT create the new sale entry
     */
    public function reverseSale($sale, $oldReferenceNo = null)
    {
        // ✅ FIX: Validate that sale has customer_id before proceeding
        if (empty($sale->customer_id)) {
            Log::error('ReverseSale called with empty customer_id', [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'invoice_no' => $sale->invoice_no ?? 'N/A'
            ]);
            throw new \Exception("Cannot reverse sale ledger: customer_id is missing or empty. Sale ID: {$sale->id}");
        }

        $referenceNo = $oldReferenceNo ?: ($sale->invoice_no ?: 'INV-' . $sale->id);

        Log::info('ReverseSale: Starting sale reversal', [
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'reference_no' => $referenceNo
        ]);

        // Find the original sale ledger entry to reverse
        $originalEntry = Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale')
            ->where('contact_id', $sale->customer_id)
            ->where('debit', '>', 0)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($originalEntry) {
            Log::info('ReverseSale: Found original entry to reverse', [
                'original_entry_id' => $originalEntry->id,
                'original_amount' => $originalEntry->debit
            ]);

            // Mark original entry as reversed
            $this->markEntryReversed($originalEntry, 'Sale updated');

            // Create reversal entry for audit trail
            $reversalEntry = $this->postLedgerEntry(
                (int) $sale->customer_id,
                'customer',
                now(),
                $this->withTimedSuffix($referenceNo, 'REV'),
                'sale',
                -$originalEntry->debit,
                "REVERSAL: Sale Edit - Original amount Rs{$originalEntry->debit} (ID: {$originalEntry->id})",
                null,
                'reversed'
            );

            Log::info('ReverseSale: Created reversal entry', [
                'reversal_entry_id' => $reversalEntry->id,
                'reversal_amount' => -$originalEntry->debit
            ]);

            return $reversalEntry;
        }

        Log::info('ReverseSale: No active entry found to reverse');
        return null;
    }

    /**
     * Reverse a stray active customer "sale" debit by reference (e.g. invoice renumbered,
     * customer moved, invoice cleared) so BalanceHelper matches open sales again.
     *
     * @return \App\Models\Ledger|null  The reversal row, or null if nothing matched
     */
    public function reverseOrphanCustomerSaleLedgerEntry(
        int $customerId,
        string $referenceNo,
        string $reason = 'Orphan sale ledger cleanup',
        $createdBy = null
    ): ?Ledger {
        $referenceNo = trim($referenceNo);
        if ($customerId <= 1 || $referenceNo === '') {
            return null;
        }

        return DB::transaction(function () use ($customerId, $referenceNo, $reason, $createdBy) {
            $entry = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'sale')
                ->where('reference_no', $referenceNo)
                ->where('status', 'active')
                ->where('debit', '>', 0)
                ->orderByDesc('id')
                ->first();

            if (!$entry) {
                return null;
            }

            $this->markEntryReversed($entry, $reason);

            return $this->postLedgerEntry(
                (int) $customerId,
                'customer',
                $this->nowColombo(),
                $this->withTimedSuffix($referenceNo, 'ORPHAN-REV'),
                'sale',
                -(float) $entry->debit,
                'REVERSAL: ' . $reason . ' — Original sale debit Rs.' . number_format((float) $entry->debit, 2) . ' (Ledger ID: ' . $entry->id . ')',
                $createdBy,
                'reversed'
            );
        });
    }

    /**
     * Record new sale ledger entry (Step 2 of sale edit - called AFTER payment reversals)
     * This should be called after all reversals are complete
     */
    public function recordNewSaleEntry($sale)
    {
        Log::info('RecordNewSaleEntry: Called', [
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'invoice_no' => $sale->invoice_no,
            'final_total' => $sale->final_total,
            'status' => $sale->status
        ]);

        if (empty($sale->customer_id)) {
            Log::error('RecordNewSaleEntry: Missing customer_id', [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id
            ]);
            throw new \Exception("Cannot record sale: customer_id is missing. Sale ID: {$sale->id}");
        }

        // ✅ FIX: Force creation even if converting from draft/quotation to final
        // Use the original sale date as the ledger transaction_date (not today's date).
        // NOTE: sales_date is stored as a plain Y-m-d H:i:s string already in Asia/Colombo time
        // (set by SaleSaveService via Carbon::now('Asia/Colombo')->format(...)).
        // We must use Carbon::parse($date, $tz) NOT ->setTimezone($tz):
        //   - Carbon::parse($date)->setTimezone('Asia/Colombo') → CONVERTS from UTC → shifts +5:30 (WRONG)
        //   - Carbon::parse($date, 'Asia/Colombo')              → INTERPRETS as Asia/Colombo → no shift (CORRECT)
        $saleTransactionDate = $sale->sales_date
            ? Carbon::parse($sale->sales_date, 'Asia/Colombo')
            : $this->nowColombo();
        $result = $this->recordSale($sale, null, $saleTransactionDate, true);

        if (!$result) {
            Log::error('🚨 RecordNewSaleEntry: recordSale returned NULL', [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'invoice_no' => $sale->invoice_no
            ]);
            throw new \Exception("Failed to create ledger entry in recordSale for sale #{$sale->id}");
        }

        Log::info('✅ RecordNewSaleEntry: Success', [
            'ledger_id' => $result->id,
            'sale_id' => $sale->id
        ]);

        return $result;
    }

    /**
     * Update sale (legacy method - now split into reverseSale + recordNewSaleEntry)
     * Kept for backward compatibility
     */
    public function updateSale($sale, $oldReferenceNo = null)
    {
        Log::info('UpdateSale: Starting update process', [
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'invoice_no' => $sale->invoice_no,
            'final_total' => $sale->final_total,
            'old_reference' => $oldReferenceNo
        ]);

        try {
            // STEP 1: Reverse old entry
            $reversalResult = $this->reverseSale($sale, $oldReferenceNo);
            Log::info('UpdateSale: Reversal completed', [
                'reversal_entry_id' => $reversalResult ? $reversalResult->id : 'none',
                'sale_id' => $sale->id
            ]);

            // STEP 2: Create new entry
            $newEntry = $this->recordNewSaleEntry($sale);

            if (!$newEntry) {
                Log::error('🚨 CRITICAL: recordNewSaleEntry returned NULL', [
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'invoice_no' => $sale->invoice_no,
                    'final_total' => $sale->final_total
                ]);
                throw new \Exception("Failed to create new ledger entry for sale #{$sale->id}");
            }

            Log::info('✅ UpdateSale: New entry created successfully', [
                'new_entry_id' => $newEntry->id,
                'sale_id' => $sale->id,
                'debit_amount' => $newEntry->debit
            ]);

            return $newEntry;
        } catch (\Exception $e) {
            Log::error('🚨 CRITICAL ERROR in updateSale', [
                'sale_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to ensure caller knows about the failure
        }
    }

    /**
     * Update purchase transaction - properly handles ledger cleanup and recreation
     */
    public function updatePurchase($purchase, $oldReferenceNo = null)
    {
        return DB::transaction(function () use ($purchase, $oldReferenceNo) {
            $referenceNo = $oldReferenceNo ?: ($purchase->reference_no ?: ('PUR-' . $purchase->id));

            // ✅ ENHANCED: Handle supplier changes and payment reassignments in UnifiedLedgerService
            // First check if there are any existing ledger entries for this purchase
            $existingEntries = Ledger::where('reference_no', $referenceNo)
                ->whereIn('transaction_type', ['purchase', 'purchase_payment'])
                ->where('status', 'active')
                ->get();

            Log::info('Updating purchase ledger entries', [
                'purchase_id' => $purchase->id,
                'reference_no' => $referenceNo,
                'current_supplier_id' => $purchase->supplier_id,
                'existing_entries_count' => $existingEntries->count()
            ]);

            // ✅ Handle supplier changes by reassigning payments first
            $supplierChanged = false;
            if ($existingEntries->count() > 0) {
                $oldSupplierId = $existingEntries->first()->contact_id;
                $supplierChanged = $oldSupplierId != $purchase->supplier_id;

                if ($supplierChanged) {
                    Log::info('Supplier change detected - reassigning payments', [
                        'old_supplier_id' => $oldSupplierId,
                        'new_supplier_id' => $purchase->supplier_id,
                        'purchase_id' => $purchase->id
                    ]);

                    // Reassign all payments to new supplier
                    $payments = Payment::where('reference_id', $purchase->id)
                        ->where('payment_type', 'purchase_payment')
                        ->where('supplier_id', $oldSupplierId)
                        ->get();

                    foreach ($payments as $payment) {
                        $payment->update(['supplier_id' => $purchase->supplier_id]);
                        Log::info('Payment reassigned to new supplier', [
                            'payment_id' => $payment->id,
                            'amount' => $payment->amount,
                            'old_supplier_id' => $oldSupplierId,
                            'new_supplier_id' => $purchase->supplier_id
                        ]);
                    }
                }
            }

            // ✅ FIXED: Use proper reversal accounting for ALL existing entries
            foreach ($existingEntries as $entry) {
                Log::info('Processing existing ledger entry', [
                    'entry_id' => $entry->id,
                    'entry_type' => $entry->transaction_type,
                    'old_supplier_id' => $entry->contact_id,
                    'new_supplier_id' => $purchase->supplier_id,
                    'supplier_changed' => $supplierChanged,
                    'amount' => $entry->credit ?: $entry->debit
                ]);

                // Mark original entry as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $this->appendNote(
                        $entry->notes,
                        $this->reversedTag(
                            'Purchase updated' . ($supplierChanged ? ' - Supplier changed' : ' - Amount/details updated')
                        )
                    )
                ]);

                // Create reversal entry to maintain audit trail
                $reversalType = null;
                $reversalAmount = 0;

                if ($entry->transaction_type === 'purchase') {
                    // Original purchase is CREDIT, so reversal must be DEBIT
                    $reversalType = 'purchase_adjustment';
                    $reversalAmount = $entry->credit;
                } elseif ($entry->transaction_type === 'purchase_payment') {
                    // Original purchase payment is DEBIT, so reversal must be CREDIT
                    $reversalType = 'payment_adjustment';
                    $reversalAmount = -$entry->debit;
                }

                if ($reversalType && $reversalAmount != 0) {
                    $this->postLedgerEntry(
                        (int) $entry->contact_id, // Keep original supplier for reversal
                        'supplier',
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'REV-' . $entry->id),
                        (string) $reversalType,
                        $reversalAmount,
                        'REVERSAL: Purchase Update - ' .
                        ($supplierChanged ? 'Supplier Change' : 'Amount Update') .
                        ' - Cancel amount Rs.' . number_format(abs($reversalAmount), 2),
                        null,
                        'reversed'
                    );
                }
            }

            // Step 2: Record the updated purchase with current supplier
            return $this->recordPurchase($purchase);
        });
    }

    /**
     * Update payment - properly handles ledger cleanup and recreation
     */
    public function updatePayment($payment, $oldPayment = null)
    {
        // ACCOUNTING BEST PRACTICE: Don't DELETE old entries, create REVERSAL entries
        // This maintains complete audit trail and prevents data loss

        if ($oldPayment) {
            $oldReferenceNo = $this->resolvePaymentLedgerReference($oldPayment);
            $contactType = $oldPayment->customer_id ? 'customer' : 'supplier';
            $userId = $oldPayment->customer_id ?: $oldPayment->supplier_id;

            // Instead of deleting, create REVERSAL entries
            $oldLedgerEntries = Ledger::where('reference_no', $oldReferenceNo)
                ->whereIn('transaction_type', $this->getPaymentLedgerTransactionTypes())
                ->where('contact_id', $userId)
                ->where('contact_type', $contactType)
                ->where('status', 'active') // ✅ Only reverse ACTIVE entries
                ->get();

            foreach ($oldLedgerEntries as $oldEntry) {
                // ✅ CRITICAL FIX: Mark original entry as reversed
                $this->markEntryReversed($oldEntry, 'Payment edited');

                // Create reversal entry - swap debit and credit amounts to reverse the effect
                $reversalEntry = new Ledger();
                $reversalEntry->transaction_date = now();
                $reversalEntry->reference_no = $oldReferenceNo . '-REV';
                $reversalEntry->transaction_type = $oldEntry->transaction_type;
                $reversalEntry->debit = $oldEntry->credit;  // Swap: original credit becomes debit
                $reversalEntry->credit = $oldEntry->debit;  // Swap: original debit becomes credit
                $reversalEntry->status = 'reversed'; // ✅ Reversal entries should be marked as reversed
                $reversalEntry->notes = 'REVERSAL: Payment Edit - Original amount Rs' . ($oldEntry->credit ?: $oldEntry->debit) . ' (ID: ' . $oldEntry->id . ')';
                // Note: Balance column removed from ledgers table - calculated dynamically
                $reversalEntry->contact_type = $contactType;
                $reversalEntry->contact_id = $userId;
                $reversalEntry->save();

                Log::info("Ledger reversal entry created for payment edit", [
                    'original_entry_id' => $oldEntry->id,
                    'original_reference' => $oldReferenceNo,
                    'reversal_reference' => $oldReferenceNo . '-REV',
                    'original_debit' => $oldEntry->debit,
                    'original_credit' => $oldEntry->credit,
                    'reversal_debit' => $reversalEntry->debit,
                    'reversal_credit' => $reversalEntry->credit,
                    'original_status_updated' => 'reversed',
                    'note' => 'Balance calculated dynamically - not stored in DB',
                    'contact_type' => $contactType,
                    'contact_id' => $userId
                ]);
            }

            // CRITICAL: Balances will be automatically recalculated by the ledger system
            // This ensures running balance is correct for both customer and supplier

            Log::info("Ledger balances will be recalculated automatically after reversal", [
                'contact_type' => $contactType,
                'contact_id' => $userId,
                'note' => 'Final balance calculated dynamically using sum of debit-credit'
            ]);
        }

        // Now record the new/updated payment
        // This will automatically trigger recalculateAllBalances again in createEntry
        if ($payment->customer_id) {
            return $this->recordSalePayment($payment);
        } elseif ($payment->supplier_id) {
            return $this->recordPurchasePayment($payment);
        }

        throw new \Exception('Payment must have either customer_id or supplier_id');
    }

    /**
     * Update sale return - properly handles ledger cleanup and recreation
     */
    public function updateSaleReturn($saleReturn, $oldReferenceNo = null)
    {
        return DB::transaction(function () use ($saleReturn, $oldReferenceNo) {
            // Must match reference used in recordSaleReturn (invoice_number, e.g. SR-0156)
            $referenceNo = $oldReferenceNo
                ?: ($saleReturn->invoice_number ?: ('SR-' . $saleReturn->id));

            $contactId = $this->resolveSaleReturnCustomerId($saleReturn);

            // ✅ FIXED: Use proper reversal accounting instead of hard delete
            // Step 1: Mark original return entry as reversed
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->whereIn('transaction_type', self::SALE_RETURN_LEDGER_TYPES)
                ->where('status', 'active')
                ->when($contactId > 0, fn ($q) => $q->where('contact_id', $contactId))
                ->first();

            if ($originalEntry) {
                $this->markEntryReversed($originalEntry, 'Return updated');

                // Step 2: Create REVERSAL entry to cancel old return
                $this->postLedgerEntry(
                    (int) $originalEntry->contact_id,
                    'customer',
                    $this->nowColombo(),
                    $this->withTimedSuffix($referenceNo, 'REV'),
                    $originalEntry->transaction_type,
                    -$originalEntry->credit, // Reverse the credit
                    'REVERSAL: Sale Return Update - Cancel previous amount Rs.' . number_format($originalEntry->credit, 2),
                    null,
                    'reversed'
                );
            }

            // Step 3: Record the updated return
            return $this->recordSaleReturn($saleReturn);
        });
    }

    /**
     * Update purchase return - properly handles ledger cleanup and recreation
     */
    public function updatePurchaseReturn($purchaseReturn, $oldReferenceNo = null)
    {
        return DB::transaction(function () use ($purchaseReturn, $oldReferenceNo) {
            $referenceNo = $oldReferenceNo ?: $purchaseReturn->reference_no;

            // Step 1: Find the original purchase return entry
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('transaction_type', 'purchase_return')
                ->where('contact_id', $purchaseReturn->supplier_id)
                ->where('status', 'active')
                ->first();

            if ($originalEntry) {
                // Step 2: Mark original entry as reversed
                $this->markEntryReversed($originalEntry, 'Return updated');

                // Step 3: Create REVERSAL entry to cancel the old return
                // The Ledger model will automatically handle the debit/credit logic for purchase_return_reversal
                // Use the original entry's debit amount (since purchase_return creates debit entries)
                $originalAmount = $originalEntry->debit ?: $originalEntry->amount;

                $this->postLedgerEntry(
                    (int) $purchaseReturn->supplier_id,
                    'supplier',
                    $this->nowColombo(),
                    $this->withTimedSuffix($referenceNo, 'REV'),
                    'purchase_return_reversal',
                    $originalAmount,
                    'REVERSAL: Purchase Return Update - Reversing previous return of Rs.' . number_format($originalAmount, 2),
                    null,
                    'reversed'
                );
            }

            // Step 4: Record the updated purchase return as new entry
            return $this->recordPurchaseReturn($purchaseReturn);
        });
    }

    /**
     * Delete transaction ledger entries - for when transactions are completely removed
     */
    public function deleteSaleLedger($sale)
    {
        return DB::transaction(function () use ($sale) {
            $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;

            // ✅ FIXED: Proper reversal accounting for deletion
            // Step 1: Find and mark original entries as reversed
            $originalEntries = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $sale->customer_id)
                ->whereIn('transaction_type', ['sale', 'payments'])
                ->where('status', 'active')
                ->get();

            $affectedRows = 0;
            $reversalNote = $this->reversedTag('Sale deleted');

            foreach ($originalEntries as $entry) {
                // Step 1: Mark original as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $this->appendNote($entry->notes, $reversalNote)
                ]);

                // Step 2: Create REVERSAL entry for complete audit trail
                if ($entry->transaction_type === 'sale') {
                    // Sale was DEBIT, so create CREDIT to reverse it
                    $this->postLedgerEntry(
                        (int) $sale->customer_id,
                        'customer',
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'DEL-REV'),
                        'sale_adjustment',
                        -$entry->debit, // Negative creates CREDIT to reverse DEBIT
                        'REVERSAL: Sale Deletion - Cancel amount Rs.' . number_format($entry->debit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']',
                        null,
                        'reversed'
                    );
                } else {
                    // Payment was CREDIT, so create DEBIT to reverse it
                    $this->postLedgerEntry(
                        (int) $sale->customer_id,
                        'customer',
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'DEL-PAY-REV'),
                        'payment_adjustment',
                        $entry->credit, // Positive creates DEBIT to reverse CREDIT
                        'REVERSAL: Sale Payment Deletion - Cancel amount Rs.' . number_format($entry->credit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']',
                        null,
                        'reversed'
                    );
                }

                $affectedRows++;
            }

            Log::info("Complete reversal accounting completed for sale deletion", [
                'sale_id' => $sale->id,
                'reference_no' => $referenceNo,
                'customer_id' => $sale->customer_id,
                'affected_rows' => $affectedRows,
                'reversal_entries_created' => $affectedRows
            ]);

            return $affectedRows;
        });
    }

    /**
     * Delete purchase ledger entries - for when transactions are completely removed
     */
    public function deletePurchaseLedger($purchase)
    {
        return DB::transaction(function () use ($purchase) {
            // ✅ CRITICAL: Use purchase->reference_no which has the correct format (PUR001, PUR002, etc)
            // Not 'PUR-' . $purchase->id which would be PUR-1, PUR-2 (wrong format)
            $referenceNo = $purchase->reference_no ?: ('PUR-' . $purchase->id);

            // ✅ FIXED: Proper reversal accounting for deletion
            // Step 1: Find and mark original entries as reversed
            // CRITICAL: Use 'purchase_payment' not 'payments' - recordPurchasePayment() uses 'purchase_payment' type
            $originalEntries = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $purchase->supplier_id)
                ->whereIn('transaction_type', ['purchase', 'purchase_payment'])
                ->where('status', 'active')
                ->get();

            $affectedRows = 0;
            $reversalNote = $this->reversedTag('Purchase deleted');

            foreach ($originalEntries as $entry) {
                // Step 1: Mark original as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $this->appendNote($entry->notes, $reversalNote)
                ]);

                // Step 2: Create REVERSAL entry for complete audit trail
                if ($entry->transaction_type === 'purchase') {
                    // Purchase was CREDIT, so create DEBIT to reverse it
                    $this->postLedgerEntry(
                        (int) $purchase->supplier_id,
                        'supplier',
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'DEL-REV'),
                        'purchase_adjustment',
                        $entry->credit, // Positive creates DEBIT to reverse CREDIT
                        'REVERSAL: Purchase Deletion - Cancel amount Rs.' . number_format($entry->credit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']',
                        null,
                        'reversed'
                    );
                } else {
                    // Payment was DEBIT, so create CREDIT to reverse it
                    $this->postLedgerEntry(
                        (int) $purchase->supplier_id,
                        'supplier',
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'DEL-PAY-REV'),
                        'payment_adjustment',
                        -$entry->debit, // Negative creates CREDIT to reverse DEBIT
                        'REVERSAL: Purchase Payment Deletion - Cancel amount Rs.' . number_format($entry->debit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']',
                        null,
                        'reversed'
                    );
                }

                $affectedRows++;
            }

            Log::info("Complete reversal accounting completed for purchase deletion", [
                'purchase_id' => $purchase->id,
                'reference_no' => $referenceNo,
                'supplier_id' => $purchase->supplier_id,
                'affected_rows' => $affectedRows,
                'reversal_entries_created' => $affectedRows
            ]);

            return $affectedRows;
        });
    }

    /**
     * Delete payment ledger entries - for when payments are removed
     */
    /**
     * Delete payment ledger entries - MARKS entries as deleted instead of removing
     * This maintains complete audit trail for accounting compliance
     * Similar to edit logic: -OLD, -REV patterns, now also -DELETED
     */
    public function deletePaymentLedger($payment)
    {
        // CRITICAL: Build the unique reference using payment ID (same as updatePayment)
        // For bulk payments, multiple payments have same base reference
        // We MUST include payment ID to identify the specific payment entries
        $baseReference = $payment->reference_no ?: 'PAY-' . $payment->id;
        $referenceNo = $baseReference . '-PAY' . $payment->id;

        $userId = $payment->customer_id ?: $payment->supplier_id;
        $contactType = $payment->customer_id ? 'customer' : 'supplier';

        Log::info("Marking payment ledger entries as deleted", [
            'payment_id' => $payment->id,
            'base_reference' => $baseReference,
            'unique_reference' => $referenceNo,
            'contact_id' => $userId,
            'contact_type' => $contactType
        ]);

        // ACCOUNTING BEST PRACTICE: Don't delete ledger entries, MARK them as deleted
        // This maintains complete audit trail - all transactions remain visible in logs
        // Similar to how we mark -OLD entries during edits

        // STEP 1: Find ALL related entries to mark as deleted
        $entriesToMark = Ledger::where('contact_id', $userId)
            ->where('contact_type', $contactType)
            ->where('transaction_type', 'payments')
            ->where(function($query) use ($referenceNo) {
                $query->where('reference_no', $referenceNo)              // Original entry
                      ->orWhere('reference_no', $referenceNo . '-REV')   // Reversal entry
                      ->orWhere('reference_no', $referenceNo . '-OLD')   // Old entry (before edit)
                      ->orWhere('reference_no', $referenceNo . '-OLD-REV'); // Old reversal entry
            })
            ->get();

        $markedCount = 0;

        // STEP 2: Mark each entry with -DELETED suffix and update notes
        foreach ($entriesToMark as $entry) {
            // Skip if already marked as deleted
            if (strpos($entry->reference_no, '-DELETED') !== false) {
                continue;
            }

            $oldReference = $entry->reference_no;
            $entry->reference_no = $oldReference . '-DELETED';
            $entry->status = 'reversed'; // Mark as reversed in addition to reference change
            $entry->notes = ($entry->notes ? $entry->notes . ' | ' : '') .
                '[DELETED] Payment deleted on ' . $this->nowColombo()->format('Y-m-d H:i:s') .
                ' - Original ref: ' . $oldReference;
            $entry->save();

            $markedCount++;

            Log::info("Ledger entry marked as deleted", [
                'entry_id' => $entry->id,
                'old_reference' => $oldReference,
                'new_reference' => $entry->reference_no,
                'debit' => $entry->debit,
                'credit' => $entry->credit
            ]);
        }

        // STEP 3: Create REVERSAL entries to cancel out the deleted payment's effect
        // This maintains accurate running balance without actually deleting records
        foreach ($entriesToMark as $entry) {
            // Skip reversal entries themselves
            if (strpos($entry->reference_no, '-REV') !== false &&
                strpos($entry->reference_no, '-DELETED') === false) {
                continue;
            }

            // Skip if this was an -OLD entry (already reversed during edit)
            if (strpos($entry->reference_no, '-OLD') !== false &&
                strpos($entry->reference_no, '-DELETED') === false) {
                continue;
            }

            // Create reversal entry ONLY for the main payment entry
            if (strpos($entry->reference_no, '-DELETED') !== false &&
                strpos($entry->reference_no, '-OLD') === false &&
                strpos($entry->reference_no, '-REV-DELETED') === false) {

                $reversalEntry = new Ledger();
                $reversalEntry->transaction_date = now();
                $reversalEntry->reference_no = $referenceNo . '-DEL-REV';
                $reversalEntry->transaction_type = 'payments';
                // SWAP: Original credit becomes reversal debit, original debit becomes reversal credit
                $reversalEntry->debit = $entry->credit;
                $reversalEntry->credit = $entry->debit;
                $reversalEntry->balance = 0; // Will be recalculated
                $reversalEntry->contact_type = $contactType;
                $reversalEntry->contact_id = $userId;
                $reversalEntry->notes = 'Reversal of deleted payment - Amount: Rs ' .
                    number_format($entry->credit > 0 ? $entry->credit : $entry->debit, 2) .
                    ' | Original ref: ' . str_replace('-DELETED', '', $entry->reference_no);
                $reversalEntry->save();

                Log::info("Delete reversal entry created", [
                    'reversal_id' => $reversalEntry->id,
                    'reversal_reference' => $reversalEntry->reference_no,
                    'original_debit' => $entry->debit,
                    'original_credit' => $entry->credit,
                    'reversal_debit' => $reversalEntry->debit,
                    'reversal_credit' => $reversalEntry->credit
                ]);
            }
        }

        Log::info("Payment ledger entries marked as deleted", [
            'marked_count' => $markedCount,
            'entries_found' => $entriesToMark->count(),
            'reference_patterns_checked' => [
                'original' => $referenceNo,
                'reversal' => $referenceNo . '-REV',
                'old' => $referenceNo . '-OLD',
                'old_reversal' => $referenceNo . '-OLD-REV'
            ]
        ]);

        // STEP 4: Balances will be automatically recalculated by the ledger system

        Log::info("Balances will be recalculated automatically after payment deletion", [
            'contact_id' => $userId,
            'contact_type' => $contactType,
            'final_balance' => $contactType === 'customer'
                ? BalanceHelper::getCustomerBalance($userId)
                : BalanceHelper::getSupplierBalance($userId)
        ]);

        return $markedCount;
    }

    /**
     * Delete return ledger entries - for when returns are removed
     */
    public function deleteReturnLedger($return, $type = 'sale_return')
    {
        return DB::transaction(function () use ($return, $type) {
            $referenceNo = $type === 'sale_return'
                ? ($return->invoice_number ?? 'SR-' . $return->id)
                : 'PR-' . $return->id;
            $userId = $type === 'sale_return' ? $return->customer_id : $return->supplier_id;
            $contactType = $type === 'sale_return' ? 'customer' : 'supplier';

            // ✅ FIXED: Proper reversal accounting for deletion
            // Step 1: Find and mark original entries as reversed
            $originalEntries = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $userId)
                ->when(
                    $type === 'sale_return',
                    fn ($q) => $q->whereIn('transaction_type', self::SALE_RETURN_LEDGER_TYPES),
                    fn ($q) => $q->where('transaction_type', $type)
                )
                ->where('status', 'active')
                ->get();

            $affectedRows = 0;
            $reversalNote = $this->reversedTag('Return deleted');

            foreach ($originalEntries as $entry) {
                // Step 1: Mark original as reversed
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $this->appendNote($entry->notes, $reversalNote)
                ]);

                // Step 2: Create REVERSAL entry for complete audit trail
                if ($type === 'sale_return') {
                    // Sale return was CREDIT, so create DEBIT to reverse it
                    $this->postLedgerEntry(
                        (int) $userId,
                        (string) $contactType,
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'DEL-REV'),
                        $entry->transaction_type,
                        $entry->credit, // Positive creates DEBIT to reverse CREDIT
                        'REVERSAL: Sale Return Deletion - Cancel amount Rs.' . number_format($entry->credit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']',
                        null,
                        'reversed'
                    );
                } else {
                    // Purchase return was DEBIT, so create CREDIT to reverse it
                    $this->postLedgerEntry(
                        (int) $userId,
                        (string) $contactType,
                        $this->nowColombo(),
                        $this->withTimedSuffix($referenceNo, 'DEL-REV'),
                        'purchase_return',
                        -$entry->debit, // Negative creates CREDIT to reverse DEBIT
                        'REVERSAL: Purchase Return Deletion - Cancel amount Rs.' . number_format($entry->debit, 2) . ' [Cancels Entry ID: ' . $entry->id . ']',
                        null,
                        'reversed'
                    );
                }

                $affectedRows++;
            }

            Log::info("Complete reversal accounting completed for return deletion", [
                'return_id' => $return->id,
                'reference_no' => $referenceNo,
                'contact_id' => $userId,
                'contact_type' => $contactType,
                'return_type' => $type,
                'affected_rows' => $affectedRows,
                'reversal_entries_created' => $affectedRows
            ]);

            return $affectedRows;
        });
    }

    /**
     * Get supplier summary
     *
     * @param int $supplierId
     * @return array
     */
    public function getSupplierSummary(int $supplierId): array
    {
        // Use the existing getSupplierLedger method to get all entries
        $ledgerData = $this->getSupplierLedger($supplierId, null, null);
        return $this->balanceQueries()->summarizeSupplierLedgerData($supplierId, $ledgerData);
    }

    /**
     * Recalculate all balances for a supplier from scratch
     *
     * @param int $supplierId
     * @return void
     */
    public function recalculateSupplierBalance(int $supplierId): void
    {
        $this->maintenance()->recalculateSupplierBalance($supplierId);
    }

    /**
     * Validate ledger consistency for a supplier
     *
     * @param int $supplierId
     * @return array
     */
    public function validateSupplierLedger(int $supplierId): array
    {
        return $this->maintenance()->validateSupplierLedger($supplierId);
    }

    /**
     * Delete ledger entries for a specific reference and contact
     *
     * @param string $referenceNo
     * @param int $contactId
     * @param string $contactType
     * @return void
     */
    public function deleteLedgerEntries(string $referenceNo, int $contactId, string $contactType): void
    {
        $this->maintenance()->deleteLedgerEntries($referenceNo, $contactId, $contactType);
    }

    /**
     * Get current balance for a customer or supplier
     *
     * @param string $contactType ('customer' or 'supplier')
     * @param int $userId
     * @return float
     */

    /**
     * Get detailed transaction type with audit trail information
     */
    private function getDetailedTransactionType($ledger)
    {
        $refNo = $ledger->reference_no;
        $type = $ledger->transaction_type;

        // Check for audit trail markers
        if (strpos($refNo, '-REV') !== false) {
            return 'Reversal Entry';
        } elseif (strpos($refNo, '-OLD-REV') !== false) {
            return 'Old Entry Reversal';
        } elseif (strpos($refNo, '-OLD') !== false) {
            return 'Original Entry (Before Edit)';
        } elseif (strpos($refNo, '-DELETED') !== false) {
            return 'Deleted Entry';
        } elseif (strpos($refNo, '-DEL-REV') !== false) {
            return 'Deletion Reversal';
        } elseif (strpos($ledger->notes ?: '', 'REVERSAL:') === 0) {
            return 'System Reversal';
        } else {
            return Ledger::formatTransactionType($type) . ' (Current)';
        }
    }

    /**
     * Sort audit-trail entries into a human-readable edit sequence.
     */
    private function sortLedgerTransactionsForAuditTrail($transactions)
    {
        return $transactions->sortBy(function ($ledger) {
            $groupKey = $this->getAuditTrailGroupKey($ledger);
            $priority = $this->getAuditTrailSortPriority($ledger);
            $timestamp = $ledger->created_at
                ? Carbon::parse($ledger->created_at)->format('YmdHis')
                : '00000000000000';
            $id = str_pad((string) ($ledger->id ?? 0), 10, '0', STR_PAD_LEFT);

            return $groupKey . '|' . str_pad((string) $priority, 2, '0', STR_PAD_LEFT) . '|' . $timestamp . '|' . $id;
        })->values();
    }

    /**
     * Group all audit rows that belong to the same invoice/payment edit chain.
     */
    private function getAuditTrailGroupKey($ledger): string
    {
        $referenceNo = (string) ($ledger->reference_no ?? '');
        $groupKey = preg_replace('/-REV.*$/', '', $referenceNo) ?? $referenceNo;
        $groupKey = str_replace(['-OLD', '-DELETED'], '', $groupKey);

        return $groupKey;
    }

    /**
     * Prioritize audit rows so each edit chain reads in the expected business order.
     */
    private function getAuditTrailSortPriority($ledger): int
    {
        $referenceNo = $ledger->reference_no ?? '';
        $transactionType = $ledger->transaction_type ?? '';
        $notes = $ledger->notes ?? '';
        $status = $ledger->status ?? null;

        // Original rows (before the edit chain) first.
        if ($status === 'reversed' && !str_contains($referenceNo, '-REV')) {
            return 10;
        }

        // Sale reversal row.
        if (str_contains($referenceNo, '-REV') && str_starts_with($notes, 'REVERSAL: Sale Edit')) {
            return 20;
        }

        // Payment reversal row.
        if (str_contains($referenceNo, '-REV') && str_starts_with($notes, 'REVERSAL: Payment Edit')) {
            return 30;
        }

        // New sale created after the edit.
        if ($status === 'active' && $transactionType === 'sale') {
            return 40;
        }

        // New payment created after the edit.
        if ($status === 'active' && in_array($transactionType, ['payments', 'sale_payment', 'purchase_payment', 'discount_given'], true)) {
            return 50;
        }

        return 60;
    }

    /**
     * Get enhanced transaction description for audit trail
     */
    private function getEnhancedTransactionDescription($ledger)
    {
        $notes = $ledger->notes ?: '';
        $refNo = $ledger->reference_no;

        // Handle reversal entries
        if (strpos($notes, 'REVERSAL:') === 0) {
            return $notes; // Already formatted reversal message
        }

        // Handle audit trail entries
        if (strpos($refNo, '-REV') !== false) {
            $originalRef = str_replace(['-REV', '-OLD-REV', '-DEL-REV'], '', $refNo);
            return "Reversal entry for payment #{$originalRef}. " . $notes;
        } elseif (strpos($refNo, '-OLD') !== false) {
            $originalRef = str_replace('-OLD', '', $refNo);
            return "Original entry before edit for payment #{$originalRef}. " . $notes;
        } elseif (strpos($refNo, '-DELETED') !== false) {
            $originalRef = str_replace('-DELETED', '', $refNo);
            return "Entry marked as deleted for payment #{$originalRef}. " . $notes;
        } else {
            // Current/active entry
            return $notes ?: "Current active entry for {$refNo}";
        }
    }

    /**
     * 🔥 PERFECT REVERSAL ACCOUNTING: Payment Edit
     * Creates reversal entry and new entry for payment edits
     */
    public function editPayment($payment, $oldAmount, $newAmount, $editReason = '', $editedBy = null)
    {
        return DB::transaction(function () use ($payment, $oldAmount, $newAmount, $editReason, $editedBy) {
            // Skip if amounts are identical
            if ($oldAmount == $newAmount) {
                return null;
            }

            $referenceNo = $this->resolvePaymentLedgerReference($payment);
            $contactType = $this->resolvePaymentContactType($payment);
            $contactId = $this->resolvePaymentContactId($payment, $contactType);

            Log::info("🔧 editPayment() STARTED", [
                'payment_id' => $payment->id,
                'reference_no' => $referenceNo,
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'edit_reason' => $editReason
            ]);

            // Step 1: Mark original payment entry as REVERSED
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $contactId)
                ->where('contact_type', $contactType)
                ->whereIn('transaction_type', $this->getPaymentLedgerTransactionTypes())
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            Log::info("🔍 editPayment() searching for original entry", [
                'reference_no' => $referenceNo,
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'payment_types' => $this->getPaymentLedgerTransactionTypes(),
                'original_entry_found' => $originalEntry ? 'YES (ID: ' . $originalEntry->id . ')' : 'NO'
            ]);

            if ($originalEntry) {
                $this->markEntryReversed($originalEntry, 'Payment edited');

                Log::info("✅ editPayment() marked original entry as REVERSED", [
                    'entry_id' => $originalEntry->id,
                    'old_status' => 'active',
                    'new_status' => 'reversed'
                ]);
            } else {
                Log::warning("❌ editPayment() FAILED: Original entry NOT FOUND", [
                    'reference_no' => $referenceNo,
                    'contact_id' => $contactId,
                    'contact_type' => $contactType,
                    'payment_id' => $payment->id
                ]);
            }

            // Step 2: Create REVERSAL entry to cancel old payment
            $reversalEntry = $this->postLedgerEntry(
                (int) $contactId,
                (string) $contactType,
                $this->nowColombo(),
                $this->withTimedSuffix($referenceNo, 'REV'),
                'payment_adjustment',
                $oldAmount, // Positive amount creates DEBIT to reverse old CREDIT
                'REVERSAL: Payment Edit - Cancel previous amount Rs.' . number_format($oldAmount, 2) . ($editReason ? ' | Reason: ' . $editReason : ''),
                $editedBy,
                'reversed'
            );

            Log::info("📝 editPayment() created reversal entry", [
                'reversal_entry_id' => $reversalEntry->id,
                'reference_no' => $reversalEntry->reference_no,
                'amount' => $reversalEntry->amount
            ]);

            // Step 3: Create NEW payment entry with correct amount
            $newPaymentEntry = $this->postLedgerEntry(
                (int) $contactId,
                (string) $contactType,
                $this->nowColombo(),
                $referenceNo,
                $this->resolvePaymentLedgerTransactionType($payment, $originalEntry->transaction_type ?? null),
                $newAmount,
                'Payment Edit - New Amount Rs.' . number_format($newAmount, 2) .
                ($editReason ? ' | Reason: ' . $editReason : ''),
                $editedBy
            );

            Log::info("📝 editPayment() created new payment entry", [
                'new_entry_id' => $newPaymentEntry->id,
                'reference_no' => $newPaymentEntry->reference_no,
                'amount' => $newPaymentEntry->amount
            ]);

            Log::info("✅ Perfect reversal accounting completed for payment edit", [
                'payment_id' => $payment->id,
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'reference_no' => $referenceNo,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'original_entry_id' => $originalEntry ? $originalEntry->id : null,
                'reversal_entry_id' => $reversalEntry->id,
                'new_entry_id' => $newPaymentEntry->id
            ]);

            return [
                'reversal_entry' => $reversalEntry,
                'new_entry' => $newPaymentEntry,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'method' => 'perfect_reversal_accounting'
            ];
        });
    }

    /**
     * 🔥 PERFECT REVERSAL ACCOUNTING: Payment Delete
     * Creates reversal entry for payment deletions
     */
    public function deletePayment($payment, $deleteReason = '', $deletedBy = null)
    {
        return DB::transaction(function () use ($payment, $deleteReason, $deletedBy) {
            $referenceNo = $this->resolvePaymentLedgerReference($payment);
            $contactType = $this->resolvePaymentContactType($payment);
            $contactId = $this->resolvePaymentContactId($payment, $contactType);

            // Check if this is a return payment
            $isReturnPayment = $payment->payment_type === 'purchase_return' ||
                               $payment->payment_type === 'sale_return_with_bill' ||
                               $payment->payment_type === 'sale_return_without_bill' ||
                               (isset($payment->notes) && strpos(strtolower($payment->notes), 'return') !== false);

            // Step 1: Mark original payment entry as REVERSED
            $originalEntry = Ledger::where('reference_no', $referenceNo)
                ->where('contact_id', $contactId)
                ->where('contact_type', $contactType)
                ->whereIn('transaction_type', $this->getPaymentLedgerTransactionTypes())
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($originalEntry) {
                $this->markEntryReversed($originalEntry, 'Payment deleted');

                // Step 2: Create REVERSAL entry to cancel the deleted payment
                // Payment ledger entries are CREDIT (reduce customer debt), so reversal should be DEBIT
                $reversalNotes = 'REVERSAL: Payment Deleted - Cancel amount Rs.' . number_format($payment->amount, 2);
                if ($isReturnPayment) {
                    $reversalNotes .= ' (Return payment reversal)';
                }
                if ($deleteReason) {
                    $reversalNotes .= ' | Reason: ' . $deleteReason;
                }

                // ✅ CRITICAL FIX: Pass NEGATIVE amount to create DEBIT reversal entry
                // For customer payments: positive amount = CREDIT, negative amount = DEBIT
                // We want DEBIT to reverse the CREDIT payment
                $reversalEntry = $this->postLedgerEntry(
                    (int) $contactId,
                    (string) $contactType,
                    $this->nowColombo(),
                    $this->withTimedSuffix($referenceNo, 'DEL'),
                    (string) $originalEntry->transaction_type,
                    -$payment->amount, // ✅ NEGATIVE creates DEBIT for reversal
                    (string) $reversalNotes,
                    $deletedBy,
                    'reversed'
                );

                Log::info("Perfect reversal accounting completed for payment deletion", [
                    'payment_id' => $payment->id,
                    'contact_id' => $contactId,
                    'contact_type' => $contactType,
                    'reference_no' => $referenceNo,
                    'amount' => $payment->amount,
                    'original_entry_id' => $originalEntry->id,
                    'reversal_entry_id' => $reversalEntry->id
                ]);

                return [
                    'reversal_entry' => $reversalEntry,
                    'deleted_amount' => $payment->amount,
                    'method' => 'perfect_reversal_accounting'
                ];
            }

            Log::warning("No active ledger entry found for payment deletion", [
                'payment_id' => $payment->id,
                'reference_no' => $referenceNo,
                'contact_id' => $contactId,
                'contact_type' => $contactType
            ]);

            return null;
        });
    }

    /**
     * Reverse a payment entry by marking related ledger entries as reversed
     */
    private function reversePaymentEntry($payment, $reason, $reversedBy = null)
    {
        // Find the ledger entry for this payment using reference_no pattern
        $referenceNo = 'PAY-' . $payment->id;
        $ledgerEntry = Ledger::where('reference_no', $referenceNo)
            ->where('contact_id', $payment->customer_id ?: $payment->supplier_id)
            ->where('status', 'active')
            ->first();

        if ($ledgerEntry) {
            return Ledger::reverseEntry($ledgerEntry->id, $reason, $reversedBy);
        }

        return null;
    }

    /**
     * Handle sale edit - reverse old entries and create new ones
     */
    public function editSaleTransaction($sale, $oldFinalTotal, $newFinalTotal, $editReason = '', $editedBy = null)
    {
        // Reverse the original sale entry using reference_no pattern
        $referenceNo = 'SALE-' . $sale->id;
        $ledgerEntry = Ledger::where('reference_no', $referenceNo)
            ->where('transaction_type', 'sale')
            ->where('contact_id', $sale->customer_id)
            ->where('status', 'active')
            ->first();

        if ($ledgerEntry) {
            Ledger::reverseEntry($ledgerEntry->id, "Sale edited: " . $editReason, $editedBy);
        }

        // Create new sale entry
        return $this->recordSale($sale, $editedBy);
    }

    /**
     * Get balances for multiple customers/suppliers - DELEGATES to BalanceHelper
     */
    public function getBulkBalances($contactIds, $contactType)
    {
        return $this->balanceQueries()->getBulkBalances($contactIds, $contactType);
    }

    /**
     * Get balance summary by contact type - DELEGATES to BalanceHelper
     */
    public function getBalanceSummary($contactType = null)
    {
        return $this->balanceQueries()->getBalanceSummary($contactType);
    }

    /**
     * Get customer statement with running balances - DELEGATES to Ledger
     */
    public function getCustomerStatementWithRunningBalance($customerId, $fromDate = null, $toDate = null)
    {
        return $this->balanceQueries()->getCustomerStatementWithRunningBalance($customerId, $fromDate, $toDate);
    }

    /**
     * Get supplier statement with running balances - DELEGATES to Ledger
     */
    public function getSupplierStatementWithRunningBalance($supplierId, $fromDate = null, $toDate = null)
    {
        return $this->balanceQueries()->getSupplierStatementWithRunningBalance($supplierId, $fromDate, $toDate);
    }

    /**
     * Get all customers with their current balances (bulk operation)
     */
    public function getAllCustomersWithBalances()
    {
        return $this->balanceQueries()->getAllCustomersWithBalances();
    }

    /**
     * Get all suppliers with their current balances - USES BalanceHelper
     */
    public function getAllSuppliersWithBalances()
    {
        return $this->balanceQueries()->getAllSuppliersWithBalances();
    }

    /**
     * ===================================================================
     * 🎯 CENTRALIZED OPENING BALANCE HANDLER
     * ===================================================================
     *
     * Handles all opening balance operations for customers/suppliers
     * This centralizes logic that was scattered in Customer model
     */
    public function handleCustomerOpeningBalance($customerId, $newOpeningBalance)
    {
        return DB::transaction(function () use ($customerId, $newOpeningBalance) {

            // Find existing opening balance entry (if any)
            $existingEntry = Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'opening_balance')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingEntry) {
                // Customer already has opening balance - need to adjust
                $oldAmount = $existingEntry->amount ?? ($existingEntry->debit - $existingEntry->credit);

                if ($oldAmount != $newOpeningBalance) {
                    // Use proper reversal accounting for adjustment
                    return $this->recordOpeningBalanceAdjustment(
                        $customerId,
                        'customer',
                        $oldAmount,
                        $newOpeningBalance,
                        'Opening Balance Update via Customer Model'
                    );
                }
            } else {
                // No existing entry - create new opening balance
                if ($newOpeningBalance != 0) {
                    return $this->recordOpeningBalance(
                        $customerId,
                        'customer',
                        $newOpeningBalance,
                        'Opening Balance for Customer ID: ' . $customerId
                    );
                }
            }

            return null; // No changes needed
        });
    }

    /**
     * Update purchase payment in ledger when payment amount changes
     */
    public function updatePurchasePayment($payment, $purchase)
    {
        return DB::transaction(function () use ($payment, $purchase) {
            $referenceNo = $this->resolvePaymentLedgerReference($payment);

            Log::info('Updating purchase payment in ledger', [
                'payment_id' => $payment->id,
                'purchase_id' => $purchase->id,
                'reference_no' => $referenceNo,
                'amount' => $payment->amount,
                'supplier_id' => $purchase->supplier_id
            ]);

            // Mark existing payment ledger entries as reversed
            $existingEntries = Ledger::where('reference_no', $referenceNo)
                ->whereIn('transaction_type', ['purchase_payment', 'payments'])
                ->where('contact_id', $purchase->supplier_id)
                ->where('status', 'active')
                ->get();

            foreach ($existingEntries as $entry) {
                $entry->update([
                    'status' => 'reversed',
                    'notes' => $this->appendNote($entry->notes ?: 'Purchase payment', $this->reversedTag('Payment updated'))
                ]);

                // Create reversal entry
                $this->postLedgerEntry(
                    (int) $purchase->supplier_id,
                    'supplier',
                    $this->nowColombo(),
                    $this->withTimedSuffix($referenceNo, 'REV'),
                    (string) $entry->transaction_type,
                    $entry->debit ? -$entry->debit : $entry->credit,
                    'REVERSAL: Payment Update - Cancel amount Rs.' . number_format($entry->debit ?: $entry->credit, 2),
                    null,
                    'reversed'
                );
            }

            // Create new payment entry with updated amount
            return $this->recordPurchasePayment($payment, $purchase);
        });
    }
}
