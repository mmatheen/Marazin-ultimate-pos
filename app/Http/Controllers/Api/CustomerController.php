<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use App\Models\User;
use App\Services\Customer\CustomerCrudService;
use App\Services\Customer\CustomerListingService;
use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    protected $unifiedLedgerService;
    protected CustomerListingService $customerListingService;
    protected CustomerCrudService $customerCrudService;

    function __construct(
        UnifiedLedgerService $unifiedLedgerService,
        CustomerListingService $customerListingService,
        CustomerCrudService $customerCrudService
    )
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        $this->customerListingService = $customerListingService;
        $this->customerCrudService = $customerCrudService;
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

    $payload = $this->customerListingService->buildIndexPayload($user);

    return response()->json([
        'status' => 200,
        'message' => $payload['customers'],
        'total_customers' => $payload['total_customers'],
        'sales_rep_info' => $payload['sales_rep_info'],
        'show_rep_invoice_due' => $payload['show_rep_invoice_due'],
    ]);
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
            'allow_sms' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ], 400);
        }

        try {
            $result = $this->customerCrudService->createFromInput($request->all(), true);

            if (isset($result['errors'])) {
                return response()->json([
                    'status' => 400,
                    'errors' => $result['errors'],
                ], 400);
            }

            return response()->json([
                'status' => 200,
                'message' => "New Customer Created Successfully!",
                'calculated_credit_limit' => $result['calculated_credit_limit'] ?? 0,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Customer creation QueryException: ' . $e->getMessage());

            return $this->customerErrorResponseFromException($e, 'creating', 400);
        } catch (\Exception $e) {
            Log::error('Customer creation Exception: ' . $e->getMessage());

            return $this->customerErrorResponseFromException($e, 'creating', 500);
        }
    }

    public function show(int $id)
    {
        $customer = $this->customerCrudService->findByIdWithCity($id);

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
            'allow_sms' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ], 400);
        }

        $customer = $this->customerCrudService->findByIdWithCity($id);

        if ($customer) {
            try {
                $result = $this->customerCrudService->updateFromInput(
                    $customer,
                    $request->all(),
                    true,
                    true,
                    $this->unifiedLedgerService,
                    'Opening balance updated via API'
                );

                if (isset($result['errors'])) {
                    return response()->json([
                        'status' => 400,
                        'errors' => $result['errors'],
                    ], 400);
                }

                return response()->json([
                    'status' => 200,
                    'message' => "Customer Details Updated Successfully!",
                    'calculated_credit_limit' => $result['calculated_credit_limit'] ?? $customer->credit_limit,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Customer update error: ' . $e->getMessage());

                if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1048 && strpos($e->getMessage(), 'customer_type') !== false) {
                    return response()->json([
                        'status' => 400,
                        'errors' => [
                            'customer_type' => ['Customer type is required and must be either wholesaler or retailer.'],
                        ],
                    ], 400);
                }

                return $this->customerErrorResponseFromException($e, 'updating', 400);
            } catch (\Exception $e) {
                Log::error('Customer update error: ' . $e->getMessage());

                return $this->customerErrorResponseFromException($e, 'updating', 500);
            }
        }

        return response()->json(['status' => 404, 'message' => "No Such Customer Found!"]);
    }

    private function customerErrorResponseFromException(\Throwable $e, string $action, int $defaultStatus)
    {
        $duplicateField = $this->customerCrudService->getDuplicateFieldFromException($e);

        if ($duplicateField === 'mobile_no') {
            return response()->json([
                'status' => 400,
                'errors' => [
                    'mobile_no' => ['This mobile number is already registered with another customer.'],
                ],
            ], 400);
        }

        if ($duplicateField === 'email') {
            return response()->json([
                'status' => 400,
                'errors' => [
                    'email' => ['This email address is already registered with another customer.'],
                ],
            ], 400);
        }

        $message = $defaultStatus === 500
            ? "Error {$action} customer. Please try again."
            : "Error {$action} customer. Please check your input and try again.";

        return response()->json([
            'status' => $defaultStatus,
            'message' => $message,
        ], $defaultStatus);
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
