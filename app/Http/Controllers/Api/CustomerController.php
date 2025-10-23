<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use App\Models\SalesRep;
use App\Models\User;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    protected $unifiedLedgerService;

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        // $this->middleware('permission:view customer', ['only' => ['index', 'show', 'Customer']]);
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

    // Use normal query with location scope - it will handle sales rep filtering automatically
    $query = Customer::with(['sales', 'salesReturns', 'payments', 'city']);

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

    /**
     * Apply filter for sales reps based on cities in their active route assignments
     */
    private function applySalesRepFilter($query, $user)
    {
        // Get all **active** SalesRep assignments for this user
        $salesRepAssignments = SalesRep::where('user_id', $user->id)
            ->where('status', 'active') // SalesRep assignment is active
            ->with(['route' => function ($q) {
                $q->where('status', 'active'); // Only include routes that are active
            }, 'route.cities'])
            ->get();

        // Extract all city IDs from all active routes
        $cityIds = $salesRepAssignments
            ->pluck('route.cities') // Get cities from each route
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();

        if (!empty($cityIds)) {
            $query->whereIn('city_id', $cityIds);
        } else {
            // No cities assigned → only show walk-in customers (city_id = null)
            $query->whereNull('city_id');
        }

        return $query;
    }

    private function getSalesRepInfo($user)
    {
        $salesRepAssignments = SalesRep::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['route' => function ($q) {
                $q->where('status', 'active');
            }, 'route.cities'])
            ->get();

        if ($salesRepAssignments->isEmpty() || !$salesRepAssignments->contains('route')) {
            return null;
        }

        // Collect all unique cities across all active routes
        $allCities = $salesRepAssignments
            ->pluck('route.cities')
            ->flatten()
            ->unique('id');

        return [
            'assigned_routes' => $salesRepAssignments->pluck('route.name')->filter()->toArray(),
            'total_routes'    => $salesRepAssignments->count(),
            'assigned_cities' => $allCities->pluck('name')->toArray(),
            'total_cities'    => $allCities->count(),
            'sales_rep_id'    => $salesRepAssignments->first()->id,
        ];
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mobile_no' => 'required|numeric|digits_between:10,15|unique:customers,mobile_no',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric',
            'credit_limit' => 'nullable|numeric|min:0',
            'city_id' => 'nullable|integer|exists:cities,id',
            'customer_type' => 'required|in:wholesaler,retailer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Check for duplicate mobile number before creating
            $existingCustomer = Customer::where('mobile_no', $request->mobile_no)->first();
            if ($existingCustomer) {
                return response()->json([
                    'status' => 400,
                    'errors' => [
                        'mobile_no' => ['This mobile number is already registered with another customer.']
                    ]
                ], 400);
            }

            // Check for duplicate email if provided
            if ($request->email) {
                $existingCustomerByEmail = Customer::where('email', $request->email)->first();
                if ($existingCustomerByEmail) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'email' => ['This email address is already registered with another customer.']
                        ]
                    ], 400);
                }
            }

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
                'customer_type',
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
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Customer creation QueryException: ' . $e->getMessage());
            
            // Handle specific database constraint violations as fallback
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                $errorMessage = $e->getMessage();
                
                // Check for any mobile number related duplicate
                if (strpos($errorMessage, 'mobile') !== false || strpos($errorMessage, 'mobile_no') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'mobile_no' => ['This mobile number is already registered with another customer.']
                        ]
                    ], 400);
                }
                
                // Check for any email related duplicate
                if (strpos($errorMessage, 'email') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'email' => ['This email address is already registered with another customer.']
                        ]
                    ], 400);
                }
                
                return response()->json([
                    'status' => 400,
                    'message' => 'A customer with these details already exists.'
                ], 400);
            }
            
            return response()->json([
                'status' => 400,
                'message' => "Error creating customer. Please check your input and try again."
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer creation Exception: ' . $e->getMessage());
            
            // Check if this is a duplicate entry error that wasn't caught above
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Duplicate') !== false || strpos($errorMessage, '1062') !== false) {
                if (strpos($errorMessage, 'mobile') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'mobile_no' => ['This mobile number is already registered with another customer.']
                        ]
                    ], 400);
                }
                return response()->json([
                    'status' => 400,
                    'message' => 'A customer with these details already exists.'
                ], 400);
            }
            
            return response()->json([
                'status' => 500,
                'message' => "Error creating customer. Please try again."
            ], 500);
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
            'mobile_no' => 'required|numeric|digits_between:10,15|unique:customers,mobile_no,' . $id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric',
            'credit_limit' => 'nullable|numeric|min:0',
            'city_id' => 'nullable|integer|exists:cities,id',
            'customer_type' => 'required|in:wholesaler,retailer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ], 400);
        }

        $customer = Customer::find($id);
        if ($customer) {
            try {
                DB::beginTransaction();

                // Store old opening balance for ledger adjustment
                $oldOpeningBalance = $customer->opening_balance;
                $newOpeningBalance = $request->input('opening_balance', $oldOpeningBalance);

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
                    'customer_type',
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

                // Handle opening balance adjustment in ledger
                if ($oldOpeningBalance != $newOpeningBalance) {
                    $this->unifiedLedgerService->recordOpeningBalanceAdjustment(
                        $customer->id,
                        'customer',
                        $oldOpeningBalance,
                        $newOpeningBalance,
                        'Opening balance updated via API'
                    );
                }

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => "Customer Details Updated Successfully!",
                    'calculated_credit_limit' => $customerData['credit_limit'] ?? $customer->credit_limit
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                
                // Handle specific database constraint violations
                if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                    $errorMessage = $e->getMessage();
                    
                if (strpos($errorMessage, 'customers_mobile_no_unique') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'mobile_no' => ['This mobile number is already registered with another customer.']
                        ]
                    ], 400);
                }                    if (strpos($errorMessage, 'customers_email_unique') !== false) {
                        return response()->json([
                            'status' => 400,
                            'errors' => [
                                'email' => ['This email address is already registered with another customer.']
                            ]
                        ]);
                    }
                    
                    return response()->json([
                        'status' => 400,
                        'message' => 'A customer with these details already exists.'
                    ]);
                }
                
                // Handle null constraint violations
                if ($e->errorInfo[1] == 1048) { // Cannot be null error code
                    $errorMessage = $e->getMessage();
                    
                    if (strpos($errorMessage, 'customer_type') !== false) {
                        return response()->json([
                            'status' => 400,
                            'errors' => [
                                'customer_type' => ['Customer type is required and must be either wholesaler or retailer.']
                            ]
                        ]);
                    }
                }
                
                Log::error('Customer update error: ' . $e->getMessage());
                return response()->json([
                    'status' => 400,
                    'message' => "Error updating customer. Please check your input and try again."
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Customer update error: ' . $e->getMessage());
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

    /**
     * Filter customers by city names
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterByCities(Request $request)
    {
        // Accept either city_ids (array of integers) or cities (array of strings)
        $validator = Validator::make($request->all(), [
            'city_ids' => 'sometimes|required_without:cities|array',
            'city_ids.*' => 'integer',
            'cities' => 'sometimes|required_without:city_ids|array',
            'cities.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid cities data',
                'errors' => $validator->messages()
            ], 400);
        }

        $cityIds = [];

        // If city_ids are provided directly, use them
        if ($request->has('city_ids') && !empty($request->city_ids)) {
            $cityIds = $request->city_ids;
        }
        // Otherwise, convert city names to IDs
        elseif ($request->has('cities') && !empty($request->cities)) {
            $cityNames = array_map('strtolower', $request->cities);
            // Get city IDs from names
            $cityIds = City::whereIn(DB::raw('LOWER(name)'), $cityNames)
                ->pluck('id')
                ->toArray();
        }

        // Get customers from these cities + walk-in customers
        $query = Customer::with(['city']);

        if (!empty($cityIds)) {
            $query->where(function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds)
                    ->orWhereNull('city_id'); // Include walk-in customers
            });
        } else {
            // If no cities found, only show walk-in customers
            $query->whereNull('city_id');
        }

        $customers = $query->orderBy('first_name')->get();

        return response()->json([
            'status' => true,
            'message' => 'Customers filtered successfully',
            'customers' => $customers,
            'found_city_ids' => $cityIds,
            'total_customers' => $customers->count()
        ]);
    }
}
