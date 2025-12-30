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
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        $cities = Cache::remember('cities_list', 3600, function() {
            return City::select('id', 'name', 'district', 'province')->get();
        });
        $customerGroups = Cache::remember('customer_groups_list', 3600, function() {
            return CustomerGroup::select('id', 'customerGroupName')->get();
        });

        return view('contact.customer.customer', compact('cities', 'customerGroups'));
    }


  public function index()
{
    /** @var User $user */
    $user = auth()->user();

    if (!$user) {
        return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
    }

    // Check if user is a sales rep and get ALL active assignments
    $salesRepAssignments = \App\Models\SalesRep::where('user_id', $user->id)
        ->where('status', 'active')
        ->with(['route.cities'])
        ->get();

    // Start with bypassing location scope, only eager load city relation
    $query = Customer::withoutLocationScope()
        ->with('city:id,name')
        ->select([
            'id', 'prefix', 'first_name', 'last_name', 'mobile_no', 'email',
            'address', 'location_id', 'opening_balance', 'credit_limit',
            'city_id', 'customer_type'
        ]);

    // Apply sales rep route filtering if user is a sales rep
    if ($salesRepAssignments->isNotEmpty()) {
        $query = $this->applySalesRepFilter($query, $salesRepAssignments);
    }

    $customers = $query->orderBy('first_name')->get();

    // Fetch all customer balances in one optimized query using BalanceHelper
    $customerIds = $customers->pluck('id')->toArray();
    $balances = BalanceHelper::getBulkCustomerBalances($customerIds);

    // Fetch sales dues from sales table (optimized bulk query)
    $salesDues = DB::table('sales')
        ->whereIn('customer_id', $customerIds)
        ->whereIn('status', ['final', 'suspend'])
        ->select('customer_id', DB::raw('SUM(total_due) as total_sale_due'))
        ->groupBy('customer_id')
        ->pluck('total_sale_due', 'customer_id');

    // Fetch return dues from sales_returns table (optimized bulk query)
    $returnDues = DB::table('sales_returns')
        ->whereIn('customer_id', $customerIds)
        ->select('customer_id', DB::raw('SUM(total_due) as total_return_due'))
        ->groupBy('customer_id')
        ->pluck('total_return_due', 'customer_id');

    $customers = $customers->map(function ($customer) use ($balances, $salesDues, $returnDues) {
        // Concatenate full name in PHP instead of using accessor
        $fullName = trim(($customer->prefix ? $customer->prefix . ' ' : '') .
                        $customer->first_name . ' ' .
                        ($customer->last_name ?? ''));

        // Get the calculated balance from BalanceHelper (single source of truth)
        $currentBalance = $balances->get($customer->id, (float)$customer->opening_balance);

        // Get actual sales and return dues from respective tables
        $totalSaleDue = (float)($salesDues->get($customer->id, 0));
        $totalReturnDue = (float)($returnDues->get($customer->id, 0));

        return [
            'id' => $customer->id,
            'prefix' => $customer->prefix,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'full_name' => $fullName,
            'mobile_no' => $customer->mobile_no,
            'email' => $customer->email,
            'address' => $customer->address,
            'location_id' => $customer->location_id,
            'opening_balance' => (float)$customer->opening_balance,
            'current_balance' => (float)$currentBalance, // ✅ Accurate balance from unified ledger
            'total_sale_due' => $totalSaleDue, // ✅ Actual unpaid sales from sales table
            'total_return_due' => $totalReturnDue, // ✅ Actual returns from sales_returns table
            'current_due' => (float)max(0, $currentBalance), // Only positive balances (customer owes)
            'city_id' => $customer->city_id,
            'city_name' => $customer->city?->name ?? '',
            'credit_limit' => (float)$customer->credit_limit,
            'customer_type' => $customer->customer_type,
        ];
    });

    return response()->json([
        'status' => 200,
        'message' => $customers,
        'total_customers' => $customers->count(),
        'sales_rep_info' => $salesRepAssignments->isNotEmpty() ? $this->getSalesRepInfoFromAssignments($salesRepAssignments) : null
    ]);
}

    /**
     * Apply sales rep route-based filtering to customer query
     * Only shows customers in cities assigned to the sales rep's routes
     * Supports multiple route assignments per sales rep
     */
    private function applySalesRepFilter($query, $salesRepAssignments)
    {
        // Collect all city IDs from all assigned routes
        $allCityIds = [];

        foreach ($salesRepAssignments as $assignment) {
            if ($assignment->route && $assignment->route->cities) {
                $cityIds = $assignment->route->cities->pluck('id')->toArray();
                $allCityIds = array_merge($allCityIds, $cityIds);
            }
        }

        // Remove duplicates
        $allCityIds = array_unique($allCityIds);

        if (!empty($allCityIds)) {
            // Filter customers by all assigned cities from all routes
            $query->whereIn('city_id', $allCityIds);
        } else {
            // If no cities in any route, show no customers (empty result)
            $query->whereRaw('1 = 0');
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

    private function getSalesRepInfoFromCache($salesRep)
    {
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

    /**
     * Get sales rep info from multiple route assignments
     */
    private function getSalesRepInfoFromAssignments($salesRepAssignments)
    {
        $allRoutes = [];
        $allCities = [];
        $salesRepIds = [];

        foreach ($salesRepAssignments as $assignment) {
            if ($assignment->route) {
                $allRoutes[] = $assignment->route->name;
                if ($assignment->route->cities) {
                    $cities = $assignment->route->cities->pluck('name')->toArray();
                    $allCities = array_merge($allCities, $cities);
                }
            }
            $salesRepIds[] = $assignment->id;
        }

        // Remove duplicate city names
        $allCities = array_unique($allCities);
        $allRoutes = array_unique($allRoutes);

        return [
            'routes' => $allRoutes,
            'route_names' => implode(', ', $allRoutes),
            'assigned_cities' => array_values($allCities),
            'total_cities' => count($allCities),
            'total_routes' => count($allRoutes),
            'sales_rep_ids' => $salesRepIds,
            'total_assignments' => $salesRepAssignments->count()
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
            $existingCustomer = Customer::withoutLocationScope()->where('mobile_no', $request->mobile_no)->first();
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
                $existingCustomerByEmail = Customer::withoutLocationScope()->where('email', $request->email)->first();
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
            if (strpos($errorMessage, 'Duplicate') !== false || strpos($errorMessage, '1062') !== false || strpos($errorMessage, 'Integrity constraint violation') !== false) {
                // Check for mobile number duplicate
                if (strpos($errorMessage, 'mobile') !== false || strpos($errorMessage, 'mobile_no') !== false || strpos($errorMessage, 'customers_mobile_no_unique') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'mobile_no' => ['This mobile number is already registered with another customer.']
                        ]
                    ], 400);
                }

                // Check for email duplicate
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
                'status' => 500,
                'message' => "Error creating customer. Please try again."
            ], 500);
        }
    }

    public function show(int $id)
    {
        $customer = Customer::withoutLocationScope()->with(['city'])->find($id);
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

        $customer = Customer::withoutLocationScope()->find($id);
        if ($customer) {
            try {
                DB::beginTransaction();

                // Check for duplicate mobile number before updating (excluding current customer)
                $existingCustomer = Customer::withoutLocationScope()
                    ->where('mobile_no', $request->mobile_no)
                    ->where('id', '!=', $id)
                    ->first();
                if ($existingCustomer) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'mobile_no' => ['This mobile number is already registered with another customer.']
                        ]
                    ], 400);
                }

                // Check for duplicate email if provided (excluding current customer)
                if ($request->email) {
                    $existingCustomerByEmail = Customer::withoutLocationScope()
                        ->where('email', $request->email)
                        ->where('id', '!=', $id)
                        ->first();
                    if ($existingCustomerByEmail) {
                        return response()->json([
                            'status' => 400,
                            'errors' => [
                                'email' => ['This email address is already registered with another customer.']
                            ]
                        ], 400);
                    }
                }

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

                // Check if this is a duplicate entry error that wasn't caught above
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'Duplicate') !== false || strpos($errorMessage, '1062') !== false || strpos($errorMessage, 'Integrity constraint violation') !== false) {
                    // Check for mobile number duplicate
                    if (strpos($errorMessage, 'mobile') !== false || strpos($errorMessage, 'mobile_no') !== false || strpos($errorMessage, 'customers_mobile_no_unique') !== false) {
                        return response()->json([
                            'status' => 400,
                            'errors' => [
                                'mobile_no' => ['This mobile number is already registered with another customer.']
                            ]
                        ], 400);
                    }

                    // Check for email duplicate
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
                    'status' => 500,
                    'message' => "Error updating customer. Please try again."
                ], 500);
            }
        }

        return response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }

    public function destroy(int $id)
    {

        if ($id == 1) {
            return response()->json([
                'status' => 403,
                'message' => 'Cannot delete Walk-In Customer! This is a system-protected customer.'
            ], 403);
        }
        $customer = Customer::withoutLocationScope()->find($id);
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

        $query = Customer::withoutLocationScope()->with(['city']);

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

        $user = auth()->user();

        // Get customers from these cities + walk-in customers
        $query = Customer::withoutLocationScope()->with(['city']);

        // Check if user is a sales rep
        $isSalesRep = false;
        if ($user) {
            $salesRep = \App\Models\SalesRep::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
            $isSalesRep = (bool) $salesRep;
        }

        if (!empty($cityIds)) {
            $query->where(function ($q) use ($cityIds, $isSalesRep) {
                $q->whereIn('city_id', $cityIds);

                // Only include customers without city assignment for non-sales rep users
                if (!$isSalesRep) {
                    $q->orWhereNull('city_id');
                }
            });
        } else {
            // If no cities found, only show walk-in customers
            $query->whereNull('city_id');
        }

        // For filterByCities, don't apply additional sales rep filtering
        // The frontend route selection already determines which city IDs to send
        // So we trust the city_ids provided and don't further restrict by sales rep's default route

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
