<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Sale;
use App\Models\Setting;
use App\Http\Controllers\Web\ProductController as ProductManager;
use App\Services\Sale\SaleValidationService;
use App\Services\Sale\SaleDeleteService;
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
use App\Services\Sale\SaleQueryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class SaleController extends Controller
{
    protected $saleValidationService;
    protected $saleDeleteService;
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
    protected $saleQueryService;

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct(
        SaleValidationService       $saleValidationService,
        SaleDeleteService           $saleDeleteService,
        SaleInvoiceNumberService    $saleInvoiceNumberService,
        SaleAmountCalculator        $saleAmountCalculator,
        SaleLedgerManager           $saleLedgerManager,
        SalePaymentProcessor        $salePaymentProcessor,
        SaleResponseBuilder         $saleResponseBuilder,
        SaleProductProcessor        $saleProductProcessor,
        SaleSaveService             $saleSaveService,
        SaleEditDataBuilder         $saleEditDataBuilder,
        SaleDataTableService        $saleDataTableService,
        SaleReceiptService          $saleReceiptService,
        CustomerPriceHistoryService $customerPriceHistoryService,
        SaleOrderConversionService  $saleOrderConversionService,
        SaleQueryService            $saleQueryService
    ) {
        $this->saleValidationService       = $saleValidationService;
        $this->saleDeleteService           = $saleDeleteService;
        $this->saleInvoiceNumberService    = $saleInvoiceNumberService;
        $this->saleAmountCalculator        = $saleAmountCalculator;
        $this->saleLedgerManager           = $saleLedgerManager;
        $this->salePaymentProcessor        = $salePaymentProcessor;
        $this->saleResponseBuilder         = $saleResponseBuilder;
        $this->saleProductProcessor        = $saleProductProcessor;
        $this->saleSaveService             = $saleSaveService;
        $this->saleEditDataBuilder         = $saleEditDataBuilder;
        $this->saleDataTableService        = $saleDataTableService;
        $this->saleReceiptService          = $saleReceiptService;
        $this->customerPriceHistoryService = $customerPriceHistoryService;
        $this->saleOrderConversionService  = $saleOrderConversionService;
        $this->saleQueryService            = $saleQueryService;

        $this->middleware('permission:view all sales|view own sales', ['only' => ['listSale', 'index', 'show', 'getDataTableSales', 'salesDetails']]);
        $this->middleware('permission:create sale',        ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:access pos',         ['only' => ['pos']]);
        $this->middleware('permission:edit sale',          ['only' => ['editSale']]);
        $this->middleware('permission:delete sale',        ['only' => ['destroy', 'deleteSuspendedSale']]);
        $this->middleware('permission:print sale invoice', ['only' => ['printRecentTransaction']]);

        // Restrict users with 'view own sales' to only see their own records
        $this->middleware(function ($request, $next) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if ($user && $user->can('view own sales') && !$user->can('view all sales')) {
                Sale::addGlobalScope('own_sale', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }
            return $next($request);
        })->only(['index', 'listSale', 'getDataTableSales', 'salesDetails']);
    }

    // -------------------------------------------------------------------------
    // INDEX — list all sales (API + DataTable)
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        if ($request->has('draw') || $request->has('length')) {
            return $this->getDataTableSales($request);
        }

        return response()->json([
            'sales' => $this->saleQueryService->resolveIndex($request),
        ], 200);
    }

    // -------------------------------------------------------------------------
    // PAGE VIEWS — blade pages
    // -------------------------------------------------------------------------

    public function listSale()
    {
        $currentUser = auth()->user();

        $locations = Location::select('id', 'name')->get();
        $customers = Customer::select('id', 'first_name', 'last_name')->get();

        $isMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;

        $usersQuery = User::select('id', 'full_name');
        if (!$isMasterSuperAdmin) {
            $usersQuery->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', 'Master Super Admin');
            });
        }
        $users = $usersQuery->get();

        return view('sell.sale', compact('locations', 'customers', 'users'));
    }

    public function pos()
    {
        $perms = $this->resolvePosPermissions(auth()->user());

        return view('sell.pos', $perms);
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

    // -------------------------------------------------------------------------
    // STORE / UPDATE — create or update a sale
    // -------------------------------------------------------------------------

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

                $isUpdate        = $id !== null;
                $sale            = $isUpdate ? Sale::with(['products'])->findOrFail($id) : new Sale();
                $referenceNo     = $isUpdate ? ($sale->reference_no ?? '') : $this->saleInvoiceNumberService->generateReferenceNo();
                $oldStatus       = $isUpdate ? $sale->getOriginal('status') : null;
                $newStatus       = $request->status;
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
                $saveData             = $this->saleSaveService->fillAndSave(
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

    // -------------------------------------------------------------------------
    // SHOW — read single records / search
    // -------------------------------------------------------------------------

    public function salesDetails($id)
    {
        try {
            $salesDetails = $this->saleQueryService->getDetails((int) $id);
            return response()->json(['salesDetails' => $salesDetails], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
    }

    public function getSaleByInvoiceNo($invoiceNo)
    {
        try {
            $data = $this->saleQueryService->getByInvoiceNo($invoiceNo);
            return response()->json($data, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Sale not found'], 404);
        } catch (\DomainException $e) {
            return response()->json(json_decode($e->getMessage(), true), 409);
        }
    }

    public function searchSales(Request $request)
    {
        $sales = $this->saleQueryService->search($request->get('term', ''));
        return response()->json($sales);
    }

    public function fetchSuspendedSales()
    {
        try {
            return response()->json($this->saleQueryService->getSuspended()->values(), 200);
        } catch (\Exception $e) {
            logger()->error('Error fetching suspended sales: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch suspended sales'], 500);
        }
    }

    public function getDataTableSales(Request $request)
    {
        try {
            return response()->json($this->saleDataTableService->getData($request, auth()->user()));
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

    // -------------------------------------------------------------------------
    // EDIT — load sale into POS for editing
    // -------------------------------------------------------------------------

    public function editSale($id)
    {
        try {
            $sale        = $this->saleEditDataBuilder->findWithRelations((int) $id);
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

    // -------------------------------------------------------------------------
    // SALE ORDERS — convert, update, cancel
    // -------------------------------------------------------------------------

    public function convertToInvoice($id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);
            $invoice   = $this->saleOrderConversionService->convert($saleOrder);

            return response()->json([
                'status'    => 200,
                'message'   => 'Sale Order converted to Invoice successfully! Invoice created with proper stock allocation.',
                'invoice'   => [
                    'id'          => $invoice->id,
                    'invoice_no'  => $invoice->invoice_no,
                    'final_total' => $invoice->final_total,
                    'customer_id' => $invoice->customer_id,
                ],
                'print_url' => "/sales/print-recent-transaction/{$invoice->id}",
                'success'   => true,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    public function updateSaleOrder(Request $request, $id)
    {
        try {
            $saleOrder      = Sale::findOrFail($id);
            $originalStatus = $saleOrder->order_status;
            $data           = $request->all();

            $updatedOrder = $this->saleOrderConversionService->updateOrder($saleOrder, $data);

            $wasCancelled = isset($data['order_status'])
                && $data['order_status'] === 'cancelled'
                && $originalStatus !== 'cancelled';

            $message = $wasCancelled
                ? 'Sale Order cancelled successfully and stock restored!'
                : 'Sale Order updated successfully!';

            return response()->json([
                'status'     => 200,
                'message'    => $message,
                'sale_order' => $updatedOrder,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    public function cancelConvertedInvoice($invoiceId)
    {
        try {
            $invoice = Sale::findOrFail($invoiceId);
            $this->saleOrderConversionService->revert($invoice);

            return response()->json([
                'status'       => 200,
                'message'      => 'Invoice cancelled successfully. Reverted back to Sale Order.',
                'sale_order'   => [
                    'id'           => $invoice->id,
                    'order_number' => $invoice->order_number,
                    'status'       => 'confirmed',
                ],
                'redirect_url' => route('sale-orders-list'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    // -------------------------------------------------------------------------
    // DESTROY — delete sales
    // -------------------------------------------------------------------------

    public function destroy($id)
    {
        try {
            $sale         = Sale::findOrFail($id);
            $restoreStock = !in_array($sale->status, ['draft', 'quotation']);

            $this->saleDeleteService->delete($sale, $restoreStock);

            $message = $restoreStock
                ? 'Sale deleted, stock restored, and customer balance updated successfully.'
                : 'Sale deleted successfully.';

            return response()->json(['status' => 200, 'message' => $message], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 404, 'message' => 'Sale not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting sale', ['sale_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['status' => 500, 'message' => 'An error occurred while deleting the sale: ' . $e->getMessage()], 500);
        }
    }

    public function deleteSuspendedSale($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'suspend') {
                return response()->json(['message' => 'Sale is not suspended.'], 400);
            }

            $this->saleDeleteService->delete($sale, true);

            return response()->json(['message' => 'Suspended sale deleted, stock restored, and customer balance updated successfully.'], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting suspended sale', [
                'sale_id' => $id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while deleting the sale: ' . $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // UTILITY — print, cache, logging, price history
    // -------------------------------------------------------------------------

    public function printRecentTransaction($id, Request $request)
    {
        try {
            $html = $this->saleReceiptService->getHtml((int) $id, $request->query('layout'));
            return response()->json(['invoice_html' => $html], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function clearSalesCache()
    {
        Cache::forget('sales_final_count');
        return response()->json(['message' => 'Sales cache cleared'], 200);
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

    public function logPricingError(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id'    => 'required|integer',
                'product_name'  => 'required|string',
                'customer_type' => 'required|string',
                'batch_id'      => 'nullable|integer',
                'batch_no'      => 'nullable|string',
                'timestamp'     => 'required|string',
                'location_id'   => 'required|integer',
            ]);

            Log::warning('Pricing error reported by client', $validated);

            return response()->json(['status' => 200, 'message' => 'Pricing error logged successfully']);

        } catch (\Exception $e) {
            Log::error('Failed to log pricing error', [
                'error'        => $e->getMessage(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['status' => 500, 'message' => 'Failed to log pricing error'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function resolvePosPermissions(?User $user): array
    {
        $allowedPriceTypes = [];
        if ($user?->can('select retail price'))     $allowedPriceTypes[] = 'retail';
        if ($user?->can('select wholesale price'))  $allowedPriceTypes[] = 'wholesale';
        if ($user?->can('select special price'))    $allowedPriceTypes[] = 'special';
        if ($user?->can('select max retail price')) $allowedPriceTypes[] = 'max_retail';

        $freeQtyEnabled = (int) (Setting::value('enable_free_qty') ?? 1);

        return [
            'allowedPriceTypes'      => $allowedPriceTypes,
            'canEditUnitPrice'       => (bool) ($user?->can('edit unit price in pos')),
            'canEditDiscount'        => (bool) ($user?->can('edit discount in pos')),
            'priceValidationEnabled' => (int) (Setting::value('enable_price_validation') ?? 1),
            'freeQtyEnabled'         => $freeQtyEnabled,
            'canUseFreeQty'          => (bool) ($freeQtyEnabled && $user?->can('use free quantity')),
            'canUseQuickPriceEntry'  => (bool) ($user?->can('quick price entry')),
            'miscItemProductId'      => ProductManager::resolveCashItemProductId(),
        ];
    }

}

