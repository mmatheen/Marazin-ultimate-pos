<?php

namespace App\Http\Controllers;

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
use App\Exports\CustomerExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    protected $unifiedLedgerService;

    function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->middleware('permission:view customer', ['only' => ['index', 'show', 'Customer']]);
        $this->middleware('permission:create customer', ['only' => ['store']]);
        $this->middleware('permission:edit customer', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete customer', ['only' => ['destroy']]);
        $this->middleware('permission:export customer', ['only' => ['export']]);
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


    public function index(Request $request)
{
    /** @var User $user */
    $user = auth()->user();

    if (!$user) {
        return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
    }

    // OPTIMIZATION: If simple=true, return only id, first_name, last_name for dropdowns (fast!)
    if ($request->query('simple') === 'true' || $request->query('simple') === '1') {
        $query = Customer::withoutGlobalScopes()
            ->select(['id', 'first_name', 'last_name']);

        // Apply sales rep route filtering if user is a sales rep
        if ($user->isSalesRep()) {
            $query = $this->applySalesRepFilter($query, $user);
        }

        $customers = $query->orderBy('first_name')->get();

        return response()->json([
            'status' => true,
            'message' => $customers
        ]);
    }

    // Start with bypassing all global scopes to ensure accurate balance calculations
    $query = Customer::withoutGlobalScopes()
        ->with('city:id,name')
        ->select([
            'id', 'prefix', 'first_name', 'last_name', 'mobile_no', 'email',
            'address', 'location_id', 'opening_balance', 'credit_limit',
            'city_id', 'customer_type'
        ]);

    // Apply sales rep route filtering if user is a sales rep
    if ($user->isSalesRep()) {
        $query = $this->applySalesRepFilter($query, $user);
    }

    $customers = $query->orderBy('first_name')->get();

    // Fetch all customer balances in one optimized query using BalanceHelper (single source of truth)
    $customerIds = $customers->pluck('id')->toArray();
    $balances = BalanceHelper::getBulkCustomerBalances($customerIds);
    $advances = BalanceHelper::getBulkCustomerAdvances($customerIds);

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

    $customers = $customers->map(function ($customer) use ($balances, $advances, $salesDues, $returnDues) {
        // Concatenate full name in PHP instead of using accessor to avoid N+1
        $fullName = trim(($customer->prefix ? $customer->prefix . ' ' : '') .
                        $customer->first_name . ' ' .
                        ($customer->last_name ?? ''));

        // Get the calculated balance from BalanceHelper (single source of truth)
        $currentBalance = $balances->get($customer->id, (float)$customer->opening_balance);
        $advanceCredit = $advances->get($customer->id, 0);

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
            'total_advance_credit' => (float)$advanceCredit, // ✅ Advance credit from overpayments
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

    /**
     * Get customers who have bounced cheques (for recovery payment dropdown)
     */
    public function getCustomersWithBouncedCheques()
    {
        try {
            $customers = Customer::whereHas('payments', function($query) {
                $query->where('payment_method', 'cheque')
                      ->where('cheque_status', 'bounced');
            })
            ->where('full_name', '!=', 'Walk-in Customer') // Exclude walk-in customers
            ->where('id', '!=', 1) // Exclude default walk-in customer ID
            ->select('id', 'full_name', 'mobile_no', 'email')
            ->orderBy('full_name')
            ->get();

            // Add bounced cheque count and floating balance for each customer
            $customers = $customers->map(function($customer) {
                $bouncedCount = $customer->payments()
                    ->where('payment_method', 'cheque')
                    ->where('cheque_status', 'bounced')
                    ->count();

                $floatingBalance = $customer->getFloatingBalance();

                return [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'mobile_no' => $customer->mobile_no,
                    'email' => $customer->email,
                    'bounced_cheques_count' => $bouncedCount,
                    'floating_balance' => $floatingBalance
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => $customers,
                'total_customers' => $customers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get customers with bounced cheques: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to load customers with bounced cheques'
            ], 500);
        }
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'prefix' => 'nullable|string|max:10',
    //         'first_name' => 'required|string|max:255',
    //         'last_name' => 'nullable|string|max:255',
    //         'mobile_no' => 'required|numeric|digits_between:10,15|unique:customers,mobile_no',
    //         'email' => 'nullable|email|max:255|unique:customers,email',
    //         'address' => 'nullable|string|max:500',
    //         'opening_balance' => 'nullable|numeric',
    //         'credit_limit' => 'nullable|numeric|min:0',
    //         'city_id' => 'nullable|integer|exists:cities,id',
    //         'customer_type' => 'nullable|in:wholesaler,retailer',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 400,
    //             'errors' => $validator->messages()
    //         ]);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $customerData = $request->only([
    //             'prefix',
    //             'first_name',
    //             'last_name',
    //             'mobile_no',
    //             'email',
    //             'address',
    //             'opening_balance',
    //             'credit_limit',
    //             'city_id',
    //             'customer_type',
    //         ]);

    //         // Auto-calculate credit limit if not provided but city is selected
    //         if (!$request->has('credit_limit') || $request->credit_limit === null) {
    //             if ($request->city_id) {
    //                 $customerData['credit_limit'] = Customer::calculateCreditLimitForCity($request->city_id);
    //             }
    //         }

    //         $customer = Customer::create($customerData);

    //         DB::commit();

    //         return response()->json([
    //             'status' => 200,
    //             'message' => "New Customer Created Successfully!",
    //             'calculated_credit_limit' => $customerData['credit_limit'] ?? 0
    //         ]);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         DB::rollBack();

    //         // Handle specific database constraint violations
    //         if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
    //             $errorMessage = $e->getMessage();

    //             if (strpos($errorMessage, 'customers_mobile_no_unique') !== false) {
    //                 return response()->json([
    //                     'status' => 400,
    //                     'errors' => [
    //                         'mobile_no' => ['This mobile number is already registered with another customer.']
    //                     ]
    //                 ]);
    //             }

    //             if (strpos($errorMessage, 'customers_email_unique') !== false) {
    //                 return response()->json([
    //                     'status' => 400,
    //                     'errors' => [
    //                         'email' => ['This email address is already registered with another customer.']
    //                     ]
    //                 ]);
    //             }

    //             return response()->json([
    //                 'status' => 400,
    //                 'message' => 'A customer with these details already exists.'
    //             ]);
    //         }

    //         Log::error('Customer creation error: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 500,
    //             'message' => "Error creating customer. Please try again."
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Customer creation error: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 500,
    //             'message' => "Error creating customer: " . $e->getMessage()
    //         ]);
    //     }
    // }
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

            Log::error('Customer creation QueryException', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->errorInfo[1] ?? 'Unknown',
                'sql_state' => $e->errorInfo[0] ?? 'Unknown',
                'request_data' => $request->all()
            ]);

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
                }

                if (strpos($errorMessage, 'customers_email_unique') !== false) {
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

            // Handle null constraint violations
            if ($e->errorInfo[1] == 1048) { // Cannot be null error code
                $errorMessage = $e->getMessage();

                if (strpos($errorMessage, 'customer_type') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'customer_type' => ['Customer type is required and must be either wholesaler or retailer.']
                        ]
                    ], 400);
                }
            }

            Log::error('Customer creation error: ' . $e->getMessage());
            return response()->json([
                'status' => 400,
                'message' => "Error creating customer. Please check your input and try again."
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer creation Exception', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

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
        // ✅ CRITICAL PROTECTION: Prevent editing Walk-In Customer's essential details
        if ($id == 1) {
            return response()->json([
                'status' => 403,
                'message' => 'Cannot modify Walk-In Customer! This is a system-protected customer.'
            ], 403);
        }

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

                // Handle opening balance adjustment with payment consideration
                if ($oldOpeningBalance != $newOpeningBalance) {
                    $this->handleOpeningBalanceEditWithPayments(
                        $customer->id,
                        $oldOpeningBalance,
                        $newOpeningBalance
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
                        ]);
                    }

                    if (strpos($errorMessage, 'customers_email_unique') !== false) {
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

                Log::error('Customer update error: ' . $e->getMessage());
                return response()->json([
                    'status' => 500,
                    'message' => "Error updating customer. Please try again."
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
        // ✅ CRITICAL PROTECTION: Prevent deletion of Walk-In Customer (ID 1)
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
        $cities = $request->input('cities', []);

        if (empty($cities)) {
            return response()->json([
                'status' => 400,
                'message' => 'Cities are required'
            ]);
        }

        // Convert city names to lowercase for case-insensitive comparison
        $cityNames = array_map('strtolower', $cities);

        $customers = Customer::withoutLocationScope()->with(['city'])
            ->where(function ($query) use ($cityNames) {
                // Include customers from the specified cities
                $query->whereHas('city', function ($cityQuery) use ($cityNames) {
                    $cityQuery->whereRaw('LOWER(name) IN (' . implode(',', array_fill(0, count($cityNames), '?')) . ')', $cityNames);
                });

                // Also include customers without any city assigned
                // This allows sales reps to see customers who haven't been assigned a city yet
                $query->orWhereNull('city_id');
            })
            ->orderBy('first_name')
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
                    'city_name' => $customer->city?->name ?? 'No City',
                    'customer_type' => $customer->customer_type,
                    'credit_limit' => (float)$customer->credit_limit,
                    'current_balance' => (float)BalanceHelper::getCustomerBalance($customer->id),
                ];
            });

        return response()->json([
            'status' => true, // Changed to true to match the frontend expectation
            'customers' => $customers,
            'total_customers' => $customers->count(),
            'message' => "Found {$customers->count()} customers (including those without city assignment)"
        ]);
    }

    /**
     * Handle opening balance edit considering existing payments (CRITICAL SECURITY FIX)
     * This prevents double accounting and phantom debt creation
     */
    /**
     * Handle opening balance edits with proper reversal accounting
     * SIMPLIFIED: Just use the UnifiedLedgerService for proper accounting
     */
    private function handleOpeningBalanceEditWithPayments($customerId, $oldOpeningBalance, $newOpeningBalance)
    {
        Log::info("Opening balance edit for customer {$customerId}", [
            'old_opening_balance' => $oldOpeningBalance,
            'new_opening_balance' => $newOpeningBalance
        ]);

        // ✅ SIMPLIFIED & CORRECT APPROACH:
        // The UnifiedLedgerService already handles all the complexity correctly
        // It will create proper reversal entries + new opening balance entries
        // No need to consider payments here - that's handled by the balance calculation logic

        if ($oldOpeningBalance != $newOpeningBalance) {
            $notes = sprintf(
                "Opening Balance Edit: Rs.%s -> Rs.%s",
                number_format($oldOpeningBalance, 2),
                number_format($newOpeningBalance, 2)
            );

            $this->unifiedLedgerService->recordOpeningBalanceAdjustment(
                $customerId,
                'customer',
                $oldOpeningBalance,  // ✅ CORRECT: Use actual old opening balance amount
                $newOpeningBalance,  // ✅ CORRECT: Use actual new opening balance amount
                $notes
            );

            Log::info("Opening balance adjustment created using proper reversal accounting", [
                'customer_id' => $customerId,
                'old_amount' => $oldOpeningBalance,
                'new_amount' => $newOpeningBalance
            ]);
        } else {
            Log::info("No opening balance change detected", [
                'customer_id' => $customerId,
                'amount' => $oldOpeningBalance
            ]);
        }
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

}
