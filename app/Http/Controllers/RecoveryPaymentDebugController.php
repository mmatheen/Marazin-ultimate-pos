<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;

class RecoveryPaymentDebugController extends Controller
{
    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    /**
     * Debug recovery payments and ledger entries
     */
    public function debugRecoveryPayments(Request $request)
    {
        try {
            $customerId = $request->get('customer_id', 1); // Default customer ID for testing
            
            $debugInfo = [
                'timestamp' => Carbon::now()->toDateTimeString(),
                'customer_id' => $customerId
            ];

            // Get customer info
            $customer = Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Customer not found',
                    'debug_info' => $debugInfo
                ]);
            }

            $debugInfo['customer'] = [
                'name' => $customer->full_name,
                'floating_balance' => $customer->getFloatingBalance(),
                'total_outstanding' => $customer->getTotalOutstanding()
            ];

            // Get recovery payments
            $recoveryPayments = Payment::where('customer_id', $customerId)
                ->where('payment_type', 'recovery')
                ->with(['recoveryForPayment'])
                ->orderBy('created_at', 'desc')
                ->get();

            $debugInfo['recovery_payments'] = $recoveryPayments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_status' => $payment->payment_status,
                    'notes' => $payment->notes,
                    'created_at' => $payment->created_at->toDateTimeString(),
                    'recovery_for_payment_id' => $payment->recovery_for_payment_id,
                    'original_bounced_cheque' => $payment->recoveryForPayment ? [
                        'cheque_number' => $payment->recoveryForPayment->cheque_number,
                        'amount' => $payment->recoveryForPayment->amount,
                        'cheque_status' => $payment->recoveryForPayment->cheque_status
                    ] : null
                ];
            })->toArray();

            // Get ledger entries for bounce_recovery
            $recoveryLedgerEntries = Ledger::where('user_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'bounce_recovery')
                ->orderBy('created_at', 'desc')
                ->get();

            $debugInfo['recovery_ledger_entries'] = $recoveryLedgerEntries->map(function($entry) {
                return [
                    'id' => $entry->id,
                    'transaction_date' => $entry->transaction_date->toDateTimeString(),
                    'reference_no' => $entry->reference_no,
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                    'balance' => $entry->balance,
                    'notes' => $entry->notes,
                    'created_at' => $entry->created_at->toDateTimeString()
                ];
            })->toArray();

            // Get bounced payments for comparison
            $bouncedPayments = Payment::where('customer_id', $customerId)
                ->where('payment_method', 'cheque')
                ->where('cheque_status', 'bounced')
                ->with(['recoveryPayments'])
                ->get();

            $debugInfo['bounced_payments'] = $bouncedPayments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'cheque_number' => $payment->cheque_number,
                    'amount' => $payment->amount,
                    'bank_charges' => $payment->bank_charges ?? 0,
                    'total_impact' => $payment->amount + ($payment->bank_charges ?? 0),
                    'recovery_count' => $payment->recoveryPayments->count(),
                    'recovery_payments' => $payment->recoveryPayments->map(function($recovery) {
                        return [
                            'id' => $recovery->id,
                            'amount' => $recovery->amount,
                            'status' => $recovery->payment_status
                        ];
                    })->toArray()
                ];
            })->toArray();

            // Check ledger entry validation
            $debugInfo['validation'] = [
                'recovery_payments_count' => count($debugInfo['recovery_payments']),
                'recovery_ledger_entries_count' => count($debugInfo['recovery_ledger_entries']),
                'mismatch_detected' => count($debugInfo['recovery_payments']) !== count($debugInfo['recovery_ledger_entries'])
            ];

            // Get recent ledger entries for context
            $recentLedgerEntries = Ledger::where('user_id', $customerId)
                ->where('contact_type', 'customer')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $debugInfo['recent_ledger_entries'] = $recentLedgerEntries->map(function($entry) {
                return [
                    'transaction_type' => $entry->transaction_type,
                    'reference_no' => $entry->reference_no,
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                    'balance' => $entry->balance,
                    'created_at' => $entry->created_at->toDateTimeString()
                ];
            })->toArray();

            return response()->json([
                'status' => 200,
                'message' => 'Recovery payment debug information retrieved successfully',
                'debug_info' => $debugInfo
            ]);

        } catch (\Exception $e) {
            Log::error('Recovery payment debug failed: ' . $e->getMessage(), [
                'customer_id' => $request->get('customer_id'),
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test creating a recovery payment to check if it creates ledger entries
     */
    public function testRecoveryPayment(Request $request)
    {
        $customerId = $request->get('customer_id', 1);
        $amount = $request->get('amount', 100.00);
        
        try {
            DB::beginTransaction();

            $debugInfo = [
                'timestamp' => Carbon::now()->toDateTimeString(),
                'test_parameters' => [
                    'customer_id' => $customerId,
                    'amount' => $amount
                ]
            ];

            // Check customer floating balance before
            $customer = Customer::findOrFail($customerId);
            $balanceBefore = $customer->getFloatingBalance();
            
            $debugInfo['before_test'] = [
                'floating_balance' => $balanceBefore
            ];

            // Create test recovery ledger entry
            $ledgerEntry = $this->unifiedLedgerService->recordFloatingBalanceRecovery(
                $customerId,
                $amount,
                'cash',
                'Test recovery payment for debugging'
            );

            $debugInfo['ledger_entry_created'] = [
                'id' => $ledgerEntry->id,
                'transaction_type' => $ledgerEntry->transaction_type,
                'debit' => $ledgerEntry->debit,
                'credit' => $ledgerEntry->credit,
                'balance' => $ledgerEntry->balance,
                'reference_no' => $ledgerEntry->reference_no
            ];

            // Create test payment record
            $payment = Payment::create([
                'customer_id' => $customerId,
                'payment_type' => 'recovery',
                'payment_method' => 'cash',
                'amount' => $amount,
                'payment_date' => Carbon::now(),
                'notes' => 'Test recovery payment for debugging',
                'payment_status' => 'completed',
                'reference_no' => 'TEST-RECOVERY-' . time()
            ]);

            $debugInfo['payment_created'] = [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_type' => $payment->payment_type,
                'payment_status' => $payment->payment_status
            ];

            // Check customer floating balance after
            $customer->refresh();
            $balanceAfter = $customer->getFloatingBalance();
            
            $debugInfo['after_test'] = [
                'floating_balance' => $balanceAfter,
                'balance_change' => $balanceAfter - $balanceBefore,
                'expected_change' => -$amount,
                'correct_change' => abs(($balanceAfter - $balanceBefore) - (-$amount)) < 0.01
            ];

            // If this is just a test, rollback
            if ($request->get('rollback', true)) {
                DB::rollback();
                $debugInfo['rollback'] = true;
            } else {
                DB::commit();
                $debugInfo['rollback'] = false;
            }

            return response()->json([
                'status' => 200,
                'message' => 'Test recovery payment completed successfully',
                'debug_info' => $debugInfo
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Test recovery payment failed: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'amount' => $amount,
                'error' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Test failed: ' . $e->getMessage(),
                'debug_info' => [
                    'error_details' => $e->getMessage(),
                    'customer_id' => $customerId,
                    'amount' => $amount
                ]
            ], 500);
        }
    }
}