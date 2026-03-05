<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Get the current open register for a location and user.
     */
    public function getCurrentOpenRegister(int $locationId, int $userId): ?CashRegister
    {
        return CashRegister::query()
            ->forLocation($locationId)
            ->forUser($userId)
            ->open()
            ->first();
    }

    /**
     * Open a new register session. Only one open register per (location, user) at a time.
     *
     * @throws \RuntimeException if user already has an open register for this location
     */
    public function openRegister(int $locationId, int $userId, float $openingAmount): CashRegister
    {
        $existing = $this->getCurrentOpenRegister($locationId, $userId);
        if ($existing) {
            throw new \RuntimeException(__('cash_register.already_open'));
        }

        return DB::transaction(function () use ($locationId, $userId, $openingAmount) {
            return CashRegister::create([
                'location_id'    => $locationId,
                'user_id'       => $userId,
                'opening_amount' => $openingAmount,
                'opening_at'    => now(),
                'status'        => 'open',
            ]);
        });
    }

    /**
     * Record a pay-in (cash added to drawer).
     */
    public function payIn(int $registerId, float $amount, ?string $notes = null): CashRegisterTransaction
    {
        $register = CashRegister::open()->findOrFail($registerId);

        return DB::transaction(function () use ($register, $amount, $notes) {
            return $register->transactions()->create([
                'type'       => CashRegisterTransaction::TYPE_PAY_IN,
                'amount'     => $amount,
                'notes'      => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Record a pay-out (cash removed from drawer).
     */
    public function payOut(int $registerId, float $amount, ?string $notes = null): CashRegisterTransaction
    {
        $register = CashRegister::open()->findOrFail($registerId);

        return DB::transaction(function () use ($register, $amount, $notes) {
            return $register->transactions()->create([
                'type'       => CashRegisterTransaction::TYPE_PAY_OUT,
                'amount'     => $amount,
                'notes'      => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Record an expense paid from the drawer (link to expense and optionally create transaction).
     */
    public function recordExpenseFromDrawer(int $registerId, int $expenseId, float $amount, ?string $notes = null): CashRegisterTransaction
    {
        $register = CashRegister::open()->findOrFail($registerId);

        return DB::transaction(function () use ($register, $expenseId, $amount, $notes) {
            return $register->transactions()->create([
                'type'           => CashRegisterTransaction::TYPE_EXPENSE,
                'amount'         => $amount,
                'reference_type' => 'expense',
                'reference_id'   => $expenseId,
                'notes'          => $notes,
                'created_by'     => auth()->id(),
            ]);
        });
    }

    /**
     * Calculate expected cash balance for a register using:
     * Opening + Cash Sales + Pay In - Pay Out - Expenses - Refunds
     */
    public function getExpectedBalance(int $registerId): float
    {
        $register = CashRegister::findOrFail($registerId);
        $opening  = (float) $register->opening_amount;
        $endAt    = $register->closing_at ?? now();

        $payIn   = $register->transactions()->where('type', CashRegisterTransaction::TYPE_PAY_IN)->sum('amount');
        $payOut  = $register->transactions()->where('type', CashRegisterTransaction::TYPE_PAY_OUT)->sum('amount');
        $expense = $register->transactions()->where('type', CashRegisterTransaction::TYPE_EXPENSE)->sum('amount');
        $refunds = $register->transactions()->where('type', CashRegisterTransaction::TYPE_REFUND_CASH)->sum('amount');

        // Cash sales: payments linked to this register, or (no link) sale in session by location/user/date
        $cashSales = (float) Payment::withoutGlobalScopes()
            ->where('payment_type', 'sale')
            ->where('payment_method', 'cash')
            ->where(function ($q) use ($register, $endAt) {
                $q->where('cash_register_id', $register->id)
                    ->orWhere(function ($q2) use ($register, $endAt) {
                        $q2->whereNull('cash_register_id')
                            ->whereIn('reference_id', function ($sub) use ($register, $endAt) {
                                $sub->select('id')->from('sales')
                                    ->where('location_id', $register->location_id)
                                    ->where('user_id', $register->user_id)
                                    ->whereBetween('sales_date', [$register->opening_at, $endAt]);
                            });
                    });
            })
            ->sum('amount');

        // Refunds: from cash_register_transactions (created when cash refund is recorded)
        $refunds = (float) $register->transactions()->where('type', CashRegisterTransaction::TYPE_REFUND_CASH)->sum('amount');

        return $opening + $cashSales + $payIn - $payOut - $expense - $refunds;
    }

    /**
     * Close the register with counted cash and store difference.
     */
    public function closeRegister(int $registerId, float $closingAmount, ?string $notes = null): CashRegister
    {
        $register = CashRegister::open()->findOrFail($registerId);
        $expected = $this->getExpectedBalance($registerId);

        return DB::transaction(function () use ($register, $registerId, $closingAmount, $expected, $notes) {
            $register->update([
                'closing_at'       => now(),
                'closing_amount'   => $closingAmount,
                'expected_balance' => $expected,
                'difference'       => $closingAmount - $expected,
                'status'           => 'closed',
                'closed_by'        => auth()->id(),
                'notes'            => $notes,
            ]);

            return $register->fresh();
        });
    }

    /**
     * Record sale cash (called when a cash payment is recorded for a sale). Links payment to register and creates transaction.
     */
    public function recordSaleCash(CashRegister $register, int $saleId, float $amount, ?int $paymentId = null): CashRegisterTransaction
    {
        return DB::transaction(function () use ($register, $saleId, $amount, $paymentId) {
            $tx = $register->transactions()->create([
                'type'            => CashRegisterTransaction::TYPE_SALE_CASH,
                'amount'          => $amount,
                'reference_type'  => 'sale',
                'reference_id'    => $saleId,
                'description'     => 'POS sale',
                'created_by'      => auth()->id(),
            ]);

            if ($paymentId) {
                Payment::withoutGlobalScopes()->where('id', $paymentId)->update(['cash_register_id' => $register->id]);
            }

            return $tx;
        });
    }

    /**
     * Record refund cash (called when a cash refund is issued). Creates transaction and optionally links payment.
     */
    public function recordRefundCash(CashRegister $register, int $salesReturnId, float $amount, ?int $paymentId = null): CashRegisterTransaction
    {
        return DB::transaction(function () use ($register, $salesReturnId, $amount, $paymentId) {
            $tx = $register->transactions()->create([
                'type'            => CashRegisterTransaction::TYPE_REFUND_CASH,
                'amount'          => $amount,
                'reference_type'  => 'sales_return',
                'reference_id'    => $salesReturnId,
                'description'     => 'Cash refund',
                'created_by'      => auth()->id(),
            ]);

            if ($paymentId) {
                Payment::withoutGlobalScopes()->where('id', $paymentId)->update(['cash_register_id' => $register->id]);
            }

            return $tx;
        });
    }
}
