<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Ledger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    function __construct()
    {
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
            if ($supplier) {
                Ledger::create([
                    'transaction_date' => now(),
                    'reference_no' => 'OB-' . $supplier->id,
                    'transaction_type' => 'opening_balance',
                    'debit' => $request->opening_balance ?? 0,
                    'credit' => 0,
                    'balance' => $request->opening_balance ?? 0,
                    'contact_type' => 'supplier',
                    'user_id' => $supplier->id,
                ]);

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
            $supplier->update([
                'prefix' => $request->prefix,
                'first_name' => $request->first_name ?? '',
                'last_name' => $request->last_name ?? '',
                'mobile_no' => $request->mobile_no ?? '',
                'email' => $request->email ?? null,
                'address' => $request->address ?? '',
                'opening_balance' => $request->opening_balance ?? 0,
                'current_balance' => $request->opening_balance ?? 0,
            ]);

            // Insert ledger entry for updated opening balance
            Ledger::create([
                'transaction_date' => now(),
                'reference_no' => 'OB-' . $supplier->id,
                'transaction_type' => 'opening_balance',
                'debit' => $request->opening_balance ?? 0,
                'credit' => 0,
                'balance' => $request->opening_balance ?? 0,
                'contact_type' => 'supplier',
                'user_id' => $supplier->id,
            ]);

            return response()->json([
                'status' => 200,
                'message' => "Old Supplier Details Updated Successfully!"
            ]);
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