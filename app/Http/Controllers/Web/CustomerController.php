<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use App\Models\SalesRep;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Location; // Ensure Location model is imported
use App\Models\User; // Ensure User model is imported
use App\Exports\CustomerExport;
use App\Exports\CustomerTemplateExport;
use App\Imports\CustomerImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\BalanceHelper;

class CustomerController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:view customer', ['only' => ['index', 'show', 'Customer']]);
        $this->middleware('permission:create customer', ['only' => ['store']]);
        $this->middleware('permission:edit customer', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete customer', ['only' => ['destroy']]);
        $this->middleware('permission:export customer', ['only' => ['export']]);
        $this->middleware('permission:import customer', ['only' => ['importCustomer', 'importCustomerStore']]);
    }

    public function Customer()
    {
        $cities = City::all();
        $customerGroups = CustomerGroup::all();

        return view('contact.customer.customer', compact('cities', 'customerGroups'));
    }

    public function importCustomer()
    {
        return view('contact.customer.import_customer');
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

        $customers = $customers->map(function ($customer) use ($balances) {
            // Concatenate full name in PHP instead of using accessor
            $fullName = trim(($customer->prefix ? $customer->prefix . ' ' : '') .
                            $customer->first_name . ' ' .
                            ($customer->last_name ?? ''));

            // Get the calculated balance from BalanceHelper (single source of truth)
            $currentBalance = $balances->get($customer->id, (float)$customer->opening_balance);

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
                'current_balance' => (float)$currentBalance, // Accurate balance from unified ledger
                'total_sale_due' => 0.0, // Deprecated - balance is in current_balance
                'total_return_due' => 0.0, // Deprecated - included in current_balance
                'current_due' => (float)$currentBalance, // Same as current_balance
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
            'customer_type' => 'nullable|in:wholesaler,retailer',
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

            // Handle specific database constraint violations
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                $errorMessage = $e->getMessage();

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
            'customer_type' => 'nullable|in:wholesaler,retailer',
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

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => "Customer Details Updated Successfully!",
                    'calculated_credit_limit' => $customerData['credit_limit'] ?? $customer->credit_limit
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                Log::error('Customer update QueryException: ' . $e->getMessage());

                // Handle specific database constraint violations
                if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                    $errorMessage = $e->getMessage();

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
                    'status' => 400,
                    'message' => "Error updating customer. Please check your input and try again."
                ], 400);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Customer update Exception: ' . $e->getMessage());

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
     * Filter customers by cities
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterByCities(Request $request)
    {
        $cityIds = $request->input('city_ids', []);

        if (empty($cityIds)) {
            return response()->json([
                'status' => 400,
                'message' => 'City IDs are required'
            ]);
        }

        $user = auth()->user();

        $customers = Customer::withoutLocationScope()->with(['city']);

        // Check if user is a sales rep
        $isSalesRep = false;
        if ($user) {
            $salesRep = \App\Models\SalesRep::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
            $isSalesRep = (bool) $salesRep;
        }

        $customers = $customers->where(function ($query) use ($cityIds, $isSalesRep) {
            $query->whereIn('city_id', $cityIds);

            // Only include customers without city assignment for non-sales rep users
            if (!$isSalesRep) {
                $query->orWhereNull('city_id');
            }
        });

        // For filterByCities, don't apply additional sales rep filtering
        // The frontend route selection already determines which city IDs to send
        // So we trust the city_ids provided and don't further restrict by sales rep's default route

        $customers = $customers->orderBy('first_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'prefix' => $customer->prefix,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'full_name' => $customer->full_name,
                    'mobile' => $customer->mobile_no,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'city_id' => $customer->city_id,
                    'city_name' => $customer->city?->name ?? '',
                    'customer_type' => $customer->customer_type,
                    'credit_limit' => (float)$customer->credit_limit,
                    'current_balance' => (float)$customer->current_balance,
                ];
            });

        return response()->json([
            'status' => 200,
            'customers' => $customers,
            'total_customers' => $customers->count()
        ]);
    }

    /**
     * Export customers to Excel
     */
    public function export()
    {
        try {
            return Excel::download(new CustomerExport, 'customers_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Customer export failed: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Export failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Get customer credit information including floating balance
     */
    public function getCreditInfo($id)
    {
        try {
            $customer = Customer::with(['sales', 'payments'])->findOrFail($id);

            // Calculate total sales amount
            $totalSales = $customer->sales()->sum('final_total');

            // Calculate total payments received
            $totalPayments = $customer->payments()->sum('amount');

            // Calculate total due (sales - payments)
            $totalDue = $totalSales - $totalPayments;

            // Calculate floating balance from bounced cheques
            $floatingBalance = $customer->payments()
                ->where('payment_method', 'cheque')
                ->where('cheque_status', 'bounced')
                ->sum(DB::raw('amount + COALESCE(bank_charges, 0)'));

            // Get credit limit from customer
            $creditLimit = $customer->credit_limit ?? 0;

            // Calculate available credit
            $availableCredit = $creditLimit - $totalDue;

            $data = [
                'total_due' => $totalDue,
                'credit_limit' => $creditLimit,
                'available_credit' => max(0, $availableCredit),
                'floating_balance' => $floatingBalance,
                'total_sales' => $totalSales,
                'total_payments' => $totalPayments,
            ];

            return response()->json([
                'status' => 200,
                'message' => 'Customer credit info retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching customer credit info: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to fetch customer credit information'
            ], 500);
        }
    }

    /**
     * Record recovery payment for bounced cheques (floating balance)
     */
    public function recordRecoveryPayment(Request $request, $customerId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string|in:cash,bank_transfer,card,upi',
                'payment_date' => 'required|date',
                'notes' => 'nullable|string|max:500',
                'reference_no' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $customer = Customer::findOrFail($customerId);

            // Calculate current floating balance
            $currentFloatingBalance = $customer->payments()
                ->where('payment_method', 'cheque')
                ->where('cheque_status', 'bounced')
                ->sum(DB::raw('amount + COALESCE(bank_charges, 0)'));

            $recoveryAmount = $request->amount;

            // Validate recovery amount doesn't exceed floating balance
            if ($recoveryAmount > $currentFloatingBalance) {
                return response()->json([
                    'status' => 400,
                    'message' => "Recovery amount cannot exceed floating balance of Rs. " . number_format($currentFloatingBalance, 2)
                ], 400);
            }

            DB::beginTransaction();

            // Create recovery payment record
            $payment = $customer->payments()->create([
                'payment_method' => 'floating_balance_recovery',
                'amount' => -$recoveryAmount, // Negative amount to reduce floating balance
                'payment_date' => $request->payment_date,
                'notes' => $request->notes ?? 'Recovery payment for bounced cheques',
                'reference_no' => $request->reference_no,
                'actual_payment_method' => $request->payment_method,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Calculate new balances
            $newFloatingBalance = $currentFloatingBalance - $recoveryAmount;
            $totalDue = $customer->sales()->sum('final_total') - $customer->payments()->sum('amount');

            $balanceUpdate = [
                'payment_amount' => $recoveryAmount,
                'old_floating_balance' => $currentFloatingBalance,
                'new_floating_balance' => $newFloatingBalance,
                'total_outstanding' => max(0, $totalDue + $newFloatingBalance),
            ];

            return response()->json([
                'status' => 200,
                'message' => 'Recovery payment recorded successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'balance_update' => $balanceUpdate
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording recovery payment: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to record recovery payment'
            ], 500);
        }
    }

    /**
     * Export blank customer import template
     */
    public function exportCustomerBlankTemplate()
    {
        return Excel::download(new CustomerTemplateExport(true), 'Import_Customer_Template.xlsx');
    }

    /**
     * Export all customers with data
     */
    public function exportCustomers()
    {
        return Excel::download(new CustomerTemplateExport(), 'Customers_Export_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Import customers from Excel file
     */
    public function importCustomerStore(Request $request)
    {
        // Validate the request file and optional city
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
            'import_city' => 'nullable|integer|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Use default location ID 1
        $selectedLocationId = 1;

        // Get optional city override (if selected, all customers will use this city)
        $overrideCityId = $request->input('import_city') ? (int)$request->input('import_city') : null;

        // Check if the file is present in the request
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Check if file upload was successful
            if ($file->isValid()) {
                // Create an instance of the import class with optional city override
                $import = new CustomerImport($selectedLocationId, $overrideCityId);

                // Process the Excel file
                Excel::import($import, $file);

                // Get validation errors from the import process
                $validationErrors = $import->getValidationErrors();
                $records = $import->getData();

                // If there are validation errors, return them in the response
                if (!empty($validationErrors)) {
                    return response()->json([
                        'status' => 401,
                        'validation_errors' => $validationErrors,
                    ]);
                }

                return response()->json([
                    'status' => 200,
                    'data' => $records,
                    'message' => "Import Customers Excel file uploaded successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "File upload failed. Please try again."
                ]);
            }
        }

        return response()->json([
            'status' => 400,
            'message' => "No file uploaded or file is invalid."
        ]);
    }
}
