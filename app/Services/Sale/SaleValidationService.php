<?php

namespace App\Services\Sale;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class SaleValidationService
{
    // -------------------------------------------------------------------------
    // 1. REQUEST FIELD VALIDATION
    // -------------------------------------------------------------------------

    /**
     * Validate all incoming sale request fields.
     *
     * Returns a MessageBag (with all field errors) if validation fails.
     * Returns null if everything passes — caller does nothing and continues.
     */
    public function validateRequest(Request $request): ?MessageBag
    {
        // Pre-load all products+units in one query to avoid N+1 inside validation closures
        $productIds   = collect($request->input('products', []))->pluck('product_id')->filter()->unique()->values();
        $productCache = Product::whereIn('id', $productIds)
            ->with('unit:id,allow_decimal')
            ->get()
            ->keyBy('id');

        $validator = Validator::make($request->all(), [
            'customer_id'               => 'required|integer|exists:customers,id',
            'location_id'               => 'required|integer|exists:locations,id',
            'sales_date'                => 'required|date',
            'status'                    => 'required|string',
            'invoice_no'                => 'nullable|string|unique:sales,invoice_no',
            'sale_notes'                => 'nullable|string|max:2000',
            // Sale Order fields
            'transaction_type'          => 'nullable|string|in:invoice,sale_order',
            'expected_delivery_date'    => 'nullable|date|after_or_equal:today',
            'order_notes'               => 'nullable|string|max:1000',
            // Products
            'products'                  => 'required|array',
            'products.*.product_id'     => 'required|integer|exists:products,id',
            'products.*.quantity'       => [
                'required',
                'numeric',
                'min:0.0001',
                function ($attribute, $value, $fail) use ($request, $productCache) {
                    if (preg_match('/products\.(\d+)\.quantity/', $attribute, $matches)) {
                        $index       = $matches[1];
                        $productData = $request->input("products.$index");
                        if ($productData && isset($productData['product_id'])) {
                            $product = $productCache->get($productData['product_id']);
                            if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                $fail("The quantity must be an integer for this unit.");
                            }
                        }
                    }
                },
            ],
            'products.*.free_quantity'  => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $productCache) {
                    if ($value !== null && $value > 0) {
                        if (preg_match('/products\.(\d+)\.free_quantity/', $attribute, $matches)) {
                            $index       = $matches[1];
                            $productData = $request->input("products.$index");
                            if ($productData && isset($productData['product_id'])) {
                                $product = $productCache->get($productData['product_id']);
                                if ($product && $product->unit && !$product->unit->allow_decimal && floor($value) != $value) {
                                    $fail("The free quantity must be an integer for this unit.");
                                }
                            }
                        }
                    }
                },
            ],
            'products.*.unit_price'     => 'required|numeric|min:0',
            'products.*.subtotal'       => 'required|numeric|min:0',
            'products.*.batch_id'       => 'nullable|string|max:255',
            'products.*.price_type'     => 'required|string|in:retail,wholesale,special',
            'products.*.discount'       => 'nullable|numeric|min:0',
            'products.*.tax'            => 'nullable|numeric|min:0',
            'products.*.imei_numbers'   => 'nullable|array',
            'products.*.imei_numbers.*' => 'string|max:255',
            // Payments
            'payments'                  => 'nullable|array',
            'payments.*.payment_method' => 'required_with:payments|string',
            'payments.*.payment_date'   => 'required_with:payments|date',
            'payments.*.amount'         => 'required_with:payments|numeric|min:0',
            'total_paid'                => 'nullable|numeric|min:0',
            'payment_mode'              => 'nullable|string',
            'payment_status'            => 'nullable|string',
            'payment_reference'         => 'nullable|string',
            'payment_date'              => 'nullable|date',
            'total_amount'              => 'nullable|numeric|min:0',
            'discount_type'             => 'required|string|in:fixed,percentage',
            'discount_amount'           => 'nullable|numeric|min:0',
            'amount_given'              => 'nullable|numeric|min:0',
            'balance_amount'            => 'nullable|numeric',
            'advance_amount'            => 'nullable|numeric|min:0',
            'jobticket_description'     => 'nullable|string',
            // Floating balance
            'use_floating_balance'      => 'nullable|boolean',
            'floating_balance_amount'   => 'nullable|numeric|min:0',
            // Shipping
            'shipping_details'          => 'nullable|string|max:2000',
            'shipping_address'          => 'nullable|string|max:1000',
            'shipping_charges'          => 'nullable|numeric|min:0|max:999999.99',
            'shipping_status'           => 'nullable|string|in:pending,ordered,shipped,delivered,cancelled',
            'delivered_to'              => 'nullable|string|max:255',
            'delivery_person'           => 'nullable|string|max:255',
        ]);

        return $validator->fails() ? $validator->messages() : null;
    }

    // -------------------------------------------------------------------------
    // 2. WALK-IN CUSTOMER RULES
    // -------------------------------------------------------------------------

    /**
     * Validate Walk-In Customer (customer_id == 1) restrictions.
     *
     * Returns null  → no problem, caller continues.
     * Returns array → ['message' => '...', 'errors' => [...]] caller should
     *                  return response()->json(array_merge(['status' => 400], $result))
     */
    public function checkWalkInRules(Request $request): ?array
    {
        if ($request->customer_id != 1) {
            return null;
        }

        if (!empty($request->payments)) {
            // Cheque not allowed for Walk-In
            $hasCheque = collect($request->payments)->contains('payment_method', 'cheque');
            if ($hasCheque) {
                return [
                    'message' => 'Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.',
                    'errors'  => ['payment_method' => ['Cheque payment is not allowed for Walk-In Customer.']],
                ];
            }

            // Full payment required for Walk-In (except suspend)
            if ($request->status !== 'suspend') {
                $finalTotal    = $request->final_total ?? $request->total_amount ?? 0;
                $totalPayments = collect($request->payments)->sum('amount');

                if ($totalPayments < $finalTotal) {
                    return [
                        'message' => 'Credit sales are not allowed for Walk-In Customer. Please collect full payment or select a different customer.',
                        'errors'  => ['amount_given' => ['Full payment required for Walk-In Customer.']],
                    ];
                }
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // 3. CREDIT LIMIT VALIDATION
    // -------------------------------------------------------------------------

    /**
     * Validate that the sale does not exceed the customer's credit limit.
     *
     * - Skip for Walk-In (id == 1)
     * - Skip if customer has no credit limit set
     * - Skip for non-final statuses
     * - Skip if paid via cheque
     *
     * Throws \Exception with a detailed message if limit exceeded.
     */
    public function validateCreditLimit($customer, float $finalTotal, array $payments, string $saleStatus): bool
    {
        // Skip for Walk-In customers
        if ($customer->id == 1) {
            return true;
        }

        // Skip if no credit limit configured
        if ($customer->credit_limit <= 0) {
            return true;
        }

        // Only enforce on final sales
        if (!in_array($saleStatus, ['final'])) {
            return true;
        }

        $actualPaymentAmount = 0;
        $hasCreditPayment    = false;
        $hasChequePayment    = false;

        if (!empty($payments)) {
            $actualPaymentAmount = array_sum(array_column($payments, 'amount'));

            foreach ($payments as $payment) {
                $paymentMethod = $payment['payment_method'] ?? '';

                if ($paymentMethod === 'credit') {
                    $hasCreditPayment = true;
                }

                if ($paymentMethod === 'cheque') {
                    $hasChequePayment = true;
                }
            }
        }

        // Cheque payments bypass credit limit check
        if ($hasChequePayment) {
            return true;
        }

        $remainingBalance = max(0, $finalTotal - $actualPaymentAmount);

        // Full payment made and no explicit credit sale — nothing to check
        if ($remainingBalance <= 0 && !$hasCreditPayment) {
            return true;
        }

        // Fresh balance from ledger
        $currentBalance  = $customer->calculateBalanceFromLedger();
        $availableCredit = max(0, $customer->credit_limit - $currentBalance);

        if ($remainingBalance > $availableCredit) {
            $errorMessage  = "Credit limit exceeded for {$customer->full_name}.\n\n";
            $errorMessage .= "Credit Details:\n";
            $errorMessage .= "• Credit Limit: Rs " . number_format($customer->credit_limit, 2) . "\n";
            $errorMessage .= "• Current Outstanding: Rs " . number_format($currentBalance, 2) . "\n";
            $errorMessage .= "• Available Credit: Rs " . number_format($availableCredit, 2) . "\n\n";
            $errorMessage .= "Sale Details:\n";
            $errorMessage .= "• Total Sale Amount: Rs " . number_format($finalTotal, 2) . "\n";
            $errorMessage .= "• Payment Received: Rs " . number_format($actualPaymentAmount, 2) . "\n";
            $errorMessage .= "• Credit Amount Required: Rs " . number_format($remainingBalance, 2) . "\n\n";

            if ($availableCredit > 0) {
                $errorMessage .= "Maximum credit sale allowed: Rs " . number_format($availableCredit, 2) . "\n";
                $errorMessage .= "Exceeds limit by: Rs " . number_format($remainingBalance - $availableCredit, 2);
            } else {
                $errorMessage .= "No credit available. Please settle previous outstanding amount or pay full amount.";
            }

            throw new \Exception($errorMessage);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // 4. EDIT MODE PRICE INTEGRITY
    // -------------------------------------------------------------------------

    /**
     * Validate that unit_price and discount are not manipulated during a sale edit.
     *
     * - New products added during edit are allowed (no original to compare against).
     * - Throws \Exception if price or discount differ by more than Rs 0.01.
     */
    public function validateEditModePrice(array $productData, Sale $sale): void
    {
        try {
            $originalSaleProduct = $sale->products()
                ->where('product_id', $productData['product_id'])
                ->where('batch_id', $productData['batch_id'] ?? 'all')
                ->first();

            if (!$originalSaleProduct) {
                // New product added during edit — allow normal pricing
                return;
            }

            $originalPrice   = (float) $originalSaleProduct->price;
            $incomingPrice   = (float) $productData['unit_price'];
            $priceDiff       = abs($originalPrice - $incomingPrice);
            $allowedVariance = 0.01;

            if ($priceDiff > $allowedVariance) {
                Log::warning('Price manipulation attempt detected during sale edit', [
                    'sale_id'         => $sale->id,
                    'invoice_no'      => $sale->invoice_no,
                    'product_id'      => $productData['product_id'],
                    'batch_id'        => $productData['batch_id'] ?? 'all',
                    'original_price'  => $originalPrice,
                    'attempted_price' => $incomingPrice,
                    'difference'      => $priceDiff,
                    'user_id'         => auth()->id(),
                    'user_email'      => auth()->user()->email ?? 'unknown',
                ]);

                throw new \Exception(
                    "Price modification detected for product ID {$productData['product_id']}. " .
                    "Original price: Rs {$originalPrice}, attempted price: Rs {$incomingPrice}. " .
                    "Price changes during edit are not allowed for data integrity."
                );
            }

            // Validate discount integrity
            $originalDiscount = (float) ($originalSaleProduct->discount_amount ?? 0);
            $incomingDiscount = (float) ($productData['discount_amount'] ?? 0);
            $discountDiff     = abs($originalDiscount - $incomingDiscount);

            if ($discountDiff > $allowedVariance) {
                Log::warning('Discount manipulation attempt detected during sale edit', [
                    'sale_id'            => $sale->id,
                    'product_id'         => $productData['product_id'],
                    'original_discount'  => $originalDiscount,
                    'attempted_discount' => $incomingDiscount,
                    'difference'         => $discountDiff,
                ]);

                throw new \Exception(
                    "Discount modification detected for product ID {$productData['product_id']}. " .
                    "Discount changes during edit are not allowed."
                );
            }

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'modification detected') !== false) {
                throw $e;
            }

            Log::error('Unexpected error during edit mode price validation', [
                'sale_id'    => $sale->id,
                'product_id' => $productData['product_id'],
                'error'      => $e->getMessage(),
            ]);

            throw new \Exception('Price validation failed. Please try again or contact administrator.');
        }
    }

    // -------------------------------------------------------------------------
    // 5. STOCK AVAILABILITY FOR UPDATES
    // -------------------------------------------------------------------------

    /**
     * Validate that enough stock exists when editing a sale.
     *
     * Original sold quantities are added back (already restored) so the check
     * is: new_quantity <= (current_stock + originally_sold_quantity).
     *
     * Throws \Exception if insufficient stock.
     */
    public function validateStockForUpdate(array $productData, int $locationId, array $originalProducts): void
    {
        $freeQuantity  = floatval($productData['free_quantity'] ?? 0);
        $totalQuantity = $productData['quantity'] + $freeQuantity;
        $productId     = $productData['product_id'];
        $batchId       = $productData['batch_id'];

        // Determine original quantities already sold (paid + free)
        $originalQuantity     = 0;
        $originalFreeQuantity = 0;

        if (isset($originalProducts[$productId])) {
            if ($batchId === 'all') {
                foreach ($originalProducts[$productId] as $batchData) {
                    $originalQuantity     += $batchData['quantity']      ?? 0;
                    $originalFreeQuantity += $batchData['free_quantity'] ?? 0;
                }
            } else {
                $batchData            = $originalProducts[$productId][$batchId] ?? [];
                $originalQuantity     = $batchData['quantity']      ?? 0;
                $originalFreeQuantity = $batchData['free_quantity'] ?? 0;
            }
        }

        $originalTotalQuantity = $originalQuantity + $originalFreeQuantity;

        if (!empty($batchId) && $batchId !== 'all') {
            // Specific batch
            $currentStock   = Sale::getAvailableStock($batchId, $locationId);
            $availableStock = $currentStock + $originalTotalQuantity;

            if ($totalQuantity > $availableStock) {
                throw new \Exception(
                    "Batch ID {$batchId} does not have enough stock. " .
                    "Available: {$availableStock}, Requested: {$totalQuantity} " .
                    "(paid: {$productData['quantity']} + free: {$freeQuantity})"
                );
            }
        } else {
            // All batches — check total stock for this product at this location
            $currentTotalStock = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productId)
                ->where('location_batches.location_id', $locationId)
                ->sum('location_batches.qty');

            $availableStock = $currentTotalStock + $originalTotalQuantity;

            if ($totalQuantity > $availableStock) {
                throw new \Exception(
                    "Not enough stock available. " .
                    "Available: {$availableStock}, Requested: {$totalQuantity} " .
                    "(paid: {$productData['quantity']} + free: {$freeQuantity})"
                );
            }
        }
    }
}
