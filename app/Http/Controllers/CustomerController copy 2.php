<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use App\Models\SalesRep;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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

    // public function index()
    // {
    //     $customers = Customer::with(['sales', 'salesReturns', 'payments', 'city'])->get()->map(function ($customer) {
    //         return [
    //             'id' => $customer->id,
    //             'prefix' => $customer->prefix,
    //             'first_name' => $customer->first_name,
    //             'last_name' => $customer->last_name,
    //             'full_name' => $customer->full_name,
    //             'mobile_no' => $customer->mobile_no,
    //             'email' => $customer->email,
    //             'address' => $customer->address,
    //             'location_id' => $customer->location_id,
    //             'opening_balance' => $customer->opening_balance,
    //             'current_balance' => $customer->current_balance,
    //             'total_sale_due' => $customer->total_sale_due,
    //             'total_return_due' => $customer->total_return_due,
    //             'current_due' => $customer->current_due,
    //             'city_id' => $customer->city_id,
    //             'city_name' => $customer->city ? $customer->city->name : 'N/A',
    //             'credit_limit' => $customer->credit_limit,
    //         ];
    //     });

    //     return response()->json([
    //         'status' => 200,
    //         'message' => $customers,
    //     ]);
    // }

    public function index()
    {
        $user = auth()->user();

        // Base query with eager loading
        $query = Customer::with(['city'])
            ->withSum(['sales as total_sale_due' => function ($q) {
                $q->where('payment_status', '!=', 'Paid');
            }], 'total_due')
            ->withSum('salesReturns as total_return_due', 'total_due')
            ->withSum('payments as total_paid', 'amount');

        $salesRepInfo = null;
        $isSuperAdmin = $user->user_name === 'admin'; // Adjust condition based on your role system

        if (!$isSuperAdmin) {
            // === Sales Rep Logic: Filter by assigned route cities ===
            $salesRep = SalesRep::where('user_id', $user->id)
                ->where('status', 'active')
                ->with(['route.cities'])
                ->first();

            if ($salesRep && $salesRep->route && $salesRep->route->cities->isNotEmpty()) {
                $routeCityIds = $salesRep->route->cities->pluck('id')->toArray();
                $query->whereIn('city_id', $routeCityIds);
                $salesRepInfo = $this->getSalesRepInfo($user);
            } else {
                // No route or no cities assigned
                return response()->json([
                    'status' => 200,
                    'message' => 'No customers assigned to your route.',
                    'data' => [],
                    'total_customers' => 0,
                    'sales_rep_info' => null,
                ]);
            }
        }
        // If super admin, optionally filter by city_id from request
        else {
            $cityId = request()->input('city_id');
            if ($cityId) {
                $query->where('city_id', $cityId);
            }
            // Otherwise, show all customers (with city_id NOT NULL)
            $query->whereNotNull('city_id');
        }

        // Execute query
        $customers = $query->orderBy('first_name')->get();

        // Recalculate balance (if needed)
        $customers->each->recalculateCurrentBalance();

        // Transform data
        $data = $customers->map(function ($customer) {
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
                'opening_balance' => (float) $customer->opening_balance,
                'current_balance' => (float) $customer->current_balance,
                'total_sale_due' => (float) ($customer->total_sale_due ?? 0),
                'total_return_due' => (float) ($customer->total_return_due ?? 0),
                'current_due' => (float) $customer->current_due,
                'city_id' => $customer->city_id,
                'city_name' => $customer->city?->name ?? 'Unknown City',
                'credit_limit' => (float) $customer->credit_limit,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Customers retrieved successfully',
            'data' => $data,
            'total_customers' => $data->count(),
            'sales_rep_info' => $isSuperAdmin ? null : $salesRepInfo,
        ]);
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
                'message' => 'City ID is required',
                'data' => null,
            ], 400);
        }

        $creditLimit = Customer::calculateCreditLimitForCity($cityId);

        return response()->json([
            'status' => 200,
            'message' => 'Credit limit retrieved successfully',
            'data' => [
                'city_id' => (int)$cityId,
                'credit_limit' => (float)$creditLimit,
            ],
        ]);
    }

    public function getCustomersByRoute($routeId)
    {
        $route = \App\Models\Route::with('cities')->find($routeId);

        if (!$route) {
            return response()->json([
                'status' => 404,
                'message' => 'Route not found',
                'data' => null,
            ], 404);
        }

        $routeCityIds = $route->cities->pluck('id')->toArray();

        if (empty($routeCityIds)) {
            return response()->json([
                'status' => 200,
                'message' => 'Route has no cities assigned.',
                'data' => [
                    'route' => [
                        'id' => $route->id,
                        'name' => $route->name,
                        'cities' => [],
                    ],
                    'customers' => [],
                    'total_customers' => 0,
                ],
            ]);
        }

        $customers = Customer::with(['city'])
            ->whereIn('city_id', $routeCityIds) // Only city-based, no null
            ->orderBy('first_name')
            ->get();

        $data = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'mobile_no' => $customer->mobile_no,
                'email' => $customer->email,
                'city_id' => $customer->city_id,
                'city_name' => $customer->city?->name ?? 'Unknown',
                'current_balance' => (float)$customer->current_balance,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Customers by route retrieved successfully',
            'data' => [
                'route' => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'cities' => $route->cities->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
                ],
                'customers' => $data,
                'total_customers' => $data->count(),
            ],
        ]);
    }

    private function getSalesRepInfo($user)
    {
        $salesRep = SalesRep::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['route' => function ($q) {
                $q->with('cities'); // include cities
            }])
            ->first();

        if (!$salesRep) {
            return null;
        }

        return [
            'id' => $salesRep->id,
            'name' => $salesRep->user?->user_name ?? 'No Name',
            'route_id' => $salesRep->route?->id,
            'route_name' => $salesRep->route?->name,
            'assigned_cities' => $salesRep->route?->cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'district' => $city->district,
                    'province' => $city->province,
                ];
            })->all(),
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
}
