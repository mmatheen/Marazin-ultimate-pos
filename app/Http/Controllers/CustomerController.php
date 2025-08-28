<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use App\Models\SalesRep;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:view customer', ['only' => ['index', 'show', 'Customer']]);
        $this->middleware('permission:create customer', ['only' => ['store']]);
        $this->middleware('permission:edit customer', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete customer', ['only' => ['destroy']]);
    }

    public function Customer()
    {
        $cities = City::all();
        $customerGroups = CustomerGroup::all();

        return view('contact.customer.customer', compact('cities', 'customerGroups'));
    }

  
    public function index()
{
    /** @var User $user */
    $user = auth()->user();

    if (!$user) {
        return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
    }

    $query = Customer::withoutLocationScope()
        ->with(['sales', 'salesReturns', 'payments', 'city']);

    if ($user->isSalesRep()) {
        $query = $this->applySalesRepFilter($query, $user);
    }

    $customers = $query->orderBy('first_name')->get()->map(function ($customer) {
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
            'opening_balance' => (float)$customer->opening_balance,
            'current_balance' => (float)$customer->current_balance,
            'total_sale_due' => (float)$customer->total_sale_due,
            'total_return_due' => (float)$customer->total_return_due,
            'current_due' => (float)$customer->current_due,
            'city_id' => $customer->city_id,
            'city_name' => $customer->city?->name ?? '',
            'credit_limit' => (float)$customer->credit_limit,
        ];
    });

    return response()->json([
        'status' => 200,
        'message' => $customers,
        'total_customers' => $customers->count(),
        'sales_rep_info' => $user->isSalesRep() ? $this->getSalesRepInfo($user) : null
    ]);
}

    private function applySalesRepFilter($query, $user)
    {
        $salesRep = SalesRep::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['route.cities'])
            ->first();

        if ($salesRep && $salesRep->route) {
            $routeCityIds = $salesRep->route->cities->pluck('id')->toArray();

            if (!empty($routeCityIds)) {
                $query->whereIn('city_id', $routeCityIds);
            } else {
                $query->whereNull('city_id');
            }
        }

        return $query;
    }

    private function getSalesRepInfo($user)
    {
        $salesRep = SalesRep::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['route.cities'])
            ->first();

        if (!$salesRep || !$salesRep->route) {
            return null;
        }

        return [
            'route_name' => $salesRep->route->name,
            'assigned_cities' => $salesRep->route->cities->pluck('name')->toArray(),
            'total_cities' => $salesRep->route->cities->count(),
            'sales_rep_id' => $salesRep->id
        ];
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
            'credit_limit' => 'nullable|numeric|min:0',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        try {
            DB::beginTransaction();

            $customerData = $request->only([
                'prefix',
                'first_name',
                'last_name',
                'mobile_no',
                'email',
                'address',
                'opening_balance',
                'credit_limit',
                'city_id',
            ]);

            // Auto-calculate credit limit if not provided but city is selected
            if (!$request->has('credit_limit') || $request->credit_limit === null) {
                if ($request->city_id) {
                    $customerData['credit_limit'] = Customer::calculateCreditLimitForCity($request->city_id);
                }
            }

            $customer = Customer::create($customerData);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => "New Customer Created Successfully!",
                'calculated_credit_limit' => $customerData['credit_limit'] ?? 0
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => "Error creating customer: " . $e->getMessage()
            ]);
        }
    }

    public function show(int $id)
    {
        $customer = Customer::with(['city'])->find($id);
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
            'credit_limit' => 'nullable|numeric|min:0',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $customer = Customer::find($id);
        if ($customer) {
            try {
                DB::beginTransaction();

                $customerData = $request->only([
                    'prefix',
                    'first_name',
                    'last_name',
                    'mobile_no',
                    'email',
                    'address',
                    'opening_balance',
                    'credit_limit',
                    'city_id',
                ]);

                // Auto-calculate credit limit if city changed and credit limit wasn't manually provided
                if ($request->city_id != $customer->city_id) {
                    // Check if the current credit limit matches the calculated one for the old city
                    $oldCalculatedLimit = Customer::calculateCreditLimitForCity($customer->city_id);

                    if ($customer->credit_limit == $oldCalculatedLimit && (!$request->has('credit_limit') || $request->credit_limit === null)) {
                        $customerData['credit_limit'] = Customer::calculateCreditLimitForCity($request->city_id);
                    }
                }

                $customer->update($customerData);

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => "Customer Details Updated Successfully!",
                    'calculated_credit_limit' => $customerData['credit_limit'] ?? $customer->credit_limit
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => "Error updating customer: " . $e->getMessage()
                ]);
            }
        }

        return response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }

    public function destroy(int $id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            try {
                DB::beginTransaction();

                // Check if customer has any sales, returns, or payments
                if (
                    $customer->sales()->count() > 0 ||
                    $customer->salesReturns()->count() > 0 ||
                    $customer->payments()->count() > 0
                ) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Cannot delete customer with existing transactions!'
                    ]);
                }

                $customer->delete();

                DB::commit();

                return response()->json(['status' => 200, 'message' => "Customer Details Deleted Successfully!"]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => "Error deleting customer: " . $e->getMessage()
                ]);
            }
        }

        return response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }

    /**
     * Get calculated credit limit for a city
     */
    public function getCreditLimitForCity(Request $request)
    {
        $cityId = $request->input('city_id');

        if (!$cityId) {
            return response()->json([
                'status' => 400,
                'message' => 'City ID is required'
            ]);
        }

        $creditLimit = Customer::calculateCreditLimitForCity($cityId);

        return response()->json([
            'status' => 200,
            'credit_limit' => $creditLimit
        ]);
    }

    /**
     * Get customers for a specific route
     * 
     * @param int $routeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomersByRoute($routeId)
    {
        $route = \App\Models\Route::with('cities')->find($routeId);

        if (!$route) {
            return response()->json([
                'status' => 404,
                'message' => 'Route not found'
            ]);
        }

        $routeCityIds = $route->cities->pluck('id')->toArray();

        $query = Customer::with(['city']);

        if (!empty($routeCityIds)) {
            $query->where(function ($q) use ($routeCityIds) {
                $q->whereIn('city_id', $routeCityIds)
                    ->orWhereNull('city_id');
            });
        } else {
            $query->whereNull('city_id');
        }

        $customers = $query->orderBy('first_name')->get();

        return response()->json([
            'status' => 200,
            'route' => [
                'id' => $route->id,
                'name' => $route->name,
                'cities' => $route->cities->pluck('name')->toArray()
            ],
            'customers' => $customers,
            'total_customers' => $customers->count()
        ]);
    }
}
