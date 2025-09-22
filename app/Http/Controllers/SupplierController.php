<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    protected $unifiedLedgerService;

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->middleware('permission:view supplier', ['only' => ['index', 'show','Supplier']]);
        $this->middleware('permission:create supplier', ['only' => ['store']]);
        $this->middleware('permission:edit supplier', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete supplier', ['only' => ['destroy']]);
    }

    public function Supplier(){
        return view('contact.supplier.supplier');
    }

    public function index()
    {
        $suppliers = Supplier::with(['purchases', 'purchaseReturns'])->get()->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'prefix' => $supplier->prefix,
                'first_name' => $supplier->first_name,
                'last_name' => $supplier->last_name,
                'full_name' => $supplier->getFullNameAttribute(),
                'mobile_no' => $supplier->mobile_no,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'location_id' => $supplier->location_id,
                'opening_balance' => $supplier->opening_balance,
                'current_balance' => $supplier->current_balance,
                'total_purchase_due' => $supplier->getTotalPurchaseDueAttribute(),
                'total_return_due' => $supplier->getTotalReturnDueAttribute(),
            ];
        });

        if ($suppliers->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $suppliers
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Suppliers Found"
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'prefix' => 'nullable|string|max:10',  // Add max length if applicable
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'mobile_no' => 'required|numeric|digits_between:10,15|unique:suppliers,mobile_no',  // Ensure valid mobile number length and uniqueness
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'opening_balance' => 'nullable|numeric',  // Ensure opening balance is a valid number
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $supplier = Supplier::create([
                'prefix' => $request->prefix,
                'first_name' => $request->first_name ?? '',
                'last_name' => $request->last_name ?? '',
                'mobile_no' => $request->mobile_no ?? '',
                'email' => $request->email ?? null,
                'address' => $request->address ?? '',
                'opening_balance' => $request->opening_balance ?? 0,
            ]);

            // Insert ledger entry for opening balance
            if ($supplier && ($request->opening_balance ?? 0) != 0) {
                $this->unifiedLedgerService->recordOpeningBalance(
                    $supplier->id,
                    'supplier',
                    $request->opening_balance ?? 0,
                    'Opening balance for supplier: ' . $supplier->first_name . ' ' . $supplier->last_name
                );

                return response()->json([
                    'status' => 200,
                    'message' => "New Supplier Details Created Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "Something went wrong!"
                ]);
            }
        }
    }

    public function show(int $id)
    {
        $getValue = Supplier::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Supplier Found!"
            ]);
        }
    }

    public function edit(int $id)
    {
        $getValue = Supplier::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Supplier Found!"
            ]);
        }
    }

    public function update(Request $request, int $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'status' => 404,
                'message' => "No Such Supplier Found!"
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'prefix' => 'nullable|string|max:10',  // Add max length if applicable
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'mobile_no' => [
                    'required',
                    'numeric',
                    'digits_between:10,15',
                    Rule::unique('suppliers')->ignore($supplier->id),  // Ensure valid mobile number length and uniqueness excluding current supplier
                ],
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'opening_balance' => 'nullable|numeric',  // Ensure opening balance is a valid number
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            try {
                DB::beginTransaction();

                // Store old opening balance for ledger adjustment
                $oldOpeningBalance = $supplier->opening_balance;
                $newOpeningBalance = $request->input('opening_balance', $oldOpeningBalance);

                $supplier->update([
                    'prefix' => $request->prefix,
                    'first_name' => $request->first_name ?? '',
                    'last_name' => $request->last_name ?? '',
                    'mobile_no' => $request->mobile_no ?? '',
                    'email' => $request->email ?? null,
                    'address' => $request->address ?? '',
                    'opening_balance' => $newOpeningBalance,
                    'current_balance' => $newOpeningBalance,
                ]);

                // Handle opening balance adjustment in ledger
                if ($oldOpeningBalance != $newOpeningBalance) {
                    $this->unifiedLedgerService->recordOpeningBalanceAdjustment(
                        $supplier->id,
                        'supplier',
                        $oldOpeningBalance,
                        $newOpeningBalance,
                        'Opening balance updated via supplier profile'
                    );
                }

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => "Supplier Details Updated Successfully!"
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => "Error updating supplier: " . $e->getMessage()
                ]);
            }
        }
    }

    public function destroy(int $id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $supplier->delete();
            return response()->json([
                'status' => 200,
                'message' => "Supplier Details Deleted Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Supplier Found!"
            ]);
        }
    }
}