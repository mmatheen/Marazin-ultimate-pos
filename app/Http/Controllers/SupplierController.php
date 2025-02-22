<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Ledger;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
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
                'prefix' => 'required|string|max:10',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'mobile_no' => 'required|numeric|digits_between:10,15',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:255',
                'opening_balance' => 'required|numeric',
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
                'email' => $request->email ?? '',
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
        $validator = Validator::make(
            $request->all(),
            [
                'prefix' => 'required|string|max:10',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'mobile_no' => 'required|numeric|digits_between:10,15',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:255',
                'opening_balance' => 'required|numeric',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $supplier = Supplier::find($id);

            if ($supplier) {
                $supplier->update([
                    'prefix' => $request->prefix,
                    'first_name' => $request->first_name ?? '',
                    'last_name' => $request->last_name ?? '',
                    'mobile_no' => $request->mobile_no ?? '',
                    'email' => $request->email ?? '',
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
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Supplier Found!"
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