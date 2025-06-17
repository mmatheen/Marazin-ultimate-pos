<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:view customer', ['only' => ['index', 'show','Customer']]);
        $this->middleware('permission:create customer', ['only' => ['store']]);
        $this->middleware('permission:edit customer', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete customer', ['only' => ['destroy']]);
    }

    public function Customer()
    {
        return view('contact.customer.customer');
    }

    public function index()
    {
        $customers = Customer::with(['sales', 'salesReturns', 'payments'])->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'prefix' => $customer->prefix,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'full_name' => $customer->full_name,
                'mobile_no' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'location_id' => $customer->location_id,
                'opening_balance' => $customer->opening_balance,
                'current_balance' => $customer->current_balance,
                'total_sale_due' => $customer->total_sale_due,
                'total_return_due' => $customer->total_return_due,
                'current_due' => $customer->current_due,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => $customers,
        ]);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mobile_no' => 'required|numeric|digits_between:10,15',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $customer = Customer::create($request->only([
            'prefix',
            'first_name',
            'last_name',
            'mobile_no',
            'email',
            'address',
            'opening_balance',
        ]));

        return $customer ? response()->json(['status' => 200, 'message' => "New Customer Created Successfully!"])
            : response()->json(['status' => 500, 'message' => "Something went wrong!"]);
    }

    public function show(int $id)
    {
        $customer = Customer::find($id);
        return $customer ? response()->json(['status' => 200, 'customer' => $customer])
            : response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }

    public function edit(int $id)
    {
        return $this->show($id); // Reuse the show logic
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mobile_no' => 'required|numeric|digits_between:10,15',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $customer = Customer::find($id);
        if ($customer) {
            $customer->update($request->only([
                'prefix',
                'first_name',
                'last_name',
                'mobile_no',
                'email',
                'address',
                'opening_balance',
                // 'location_id',
            ]));

            return response()->json(['status' => 200, 'message' => "Customer Details Updated Successfully!"]);
        }

        return response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }

    public function destroy(int $id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->delete();
            return response()->json(['status' => 200, 'message' => "Customer Details Deleted Successfully!"]);
        }

        return response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }
}
