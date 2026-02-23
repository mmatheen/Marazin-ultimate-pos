<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\LocationBatch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\SalesReturn;
use App\Models\StockHistory;
use App\Models\SaleImei;
use App\Models\ImeiNumber;
use App\Services\UnifiedLedgerService;
use App\Services\PaymentService;
use App\Services\Sale\SaleValidationService;
use App\Services\Sale\SaleInvoiceNumberService;
use App\Services\Sale\SaleAmountCalculator;
use App\Services\Sale\SaleLedgerManager;
use App\Services\Sale\SalePaymentProcessor;
use App\Services\Sale\SaleResponseBuilder;
use App\Services\Sale\SaleProductProcessor;
use App\Services\Sale\SaleSaveService;
use App\Services\Sale\CustomerPriceHistoryService;
use App\Services\Sale\SaleDataTableService;
use App\Services\Sale\SaleEditDataBuilder;
use App\Services\Sale\SaleReceiptService;
use App\Services\Sale\SaleOrderConversionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class SaleController extends Controller
{
    protected $unifiedLedgerService;
    protected $paymentService;
    protected $saleValidationService;
    protected $saleInvoiceNumberService;
    protected $saleAmountCalculator;
    protected $saleLedgerManager;
    protected $salePaymentProcessor;
    protected $saleResponseBuilder;
    protected $saleProductProcessor;
    protected $saleSaveService;
    protected $saleEditDataBuilder;
    protected $saleDataTableService;
    protected $saleReceiptService;
    protected $customerPriceHistoryService;
    protected $saleOrderConversionService;

    function __construct(
        UnifiedLedgerService    $unifiedLedgerService,
        PaymentService          $paymentService,
        SaleValidationService   $saleValidationService,
        SaleInvoiceNumberService $saleInvoiceNumberService,
        SaleAmountCalculator    $saleAmountCalculator,
        SaleLedgerManager       $saleLedgerManager,
        SalePaymentProcessor    $salePaymentProcessor,
        SaleResponseBuilder     $saleResponseBuilder,
        SaleProductProcessor    $saleProductProcessor,
        SaleSaveService         $saleSaveService,
        SaleEditDataBuilder     $saleEditDataBuilder,
        SaleDataTableService    $saleDataTableService,
        SaleReceiptService      $saleReceiptService,
        CustomerPriceHistoryService $customerPriceHistoryService,
        SaleOrderConversionService $saleOrderConversionService
        ) {
        $this->unifiedLedgerService    = $unifiedLedgerService;
        $this->paymentService          = $paymentService;
        $this->saleValidationService   = $saleValidationService;
        $this->saleInvoiceNumberService = $saleInvoiceNumberService;
        $this->saleAmountCalculator    = $saleAmountCalculator;
        $this->saleLedgerManager       = $saleLedgerManager;
        $this->salePaymentProcessor    = $salePaymentProcessor;
        $this->saleResponseBuilder     = $saleResponseBuilder;
        $this->saleProductProcessor    = $saleProductProcessor;
        $this->saleSaveService         = $saleSaveService;
        $this->saleEditDataBuilder     = $saleEditDataBuilder;
        $this->saleDataTableService    = $saleDataTableService;
        $this->saleReceiptService      = $saleReceiptService;
        $this->customerPriceHistoryService  = $customerPriceHistoryService;
        $this->saleOrderConversionService    = $saleOrderConversionService;

        $this->middleware('permission:view all sales|view own sales', ['only' => ['listSale', 'index', 'show', 'getDataTableSales', 'salesDetails']]);
        $this->middleware('permission:create sale', ['only' => ['addSale', 'storeOrUpdate']]);
        $this->middleware('permission:access pos', ['only' => ['pos']]);
        $this->middleware('permission:edit sale', ['only' => ['editSale']]);
        $this->middleware('permission:delete sale', ['only' => ['destroy']]);
        $this->middleware('permission:print sale invoice', ['only' => ['printInvoice']]);

        // Middleware for sale permissions
        // If user has 'view own sales', restrict to their own sales; otherwise, allow all sales
        $this->middleware(function ($request, $next) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if ($user && $user->can('view own sales') && !$user->can('view all sales')) {
                // Only allow access to own sales
                Sale::addGlobalScope('own_sale', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }
            return $next($request);
        })->only(['index', 'listSale', 'getDataTableSales', 'salesDetails']);
    }


    public function listSale()
    {
        $currentUser = auth()->user();

        // Get filter data for dropdowns
        $locations = \App\Models\Location::select('id', 'name')->get();
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name')->get();

        // Apply same user filtering logic as UserController
        $isMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;

        $usersQuery = \App\Models\User::select('id', 'full_name');

        if (!$isMasterSuperAdmin) {
            // Non-Master Super Admin users cannot see Master Super Admin users
            $usersQuery->whereDoesntHave('roles', function($roleQuery) {
                $roleQuery->where('name', 'Master Super Admin');
            });
        }

        $users = $usersQuery->get();

        return view('sell.sale', compact('locations', 'customers', 'users'));
    }

    public function addSale()
    {
        $canUseFreeQty = (bool)(\App\Models\Setting::value('enable_free_qty') ?? 1) && auth()->user()?->can('use free quantity');
        return view('sell.add_sale', compact('canUseFreeQty'));
    }

    public function pos()
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $perms = $this->resolvePosPermissions($user);

        // Pass feature flag to view
        $useModularPOS = env('USE_MODULAR_POS', false); // Default to false (old system) for safety

        return view('sell.pos', array_merge($perms, compact('useModularPOS')));
    }

    public function draft()
    {
        return view('sell.draft_list');
    }

    public function quotation()
    {
        return view('sell.quotation_list');
    }

    public function saleOrdersList()
    {
        return view('sell.sale_orders_list');
    }

    /**
     * Convert Sale Order to Invoice
     */
    public function convertToInvoice($id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);

            // Validate it's a sale order
            if ($saleOrder->transaction_type !== 'sale_order') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This is not a Sale Order'
                ], 400);
            }

            // Validate not already converted
            if ($saleOrder->order_status === 'completed') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This Sale Order has already been converted to an invoice'
                ], 400);
            }

            // Convert using SaleOrderConversionService
            $invoice = $this->saleOrderConversionService->convert($saleOrder);

            // Create ledger entry for the invoice (skip for Walk-In customers)
            if ($invoice->customer_id != 1) {
                $this->unifiedLedgerService->recordSale($invoice);
            }

            // Return success without redirecting to edit (stock already deducted)
            return response()->json([
                'status' => 200,
                'message' => 'Sale Order converted to Invoice successfully! Invoice created with proper stock allocation.',
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'final_total' => $invoice->final_total,
                    'customer_id' => $invoice->customer_id
                ],
                'print_url' => "/sales/print-recent-transaction/{$invoice->id}",
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function updateSaleOrder(Request $request, $id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);

            // Validate it's a sale order
            if ($saleOrder->transaction_type !== 'sale_order') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This is not a Sale Order'
                ], 400);
            }

            // Store original status to check for cancellation
            $originalStatus = $saleOrder->order_status;

            // Get the JSON data
            $data = $request->all();

            // Check if this is a cancellation request
            $isCancellation = isset($data['order_status']) &&
                             $data['order_status'] === 'cancelled' &&
                             $originalStatus !== 'cancelled';

            // If cancelling, use database transaction to restore stock
            if ($isCancellation) {
                DB::transaction(function () use ($saleOrder, $data) {
                    // Get all products from the sale order
                    $products = $saleOrder->products;

                    // Restore stock for each product
                    foreach ($products as $product) {
                        $this->saleProductProcessor->restoreStock($product, StockHistory::STOCK_TYPE_SALE_ORDER_REVERSAL);
                    }

                    // Update sale order status and other fields
                    $saleOrder->order_status = $data['order_status'];
                    $saleOrder->status = 'cancelled'; // Also update main status field

                    if (isset($data['order_notes'])) {
                        $saleOrder->order_notes = $data['order_notes'];
                    }

                    if (isset($data['expected_delivery_date'])) {
                        $saleOrder->expected_delivery_date = $data['expected_delivery_date'];
                    }

                    $saleOrder->save();
                });

                return response()->json([
                    'status' => 200,
                    'message' => 'Sale Order cancelled successfully and stock restored!',
                    'sale_order' => $saleOrder->fresh()
                ], 200);
            } else {
                // Normal update (not cancellation)
                if (isset($data['order_status'])) {
                    $saleOrder->order_status = $data['order_status'];
                }

                if (isset($data['order_notes'])) {
                    $saleOrder->order_notes = $data['order_notes'];
                }

                if (isset($data['expected_delivery_date'])) {
                    $saleOrder->expected_delivery_date = $data['expected_delivery_date'];
                }

                $saleOrder->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Sale Order updated successfully!',
                    'sale_order' => $saleOrder
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function cancelConvertedInvoice($invoiceId)
    {
        try {
            $invoice = Sale::findOrFail($invoiceId);

            // Validate it's an invoice
            if ($invoice->transaction_type !== 'invoice') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This is not an invoice'
                ], 400);
            }

            // Revert via SaleOrderConversionService
            $this->saleOrderConversionService->revert($invoice);

            return response()->json([
                'status' => 200,
                'message' => 'Invoice cancelled successfully. Reverted back to Sale Order.',
                'sale_order' => [
                    'id' => $invoice->id,
                    'order_number' => $invoice->order_number,
                    'status' => 'confirmed'
                ],
                'redirect_url' => route('sale-orders-list')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    //draft sales

    public function index(Request $request)
    {
        // Check if this is a DataTable request
        if ($request->has('draw') || $request->has('length')) {
            return $this->getDataTableSales($request);
        }

        // Check if this is a request for Recent Transactions (includes all statuses)
        if ($request->has('recent_transactions') && $request->get('recent_transactions') == 'true') {
            // For Recent Transactions in POS, we need all statuses so frontend can filter by tabs
            // ✅ Exclude Sale Orders and cancelled invoices - only show actual active invoices
            $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                ->where(function($query) {
                    $query->where('transaction_type', 'invoice')
                          ->orWhereNull('transaction_type');
                })
                ->whereIn('status', ['final', 'quotation', 'draft', 'jobticket', 'suspend'])
                ->where('payment_status', '!=', 'Cancelled') // Exclude cancelled invoices
                ->orderBy('created_at', 'desc')
                ->limit(200) // Increased limit for Recent Transactions
                ->get();
        } else {
            // Check if this is specifically for sale orders list page
            if ($request->has('sale_orders') && $request->get('sale_orders') == 'true') {
                // Only return sale orders for sale orders list page
                $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                    ->where('transaction_type', 'sale_order')
                    ->orderBy('created_at', 'desc')
                    ->limit(200)
                    ->get();
            }
            // Check if this is a request for draft, quotation, or suspend sales
            elseif ($request->has('status') && in_array($request->get('status'), ['draft', 'quotation', 'suspend'])) {
                // Return sales with the specified status
                $status = $request->get('status');
                $sales = Sale::with('products.product', 'customer', 'location', 'payments', 'user')
                    ->where('status', $status)
                    ->orderBy('created_at', 'desc')
                    ->limit(200)
                    ->get();
            }
            else {

                $query = $request->has('customer_id')
                    ? Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                        ->with('products.product', 'customer', 'location', 'payments', 'user')
                        ->where('customer_id', $request->customer_id)
                    : Sale::with('products.product', 'customer', 'location', 'payments', 'user');

                $sales = $query
                    ->where('status', 'final')
                    ->where('transaction_type', '!=', 'sale_order') // Explicitly exclude sale orders
                    ->where(function($query) {
                        // Only include actual invoices
                        $query->where('transaction_type', 'invoice')
                              ->orWhereNull('transaction_type'); // Legacy records without transaction_type
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();
            }
        }

        return response()->json(['sales' => $sales], 200);
    }

    public function getDataTableSales(Request $request)
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            return response()->json($this->saleDataTableService->getData($request, $user));
        } catch (\Exception $e) {
            Log::error('Sales DataTable Error: ' . $e->getMessage());
            return response()->json([
                'error'           => 'Failed to fetch sales data',
                'message'         => $e->getMessage(),
                'draw'            => $request->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ], 500);
        }
    }

    public function clearSalesCache()
    {
        Cache::forget('sales_final_count');
        return response()->json(['message' => 'Sales cache cleared'], 200);
    }


    public function salesDetails($id)
    {
        try {
            $salesDetails = Sale::with('products.product', 'customer', 'location', 'payments')->findOrFail($id);
            return response()->json(['salesDetails' => $salesDetails], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
    }


    public function storeOrUpdate(Request $request, $id = null)
    {
        $validationErrors = $this->saleValidationService->validateRequest($request);
        if ($validationErrors) {
            return response()->json(['status' => 400, 'errors' => $validationErrors]);
        }

        $walkInError = $this->saleValidationService->checkWalkInRules($request);
        if ($walkInError) {
            return response()->json(array_merge(['status' => 400], $walkInError));
        }

        try {
            $startTime = microtime(true);

            // Early credit limit check (before opening a DB transaction)
            if ($request->customer_id != 1) {
                $customer  = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);
                $estimated = $this->saleAmountCalculator->estimateFinalTotal($request);

                try {
                    $this->saleValidationService->validateCreditLimit(
                        $customer, $estimated, $request->payments ?? [], $request->status
                    );
                } catch (\Exception $e) {
                    return response()->json([
                        'status'  => 400,
                        'message' => $e->getMessage(),
                        'errors'  => ['credit_limit' => ['Credit limit would be exceeded by this sale.']],
                    ]);
                }
            }

            $sale = DB::transaction(function () use ($request, $id) {

                $isUpdate    = $id !== null;
                $sale        = $isUpdate ? Sale::with(['products'])->findOrFail($id) : new Sale();
                $referenceNo = $isUpdate ? ($sale->reference_no ?? '') : $this->generateReferenceNo();
                $oldStatus   = $isUpdate ? $sale->getOriginal('status') : null;
                $newStatus   = $request->status;
                $transactionType = $request->transaction_type ?? 'invoice';

                // Calculate all monetary amounts and correct product subtotals
                $amounts = $this->saleAmountCalculator->calculate($request->products, $request, $newStatus);
                $request->merge(['products' => $amounts['corrected_products']]);

                // Resolve invoice / order number
                $numberData = $this->saleInvoiceNumberService->resolve(
                    $sale, $isUpdate, $oldStatus, $newStatus,
                    $transactionType, $request->location_id, $request->order_status
                );

                // Re-check credit limit inside transaction using the corrected final_total
                if ($request->customer_id != 1) {
                    $customer = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);
                    $this->saleValidationService->validateCreditLimit(
                        $customer, $amounts['final_total'], $request->payments ?? [], $newStatus
                    );
                }

                // Fill and persist the sale model; returns pre-save change flags
                $saveData = $this->saleSaveService->fillAndSave(
                    $sale, $request, $isUpdate, $amounts, $numberData, $newStatus, $transactionType, $referenceNo
                );
                $oldCustomerId        = $saveData['old_customer_id'];
                $oldFinalTotal        = $saveData['old_final_total'];
                $customerChanged      = $saveData['customer_changed'];
                $financialDataChanged = $saveData['financial_data_changed'];

                // Write ledger entries
                $this->saleLedgerManager->record(
                    $sale, $isUpdate, $oldStatus, $newStatus, $transactionType,
                    (int) $request->customer_id, $oldCustomerId, $oldFinalTotal,
                    $customerChanged, $financialDataChanged, $referenceNo
                );

                // Record payments (and job ticket advance if applicable)
                $this->salePaymentProcessor->process(
                    $sale, $request, $isUpdate, $customerChanged, $transactionType, $amounts
                );

                // Guard: Walk-In customers must pay in full
                if ($transactionType !== 'sale_order' && $request->customer_id == 1
                    && $amounts['amount_given'] < $sale->final_total) {
                    throw new \Exception('Partial payment is not allowed for Walk-In Customer.');
                }

                // Deduct / restore stock and create sales_product records
                $this->saleProductProcessor->process(
                    $sale, $request, $isUpdate, $oldStatus, $newStatus, $transactionType
                );

                return $sale;
            });

            return $this->saleResponseBuilder->build($sale, $request, $id, $startTime);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }



    private function generateReferenceNo()
    {
        return 'SALE-' . now()->format('Ymd');
    }

    /**
     * Resolve POS permission flags for a user.
     * Shared between pos() and editSale() — single source of truth.
     */
    private function resolvePosPermissions(?User $user): array
    {
        $allowedPriceTypes = [];
        if ($user?->can('select retail price'))     $allowedPriceTypes[] = 'retail';
        if ($user?->can('select wholesale price'))  $allowedPriceTypes[] = 'wholesale';
        if ($user?->can('select special price'))    $allowedPriceTypes[] = 'special';
        if ($user?->can('select max retail price')) $allowedPriceTypes[] = 'max_retail';

        $freeQtyEnabled = (int)(\App\Models\Setting::value('enable_free_qty') ?? 1);

        return [
            'allowedPriceTypes'      => $allowedPriceTypes,
            'canEditUnitPrice'       => (bool) ($user?->can('edit unit price in pos')),
            'canEditDiscount'        => (bool) ($user?->can('edit discount in pos')),
            'priceValidationEnabled' => (int)(\App\Models\Setting::value('enable_price_validation') ?? 1),
            'freeQtyEnabled'         => $freeQtyEnabled,
            'canUseFreeQty'          => (bool) ($freeQtyEnabled && $user?->can('use free quantity')),
        ];
    }



    public function getSaleByInvoiceNo($invoiceNo)
    {
        $sale = Sale::with([
            'products.product.unit', // eager load product and its unit
            'salesReturns' // load existing returns
        ])->where('invoice_no', $invoiceNo)->first();

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        // Check if this sale has already been returned
        if ($sale->salesReturns->count() > 0) {
            return response()->json([
                'error' => 'This sale has already been returned. Multiple returns for the same invoice are not allowed.',
                'returned_count' => $sale->salesReturns->count(),
                'return_details' => $sale->salesReturns->map(function($return) {
                    return [
                        'return_date' => $return->return_date,
                        'return_total' => $return->return_total,
                        'notes' => $return->notes
                    ];
                })
            ], 409); // 409 Conflict status code
        }

        $products = $sale->products->map(function ($product) use ($sale) {
            $currentQuantity = $sale->getCurrentSaleQuantity($product->product_id);
            $product->current_quantity = $currentQuantity;

            // Use the actual stored price from sales_products table
            // This already includes all discounts applied during the sale
            $actualPrice = $product->price; // This is the final price customer paid per unit

            // Add unit details with better null handling
            $productModel = $product->product;
            if ($productModel && $productModel->unit) {
                $product->unit = [
                    'id' => $productModel->unit->id,
                    'name' => $productModel->unit->name,
                    'short_name' => $productModel->unit->short_name,
                    'allow_decimal' => $productModel->unit->allow_decimal
                ];
            } else {
                $product->unit = [
                    'id' => null,
                    'name' => 'Pieces',
                    'short_name' => 'Pc(s)',
                    'allow_decimal' => false
                ];
            }

            // Set the return price (same as the price customer actually paid)
            $product->return_price = $actualPrice;

            return $product;
        })->filter(function ($product) {
            // Only include products with current quantity > 0
            return $product->current_quantity > 0;
        })->values(); // Reset array keys after filtering

        return response()->json([
            'sale_id' => $sale->id,
            'invoice_no' => $invoiceNo,
            'customer_id' => $sale->customer_id,
            'location_id' => $sale->location_id,
            'products' => $products,
            // Include original sale discount information for proportional calculation
            'original_discount' => [
                'discount_type' => $sale->discount_type, // 'percentage' or 'fixed'
                'discount_amount' => $sale->discount_amount ?? 0,
                'subtotal' => $sale->subtotal ?? 0,
                'final_total' => $sale->final_total ?? 0,
                'total_original_quantity' => $sale->products->sum('quantity') // Total quantity in original sale
            ]
        ], 200);
    }

    public function searchSales(Request $request)
    {
        $term = $request->get('term');
        $sales = Sale::where('invoice_no', 'LIKE', '%' . $term . '%')
            ->orWhere('id', 'LIKE', '%' . $term . '%')
            ->get(['invoice_no as value', 'id']);

        return response()->json($sales);
    }

    public function fetchSuspendedSales()
    {
        try {
            $suspendedSales = Sale::where('status', 'suspend')
                ->with(['customer', 'products.product'])
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'invoice_no' => $sale->invoice_no, // Changed from reference_no
                        'sales_date' => $sale->created_at, // Changed to full date object
                        'customer' => $sale->customer ? ['name' => trim($sale->customer->first_name . ' ' . $sale->customer->last_name)] : ['name' => 'Walk-In Customer'], // Nested object
                        'products' => $sale->products->toArray(), // Full products array for .length
                        'final_total' => $sale->final_total, // Raw number, not formatted
                    ];
                });

            return response()->json($suspendedSales->values(), 200); // Ensure it returns an array
        } catch (\Exception $e) {
            logger()->error('Error fetching suspended sales: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch suspended sales'], 500);
        }
    }

    public function editSale($id)
    {
        try {
            $sale = Sale::with([
                'products.product.unit',
                'products.product.batches.locationBatches.location',
                'products.batch',
                'products.imeis',
                'customer',
                'location',
            ])->findOrFail($id);

            $saleDetails = $this->saleEditDataBuilder->build($sale);

            if (request()->ajax() || request()->is('api/*')) {
                return response()->json([
                    'status'       => 200,
                    'sale_details' => $saleDetails,
                ]);
            }

            $perms = $this->resolvePosPermissions(auth()->user());

            return view('sell.pos', array_merge(['saleDetails' => $saleDetails], $perms));
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 404, 'message' => 'Sale not found.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSuspendedSale($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            if ($sale->status !== 'suspend') {
                return response()->json(['message' => 'Sale is not suspended.'], 400);
            }

            DB::transaction(function () use ($sale) {
                // 1. Restore stock first
                foreach ($sale->products as $product) {
                    $this->saleProductProcessor->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                    $product->delete();
                }

                // 2. Clean up ledger entries to maintain accounting accuracy
                // This ensures customer balance is corrected when suspended sale is deleted
                if ($sale->customer_id && $sale->customer_id != 1) {
                    $this->unifiedLedgerService->deleteSaleLedger($sale);
                }

                // 3. Delete the sale record
                $sale->delete();
            });

            return response()->json(['message' => 'Suspended sale deleted, stock restored, and customer balance updated successfully.'], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting suspended sale', [
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'An error occurred while deleting the sale: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        DB::transaction(function () use ($sale) {
            // 1. Restore stock ONLY if it was deducted (for final/suspend sales, NOT for draft/quotation)
            // Draft and quotation sales don't affect stock, so we shouldn't restore stock when deleting them
            $shouldRestoreStock = !in_array($sale->status, ['draft', 'quotation']);

            foreach ($sale->products as $product) {
                if ($shouldRestoreStock) {
                    $this->saleProductProcessor->restoreStock($product, StockHistory::STOCK_TYPE_SALE_REVERSAL);
                }
                $product->delete();
            }

            // 2. Clean up ledger entries for non-Walk-In customers
            // Only delete ledger entries for sales that would have created them
            if ($sale->customer_id && $sale->customer_id != 1 &&
                !in_array($sale->status, ['draft', 'quotation'])) {

                $this->unifiedLedgerService->deleteSaleLedger($sale);
            }

            // 3. Delete the sale record
            $sale->delete();
        });

        $message = in_array($sale->status, ['draft', 'quotation'])
            ? 'Sale deleted successfully.'
            : 'Sale deleted, stock restored, and customer balance updated successfully.';

        return response()->json([
            'status' => 200,
            'message' => $message
        ], 200);
    }

    public function printRecentTransaction($id, Request $request)
    {
        try {
            $html = $this->saleReceiptService->getHtml((int) $id, $request->query('layout'));
            return response()->json(['invoice_html' => $html], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function logPricingError(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'product_name' => 'required|string',
                'customer_type' => 'required|string',
                'batch_id' => 'nullable|integer',
                'batch_no' => 'nullable|string',
                'timestamp' => 'required|string',
                'location_id' => 'required|integer'
            ]);

            // Log to Laravel log file with structured data
            Log::warning('POS Pricing Error', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'Unknown',
                'product_id' => $validated['product_id'],
                'product_name' => $validated['product_name'],
                'customer_type' => $validated['customer_type'],
                'batch_id' => $validated['batch_id'],
                'batch_no' => $validated['batch_no'],
                'location_id' => $validated['location_id'],
                'timestamp' => $validated['timestamp'],
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Pricing error logged successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log pricing error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to log pricing error'
            ], 500);
        }
    }




    public function getCustomerPreviousPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'product_id'  => 'required|integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $this->customerPriceHistoryService->get(
                (int) $request->customer_id,
                (int) $request->product_id
            );
            return response()->json(['status' => 200, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get customer previous price', [
                'error'       => $e->getMessage(),
                'customer_id' => $request->customer_id,
                'product_id'  => $request->product_id,
            ]);
            return response()->json(['status' => 500, 'message' => 'Failed to retrieve customer previous price'], 500);
        }
    }


}
