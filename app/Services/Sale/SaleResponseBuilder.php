<?php

namespace App\Services\Sale;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesProduct;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SaleResponseBuilder
 *
 * Handles everything that happens AFTER the DB transaction closes:
 *  1. Create cheque reminders
 *  2. Decide whether to generate a receipt
 *  3. Load customer / products / payments / user / location
 *  4. Calculate customer outstanding balance
 *  5. Build $viewData
 *  6. Render receipt HTML
 *  7. Dispatch WhatsApp (non-blocking)
 *  8. Return the final JsonResponse
 */
class SaleResponseBuilder
{
    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Build and return the JSON response after a sale is saved.
     *
     * @param  Sale     $sale
     * @param  Request  $request
     * @param  int|null $saleId     The $id parameter passed to storeOrUpdate (null = create)
     * @param  float    $startTime  microtime(true) captured before processing started
     */
    public function build(Sale $sale, Request $request, ?int $saleId, float $startTime): JsonResponse
    {
        // ── Step 1: Cheque reminders (outside transaction) ───────────────────
        if (!empty($request->payments) && $request->customer_id != 1) {
            Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->where('payment_method', 'cheque')
                ->whereNotNull('cheque_valid_date')
                ->get()
                ->each(fn($p) => $p->createReminders());
        }

        // ── Step 2: Decide whether to render a full receipt ──────────────────
        $isWalkIn           = $sale->customer_id == 1;
        $shouldGenerateReceipt = !$request->header('X-Skip-Receipt')
            && $sale->status !== 'jobticket'
            && !($isWalkIn && $request->status === 'final');

        // ── Step 3: Load all data needed for viewData ────────────────────────
        if ($shouldGenerateReceipt) {
            $sale->load(['location', 'user']);

            $customer = $isWalkIn
                ? $this->walkInCustomerObject()
                : Customer::withoutGlobalScopes()->findOrFail($sale->customer_id);

            [$products, $payments] = [
                SalesProduct::with(['product:id,product_name,sku', 'imeis:id,sale_product_id,imei_number'])
                    ->where('sale_id', $sale->id)
                    ->get(),
                Payment::where('reference_id', $sale->id)
                    ->where('payment_type', 'sale')
                    ->select('id', 'amount', 'payment_method', 'payment_date', 'reference_no', 'notes')
                    ->get(),
            ];

            $user     = $sale->user;
            $location = $sale->location;
        } else {
            $customer = null;
            $products = collect();
            $payments = collect();
            $user     = null;
            $location = null;
        }

        // ── Step 4: Customer outstanding balance ────────────────────────────
        $customerOutstandingBalance = 0;
        if ($customer && $customer->id != 1) {
            $customerOutstandingBalance = $customer->calculateBalanceFromLedger();
        }

        // ── Step 5: Build viewData ───────────────────────────────────────────
        $viewData = [
            'sale'                        => $sale,
            'customer'                    => $customer,
            'products'                    => $products,
            'payments'                    => $payments,
            'total_discount'              => $request->discount_amount ?? 0,
            'amount_given'                => $sale->amount_given,
            'balance_amount'              => $sale->balance_amount,
            'customer_outstanding_balance'=> $customerOutstandingBalance,
            'user'                        => $user,
            'location'                    => $location,
            'receiptConfig'               => $location ? $location->getReceiptConfig() : [],
        ];

        // ── Step 6: Render receipt HTML ──────────────────────────────────────
        $html = '';
        if ($shouldGenerateReceipt) {
            $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
            $html        = view($receiptView, $viewData)->render();
        }

        // ── Step 7: WhatsApp (non-blocking, skip Walk-In) ────────────────────
        if ($sale->customer_id != 1) {
            $this->sendWhatsAppAsync($customer, $sale, $viewData);
        }

        // ── Step 8: Build JSON response ──────────────────────────────────────

        // Walk-In final sales get a minimal fast response
        if ($isWalkIn && $request->status === 'final') {
            return response()->json([
                'message'      => $saleId ? 'Sale updated successfully.' : 'Sale recorded successfully.',
                'invoice_html' => '',
                'data'         => [
                    'sale'           => $sale,
                    'customer'       => $this->walkInCustomerObject(),
                    'products'       => collect(),
                    'payments'       => collect(),
                    'total_discount' => $request->discount_amount ?? 0,
                    'amount_given'   => $sale->amount_given,
                    'balance_amount' => $sale->balance_amount,
                ],
                'sale'         => $this->saleSummary($sale),
            ], 200);
        }

        return response()->json([
            'message'      => $this->successMessage($saleId, $sale),
            'invoice_html' => $html,
            'data'         => $viewData,
            'sale'         => $this->saleSummary($sale),
        ], 200);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Compose the success message based on sale type and whether it's a create or update.
     */
    private function successMessage(?int $saleId, Sale $sale): string
    {
        if ($saleId) {
            return 'Sale updated successfully.';
        }

        if ($sale->transaction_type === 'sale_order') {
            return 'Sale Order created successfully!';
        }

        if ($sale->status === 'suspend') {
            return 'Sale suspended successfully.';
        }

        return 'Sale recorded successfully.';
    }

    /**
     * The four fields always returned in the 'sale' key of the response.
     */
    private function saleSummary(Sale $sale): array
    {
        return [
            'id'               => $sale->id,
            'invoice_no'       => $sale->invoice_no,
            'order_number'     => $sale->order_number,
            'transaction_type' => $sale->transaction_type,
            'order_status'     => $sale->order_status,
        ];
    }

    /**
     * Lightweight Walk-In customer stub (no DB query needed).
     */
    private function walkInCustomerObject(): object
    {
        return (object) [
            'id'         => 1,
            'first_name' => 'Walk-In',
            'last_name'  => 'Customer',
            'full_name'  => 'Walk-In Customer',
            'mobile_no'  => '',
            'email'      => '',
        ];
    }

    /**
     * Dispatch a WhatsApp message asynchronously (non-blocking, after response).
     */
    private function sendWhatsAppAsync(object $customer, Sale $sale, array $viewData): void
    {
        dispatch(function () use ($customer, $sale, $viewData) {
            try {
                $mobileNo      = ltrim($customer->mobile_no ?? '', '0');
                $whatsAppApiUrl = env('WHATSAPP_API_URL');

                if (empty($mobileNo) || empty($whatsAppApiUrl)) {
                    Log::info('WhatsApp skipped: API URL not set or mobile number missing.');
                    return;
                }

                // Add outstanding balance to viewData for the WhatsApp receipt
                $customerOutstandingBalance = 0;
                if ($customer && $customer->id != 1) {
                    $customerOutstandingBalance = $customer->calculateBalanceFromLedger();
                }
                $viewData['customer_outstanding_balance'] = $customerOutstandingBalance;

                $location    = $sale->location;
                $receiptView = $location ? $location->getReceiptViewName() : 'sell.receipt';
                $viewData['receiptConfig'] = $location ? $location->getReceiptConfig() : [];

                $receiptHtml = view($receiptView, $viewData)->render();

                // Paper size based on layout type
                $layoutType = $location ? $location->invoice_layout_pos : '80mm';
                $paperSize  = match ($layoutType) {
                    'a4'         => 'A4',
                    'dot_matrix' => [0, 0, 612, 792],
                    default      => [0, 0, 226.77, 842],
                };

                $pdf        = Pdf::loadHTML($receiptHtml)->setPaper($paperSize, 'portrait');
                $pdfContent = $pdf->output();

                $response = Http::timeout(30)
                    ->attach('files', $pdfContent, "invoice_{$sale->invoice_no}_{$layoutType}.pdf")
                    ->post($whatsAppApiUrl, [
                        'number'  => '+94' . $mobileNo,
                        'message' => "Dear {$customer->first_name}, your invoice #{$sale->invoice_no} has been generated successfully. "
                            . "Total amount: Rs {$sale->final_total}. Thank you for your business!",
                    ]);

                if ($response->successful()) {
                    Log::info('WhatsApp message sent successfully to: ' . $mobileNo);
                } else {
                    Log::error('WhatsApp send failed: ' . $response->body());
                }
            } catch (\Exception $ex) {
                Log::error('WhatsApp send error: ' . $ex->getMessage());
            }
        })->afterResponse();
    }
}
