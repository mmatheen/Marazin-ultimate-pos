<?php

namespace App\Http\Controllers;

use App\Models\CashRegister as CashRegisterModel;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\ExpensePayment;
use App\Models\Supplier;
use App\Services\CashRegisterService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CashRegisterController extends Controller
{
    public function __construct(
        protected CashRegisterService $cashRegisterService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:view cash register|open register', ['only' => ['current', 'balance', 'closeScreen']]);
        $this->middleware('permission:open register', ['only' => ['open']]);
        $this->middleware('permission:close register', ['only' => ['close']]);
        $this->middleware('permission:pay in', ['only' => ['payIn']]);
        $this->middleware('permission:pay out', ['only' => ['payOut']]);
        $this->middleware('permission:add expense from pos', ['only' => ['addExpenseFromPos']]);
    }

    /**
     * Get current open register for the authenticated user and given location.
     * Returns a safe "no register open" response if cash_registers table does not exist (migrations not run).
     */
    public function current(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
        ]);

        $userId = Auth::id();
        $locationId = (int) $request->input('location_id');

        try {
            $register = $this->cashRegisterService->getCurrentOpenRegister($locationId, $userId);
        } catch (QueryException $e) {
            // Table missing (e.g. migrations not run) — return safe response so POS page still loads
            if ($e->getCode() === '42S02' || str_contains($e->getMessage(), "doesn't exist")) {
                return response()->json([
                    'success'  => true,
                    'open'     => false,
                    'register' => null,
                    'balance'  => 0,
                ]);
            }
            throw $e;
        }

        if (!$register) {
            return response()->json([
                'success' => true,
                'open'    => false,
                'register' => null,
                'balance'  => 0,
            ]);
        }

        $balance = $this->cashRegisterService->getExpectedBalance($register->id);

        return response()->json([
            'success'  => true,
            'open'     => true,
            'register' => [
                'id'             => $register->id,
                'location_id'    => $register->location_id,
                'user_id'        => $register->user_id,
                'opening_amount' => (float) $register->opening_amount,
                'opening_at'     => $register->opening_at->toIso8601String(),
                'status'         => $register->status,
            ],
            'balance' => round($balance, 2),
        ]);
    }

    /**
     * Open a new register session.
     */
    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'location_id'    => 'required|integer|exists:locations,id',
            'opening_amount' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();
        $locationId = (int) $request->input('location_id');
        $openingAmount = (float) $request->input('opening_amount');

        try {
            $register = $this->cashRegisterService->openRegister($locationId, $userId, $openingAmount);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'opening_amount' => [$e->getMessage()],
            ]);
        }

        $balance = $this->cashRegisterService->getExpectedBalance($register->id);

        return response()->json([
            'success'  => true,
            'message'  => __('cash_register.opened'),
            'register' => [
                'id'             => $register->id,
                'location_id'    => $register->location_id,
                'user_id'       => $register->user_id,
                'opening_amount' => (float) $register->opening_amount,
                'opening_at'     => $register->opening_at->toIso8601String(),
                'status'        => $register->status,
            ],
            'balance' => round($balance, 2),
        ]);
    }

    /**
     * Close the current register with counted cash.
     */
    public function close(Request $request): JsonResponse
    {
        $request->validate([
            'register_id'   => 'required|integer|exists:cash_registers,id',
            'closing_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $registerId = (int) $request->input('register_id');
        $closingAmount = (float) $request->input('closing_amount');
        $notes = $request->input('notes');

        $register = $this->cashRegisterService->closeRegister($registerId, $closingAmount, $notes);

        return response()->json([
            'success'  => true,
            'message'  => __('cash_register.closed'),
            'register' => [
                'id'                => $register->id,
                'closing_at'        => $register->closing_at?->toIso8601String(),
                'closing_amount'    => (float) $register->closing_amount,
                'expected_balance'  => (float) $register->expected_balance,
                'difference'        => (float) $register->difference,
                'status'            => $register->status,
            ],
        ]);
    }

    /**
     * Get close screen data (expected balance, summary) for the given register.
     */
    public function closeScreen(Request $request): JsonResponse
    {
        $request->validate([
            'register_id' => 'required|integer|exists:cash_registers,id',
        ]);

        $registerId = (int) $request->input('register_id');
        $register = CashRegisterModel::findOrFail($registerId);

        if ($register->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => __('cash_register.not_found_or_closed'),
            ], 404);
        }

        if ((int) $register->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('cash_register.unauthorized_close'),
            ], 403);
        }

        $expectedBalance = $this->cashRegisterService->getExpectedBalance($registerId);

        $summary = [
            'opening_amount'   => (float) $register->opening_amount,
            'opening_at'       => $register->opening_at->toIso8601String(),
            'expected_balance' => round($expectedBalance, 2),
        ];

        return response()->json([
            'success'  => true,
            'register' => $summary,
        ]);
    }

    /**
     * Record pay in.
     */
    public function payIn(Request $request): JsonResponse
    {
        $request->validate([
            'register_id' => 'required|integer|exists:cash_registers,id',
            'amount'      => 'required|numeric|min:0.01',
            'notes'       => 'nullable|string|max:500',
        ]);

        $registerId = (int) $request->input('register_id');
        $amount = (float) $request->input('amount');
        $notes = $request->input('notes');

        $transaction = $this->cashRegisterService->payIn($registerId, $amount, $notes);
        $balance = $this->cashRegisterService->getExpectedBalance($registerId);

        return response()->json([
            'success'     => true,
            'message'     => __('cash_register.pay_in_recorded'),
            'transaction' => [
                'id'     => $transaction->id,
                'type'   => $transaction->type,
                'amount' => (float) $transaction->amount,
                'created_at' => $transaction->created_at->toIso8601String(),
            ],
            'balance' => round($balance, 2),
        ]);
    }

    /**
     * Record pay out.
     */
    public function payOut(Request $request): JsonResponse
    {
        $request->validate([
            'register_id' => 'required|integer|exists:cash_registers,id',
            'amount'      => 'required|numeric|min:0.01',
            'notes'       => 'nullable|string|max:500',
        ]);

        $registerId = (int) $request->input('register_id');
        $amount = (float) $request->input('amount');
        $notes = $request->input('notes');

        $transaction = $this->cashRegisterService->payOut($registerId, $amount, $notes);
        $balance = $this->cashRegisterService->getExpectedBalance($registerId);

        return response()->json([
            'success'     => true,
            'message'     => __('cash_register.pay_out_recorded'),
            'transaction' => [
                'id'     => $transaction->id,
                'type'   => $transaction->type,
                'amount' => (float) $transaction->amount,
                'created_at' => $transaction->created_at->toIso8601String(),
            ],
            'balance' => round($balance, 2),
        ]);
    }

    /**
     * Get current balance for an open register.
     */
    public function balance(Request $request): JsonResponse
    {
        $request->validate([
            'register_id' => 'required|integer|exists:cash_registers,id',
        ]);

        $registerId = (int) $request->input('register_id');
        $balance = $this->cashRegisterService->getExpectedBalance($registerId);

        return response()->json([
            'success' => true,
            'balance' => round($balance, 2),
        ]);
    }

    /**
     * Add expense from POS (cash from drawer); creates expense, payment, and register transaction.
     */
    public function addExpenseFromPos(Request $request): JsonResponse
    {
        $request->validate([
            'register_id'                => 'required|integer|exists:cash_registers,id',
            'location_id'                => 'required|integer|exists:locations,id',
            'expense_parent_category_id' => 'required|integer|exists:expense_parent_categories,id',
            'expense_sub_category_id'    => 'nullable|integer|exists:expense_sub_categories,id',
            'amount'                     => 'required|numeric|min:0.01',
            'paid_to'                    => 'nullable|string|max:255',
            'note'                       => 'nullable|string|max:500',
            'supplier_id'                => 'nullable|integer|exists:suppliers,id',
        ]);

        $register = CashRegisterModel::open()->findOrFail($request->input('register_id'));
        $locationId = (int) $request->input('location_id');
        $supplierId = $request->input('supplier_id');
        if (!$supplierId) {
            $supplier = Supplier::first();
            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'No supplier found. Please create a supplier first (e.g. "POS / Cash" for drawer expenses).',
                ], 422);
            }
            $supplierId = $supplier->id;
        }

        $amount = (float) $request->input('amount');
        $expenseNo = 'EXP-' . date('Y') . '-' . str_pad((Expense::latest('id')->first()?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $register, $locationId, $supplierId, $amount, $expenseNo) {
            $expense = Expense::create([
                'expense_no'                  => $expenseNo,
                'date'                        => now()->format('Y-m-d'),
                'expense_parent_category_id'  => $request->expense_parent_category_id,
                'expense_sub_category_id'     => $request->expense_sub_category_id,
                'supplier_id'                 => $supplierId,
                'paid_to'                     => $request->input('paid_to'),
                'location_id'                 => $locationId,
                'payment_status'              => 'paid',
                'payment_method'              => 'cash',
                'total_amount'                => $amount,
                'paid_amount'                 => $amount,
                'due_amount'                  => 0,
                'note'                        => $request->input('note'),
                'created_by'                  => auth()->id(),
                'status'                      => 'active',
            ]);

            ExpenseItem::create([
                'expense_id'   => $expense->id,
                'item_name'    => $request->input('note') ?: 'POS expense',
                'description'  => '',
                'quantity'    => 1,
                'unit_price'   => $amount,
                'total'        => $amount,
                'tax_rate'     => 0,
                'tax_amount'   => 0,
            ]);

            ExpensePayment::create([
                'expense_id'        => $expense->id,
                'cash_register_id'  => $register->id,
                'payment_date'      => now()->format('Y-m-d'),
                'payment_method'    => 'cash',
                'amount'            => $amount,
                'note'              => $request->input('note') ?: 'POS expense',
                'created_by'        => auth()->id(),
            ]);

            $this->cashRegisterService->recordExpenseFromDrawer(
                (int) $register->id,
                (int) $expense->id,
                $amount,
                $request->input('note')
            );
        });

        $balance = $this->cashRegisterService->getExpectedBalance($register->id);

        return response()->json([
            'success'  => true,
            'message'  => 'Expense recorded and drawer updated.',
            'balance'  => round($balance, 2),
        ]);
    }
}
