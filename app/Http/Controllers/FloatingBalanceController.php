<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class FloatingBalanceController extends Controller
{
    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
        
        // Add permission middleware
        $this->middleware('permission:view customer balance', ['only' => ['index', 'show', 'getCustomerBalance']]);
        $this->middleware('permission:manage payments', ['only' => ['recordRecoveryPayment', 'adjustBalance']]);
    }

    /**
     * Get customer balance breakdown (bill-wise + floating)
     */
    public function getCustomerBalance($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $breakdown = $customer->getBalanceBreakdown();
            $ledgerSummary = $this->unifiedLedgerService->getCustomerBalanceSummary($customerId);

            return response()->json([
                'status' => 200,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->full_name,
                        'mobile' => $customer->mobile_no,
                        'email' => $customer->email
                    ],
                    'balance_breakdown' => $breakdown,
                    'ledger_summary' => $ledgerSummary,
                    'can_accept_cheques' => !$customer->shouldBlockChequePayments()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching customer balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record recovery payment for floating balance (bounced cheques, etc.)
     */
    public function recordRecoveryPayment(Request $request, $customerId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,card,upi',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'reference_no' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        try {
            return DB::transaction(function () use ($request, $customerId) {
                $customer = Customer::findOrFail($customerId);
                $floatingBalance = $customer->getFloatingBalance();

                if ($floatingBalance <= 0) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Customer has no floating balance to recover'
                    ]);
                }

                if ($request->amount > $floatingBalance) {
                    return response()->json([
                        'status' => 400,
                        'message' => "Payment amount (₹{$request->amount}) exceeds floating balance (₹{$floatingBalance})"
                    ]);
                }

                // Create recovery ledger entry
                $ledgerEntry = $this->unifiedLedgerService->recordFloatingBalanceRecovery(
                    $customerId,
                    $request->amount,
                    $request->payment_method,
                    $request->notes ?? "Recovery payment via {$request->payment_method}"
                );

                // Create payment record for tracking
                $payment = Payment::create([
                    'payment_date' => $request->payment_date,
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method,
                    'reference_no' => $request->reference_no ?? 'RECOVERY-' . time(),
                    'notes' => $request->notes ?? "Floating balance recovery payment",
                    'payment_type' => 'recovery',
                    'reference_id' => null, // Not linked to specific bill
                    'customer_id' => $customerId,
                    'payment_status' => 'completed'
                ]);

                $newFloatingBalance = $customer->getFloatingBalance();
                $totalOutstanding = $customer->getTotalOutstanding();

                return response()->json([
                    'status' => 200,
                    'message' => 'Recovery payment recorded successfully',
                    'data' => [
                        'payment' => $payment,
                        'ledger_entry' => $ledgerEntry,
                        'balance_update' => [
                            'old_floating_balance' => $floatingBalance,
                            'new_floating_balance' => $newFloatingBalance,
                            'total_outstanding' => $totalOutstanding,
                            'payment_amount' => $request->amount
                        ]
                    ]
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error recording recovery payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get floating balance transaction history
     */
    public function getFloatingBalanceHistory($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);
            
            $floatingTransactions = \App\Models\Ledger::where('user_id', $customerId)
                ->where('contact_type', 'customer')
                ->whereIn('transaction_type', [
                    'cheque_bounce', 
                    'bank_charges', 
                    'bounce_recovery', 
                    'adjustment_debit', 
                    'adjustment_credit'
                ])
                ->orderBy('transaction_date', 'desc')
                ->get();

            $bouncedCheques = Payment::where('customer_id', $customerId)
                ->where('payment_method', 'cheque')
                ->where('cheque_status', 'bounced')
                ->with('sale')
                ->get();

            return response()->json([
                'status' => 200,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->full_name
                    ],
                    'current_floating_balance' => $customer->getFloatingBalance(),
                    'floating_transactions' => $floatingTransactions,
                    'bounced_cheques' => $bouncedCheques,
                    'summary' => [
                        'total_bounced_amount' => $bouncedCheques->sum('amount'),
                        'total_bank_charges' => $bouncedCheques->sum('bank_charges'),
                        'total_recovered' => $floatingTransactions->where('transaction_type', 'bounce_recovery')->sum('amount')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching floating balance history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual balance adjustment (with proper authorization)
     */
    public function adjustBalance(Request $request, $customerId)
    {
        $validator = Validator::make($request->all(), [
            'adjustment_type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10|max:500',
            'authorization_code' => 'required|string' // Admin approval code
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        try {
            // Verify authorization (implement your admin verification logic)
            if (!$this->verifyAdjustmentAuthorization($request->authorization_code)) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Invalid authorization code for balance adjustment'
                ]);
            }

            return DB::transaction(function () use ($request, $customerId) {
                $customer = Customer::findOrFail($customerId);
                $transactionType = $request->adjustment_type === 'debit' ? 'adjustment_debit' : 'adjustment_credit';
                
                $ledgerEntry = \App\Models\Ledger::createEntry([
                    'user_id' => $customerId,
                    'contact_type' => 'customer',
                    'transaction_date' => \Carbon\Carbon::now('Asia/Colombo'),
                    'reference_no' => 'ADJ-' . $customerId . '-' . time(),
                    'transaction_type' => $transactionType,
                    'amount' => $request->amount,
                    'notes' => "Manual adjustment ({$request->adjustment_type}): {$request->reason} | By: " . auth()->user()->name
                ]);

                $newBalance = $customer->getTotalOutstanding();

                return response()->json([
                    'status' => 200,
                    'message' => 'Balance adjustment completed successfully',
                    'data' => [
                        'adjustment' => [
                            'type' => $request->adjustment_type,
                            'amount' => $request->amount,
                            'reason' => $request->reason,
                            'processed_by' => auth()->user()->name,
                            'processed_at' => now()
                        ],
                        'ledger_entry' => $ledgerEntry,
                        'new_total_outstanding' => $newBalance
                    ]
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error processing balance adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers with floating balances (for recovery dashboard)
     */
    public function getCustomersWithFloatingBalance()
    {
        try {
            $customersWithFloatingBalance = Customer::whereHas('ledgerEntries', function ($query) {
                $query->whereIn('transaction_type', ['cheque_bounce', 'bank_charges'])
                    ->havingRaw('SUM(amount) > 0');
            })->get()->map(function ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->full_name,
                    'mobile' => $customer->mobile_no,
                    'floating_balance' => $customer->getFloatingBalance(),
                    'bounced_cheques_count' => $customer->getBouncedChequeSummary()['count'],
                    'total_outstanding' => $customer->getTotalOutstanding(),
                    'risk_score' => $customer->getRiskScore(),
                    'cheque_blocked' => $customer->shouldBlockChequePayments()
                ];
            })->filter(function ($customer) {
                return $customer['floating_balance'] > 0;
            });

            return response()->json([
                'status' => 200,
                'data' => $customersWithFloatingBalance->values(),
                'summary' => [
                    'total_customers' => $customersWithFloatingBalance->count(),
                    'total_floating_amount' => $customersWithFloatingBalance->sum('floating_balance'),
                    'high_risk_customers' => $customersWithFloatingBalance->where('risk_score', '>', 70)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching customers with floating balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify admin authorization for balance adjustments
     */
    private function verifyAdjustmentAuthorization($code)
    {
        // Implement your authorization logic here
        // This could be a daily admin code, manager approval, etc.
        $validCodes = [
            'ADMIN' . date('Ymd'), // Daily admin code
            'MANAGER_OVERRIDE_' . date('Ymd')
        ];
        
        return in_array($code, $validCodes) || auth()->user()->hasRole('super-admin');
    }
}