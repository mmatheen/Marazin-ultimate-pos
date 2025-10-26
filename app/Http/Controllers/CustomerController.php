<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use App\Models\SalesRep;
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

    // Start with bypassing location scope, but apply sales rep filtering if needed
    $query = Customer::withoutLocationScope()->with(['sales', 'salesReturns', 'payments', 'city']);
    
    // Apply sales rep route filtering if user is a sales rep
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
            Log::error('Customer creation error: ' . $e->getMessage());
            
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
                        'Opening balance updated via customer profile'
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
                    'current_balance' => (float)$customer->current_balance,
                ];
            });

        return response()->json([
            'status' => true, // Changed to true to match the frontend expectation
            'customers' => $customers,
            'total_customers' => $customers->count(),
            'message' => "Found {$customers->count()} customers (including those without city assignment)"
        ]);
    }

}
