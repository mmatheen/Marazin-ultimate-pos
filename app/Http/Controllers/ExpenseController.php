<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\ExpensePayment;
use App\Models\ExpenseParentCategory;
use App\Models\ExpenseSubCategory;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\SupplierBalanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view expense', ['only' => ['index', 'show', 'expenseList']]);
        $this->middleware('permission:create expense', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit expense', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete expense', ['only' => ['destroy']]);
    }

    /**
     * Display the expense listing page
     */
    public function expenseList()
    {
        $expenseParentCategories = ExpenseParentCategory::all();
        $expenseSubCategories = ExpenseSubCategory::all();

        // Get all locations for now (can be filtered by user access later)
        $locations = Location::all();

        return view('expense.expense_list', compact('expenseParentCategories', 'expenseSubCategories', 'locations'));
    }

    /**
     * Display the expense creation/edit page (unified)
     */
    public function create()
    {
        // Generate expense number for new expenses
        $lastExpense = Expense::latest('id')->first();
        $expenseNo = 'EXP-' . date('Y') . '-' . str_pad(($lastExpense ? $lastExpense->id + 1 : 1), 4, '0', STR_PAD_LEFT);

        // Get all locations for now (can be filtered by user access later)
        $locations = Location::all();

        // Get all suppliers
        $suppliers = Supplier::orderBy('first_name')->get();

        return view('expense.create_expense', compact('expenseNo', 'locations', 'suppliers'));
    }

    /**
     * Get all expenses with filtering
     */
    public function index(Request $request)
    {
        $query = Expense::with([
            'expenseParentCategory',
            'expenseSubCategory',
            'supplier',
            'location',
            'creator'
        ]);

        // Filter by user accessible locations
        $userLocationIds = auth()->user()->accessibleLocationIds ?? Location::pluck('id')->toArray();
        $query->whereIn('location_id', $userLocationIds);

        // Apply filters
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('expense_parent_category_id', $request->category_id);
        }

        if ($request->has('sub_category_id') && $request->sub_category_id != '') {
            $query->where('expense_sub_category_id', $request->sub_category_id);
        }

        if ($request->has('payment_status') && $request->payment_status != '') {
            $query->where('payment_status', $request->payment_status);
        }

        // Removed supplier_id filter since using paid_to text field now

        if ($request->has('start_date') && $request->has('end_date') &&
            $request->start_date != '' && $request->end_date != '') {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('paid_to', 'like', "%{$search}%");
            });
        }

        $expenses = $query->latest()->get();

        // Always return consistent format with data array
        return response()->json([
            'status' => 200,
            'data' => $expenses,
            'total' => $expenses->count(),
            'message' => $expenses->count() > 0 ? 'Expenses loaded successfully' : 'No expenses found'
        ]);
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'expense_parent_category_id' => 'required|exists:expense_parent_categories,id',
            'expense_sub_category_id' => 'nullable|exists:expense_sub_categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'paid_to' => 'nullable|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        DB::beginTransaction();
        try {
            // Generate expense number if not provided
            if (!$request->expense_no) {
                $lastExpense = Expense::latest('id')->first();
                $expenseNo = 'EXP-' . date('Y') . '-' . str_pad(($lastExpense ? $lastExpense->id + 1 : 1), 4, '0', STR_PAD_LEFT);
            } else {
                $expenseNo = $request->expense_no;
            }

            // Handle file upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('expenses', 'public');
            }

            // Calculate payment status and handle overpayment
            $totalAmount = floatval($request->total_amount);
            $requestedPaidAmount = floatval($request->paid_amount);
            $paidAmount = $requestedPaidAmount;
            $overpaidAmount = 0;

            // Check for overpayment
            if ($requestedPaidAmount > $totalAmount) {
                $overpaidAmount = $requestedPaidAmount - $totalAmount;
                $paidAmount = $totalAmount; // Cap paid amount at total
            }

            $dueAmount = $totalAmount - $paidAmount;

            if ($paidAmount >= $totalAmount) {
                $paymentStatus = 'paid';
                $dueAmount = 0;
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Create expense
            $expense = Expense::create([
                'expense_no' => $expenseNo,
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'expense_parent_category_id' => $request->expense_parent_category_id,
                'expense_sub_category_id' => $request->expense_sub_category_id,
                'supplier_id' => $request->supplier_id,
                'paid_to' => $request->paid_to,
                'location_id' => $request->location_id,
                'payment_status' => $paymentStatus,
                'payment_method' => $request->payment_method,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_type' => $request->discount_type ?? 'fixed',
                'discount_amount' => $request->discount_amount ?? 0,
                'shipping_charges' => $request->shipping_charges ?? 0,
                'note' => $request->note,
                'attachment' => $attachmentPath,
                'created_by' => auth()->id(),
                'status' => 'active'
            ]);

            // Create expense items
            foreach ($request->items as $item) {
                ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => ($item['quantity'] * $item['unit_price']) * (($item['tax_rate'] ?? 0) / 100)
                ]);
            }

            // Create payment record if amount paid
            if ($paidAmount > 0) {
                ExpensePayment::create([
                    'expense_id' => $expense->id,
                    'payment_date' => $request->date,
                    'payment_method' => $request->payment_method,
                    'amount' => $paidAmount,
                    'reference_no' => $request->payment_reference,
                    'note' => 'Initial payment',
                    'created_by' => auth()->id()
                ]);
            }

            // Handle overpayment - add to supplier balance
            if ($overpaidAmount > 0) {
                $expense->handleOverPayment($overpaidAmount);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => "Expense created successfully!" . ($overpaidAmount > 0 ? " Overpayment of Rs.{$overpaidAmount} added to supplier balance." : ""),
                'expense_id' => $expense->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! " . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified expense
     */
    public function show(int $id)
    {
        $expense = Expense::with([
            'expenseParentCategory',
            'expenseSubCategory',
            'expenseItems',
            'payments.creator',
            'creator'
        ])->find($id);

        if ($expense) {
            return response()->json([
                'status' => 200,
                'data' => $expense
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "Expense not found!"
            ]);
        }
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit(int $id)
    {
        $expense = Expense::with(['expenseItems', 'expenseParentCategory', 'expenseSubCategory'])->find($id);

        if (!$expense) {
            return response()->json([
                'status' => 404,
                'message' => "Expense not found!"
            ]);
        }

        $expenseParentCategories = ExpenseParentCategory::all();
        $expenseSubCategories = ExpenseSubCategory::all();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 200,
                'data' => $expense,
                'categories' => $expenseParentCategories,
                'subcategories' => $expenseSubCategories
            ]);
        }

        return view('expense.edit_expense', compact('expense', 'expenseParentCategories', 'expenseSubCategories'));
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, int $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status' => 404,
                'message' => "Expense not found!"
            ]);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'expense_parent_category_id' => 'required|exists:expense_parent_categories,id',
            'expense_sub_category_id' => 'nullable|exists:expense_sub_categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'paid_to' => 'nullable|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        DB::beginTransaction();
        try {
            // Store old values for balance tracking
            $oldTotalAmount = $expense->total_amount;
            $oldPaidAmount = $expense->paid_amount;
            $oldSupplierId = $expense->supplier_id;

            // Handle file upload
            $attachmentPath = $expense->attachment;
            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $attachmentPath = $request->file('attachment')->store('expenses', 'public');
            }

            // Calculate payment status and handle overpayment
            $newTotalAmount = floatval($request->total_amount);
            $requestedPaidAmount = floatval($request->paid_amount ?? $expense->paid_amount);
            $paidAmount = $requestedPaidAmount;
            $overpaidAmount = 0;

            // Check for overpayment
            if ($requestedPaidAmount > $newTotalAmount) {
                $overpaidAmount = $requestedPaidAmount - $newTotalAmount;
                $paidAmount = $newTotalAmount; // Cap paid amount at total
            }

            $dueAmount = $newTotalAmount - $paidAmount;

            if ($paidAmount >= $newTotalAmount) {
                $paymentStatus = 'paid';
                $dueAmount = 0;
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Update expense
            $expense->update([
                'date' => $request->date,
                'reference_no' => $request->reference_no,
                'expense_parent_category_id' => $request->expense_parent_category_id,
                'expense_sub_category_id' => $request->expense_sub_category_id,
                'supplier_id' => $request->supplier_id,
                'paid_to' => $request->paid_to,
                'location_id' => $request->location_id,
                'payment_status' => $paymentStatus,
                'payment_method' => $request->payment_method,
                'total_amount' => $newTotalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_type' => $request->discount_type ?? 'fixed',
                'discount_amount' => $request->discount_amount ?? 0,
                'shipping_charges' => $request->shipping_charges ?? 0,
                'note' => $request->note,
                'attachment' => $attachmentPath,
                'updated_by' => auth()->id()
            ]);

            // Handle expense amount changes for supplier balance
            if ($oldTotalAmount != $newTotalAmount && $expense->supplier_id) {
                $expense->handleExpenseAmountChange($oldTotalAmount, $newTotalAmount);
            }

            // Delete existing items and create new ones
            $expense->expenseItems()->delete();

            foreach ($request->items as $item) {
                ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => ($item['quantity'] * $item['unit_price']) * (($item['tax_rate'] ?? 0) / 100)
                ]);
            }

            // Handle payment record update if paid amount changed
            if ($request->has('paid_amount') && $requestedPaidAmount != $oldPaidAmount) {
                // Delete existing payment records
                $expense->payments()->delete();

                // Create new payment record if amount paid
                if ($paidAmount > 0) {
                    ExpensePayment::create([
                        'expense_id' => $expense->id,
                        'payment_date' => $request->date,
                        'payment_method' => $request->payment_method,
                        'amount' => $paidAmount,
                        'reference_no' => $request->payment_reference,
                        'note' => 'Updated payment',
                        'created_by' => auth()->id()
                    ]);
                }
            }

            // Handle overpayment - add to supplier balance
            if ($overpaidAmount > 0) {
                $expense->handleOverPayment($overpaidAmount);
            }

            DB::commit();

            $message = "Expense updated successfully!";
            if ($overpaidAmount > 0) {
                $message .= " Overpayment of Rs.{$overpaidAmount} added to supplier balance.";
            }

            return response()->json([
                'status' => 200,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! " . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(int $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status' => 404,
                'message' => "Expense not found!"
            ]);
        }

        DB::beginTransaction();
        try {
            // Delete related records
            $expense->expenseItems()->delete();
            $expense->payments()->delete();

            // Delete attachment file
            if ($expense->attachment && Storage::disk('public')->exists($expense->attachment)) {
                Storage::disk('public')->delete($expense->attachment);
            }

            // Delete expense
            $expense->delete();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => "Expense deleted successfully!"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! " . $e->getMessage()
            ]);
        }
    }

    /**
     * Get sub categories by parent category
     */
    public function getSubCategories($parentCategoryId)
    {
        $subCategories = ExpenseSubCategory::where('main_expense_category_id', $parentCategoryId)->get();

        return response()->json([
            'status' => 200,
            'data' => $subCategories
        ]);
    }

    /**
     * Get expense reports
     */
    public function reports(Request $request)
    {
        $query = Expense::with(['expenseParentCategory', 'expenseSubCategory']);

        // Apply filters for reports
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('category_id') && $request->category_id != '') {
            $query->byCategory($request->category_id);
        }

        $expenses = $query->get();

        // Calculate summary
        $totalExpenses = $expenses->count();
        $totalAmount = $expenses->sum('total_amount');
        $totalPaid = $expenses->sum('paid_amount');
        $totalDue = $expenses->sum('due_amount');

        // Category wise summary
        $categoryWise = $expenses->groupBy('expense_parent_category_id')->map(function ($items, $key) {
            return [
                'category_name' => $items->first()->expenseParentCategory->expenseParentCatergoryName ?? 'Unknown',
                'count' => $items->count(),
                'total_amount' => $items->sum('total_amount'),
                'paid_amount' => $items->sum('paid_amount'),
                'due_amount' => $items->sum('due_amount')
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'data' => [
                'summary' => [
                    'total_expenses' => $totalExpenses,
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue
                ],
                'category_wise' => $categoryWise,
                'expenses' => $expenses
            ]
        ]);
    }

    /**
     * Get locations for expense form (without permission restrictions)
     */
    public function getLocationsForExpense()
    {
        try {
            // Get all locations - simplified approach for expense creation
            $locations = Location::select('id', 'name')->get();

            return response()->json([
                'status' => true,
                'message' => 'Locations fetched successfully',
                'data' => $locations
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching locations for expense: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch locations'
            ]);
        }
    }

    /**
     * Get suppliers for expense form
     */
    public function getSuppliersForExpense()
    {
        try {
            $suppliers = Supplier::select('id', 'first_name', 'last_name', 'mobile_no', 'opening_balance')
                ->orderBy('first_name')
                ->get()
                ->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->full_name,
                        'mobile' => $supplier->mobile_no,
                        'balance' => $supplier->formatted_expense_balance ?? 'Rs.0.00'
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Suppliers fetched successfully',
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching suppliers for expense: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch suppliers'
            ]);
        }
    }

    /**
     * Add payment to existing expense for settlement
     */
    public function addPayment(Request $request, int $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status' => 404,
                'message' => "Expense not found!"
            ]);
        }

        $validator = Validator::make($request->all(), [
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        DB::beginTransaction();
        try {
            $requestedPaymentAmount = floatval($request->payment_amount);
            $currentPaidAmount = $expense->paid_amount;
            $dueAmount = $expense->due_amount;

            // Handle overpayment scenario
            $actualPaymentAmount = $requestedPaymentAmount;
            $overpaidAmount = 0;

            if ($requestedPaymentAmount > $dueAmount) {
                $overpaidAmount = $requestedPaymentAmount - $dueAmount;
                $actualPaymentAmount = $dueAmount; // Only pay what's due
            }

            $newPaidAmount = $currentPaidAmount + $actualPaymentAmount;
            $newDueAmount = $expense->total_amount - $newPaidAmount;

            // Calculate new payment status
            if ($newPaidAmount >= $expense->total_amount) {
                $paymentStatus = 'paid';
                $newDueAmount = 0;
            } elseif ($newPaidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Create payment record for the actual payment amount
            $payment = ExpensePayment::create([
                'expense_id' => $expense->id,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'amount' => $actualPaymentAmount,
                'reference_no' => $request->payment_reference,
                'note' => $request->payment_note ?? 'Additional payment',
                'created_by' => auth()->id()
            ]);

            // Update expense payment status
            $expense->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $newDueAmount,
                'payment_status' => $paymentStatus,
                'updated_by' => auth()->id()
            ]);

            // Handle overpayment - add to supplier balance
            if ($overpaidAmount > 0) {
                $expense->handleOverPayment($overpaidAmount);
            }

            DB::commit();

            $message = 'Payment added successfully! New paid amount: Rs.' . number_format($newPaidAmount, 2);
            if ($overpaidAmount > 0) {
                $message .= " Overpayment of Rs.{$overpaidAmount} added to supplier balance.";
            }

            return response()->json([
                'status' => 200,
                'message' => $message,
                'data' => [
                    'expense_id' => $expense->id,
                    'total_amount' => $expense->total_amount,
                    'paid_amount' => $newPaidAmount,
                    'due_amount' => $newDueAmount,
                    'payment_status' => $paymentStatus,
                    'overpaid_amount' => $overpaidAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! " . $e->getMessage()
            ]);
        }
    }

    /**
     * Get payment history for an expense
     */
    public function getPaymentHistory(int $id)
    {
        $expense = Expense::with(['payments.creator', 'location'])->find($id);

        if (!$expense) {
            return response()->json([
                'status' => 404,
                'message' => "Expense not found!"
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'expense' => [
                    'id' => $expense->id,
                    'expense_no' => $expense->expense_no,
                    'total_amount' => $expense->total_amount,
                    'paid_amount' => $expense->paid_amount,
                    'due_amount' => $expense->due_amount,
                    'payment_status' => $expense->payment_status,
                    'location_name' => $expense->location ? $expense->location->name : 'N/A'
                ],
                'payments' => $expense->payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_date' => $payment->payment_date->format('d-m-Y'),
                        'payment_method' => $payment->payment_method,
                        'amount' => $payment->amount,
                        'reference_no' => $payment->reference_no,
                        'note' => $payment->note,
                        'created_by' => $payment->creator->name ?? 'Unknown',
                        'created_at' => $payment->created_at->format('d-m-Y H:i')
                    ];
                })
            ]
        ]);
    }

    /**
     * Edit payment record
     */
    public function editPayment(Request $request, int $paymentId)
    {
        $payment = ExpensePayment::with('expense')->find($paymentId);

        if (!$payment) {
            return response()->json([
                'status' => 404,
                'message' => "Payment record not found!"
            ]);
        }

        $validator = Validator::make($request->all(), [
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        DB::beginTransaction();
        try {
            $expense = $payment->expense;
            $oldAmount = floatval($payment->amount);
            $newAmount = floatval($request->payment_amount);
            $amountDifference = $newAmount - $oldAmount;

            // Calculate new totals
            $newPaidAmount = $expense->paid_amount + $amountDifference;
            $newDueAmount = $expense->total_amount - $newPaidAmount;

            // Handle overpayment scenario
            $overpaidAmount = 0;
            if ($newPaidAmount > $expense->total_amount) {
                $overpaidAmount = $newPaidAmount - $expense->total_amount;
                $newPaidAmount = $expense->total_amount;
                $newDueAmount = 0;
            }

            // Validate that new paid amount is not negative
            if ($newPaidAmount < 0) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Updated payment amount would make total paid amount negative'
                ]);
            }

            // Calculate new payment status
            if ($newPaidAmount >= $expense->total_amount) {
                $paymentStatus = 'paid';
                $newDueAmount = 0;
            } elseif ($newPaidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Track payment edit for supplier balance
            if ($oldAmount != $newAmount && $expense->supplier_id) {
                $expense->handlePaymentEdit($payment->id, $oldAmount, $newAmount);
            }

            // Update payment record
            $payment->update([
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'amount' => $newAmount,
                'reference_no' => $request->payment_reference,
                'note' => $request->payment_note,
                'updated_by' => auth()->id()
            ]);

            // Update expense totals
            $expense->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $newDueAmount,
                'payment_status' => $paymentStatus,
                'updated_by' => auth()->id()
            ]);

            // Handle overpayment - add to supplier balance
            if ($overpaidAmount > 0) {
                $expense->handleOverPayment($overpaidAmount);
            }

            DB::commit();

            $message = 'Payment updated successfully!';
            if ($overpaidAmount > 0) {
                $message .= " Overpayment of Rs.{$overpaidAmount} added to supplier balance.";
            }

            return response()->json([
                'status' => 200,
                'message' => $message,
                'data' => [
                    'expense_id' => $expense->id,
                    'total_amount' => $expense->total_amount,
                    'paid_amount' => $newPaidAmount,
                    'due_amount' => $newDueAmount,
                    'payment_status' => $paymentStatus,
                    'overpaid_amount' => $overpaidAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! " . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete payment record
     */
    public function deletePayment(int $paymentId)
    {
        $payment = ExpensePayment::with('expense')->find($paymentId);

        if (!$payment) {
            return response()->json([
                'status' => 404,
                'message' => "Payment record not found!"
            ]);
        }

        DB::beginTransaction();
        try {
            $expense = $payment->expense;
            $deletedAmount = floatval($payment->amount);

            // Calculate new totals after removing this payment
            $newPaidAmount = $expense->paid_amount - $deletedAmount;
            $newDueAmount = $expense->total_amount - $newPaidAmount;

            // Calculate new payment status
            if ($newPaidAmount >= $expense->total_amount) {
                $paymentStatus = 'paid';
                $newDueAmount = 0;
            } elseif ($newPaidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Track payment deletion for supplier balance
            if ($expense->supplier_id) {
                $expense->handlePaymentDeletion($payment->id, $deletedAmount);
            }

            // ✅ CRITICAL FIX: Mark payment as deleted instead of hard delete
            $payment->update([
                'status' => 'deleted',
                'deleted_at' => now(),
                'deleted_by' => auth()->id(),
                'notes' => ($payment->notes ?? '') . ' | [DELETED: Expense payment removed - ' . now()->format('Y-m-d H:i:s') . ']'
            ]);

            // Update expense totals
            $expense->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $newDueAmount,
                'payment_status' => $paymentStatus,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => "Payment deleted successfully! Amount of Rs.{$deletedAmount} added to supplier balance.",
                'data' => [
                    'expense_id' => $expense->id,
                    'total_amount' => $expense->total_amount,
                    'paid_amount' => $newPaidAmount,
                    'due_amount' => $newDueAmount,
                    'payment_status' => $paymentStatus
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong! " . $e->getMessage()
            ]);
        }
    }

    /**
     * Get single payment record for editing
     */
    public function getPayment(int $paymentId)
    {
        $payment = ExpensePayment::with('expense')->find($paymentId);

        if (!$payment) {
            return response()->json([
                'status' => 404,
                'message' => "Payment record not found!"
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'id' => $payment->id,
                'expense_id' => $payment->expense_id,
                'payment_date' => $payment->payment_date->format('Y-m-d'),
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'reference_no' => $payment->reference_no,
                'note' => $payment->note,
                'expense' => [
                    'id' => $payment->expense->id,
                    'expense_no' => $payment->expense->expense_no,
                    'total_amount' => $payment->expense->total_amount,
                    'paid_amount' => $payment->expense->paid_amount,
                    'due_amount' => $payment->expense->due_amount
                ]
            ]
        ]);
    }

    /**
     * Get supplier balance history for expenses
     */
    public function getSupplierBalanceHistory($supplierId)
    {
        try {
            $supplier = Supplier::with(['balanceLogs.expense', 'balanceLogs.payment', 'balanceLogs.creator'])
                ->find($supplierId);

            if (!$supplier) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Supplier not found'
                ]);
            }

            $balanceHistory = $supplier->balanceLogs()
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'date' => $log->formatted_date,
                        'type' => $log->transaction_type_text,
                        'amount' => $log->formatted_amount,
                        'debit_credit' => $log->debit_credit,
                        'balance_before' => $log->formatted_balance_before,
                        'balance_after' => $log->formatted_balance_after,
                        'description' => $log->description,
                        'expense_no' => $log->expense ? $log->expense->expense_no : null,
                        'created_by' => $log->creator ? $log->creator->name : 'System',
                        'metadata' => $log->metadata
                    ];
                });

            return response()->json([
                'status' => 200,
                'data' => [
                    'supplier' => [
                        'id' => $supplier->id,
                        'name' => $supplier->full_name,
                        'balance' => $supplier->formatted_expense_balance,
                        'summary' => $supplier->getBalanceSummary()
                    ],
                    'history' => $balanceHistory
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching supplier balance history: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to fetch balance history'
            ]);
        }
    }
}
