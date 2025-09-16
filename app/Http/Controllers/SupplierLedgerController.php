<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Ledger;
use App\Services\SupplierLedgerService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class SupplierLedgerController extends Controller
{
    protected $ledgerService;

    public function __construct(SupplierLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
        $this->middleware('permission:view supplier-ledger', ['only' => ['index', 'show', 'getSupplierLedger']]);
        $this->middleware('permission:manage supplier-ledger', ['only' => ['recalculateBalance', 'validateLedger']]);
    }

    /**
     * Display supplier ledger page
     */
    public function index()
    {
        return view('supplier.ledger');
    }

    /**
     * Get supplier ledger entries
     */
    public function getSupplierLedger(Request $request, $supplierId)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'transaction_type' => 'nullable|in:purchase,purchase_return,payments,opening_balance'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $supplier = Supplier::findOrFail($supplierId);
            
            $fromDate = $request->from_date ? Carbon::parse($request->from_date) : null;
            $toDate = $request->to_date ? Carbon::parse($request->to_date) : null;
            $transactionType = $request->transaction_type;

            // Get ledger entries
            $ledgerEntries = $this->ledgerService->getSupplierLedger(
                $supplierId, 
                $fromDate, 
                $toDate, 
                $transactionType
            );

            // Get supplier summary
            $summary = $this->ledgerService->getSupplierSummary($supplierId);

            return response()->json([
                'status' => 200,
                'supplier' => $supplier,
                'summary' => $summary,
                'ledger_entries' => $ledgerEntries,
                'filters' => [
                    'from_date' => $fromDate?->format('Y-m-d'),
                    'to_date' => $toDate?->format('Y-m-d'),
                    'transaction_type' => $transactionType
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error fetching supplier ledger: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get supplier summary
     */
    public function getSupplierSummary($supplierId)
    {
        try {
            $summary = $this->ledgerService->getSupplierSummary($supplierId);
            
            return response()->json([
                'status' => 200,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error fetching supplier summary: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all suppliers with their current balances
     */
    public function getAllSuppliersWithBalances()
    {
        try {
            $suppliers = Supplier::select('id', 'name', 'contact_number', 'email', 'current_balance')
                ->get()
                ->map(function ($supplier) {
                    $supplier->ledger_balance = $this->ledgerService->getLastBalance($supplier->id);
                    $supplier->balance_difference = $supplier->current_balance - $supplier->ledger_balance;
                    return $supplier;
                });

            return response()->json([
                'status' => 200,
                'suppliers' => $suppliers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error fetching suppliers: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Recalculate supplier balance
     */
    public function recalculateBalance($supplierId)
    {
        try {
            $supplier = Supplier::findOrFail($supplierId);
            
            $this->ledgerService->recalculateSupplierBalance($supplierId);
            
            $newBalance = $this->ledgerService->getLastBalance($supplierId);
            
            return response()->json([
                'status' => 200,
                'message' => 'Supplier balance recalculated successfully',
                'supplier' => $supplier->name,
                'new_balance' => $newBalance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error recalculating balance: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validate supplier ledger
     */
    public function validateLedger($supplierId)
    {
        try {
            $supplier = Supplier::findOrFail($supplierId);
            
            $validation = $this->ledgerService->validateSupplierLedger($supplierId);
            
            return response()->json([
                'status' => 200,
                'supplier' => $supplier->name,
                'is_valid' => $validation['is_valid'],
                'errors' => $validation['errors'],
                'final_balance' => $validation['final_balance']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error validating ledger: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get ledger statement for a specific period
     */
    public function getLedgerStatement(Request $request, $supplierId)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $supplier = Supplier::findOrFail($supplierId);
            $fromDate = Carbon::parse($request->from_date);
            $toDate = Carbon::parse($request->to_date);

            // Get opening balance (balance before from_date)
            $openingBalanceEntry = Ledger::where('user_id', $supplierId)
                ->where('contact_type', 'supplier')
                ->where('transaction_date', '<', $fromDate)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $openingBalance = $openingBalanceEntry ? $openingBalanceEntry->balance : 0;

            // Get transactions for the period
            $transactions = $this->ledgerService->getSupplierLedger($supplierId, $fromDate, $toDate);

            // Calculate totals
            $totalDebits = $transactions->sum('debit');
            $totalCredits = $transactions->sum('credit');
            $closingBalance = $transactions->last()?->balance ?? $openingBalance;

            $statement = [
                'supplier' => $supplier,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d')
                ],
                'opening_balance' => $openingBalance,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'closing_balance' => $closingBalance,
                'transactions' => $transactions
            ];

            return response()->json([
                'status' => 200,
                'statement' => $statement
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error generating statement: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk validate all supplier ledgers
     */
    public function validateAllLedgers()
    {
        try {
            $suppliers = Supplier::all();
            $results = [];
            $totalErrors = 0;

            foreach ($suppliers as $supplier) {
                $validation = $this->ledgerService->validateSupplierLedger($supplier->id);
                
                $results[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'is_valid' => $validation['is_valid'],
                    'error_count' => count($validation['errors']),
                    'final_balance' => $validation['final_balance']
                ];

                if (!$validation['is_valid']) {
                    $totalErrors += count($validation['errors']);
                }
            }

            return response()->json([
                'status' => 200,
                'total_suppliers' => $suppliers->count(),
                'valid_suppliers' => collect($results)->where('is_valid', true)->count(),
                'invalid_suppliers' => collect($results)->where('is_valid', false)->count(),
                'total_errors' => $totalErrors,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error validating ledgers: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk recalculate all supplier balances
     */
    public function recalculateAllBalances()
    {
        try {
            $suppliers = Supplier::all();
            $results = [];

            foreach ($suppliers as $supplier) {
                $oldBalance = $this->ledgerService->getLastBalance($supplier->id);
                $this->ledgerService->recalculateSupplierBalance($supplier->id);
                $newBalance = $this->ledgerService->getLastBalance($supplier->id);

                $results[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'difference' => $newBalance - $oldBalance
                ];
            }

            return response()->json([
                'status' => 200,
                'message' => 'All supplier balances recalculated successfully',
                'total_suppliers' => $suppliers->count(),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error recalculating balances: ' . $e->getMessage()
            ]);
        }
    }
}