<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Supplier;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SupplierFreeClaimController extends Controller
{
    public function __construct()
    {
        // Master gate: user must have 'use free quantity' to access ANY claim feature
        $this->middleware('permission:use free quantity');

        // Granular gates on top of the master gate
        $this->middleware('permission:view supplier claims',   ['only' => ['index']]);
        $this->middleware('permission:create supplier claims', ['only' => ['standalone', 'storeStandalone']]);
        $this->middleware('permission:receive supplier claims',['only' => ['create', 'store']]);
    }

    // -----------------------------------------------------------------
    // LIST all purchases that have a pending/partial/fulfilled claim
    // -----------------------------------------------------------------
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'purchaseProducts.product'])
            ->whereNotNull('claim_status')
            ->where('purchase_type', '!=', 'free_claim')
            ->orderByDesc('purchase_date');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('claim_status')) {
            $query->where('claim_status', $request->claim_status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        $claims   = $query->paginate(25)->withQueryString();
        $suppliers = Supplier::orderBy('first_name')->get();

        return view('supplier_free_claims.index', compact('claims', 'suppliers'));
    }

    // -----------------------------------------------------------------
    // RECEIVE form: for a specific original purchase
    // -----------------------------------------------------------------
    public function create(int $purchaseId)
    {
        $originalPurchase = Purchase::with([
            'supplier',
            'location',
            'purchaseProducts.product',
            'purchaseProducts.batch',
            'claimReceipts.purchaseProducts',
        ])->findOrFail($purchaseId);

        // Only allow receiving if there is something left to receive
        abort_if(
            $originalPurchase->claim_status === 'fulfilled',
            403,
            'All claimed items have already been received.'
        );

        // Build remaining qty per product
        $receiptPurchaseIds = $originalPurchase->claimReceipts()->pluck('id');
        $receivedQtyByProduct = PurchaseProduct::whereIn('purchase_id', $receiptPurchaseIds)
            ->selectRaw('product_id, SUM(quantity) as total_received')
            ->groupBy('product_id')
            ->pluck('total_received', 'product_id');

        $claimItems = $originalPurchase->purchaseProducts
            ->filter(fn ($pp) => $pp->claim_free_quantity > 0)
            ->map(function ($pp) use ($receivedQtyByProduct) {
                $pp->already_received = (float) ($receivedQtyByProduct[$pp->product_id] ?? 0);
                $pp->remaining        = max(0, (float)$pp->claim_free_quantity - $pp->already_received);
                return $pp;
            })
            ->filter(fn ($pp) => $pp->remaining > 0);

        $locations = Location::whereNull('parent_id')->get();

        return view('supplier_free_claims.receive', compact(
            'originalPurchase', 'claimItems', 'locations'
        ));
    }

    // -----------------------------------------------------------------
    // STORE a claim receipt (zero-cost purchase linked to original)
    // -----------------------------------------------------------------
    public function store(Request $request, int $purchaseId)
    {
        $originalPurchase = Purchase::with([
            'purchaseProducts',
            'claimReceipts.purchaseProducts',
        ])->findOrFail($purchaseId);

        $request->validate([
            'receive_date'    => 'required|date',
            'location_id'     => 'required|integer|exists:locations,id',
            'items'           => 'required|array|min:1',
            'items.*.product_id'          => 'required|integer|exists:products,id',
            'items.*.quantity_received'   => 'required|numeric|min:0.0001',
            'items.*.adjusted_claim_qty'  => 'nullable|numeric|min:0.0001',
            'items.*.batch_no'            => 'nullable|string|max:255',
            'items.*.expiry_date'         => 'nullable|date',
        ]);

        // Step 1: Apply any adjusted_claim_qty increases FIRST (supplier promises more than originally noted)
        foreach ($request->items as $item) {
            $adjQty = isset($item['adjusted_claim_qty']) ? (float) $item['adjusted_claim_qty'] : null;
            if ($adjQty === null) continue;

            $pp = $originalPurchase->purchaseProducts
                ->firstWhere('product_id', (int) $item['product_id']);

            if ($pp && $adjQty > (float) $pp->claim_free_quantity) {
                // Increase the claim qty on the purchase_product record
                PurchaseProduct::where('id', $pp->id)
                    ->update(['claim_free_quantity' => $adjQty]);
                $pp->claim_free_quantity = $adjQty; // update in-memory too
            }
        }

        // Refresh purchaseProducts after any adjustments
        $originalPurchase->load('purchaseProducts');

        // Step 2: Validate quantities do not exceed remaining
        $receiptPurchaseIds = $originalPurchase->claimReceipts()->pluck('id');
        $receivedQtyByProduct = PurchaseProduct::whereIn('purchase_id', $receiptPurchaseIds)
            ->selectRaw('product_id, SUM(quantity) as total_received')
            ->groupBy('product_id')
            ->pluck('total_received', 'product_id');

        $claimedQtyByProduct = $originalPurchase->purchaseProducts
            ->pluck('claim_free_quantity', 'product_id');

        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $claimed   = (float) ($claimedQtyByProduct[$productId] ?? 0);
            $received  = (float) ($receivedQtyByProduct[$productId] ?? 0);
            $remaining = $claimed - $received;
            $receiving = (float) $item['quantity_received'];

            if ($receiving > $remaining + 0.0001) {
                return back()->withErrors([
                    "items.{$productId}.quantity_received" =>
                        "Cannot receive {$receiving} — only {$remaining} remaining for product ID {$productId}.",
                ])->withInput();
            }
        }

        // Build a synthetic request that storeOrUpdate can process
        // Products: unit_cost=0, total=0, all required price fields copied from original purchase_products
        $purchaseController = app(PurchaseController::class);

        $products = [];
        foreach ($request->items as $item) {
            $originalLine = $originalPurchase->purchaseProducts
                ->firstWhere('product_id', $item['product_id']);

            $products[] = [
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity_received'],
                'free_quantity'       => 0,
                'claim_free_quantity' => 0,
                'price'               => 0,
                'discount_percent'    => 0,
                'unit_cost'           => 0,
                'wholesale_price'     => $originalLine->wholesale_price ?? 0,
                'special_price'       => $originalLine->special_price   ?? 0,
                'retail_price'        => $originalLine->retail_price    ?? 0,
                'max_retail_price'    => $originalLine->max_retail_price ?? 0,
                'total'               => 0,
                'batch_no'            => $item['batch_no']    ?? null,
                'expiry_date'         => $item['expiry_date'] ?? null,
            ];
        }

        $syntheticRequest = Request::create('/purchases/store', 'POST', [
            'supplier_id'          => $originalPurchase->supplier_id,
            'purchase_date'        => $request->receive_date,
            'purchasing_status'    => 'Received',
            'location_id'          => $request->location_id,
            'total'                => 0,
            'final_total'          => 0,
            'purchase_type'        => 'free_claim',
            'claim_reference_id'   => $purchaseId,
            'products'             => $products,
        ]);

        // Copy session/auth from the real request
        $syntheticRequest->setLaravelSession($request->session());

        try {
            $response = $purchaseController->storeOrUpdate($syntheticRequest);
            $data = json_decode($response->getContent(), true);

            if (isset($data['status']) && $data['status'] !== 200) {
                return back()->withErrors(['error' => $data['message'] ?? 'Failed to save receipt.'])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Claim receipt store failed', ['error' => $e->getMessage(), 'purchase_id' => $purchaseId]);
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('supplier-claims.index')
            ->with('toastr-success', 'Claim receipt saved. Stock updated successfully.');
    }

    // -----------------------------------------------------------------
    // STANDALONE claim form (no original purchase)
    // -----------------------------------------------------------------
    public function standalone()
    {
        $suppliers = Supplier::orderBy('first_name')->get();
        $locations = Location::whereNull('parent_id')->get();

        return view('supplier_free_claims.standalone', compact('suppliers', 'locations'));
    }

    // -----------------------------------------------------------------
    // STORE standalone claim (no original purchase — records promise only)
    // -----------------------------------------------------------------
    public function storeStandalone(Request $request)
    {
        $request->validate([
            'supplier_id'         => 'required|integer|exists:suppliers,id',
            'claim_date'          => 'required|date',
            'location_id'         => 'required|integer|exists:locations,id',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|integer|exists:products,id',
            'items.*.claimed_qty' => 'required|numeric|min:0.0001',
        ]);

        // Read prices from products table
        $purchaseController = app(PurchaseController::class);

        $products = [];
        foreach ($request->items as $item) {
            $product = \App\Models\Product::find($item['product_id']);

            $products[] = [
                'product_id'          => $item['product_id'],
                'quantity'            => 0,       // No stock yet
                'free_quantity'       => 0,
                'claim_free_quantity' => $item['claimed_qty'],
                'price'               => 0,
                'discount_percent'    => 0,
                'unit_cost'           => 0,
                'wholesale_price'     => $product->whole_sale_price ?? 0,
                'special_price'       => $product->special_price    ?? 0,
                'retail_price'        => $product->retail_price     ?? 0,
                'max_retail_price'    => $product->max_retail_price  ?? 0,
                'total'               => 0,
                'batch_no'            => null,
                'expiry_date'         => null,
            ];
        }

        $syntheticRequest = Request::create('/purchases/store', 'POST', [
            'supplier_id'        => $request->supplier_id,
            'purchase_date'      => $request->claim_date,
            'purchasing_status'  => 'Received',
            'location_id'        => $request->location_id,
            'total'              => 0,
            'final_total'        => 0,
            'purchase_type'      => 'free_claim_standalone',
            'claim_reference_id' => null,
            'products'           => $products,
        ]);

        $syntheticRequest->setLaravelSession($request->session());

        try {
            $response = $purchaseController->storeOrUpdate($syntheticRequest);
            $data = json_decode($response->getContent(), true);

            if (isset($data['status']) && $data['status'] !== 200) {
                return back()->withErrors(['error' => $data['message'] ?? 'Failed to save.'])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Standalone claim store failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('supplier-claims.index')
            ->with('toastr-success', 'Standalone claim recorded. You can receive items when the supplier delivers.');
    }
}
