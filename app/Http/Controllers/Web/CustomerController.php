<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\City;
use App\Models\CustomerGroup;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Location; // Ensure Location model is imported
use App\Models\User; // Ensure User model is imported
use App\Exports\CustomerExport;
use App\Exports\CustomerTemplateExport;
use App\Imports\CustomerImport;
use App\Services\Customer\CustomerCrudService;
use App\Services\Customer\CustomerListingService;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\BalanceHelper;

class CustomerController extends Controller
{
    protected CustomerListingService $customerListingService;
    protected CustomerCrudService $customerCrudService;

    function __construct(CustomerListingService $customerListingService, CustomerCrudService $customerCrudService)
    {
        $this->customerListingService = $customerListingService;
        $this->customerCrudService = $customerCrudService;
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

    public function viewContact(Request $request, int $id, ?string $slug = null)
    {
        $customer = Customer::withoutLocationScope()
            ->with(['city:id,name'])
            ->findOrFail($id);

        $nameForSlug = trim(
            ($customer->prefix ? $customer->prefix.' ' : '').
            ($customer->first_name ?? '').' '.
            ($customer->last_name ?? '')
        );
        $contactSlug = Str::slug($nameForSlug !== '' ? $nameForSlug : ($customer->full_name ?? 'customer'));
        if ($contactSlug === '') {
            $contactSlug = 'customer';
        }

        if ($slug !== $contactSlug) {
            $target = route('customer.view-contact', ['id' => $id, 'slug' => $contactSlug]);
            if ($request->query()) {
                $target .= '?'.http_build_query($request->query());
            }

            return redirect()->to($target, 301);
        }

        return view('contact.customer.view_contact_tabs', compact('customer', 'contactSlug'));
    }

    public function importCustomer()
    {
        return view('contact.customer.import_customer');
    }


    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $cityId = $request->filled('city_id') ? (int) $request->city_id : null;
        $payload = $this->customerListingService->buildIndexPayload($user, $cityId);

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
            'customer_type' => 'nullable|in:wholesaler,retailer',
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
            'customer_type' => 'nullable|in:wholesaler,retailer',
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
                $result = $this->customerCrudService->updateFromInput($customer, $request->all(), true);

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
                Log::error('Customer update QueryException: ' . $e->getMessage());

                return $this->customerErrorResponseFromException($e, 'updating', 400);
            } catch (\Exception $e) {
                Log::error('Customer update Exception: ' . $e->getMessage());

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

        $customers = $customers->orderBy('first_name')->get();
        $filteredIds = $customers->pluck('id')->toArray();
        $balances = BalanceHelper::getBulkCustomerBalances($filteredIds);
        $repInvoiceDues = ($user && $isSalesRep)
            ? BalanceHelper::getBulkSalesRepOpenInvoiceDues($filteredIds, (int) $user->id)
            : collect();

        $customers = $customers->map(function ($customer) use ($balances, $repInvoiceDues, $isSalesRep) {
            $currentBalance = (float) $balances->get($customer->id, (float) $customer->opening_balance);

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
                'credit_limit' => (float) $customer->credit_limit,
                'current_balance' => $currentBalance,
                'current_due' => (float) max(0, $currentBalance),
                'my_invoice_due' => $isSalesRep ? (float) $repInvoiceDues->get($customer->id, 0.0) : 0.0,
            ];
        });

        return response()->json([
            'status' => 200,
            'customers' => $customers,
            'total_customers' => $customers->count(),
            'show_rep_invoice_due' => $isSalesRep,
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
